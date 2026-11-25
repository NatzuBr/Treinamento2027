<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

// corrigido.php: versão com os 11 defeitos corrigidos.

// CORREÇÃO 1: faltava ponto e vírgula no final da instrução
$produto = "Jus de pomme biologique";
// CORREÇÃO 2: número com 0 à esquerda era lido como octal inválido; GTIN deve ser string
$gtin = "03000123456789";
// CORREÇÃO 3: separador decimal no PHP é ponto, não vírgula
$pesoBruto = 1.1;
$pesoLiquido = 1.0;
// CORREÇÃO 4: PHP diferencia maiúsculas e minúsculas ($Marca != $marca)
$marca = "Green Orchard";
$descricao = "Pressé & <b>100% naturel</b>";
$itens = 0;
$soma = 17;
$unidades = 25;
$porPagina = 10;

echo "<pre>";
echo "Produto: " . htmlspecialchars($produto, ENT_QUOTES, 'UTF-8') . "\n";
// CORREÇÃO 6: aspas simples não interpolam variáveis; trocado para aspas duplas
echo "GTIN: $gtin\n";
echo "Marca: $marca\n";
echo "Peso da embalagem (kg): " . ($pesoBruto - $pesoLiquido) . "\n";
// CORREÇÃO 7: strlen conta bytes (é = 2 bytes em UTF-8); mb_strlen conta caracteres
echo "Letras da palavra pressé: " . mb_strlen("pressé", "UTF-8") . "\n";
// CORREÇÃO 8: "5" . 3 + 2 concatenava "5" com 5 (gerando 55); somando números entre parênteses
echo "Soma de 5, 3 e 2: " . (5 + 3 + 2) . "\n";
// CORREÇÃO 9: divisão gerava 2.5; ceil() arredonda para cima o número de páginas
echo "Páginas necessárias: " . (int) ceil($unidades / $porPagina) . "\n";
// CORREÇÃO 5: divisão por zero gera DivisionByZeroError; verificando se $itens > 0
echo "Média: " . ($itens > 0 ? $soma / $itens : "sem itens para calcular") . "\n";
// CORREÇÃO 10: imprecisão de ponto flutuante (0.1 + 0.2 != 0.3); comparando com tolerância
echo "0,1 + 0,2 é igual a 0,3? " . (abs((0.1 + 0.2) - 0.3) < 0.0001 ? "sim" : "não") . "\n";
echo "</pre>";
// CORREÇÃO 11: escapando HTML com htmlspecialchars para evitar renderização de tags e XSS
echo "<p>" . htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8') . "</p>";