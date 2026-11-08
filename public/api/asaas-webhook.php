<?php
/* Webhook do Asaas: libera e envia o produto quando o pagamento é confirmado; bloqueia em estorno.
   Configurar no Asaas: URL https://sobrevivencia72h.com.br/api/asaas-webhook.php, token = ASAAS_WEBHOOK_TOKEN,
   eventos PAYMENT_CONFIRMED, PAYMENT_RECEIVED, PAYMENT_REFUNDED, PAYMENT_CHARGEBACK_REQUESTED. */
require __DIR__ . '/../../privado/lib.php';

$token = $_SERVER['HTTP_ASAAS_ACCESS_TOKEN'] ?? '';
if (!hash_equals((string) cfg('ASAAS_WEBHOOK_TOKEN'), (string) $token)) {
    registra('WEBHOOK token inválido de ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    json_saida(['ok' => false], 401);
}

$ev = json_decode((string) file_get_contents('php://input'), true) ?: [];
$evento = $ev['event'] ?? '';
$pag = $ev['payment'] ?? [];
$ref = (string) ($pag['externalReference'] ?? '');

// O Asaas pausa a fila se receber erro: sempre responder 200 para eventos que não interessam.
if (!preg_match('/^[a-f0-9]{20}$/', $ref) || !($pedido = pedido_le($ref))) {
    registra("WEBHOOK $evento ignorado (ref '$ref')");
    json_saida(['ok' => true]);
}

if (in_array($evento, ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED'], true)) {
    $pedido['status'] = 'pago';
    $pedido['pago_em'] = date('c');
    $pedido['forma'] = $pag['billingType'] ?? '';
    if (!$pedido['entregue']) {
        $pedido['entregue'] = email_entrega($pedido);
        if ($pedido['entregue']) $pedido['entregue_em'] = date('c');
        $aviso = (string) cfg('EMAIL_AVISO');
        if ($aviso) email_envia($aviso, 'Venda 72 Horas: R$ ' . number_format((float) $pedido['valor'], 2, ',', '.'),
            '<p>Nova venda confirmada.</p><p>Pedido ' . $ref . '<br>' . htmlspecialchars($pedido['nome']) . ' &lt;' . htmlspecialchars($pedido['email']) . '&gt;<br>Forma: ' . htmlspecialchars($pedido['forma']) . '<br>Entrega: ' . ($pedido['entregue'] ? 'enviada' : 'FALHOU, reenviar manualmente') . '</p>',
            "Nova venda: pedido $ref, {$pedido['nome']} <{$pedido['email']}>");
    }
    pedido_grava($pedido);
    registra("WEBHOOK $evento pedido $ref pago, entregue=" . ($pedido['entregue'] ? 'sim' : 'NÃO'));
} elseif (in_array($evento, ['PAYMENT_REFUNDED', 'PAYMENT_CHARGEBACK_REQUESTED', 'PAYMENT_DELETED'], true)) {
    $pedido['status'] = 'estornado';
    pedido_grava($pedido);
    registra("WEBHOOK $evento pedido $ref bloqueado");
}
json_saida(['ok' => true]);
