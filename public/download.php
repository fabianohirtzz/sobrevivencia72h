<?php
/* Página de download protegida: link assinado e com validade; os PDFs ficam fora do public_html. */
require (static function () { foreach ([__DIR__ . '/../privado/lib.php', __DIR__ . '/privado/lib.php'] as $f) if (is_file($f)) return $f; http_response_code(500); exit('config'); })();
header('X-Robots-Tag: noindex, nofollow');

$t = (string) ($_GET['t'] ?? '');
$ref = token_valida($t);
$pedido = $ref ? pedido_le($ref) : null;
$ok = $pedido && $pedido['status'] === 'pago';

if ($ok && isset($_GET['f'])) {
    $n = (int) $_GET['f'];
    $arq = ARQUIVOS[$n] ?? null;
    $caminho = $arq ? PRIV . '/produtos/' . $arq['arquivo'] : '';
    if (!$arq || !is_file($caminho)) { http_response_code(404); exit('Arquivo não encontrado.'); }
    if ($pedido['downloads'] >= (int) cfg('MAX_DOWNLOADS', 40)) { http_response_code(429); exit('Limite de downloads atingido. Fale com sobrevivencia72h@gmail.com.'); }
    $pedido['downloads']++;
    pedido_grava($pedido);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $arq['arquivo'] . '"');
    header('Content-Length: ' . filesize($caminho));
    header('Cache-Control: private, no-store');
    readfile($caminho);
    exit;
}
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Seus arquivos · 72 Horas</title>
<link rel="icon" href="assets/img/logo-icone-fundo.svg" type="image/svg+xml">
<link rel="stylesheet" href="assets/css/site.css?v=2">
</head>
<body>
<header class="topo"><div class="topo__barra"><a class="marca" href="./"><img src="assets/img/logo-horizontal-escuro.svg" alt="72 Horas" width="190" height="45"></a></div></header>
<main class="pagina"><div class="caixa caixa--estreita">
<?php if ($ok): ?>
  <p class="rotulo">Acesso liberado</p>
  <h1>Olá, <?= htmlspecialchars(explode(' ', $pedido['nome'])[0]) ?>. Aqui estão os seus arquivos.</h1>
  <p>Baixe os 5 PDFs e guarde uma cópia no celular e outra impressa. Comece pela Abertura do manual e faça a primeira caixa "Faça agora" ainda hoje.</p>
  <ul class="arquivos">
    <?php foreach (ARQUIVOS as $n => $a): ?>
      <li><a href="download.php?t=<?= urlencode($t) ?>&amp;f=<?= $n ?>"><?= htmlspecialchars($a['nome']) ?> <span>BAIXAR PDF</span></a></li>
    <?php endforeach; ?>
  </ul>
  <p class="nota">Dúvidas: <a href="mailto:sobrevivencia72h@gmail.com">sobrevivencia72h@gmail.com</a> ou WhatsApp <a href="https://wa.me/5548991657775">(48) 99165-7775</a>.</p>
<?php else: ?>
  <p class="rotulo">Link indisponível</p>
  <h1>Este link expirou ou não é válido.</h1>
  <p>Se você já comprou, escreva para <a href="mailto:sobrevivencia72h@gmail.com">sobrevivencia72h@gmail.com</a> ou chame no WhatsApp <a href="https://wa.me/5548991657775">(48) 99165-7775</a> que enviamos um link novo.</p>
<?php endif; ?>
</div></main>
</body>
</html>
