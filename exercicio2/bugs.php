<?php
// bugs.php: este arquivo tem 11 defeitos. Encontre, explique e corrija cada um.

$produto = "Jus de pomme biologique"
$gtin = 03000123456789;
$pesoBruto = 1,1;
$pesoLiquido = 1.0;
$Marca = "Green Orchard";
$descricao = "Pressé & <b>100% naturel</b>";
$itens = 0;
$soma = 17;
$unidades = 25;
$porPagina = 10;

echo "<pre>";
echo "Produto: $produto\n";
echo 'GTIN: $gtin' . "\n";
echo "Marca: $marca\n";
echo "Peso da embalagem (kg): " . ($pesoBruto - $pesoLiquido) . "\n";
echo "Letras da palavra pressé: " . strlen("pressé") . "\n";
echo "Soma de 5, 3 e 2: " . "5" . 3 + 2 . "\n";
echo "Páginas necessárias: " . $unidades / $porPagina . "\n";
echo "Média: " . $soma / $itens . "\n";
echo "0,1 + 0,2 é igual a 0,3? " . (0.1 + 0.2 == 0.3 ? "sim" : "não") . "\n";
echo "</pre>";
echo "<p>$descricao</p>";