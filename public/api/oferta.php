<?php
require (static function () { foreach ([__DIR__ . '/../../privado/lib.php', __DIR__ . '/../privado/lib.php'] as $f) if (is_file($f)) return $f; http_response_code(500); exit('config'); })();
$o = oferta();
json_saida(['preco' => number_format($o['preco'], 2, '.', ''), 'fim' => $o['fim'], 'agora' => time()]);
