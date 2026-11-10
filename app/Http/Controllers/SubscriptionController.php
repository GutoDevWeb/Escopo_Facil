<?php

namespace App\Http\Controllers;

use App\Models\PixPayment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AbacatePix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{
    private function available(): void
    {
        abort_unless(\App\Services\PixDemo::available(), 403, 'A demonstração de compra está indisponível neste ambiente.');
        abort_unless(in_array(config('payments.driver'), ['local', 'abacate'], true), 503);
    }

    public function start(Request $request, string $slug = 'pro')
    {
        abort_unless(in_array($slug, ['pro', 'equipe'], true), 404);
        $period = $request->validate(['period' => ['nullable', 'in:monthly,yearly']])['period'] ?? 'yearly';

        return view('welcome', ['checkoutOpen' => true, 'checkoutPeriod' => $period, 'checkoutPlan' => $slug]);
    }

    public function options(Request $request, string $slug = 'pro')
    {
        abort_unless(in_array($slug, ['pro', 'equipe'], true), 404);
        $plan = Plan::where('slug', $slug)->first();
        if (! $plan) {
            return response()->json(['message' => 'O plano selecionado está indisponível no momento. Tente novamente em instantes.'], 503);
        }

        return response()->json([
            'checkout_available' => \App\Services\PixDemo::available(),
            'plan' => ['id' => $plan->id, 'slug' => $plan->slug, 'name' => $plan->name, 'monthly' => (int) round($plan->price_monthly * 100), 'yearly' => (int) round($plan->price_yearly * 100)],
            'authenticated' => $request->user() !== null,
            'verified' => $request->user()?->hasVerifiedEmail() ?? false,
            'email' => $request->user()?->email,
            'csrf' => $request->session()->token(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function checkout(Request $request, Plan $plan)
    {
        $this->available();
        abort_unless(in_array($plan->slug, ['pro', 'equipe'], true), 404);
        $period = $request->validate(['period' => ['nullable', 'in:monthly,yearly']])['period'] ?? 'monthly';

        return view('welcome', ['checkoutOpen' => true, 'checkoutPeriod' => $period, 'checkoutPlan' => $plan->slug]);
    }

    public function process(Request $request, AbacatePix $gateway)
    {
        $this->available();
        $data = $request->validate(['plan_id' => ['required', 'integer', 'exists:plans,id'], 'period' => ['required', 'in:monthly,yearly']]);
        $plan = Plan::findOrFail($data['plan_id']);
        abort_unless(in_array($plan->slug, ['pro', 'equipe'], true), 422);
        $amount = (int) round($plan->{'price_'.$data['period']} * 100);
        abort_unless($amount >= 100, 422);
        $payment = PixPayment::where('user_id', $request->user()->id)->where('plan_id', $plan->id)
            ->where('period', $data['period'])->where('amount', $amount)->where('driver', config('payments.driver'))
            ->where('status', 'pending')->whereNotNull('code')->where('expires_at', '>', now())->latest()->first();
        if (! $payment) {
            $payment = PixPayment::create([
                'user_id' => $request->user()->id, 'plan_id' => $plan->id, 'period' => $data['period'],
                'amount' => $amount, 'driver' => config('payments.driver'), 'expires_at' => now()->addSeconds(config('payments.expires_in')),
            ]);
            try {
                if ($payment->driver === 'abacate') {
                    $charge = $gateway->create($payment);
                    $payment->update(['provider_id' => $charge['id'], 'code' => $charge['brCode'], 'expires_at' => $charge['expiresAt']]);
                } else {
                    // Deliberately not a bank-payable PIX payload.
                    $payment->update(['code' => 'ESCOPO-FACIL:SIMULACAO:'.$payment->id.':BRL:'.$amount]);
                }
            } catch (\Throwable $exception) {
                $payment->update(['status' => 'failed']);
                \Illuminate\Support\Facades\Log::warning('pix_creation_failed', ['payment_id' => $payment->id, 'exception_type' => get_class($exception)]);
                throw ValidationException::withMessages(['payment' => $exception instanceof \App\Exceptions\PixGatewayException
                    ? $exception->getMessage()
                    : ($exception instanceof \Illuminate\Http\Client\ConnectionException
                        ? 'Não foi possível conectar à AbacatePay. Aguarde e tente novamente.'
                        : 'Não foi possível gerar o PIX de teste. Confira a configuração do ambiente e tente novamente.')]);
            }
        }

        return response()->json($this->payload($payment));
    }

    public function status(Request $request, PixPayment $payment, AbacatePix $gateway)
    {
        $this->owns($request, $payment);
        if ($payment->status === 'pending' && $payment->driver === 'abacate') {
            try {
                $status = $gateway->status($payment);
                if ($status === 'PAID') {
                    $this->activate($payment);
                    $payment->refresh();
                } elseif (in_array($status, ['EXPIRED', 'CANCELLED'], true)) {
                    $payment->update(['status' => strtolower($status)]);
                }
            } catch (\Throwable $exception) {
                return response()->json(['message' => 'Não foi possível consultar a confirmação. Tente novamente.'], 503);
            }
        }
        if ($payment->status === 'pending' && $payment->expires_at->isPast()) {
            $payment->update(['status' => 'expired']);
        }

        return response()->json($this->payload($payment->fresh()));
    }

    public function simulate(Request $request, PixPayment $payment, AbacatePix $gateway)
    {
        $this->owns($request, $payment);
        if ($payment->status === 'paid') {
            return response()->json($this->payload($payment));
        }
        abort_unless($payment->status === 'pending' && $payment->expires_at->isFuture(), 409, 'Este QR Code expirou. Gere outro para continuar.');
        if ($payment->driver === 'abacate') {
            try {
                $gateway->simulate($payment);
            } catch (\Throwable $exception) {
                return response()->json(['message' => 'A simulação não foi confirmada. Tente consultar o pagamento.'], 503);
            }

            return $this->status($request, $payment, $gateway);
        }
        $this->activate($payment);

        return response()->json($this->payload($payment->fresh()));
    }

    private function owns(Request $request, PixPayment $payment): void
    {
        $this->available();
        abort_unless($payment->user_id === $request->user()->id, 404);
        abort_unless($payment->driver === config('payments.driver'), 409);
    }

    private function activate(PixPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $locked = PixPayment::lockForUpdate()->findOrFail($payment->id);
            if ($locked->status === 'paid') {
                return;
            }
            $user = User::lockForUpdate()->findOrFail($locked->user_id);
            $subscription = Subscription::where('user_id', $user->id)->where('status', 'active')->latest()->first();
            $start = $subscription && $subscription->plan_id === $locked->plan_id && $subscription->expires_at && now()->lt($subscription->expires_at)
                ? \Illuminate\Support\Carbon::parse($subscription->expires_at) : now();
            Subscription::where('user_id', $user->id)->where('status', 'active')->update(['status' => 'replaced']);
            Subscription::create([
                'user_id' => $user->id, 'plan_id' => $locked->plan_id, 'status' => 'active',
                'expires_at' => $locked->period === 'yearly' ? $start->addYearNoOverflow() : $start->addMonthNoOverflow(),
            ]);
            $user->update(['plan_id' => $locked->plan_id]);
            $locked->update(['status' => 'paid', 'paid_at' => now()]);
        });
    }

    private function payload(PixPayment $payment): array
    {
        return [
            'id' => $payment->id, 'code' => $payment->code, 'amount' => $payment->amount, 'period' => $payment->period,
            'status' => $payment->status, 'expires_at' => $payment->expires_at->toIso8601String(),
            'status_url' => route('subscription.status', $payment), 'simulate_url' => route('subscription.simulate', $payment),
        ];
    }

    public function success()
    {
        return redirect()->route('dashboard');
    }
}
