<?php
require (static function () { foreach ([__DIR__ . '/../../privado/lib.php', __DIR__ . '/../privado/lib.php'] as $f) if (is_file($f)) return $f; http_response_code(500); exit('config'); })();
$promo = isset($_GET['p']) ? trim((string) $_GET['p']) : null;
$o = oferta($promo);
json_saida(['preco' => number_format($o['preco'], 2, '.', ''), 'fim' => $o['fim'], 'agora' => time(), 'promo' => $o['promo']]);
