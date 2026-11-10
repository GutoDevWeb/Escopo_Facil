<?php

namespace App\Services;

use App\Models\PixPayment;
use App\Exceptions\PixGatewayException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            Log::warning('abacatepay_request_failed', ['http_status' => $response->status()]);
            throw new PixGatewayException(match ($response->status()) {
                401 => 'A AbacatePay recusou a chave de teste. Confira a chave configurada na hospedagem.',
                403 => 'A AbacatePay recusou o acesso. Confira as permissões da chave, incluindo TRANSPARENT:CREATE para gerar o PIX.',
                404 => 'O recurso de pagamento não foi encontrado na AbacatePay. Confira a compatibilidade da conta com a API v2.',
                400, 422 => 'A AbacatePay recusou os dados do PIX de teste. Consulte o registro da requisição no painel da AbacatePay.',
                429 => 'O limite de requisições da AbacatePay foi atingido. Aguarde um pouco e tente novamente.',
                default => 'A AbacatePay não retornou uma confirmação válida. Tente novamente em instantes.',
            });
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
            Log::warning('abacatepay_invalid_test_charge', [
                'dev_mode_valid' => ($data['devMode'] ?? null) === true,
                'amount_valid' => (int) ($data['amount'] ?? 0) === $payment->amount,
                'id_present' => is_string($data['id'] ?? null),
                'code_present' => is_string($data['brCode'] ?? null),
                'expiry_present' => ! empty($data['expiresAt']),
            ]);
            throw new PixGatewayException('A resposta da AbacatePay não corresponde a um PIX de teste válido. O pedido não foi ativado; consulte os logs privados da hospedagem.');
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
