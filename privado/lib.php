<?php
/* Núcleo do back-end: configuração, oferta, Asaas, pedidos, tokens de download e e-mail. */
declare(strict_types=1);
date_default_timezone_set('America/Sao_Paulo');

const PRIV = __DIR__;
$GLOBALS['CFG'] = file_exists(PRIV . '/config.php') ? require PRIV . '/config.php' : require PRIV . '/config.exemplo.php';

function cfg(string $k, $padrao = null) { return $GLOBALS['CFG'][$k] ?? $padrao; }

function json_saida($dados, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function registra(string $msg): void {
    @file_put_contents(PRIV . '/pedidos/eventos.log', date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND | LOCK_EX);
}

/* ---------- oferta com prazo real ---------- */
function oferta(): array {
    $fim = strtotime((string) cfg('OFERTA_FIM', '')) ?: null;
    $ativa = $fim && time() < $fim;
    $preco = $ativa ? (float) cfg('PRECO_OFERTA') : (float) cfg('PRECO_CHEIO');
    return ['ativa' => $ativa, 'preco' => $preco, 'fim' => $ativa ? $fim : null];
}

/* ---------- Asaas ---------- */
function asaas(string $metodo, string $caminho, ?array $corpo = null): array {
    // ambiente pela própria chave ($aact_prod_ = produção, $aact_hmlg_ = sandbox); senão, pelo config
    $chave = (string) cfg('ASAAS_API_KEY');
    $prod = strpos($chave, '$aact_prod_') === 0 || (strpos($chave, '$aact_hmlg_') !== 0 && cfg('ASAAS_AMBIENTE') === 'producao');
    $base = $prod ? 'https://api.asaas.com/v3' : 'https://api-sandbox.asaas.com/v3';
    $ch = curl_init($base . $caminho);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $metodo,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'User-Agent: sobrevivencia72h',
            'access_token: ' . cfg('ASAAS_API_KEY'),
        ],
    ]);
    if ($corpo !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($corpo, JSON_UNESCAPED_UNICODE));
    $resp = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    $dados = json_decode((string) $resp, true) ?: [];
    if ($status >= 400 || $resp === false) {
        registra("ASAAS $metodo $caminho -> $status $err " . substr((string) $resp, 0, 400));
        throw new RuntimeException($dados['errors'][0]['description'] ?? 'Falha ao falar com o Asaas.');
    }
    return $dados;
}

/* ---------- pedidos (um JSON por pedido) ---------- */
function pedido_caminho(string $ref): string {
    if (!preg_match('/^[a-f0-9]{20}$/', $ref)) throw new InvalidArgumentException('referência inválida');
    return PRIV . '/pedidos/' . $ref . '.json';
}
function pedido_le(string $ref): ?array {
    $p = pedido_caminho($ref);
    return is_file($p) ? json_decode((string) file_get_contents($p), true) : null;
}
function pedido_grava(array $pedido): void {
    if (!is_dir(PRIV . '/pedidos')) mkdir(PRIV . '/pedidos', 0700, true);
    file_put_contents(pedido_caminho($pedido['ref']), json_encode($pedido, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/* ---------- link de download assinado ---------- */
function b64u(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
function token_gera(string $ref, int $exp): string {
    $dados = $ref . '.' . $exp;
    return b64u($dados . '.' . substr(hash_hmac('sha256', $dados, (string) cfg('SEGREDO')), 0, 32));
}
function token_valida(string $t): ?string {
    $raw = base64_decode(strtr($t, '-_', '+/'), true);
    if (!$raw || substr_count($raw, '.') !== 2) return null;
    [$ref, $exp, $sig] = explode('.', $raw);
    $ok = hash_equals(substr(hash_hmac('sha256', $ref . '.' . $exp, (string) cfg('SEGREDO')), 0, 32), $sig);
    return ($ok && (int) $exp >= time()) ? $ref : null;
}

const ARQUIVOS = [
    1 => ['arquivo' => '72-Horas-Manual-completo.pdf', 'nome' => '72 Horas · O Manual da Família Preparada'],
    2 => ['arquivo' => '72-Horas-Bonus-1-Plano-Familiar.pdf', 'nome' => 'Bônus 1 · Plano Familiar de 72 Horas'],
    3 => ['arquivo' => '72-Horas-Bonus-2-Checklists-das-3-Mochilas.pdf', 'nome' => 'Bônus 2 · Checklists das 3 Mochilas'],
    4 => ['arquivo' => '72-Horas-Bonus-3-Guia-de-Bolso.pdf', 'nome' => 'Bônus 3 · Guia de Bolso das 72 Horas'],
];

/* ---------- e-mail (SMTP simples com SSL; cai para mail() se não houver SMTP) ---------- */
function email_envia(string $para, string $assunto, string $html, string $texto): bool {
    $de = (string) cfg('EMAIL_DE');
    $nome = (string) cfg('EMAIL_NOME');
    $limite = 'b' . bin2hex(random_bytes(8));
    $cab = [
        'From: =?UTF-8?B?' . base64_encode($nome) . "?= <$de>",
        'Reply-To: ' . cfg('EMAIL_RESPOSTA', $de),
        'MIME-Version: 1.0',
        "Content-Type: multipart/alternative; boundary=\"$limite\"",
    ];
    $corpo = "--$limite\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($texto))
        . "--$limite\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html))
        . "--$limite--\r\n";
    $assuntoCod = '=?UTF-8?B?' . base64_encode($assunto) . '?=';
    if (!cfg('SMTP_HOST')) return mail($para, $assuntoCod, $corpo, implode("\r\n", $cab));
    try {
        $s = stream_socket_client('ssl://' . cfg('SMTP_HOST') . ':' . cfg('SMTP_PORTA', 465), $en, $es, 20);
        if (!$s) throw new RuntimeException("conexão SMTP: $es");
        $le = function () use ($s) { $r = ''; while (($l = fgets($s, 515)) !== false) { $r .= $l; if (isset($l[3]) && $l[3] === ' ') break; } return $r; };
        $cmd = function (string $c, string $espera) use ($s, $le) { fwrite($s, $c . "\r\n"); $r = $le(); if (strpos($r, $espera) !== 0) throw new RuntimeException("SMTP '$c' -> $r"); return $r; };
        $le();
        $cmd('EHLO sobrevivencia72h.com.br', '250');
        $cmd('AUTH LOGIN', '334');
        $cmd(base64_encode((string) cfg('SMTP_USER')), '334');
        $cmd(base64_encode((string) cfg('SMTP_SENHA')), '235');
        $cmd("MAIL FROM:<$de>", '250');
        $cmd("RCPT TO:<$para>", '250');
        $cmd('DATA', '354');
        $msg = "To: <$para>\r\nSubject: $assuntoCod\r\nDate: " . date('r') . "\r\nMessage-ID: <" . bin2hex(random_bytes(12)) . "@sobrevivencia72h.com.br>\r\n"
            . implode("\r\n", $cab) . "\r\n\r\n" . preg_replace('/^\./m', '..', $corpo);
        $cmd($msg . "\r\n.", '250');
        $cmd('QUIT', '221');
        fclose($s);
        return true;
    } catch (Throwable $e) {
        registra('EMAIL falhou para ' . $para . ': ' . $e->getMessage());
        return false;
    }
}

function email_entrega(array $pedido): bool {
    $exp = time() + 86400 * (int) cfg('DIAS_DOWNLOAD', 30);
    $link = rtrim((string) cfg('SITE_URL'), '/') . '/download.php?t=' . token_gera($pedido['ref'], $exp);
    $nome = htmlspecialchars(explode(' ', $pedido['nome'])[0]);
    $html = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;color:#2b2926;line-height:1.6">'
        . '<div style="background:#161513;padding:24px;border-radius:12px 12px 0 0"><span style="color:#e8702a;font-size:28px;font-weight:900">72 HORAS</span>'
        . '<br><span style="color:#f3eee4;font-size:13px;letter-spacing:2px">MANUAL DA FAMÍLIA PREPARADA</span></div>'
        . '<div style="background:#f7f3ec;padding:28px;border-radius:0 0 12px 12px">'
        . "<p>Olá, $nome!</p><p>Seu pagamento foi confirmado. Obrigado pela confiança. O seu manual e os 3 bônus já estão disponíveis:</p>"
        . '<p style="text-align:center;margin:28px 0"><a href="' . $link . '" style="background:#e8702a;color:#fff;text-decoration:none;font-weight:bold;padding:14px 28px;border-radius:999px;display:inline-block">Baixar o manual e os bônus</a></p>'
        . '<p style="font-size:14px">O link vale por ' . (int) cfg('DIAS_DOWNLOAD', 30) . ' dias. Baixe os 4 arquivos e guarde uma cópia no celular e outra impressa.</p>'
        . '<p style="font-size:14px"><strong>Por onde começar:</strong> leia a Abertura e faça a primeira caixa "Faça agora" ainda hoje. Leva 2 minutos.</p>'
        . '<p style="font-size:13px;color:#6f6a61">Dúvidas: responda este e-mail ou chame no WhatsApp (48) 99165-7775.<br>Garantia de 7 dias: se não gostar, devolvemos 100% do valor.</p>'
        . '</div></div>';
    $texto = "Olá, $nome!\n\nSeu pagamento foi confirmado. Baixe o manual e os 3 bônus aqui:\n$link\n\nO link vale por "
        . (int) cfg('DIAS_DOWNLOAD', 30) . " dias.\nDúvidas: sobrevivencia72h@gmail.com ou WhatsApp (48) 99165-7775.";
    return email_envia($pedido['email'], 'Seu manual 72 Horas chegou', $html, $texto);
}
