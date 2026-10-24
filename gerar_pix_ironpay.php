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

if (!is_array($dados)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados inv├â┬ílidos']);
    exit;
}

$cliente = $dados['cliente'] ?? [];

$nome = trim((string)($cliente['name'] ?? ''));
$email = trim((string)($cliente['email'] ?? ''));
$telefone = preg_replace('/\D/', '', (string)($cliente['phone'] ?? ''));
$cpf = preg_replace('/\D/', '', (string)($cliente['cpf'] ?? ''));

if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || strlen($telefone) < 10 || strlen($cpf) !== 11) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Verifique nome, email, telefone (10 ou 11 digitos) e CPF (11 digitos)']);
    exit;
}

// Valor fixado no servidor para impedir altera├â┬º├â┬úo pelo navegador.
// Ajustar ao pre├â┬ºo real do produto cadastrado.
$valorCentavos = 3945;

$payload = [
    'amount' => $valorCentavos,
    'offer_hash' => $config['offer_hash'],
    'product_hash' => $config['product_hash'],
    'payment_method' => 'pix',
    'installments' => 1,
    'customer' => [
        'name' => $nome,
        'email' => $email,
        'phone_number' => $telefone,
        'document' => $cpf
    ],
    'cart' => [[
        'product_hash' => $config['product_hash'],
        'title' => 'CAPACETE LS2',
        'price' => $valorCentavos,
        'quantity' => 1,
        'operation_type' => 1,
        'tangible' => true
    ]],
    'transaction_origin' => 'api'
];

$url = 'https://api.ironpayapp.com.br/api/public/v1/transactions'
     . '?api_token=' . rawurlencode($config['api_token']);

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30
]);

$resposta = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$erro = curl_error($ch);
curl_close($ch);

if ($resposta === false) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Falha na conex├â┬úo com a IronPay']);
    exit;
}

$resultado = json_decode($resposta, true);

if ($httpCode < 200 || $httpCode >= 300 || !is_array($resultado)) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error' => 'IronPay recusou a solicita├â┬º├â┬úo',
        'http_status' => $httpCode, 'ironpay_details' => $resultado['errors'] ?? $resultado['message'] ?? 'Sem detalhes'
    ]);
    exit;
}

require_once __DIR__ . '/ironpay_resposta.php';
$pix = adaptarRespostaPix($resultado);

if ($pix['success'] && !empty($pix['transaction_id'])) {
    $_SESSION['ironpay_hash'] = $pix['transaction_id'];
    $_SESSION['ironpay_amount'] = 3945;
}

echo json_encode($pix);
