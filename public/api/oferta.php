<?php
require __DIR__ . '/../../privado/lib.php';
$o = oferta();
json_saida(['preco' => number_format($o['preco'], 2, '.', ''), 'fim' => $o['fim'], 'agora' => time()]);
