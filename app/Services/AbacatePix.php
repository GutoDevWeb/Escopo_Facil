<?php

namespace App\Services;

use App\Models\PixPayment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AbacatePix
{
    private function client()
    {
        $key = (string) config('payments.abacate_key');
        // Reject production credentials BEFORE making any network request.
        if (! PixDemo::hasDevelopmentKey()) {
            throw new RuntimeException('Configure uma chave de desenvolvimento dev_ ou abc_dev_ no servidor.');
        }

        return Http::baseUrl('https://api.abacatepay.com/v2')->withToken($key)
            ->acceptJson()->asJson()->connectTimeout(5)->timeout(15);
    }

    private function data($response): array
    {
        if (! $response->successful() || ! $response->json('success') || ! is_array($response->json('data'))) {
            throw new RuntimeException('Não foi possível consultar o pagamento de teste. Tente novamente.');
        }

        return $response->json('data');
    }

    public function create(PixPayment $payment): array
    {
        $data = $this->data($this->client()->post('/transparents/create', [
            'method' => 'PIX',
            'data' => [
                'amount' => $payment->amount,
                'expiresIn' => config('payments.expires_in'),
                'description' => 'Escopo Fácil — '.$payment->plan->name,
                'externalId' => $payment->id,
            ],
        ]));

        if (($data['devMode'] ?? null) !== true || (int) ($data['amount'] ?? 0) !== $payment->amount ||
            ! is_string($data['id'] ?? null) || ! is_string($data['brCode'] ?? null) || empty($data['expiresAt'])) {
            throw new RuntimeException('A cobrança não foi reconhecida como PIX de teste válido.');
        }

        return $data;
    }

    public function status(PixPayment $payment): string
    {
        $data = $this->data($this->client()->get('/transparents/check', ['id' => $payment->provider_id]));
        if (($data['id'] ?? null) !== $payment->provider_id) {
            throw new RuntimeException('A referência do pagamento não confere.');
        }

        return (string) ($data['status'] ?? 'PENDING');
    }

    public function simulate(PixPayment $payment): void
    {
        $this->data($this->client()->post('/transparents/simulate-payment?id='.rawurlencode($payment->provider_id)));
    }
}
