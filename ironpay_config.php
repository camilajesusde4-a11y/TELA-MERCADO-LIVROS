<?php
return [
    'api_token' => getenv('IRONPAY_API_TOKEN'),
    'offer_hash' => getenv('IRONPAY_OFFER_HASH'),
    'product_hash' => getenv('IRONPAY_PRODUCT_HASH'),

    // CONFIGURACAO DA OFERTA - ALTERE SOMENTE AQUI
    'produto_nome' => 'CAPACETE LS2',
    'produto_valor_centavos' => 3945
];
