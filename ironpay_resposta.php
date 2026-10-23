<?php


function adaptarRespostaPix(array $resposta): array
{
    $codigoPix = $resposta['pix']['pix_qr_code'] ?? null;
    $transacao = $resposta['hash'] ?? null;
    $status = $resposta['payment_status'] ?? 'unknown';

    if (!$transacao || !$codigoPix) {
        return [
            'success' => false,
            'error' => 'Resposta PIX incompleta'
        ];
    }

    return [
        'success' => true,
        'pix_code' => $codigoPix,
        'qr_code_url' => $resposta['pix']['pix_url'] ?? null,
        'transaction_id' => $transacao,
        'status' => $status
    ];
}
