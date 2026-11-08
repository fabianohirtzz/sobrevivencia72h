<?php
/* Recebe o formulário, cria cliente e cobrança no Asaas e devolve o link da fatura (Pix, cartão ou boleto). */
require __DIR__ . '/../../privado/lib.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_saida(['erro' => 'Método não permitido.'], 405);

$d = json_decode((string) file_get_contents('php://input'), true) ?: [];
$nome  = trim(preg_replace('/\s+/', ' ', (string) ($d['nome'] ?? '')));
$email = strtolower(trim((string) ($d['email'] ?? '')));
$cpf   = preg_replace('/\D/', '', (string) ($d['cpf'] ?? ''));
$cel   = preg_replace('/\D/', '', (string) ($d['celular'] ?? ''));

function cpf_ok(string $c): bool {
    if (strlen($c) !== 11 || preg_match('/^(\d)\1+$/', $c)) return false;
    for ($t = 9; $t < 11; $t++) {
        $s = 0;
        for ($i = 0; $i < $t; $i++) $s += (int) $c[$i] * ($t + 1 - $i);
        if (((10 * $s) % 11) % 10 !== (int) $c[$t]) return false;
    }
    return true;
}
if (mb_strlen($nome) < 5 || strpos($nome, ' ') === false) json_saida(['erro' => 'Informe o nome completo.'], 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_saida(['erro' => 'Confira o e-mail.'], 422);
if (!cpf_ok($cpf)) json_saida(['erro' => 'CPF inválido.'], 422);
if ($cel !== '' && (strlen($cel) < 10 || strlen($cel) > 11)) $cel = '';

$o = oferta();
$ref = bin2hex(random_bytes(10));
try {
    $cli = asaas('POST', '/customers', array_filter([
        'name' => $nome, 'cpfCnpj' => $cpf, 'email' => $email, 'mobilePhone' => $cel ?: null,
        'notificationDisabled' => true, 'externalReference' => $ref,
    ], fn($v) => $v !== null));
    $pag = asaas('POST', '/payments', [
        'customer'          => $cli['id'],
        'billingType'       => 'UNDEFINED',
        'value'             => $o['preco'],
        'dueDate'           => date('Y-m-d', strtotime('+2 days')),
        'description'       => '72 Horas: O Manual da Família Preparada + 3 bônus (PDF)',
        'externalReference' => $ref,
        'callback'          => ['successUrl' => rtrim((string) cfg('SITE_URL'), '/') . '/obrigado.html', 'autoRedirect' => true],
    ]);
} catch (Throwable $e) {
    json_saida(['erro' => 'Não foi possível gerar o pagamento agora. Tente de novo em instantes ou chame no WhatsApp (48) 99165-7775.'], 502);
}

pedido_grava([
    'ref' => $ref, 'criado' => date('c'), 'status' => 'aguardando',
    'nome' => $nome, 'email' => $email, 'cpf_final' => substr($cpf, -3), 'celular' => $cel,
    'valor' => $o['preco'], 'asaas_cliente' => $cli['id'], 'asaas_pagamento' => $pag['id'],
    'entregue' => false, 'downloads' => 0,
]);
registra("PEDIDO $ref criado ({$o['preco']}) pagamento {$pag['id']}");
json_saida(['url' => $pag['invoiceUrl']]);
