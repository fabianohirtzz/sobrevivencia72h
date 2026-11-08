<?php
/* Copie este arquivo para config.php (na pasta privado/, FORA do public_html) e preencha. Nunca versionar o config.php. */
return [
    // ---------- Asaas
    'ASAAS_AMBIENTE'      => 'sandbox',            // 'sandbox' para testes, 'producao' para valer
    'ASAAS_API_KEY'       => '$aact_COLE_AQUI',     // Minha conta > Integrações > Chave de API
    'ASAAS_WEBHOOK_TOKEN' => 'TROQUE_POR_UM_TOKEN_LONGO_E_ALEATORIO', // mesmo token configurado no webhook do Asaas

    // ---------- Oferta (prazo REAL: depois do fim, a página e o checkout cobram o preço cheio)
    'PRECO_OFERTA' => 39.90,
    'PRECO_CHEIO'  => 59.90,
    'OFERTA_FIM'   => '2026-11-10 20:00:00',       // no lançamento: data/hora do lançamento + 72h (horário de Brasília)

    // ---------- Site e entrega
    'SITE_URL'      => 'https://sobrevivencia72h.com.br',
    'SEGREDO'       => 'TROQUE_POR_32_CARACTERES_ALEATORIOS', // assina os links de download
    'DIAS_DOWNLOAD' => 30,                          // validade do link
    'MAX_DOWNLOADS' => 40,                          // downloads por pedido (somando os 4 arquivos)

    // ---------- E-mail (SMTP da Erehost). Se SMTP_HOST ficar vazio, usa mail() do PHP.
    'SMTP_HOST'  => 'mail.sobrevivencia72h.com.br',
    'SMTP_PORTA' => 465,                            // 465 = SSL
    'SMTP_USER'  => 'contato@sobrevivencia72h.com.br',
    'SMTP_SENHA' => '',
    'EMAIL_DE'   => 'contato@sobrevivencia72h.com.br',
    'EMAIL_NOME' => '72 Horas · Manual da Família Preparada',
    'EMAIL_RESPOSTA' => 'sobrevivencia72h@gmail.com',
    'EMAIL_AVISO'    => 'sobrevivencia72h@gmail.com', // recebe aviso de cada venda
];
