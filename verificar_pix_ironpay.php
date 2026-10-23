<?php
$origem = $_SERVER['HTTP_ORIGIN'] ?? '';
$permitida = getenv('FRONTEND_URL') ?: '';

if ($permitida !== '' && $origem === $permitida) {
    header('Access-Control-Allow-Origin: ' . $origem);
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}


session_start();
header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/ironpay_config.php';

$dados = json_decode(file_get_contents('php://input'), true);
$hash = trim((string)($dados['transaction_hash'] ?? ''));

if (!preg_match('/^[a-zA-Z0-9_-]{5,100}$/', $hash)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'ERRO',
        'details' => 'Hash da transa├â┬º├â┬úo inv├â┬ílido'
    ]);
    exit;
}

$url = 'https://api.ironpayapp.com.br/api/public/v1/transactions/'
    . rawurlencode($hash)
    . '?api_token=' . rawurlencode($config['api_token']);

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => ['Accept: application/json']
]);

$resposta = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$resultado = json_decode($resposta ?: '', true);

if ($httpCode !== 200 || !is_array($resultado)) {
    http_response_code(502);
    echo json_encode([
        'status' => 'ERRO',
        'details' => 'N├â┬úo foi poss├â┬¡vel consultar a IronPay'
    ]);
    exit;
}

$transacao = $resultado['data'] ?? [];

if (($transacao['hash'] ?? '') !== $hash || (int)($transacao['amount'] ?? 0) !== 3945 || ($transacao['payment_method'] ?? '') !== 'pix') {
    http_response_code(502);
    echo json_encode([
        'status' => 'ERRO',
        'details' => 'Transa├â┬º├â┬úo n├â┬úo corresponde ├â┬á consulta'
    ]);
    exit;
}

$status = strtolower((string)($transacao['status'] ?? ''));

echo json_encode([
    'status' => $status === 'paid' ? 'PAGO' : 'PENDENTE'
]);
