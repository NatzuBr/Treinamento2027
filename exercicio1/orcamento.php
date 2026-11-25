<?php
// 1. Regras de negócio (constantes)
const DESCONTO_PERCENTUAL = 8;
const FRETE = 18.50;
const PARCELAS = 3;
const UNIDADES_POR_DUZIA = 12;
const CASAS_DECIMAIS = 2;

// 2. Dados do pedido (Conjunto A)
$cliente = 'Café & Cia';

$item1Nome  = 'Caixa de papelão 30x20';
$item1Preco = 4.90;
$item1Qtd   = 30;
$item1Peso  = 0.12;

$item2Nome  = 'Fita adesiva 48 mm';
$item2Preco = 7.35;
$item2Qtd   = 6;
$item2Peso  = 0.25;

$item3Nome  = 'Plástico-bolha (rolo)';
$item3Preco = 39.90;
$item3Qtd   = 2;
$item3Peso  = 1.80;

// 3. Cálculos gerais (sem formatar)
$item1Subtotal = $item1Preco * $item1Qtd;
$item2Subtotal = $item2Preco * $item2Qtd;
$item3Subtotal = $item3Preco * $item3Qtd;
$subtotalGeral = $item1Subtotal + $item2Subtotal + $item3Subtotal;

$valorDesconto       = $subtotalGeral * DESCONTO_PERCENTUAL / 100;
$subtotalComDesconto = $subtotalGeral - $valorDesconto;
$total               = $subtotalComDesconto + FRETE;

$qtdTotal   = $item1Qtd + $item2Qtd + $item3Qtd;
$pesoTotal  = ($item1Peso * $item1Qtd) + ($item2Peso * $item2Qtd) + ($item3Peso * $item3Qtd);
$precoMedio = $subtotalGeral / $qtdTotal;

$item1Duzias = intdiv($item1Qtd, UNIDADES_POR_DUZIA);
$item1Soltas = $item1Qtd % UNIDADES_POR_DUZIA;

// 4. Parcelamento em centavos inteiros
$totalCentavos           = (int) round($total * 100);
$parcelaBaseCentavos     = intdiv($totalCentavos, PARCELAS);
$restoCentavos           = $totalCentavos % PARCELAS;
$primeiraParcelaCentavos = $parcelaBaseCentavos + $restoCentavos;

$demaisParcelasQtd    = PARCELAS - 1;
$somaParcelasCentavos = $primeiraParcelaCentavos + ($parcelaBaseCentavos * $demaisParcelasQtd);
$conferencia          = ($somaParcelasCentavos === $totalCentavos);

// 5. Preparação do resumo em texto (Heredoc)
$clienteSeguro = htmlspecialchars($cliente, ENT_QUOTES, 'UTF-8');
$pesoFormatado = number_format($pesoTotal, 1, ',', '.');
$qtdParcelas   = PARCELAS;

$resumoTexto = <<<TEXTO
Pedido de {$clienteSeguro} com {$qtdTotal} itens, pesando {$pesoFormatado} kg, em {$qtdParcelas} parcelas.
TEXTO;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamento — <?= $clienteSeguro ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Orçamento de Pedido</h1>
            <p class="cliente-badge">Cliente: <?= $clienteSeguro ?></p>
        </header>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Preço unitário</th>
                    <th>Quantidade</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($item1Nome, ENT_QUOTES, 'UTF-8') ?></td>
                    <td>R$ <?= number_format($item1Preco, CASAS_DECIMAIS, ',', '.') ?></td>
                    <td><?= $item1Qtd ?></td>
                    <td>R$ <?= number_format($item1Subtotal, CASAS_DECIMAIS, ',', '.') ?></td>
                </tr>
                <tr>
                    <td><?= htmlspecialchars($item2Nome, ENT_QUOTES, 'UTF-8') ?></td>
                    <td>R$ <?= number_format($item2Preco, CASAS_DECIMAIS, ',', '.') ?></td>
                    <td><?= $item2Qtd ?></td>
                    <td>R$ <?= number_format($item2Subtotal, CASAS_DECIMAIS, ',', '.') ?></td>
                </tr>
                <tr>
                    <td><?= htmlspecialchars($item3Nome, ENT_QUOTES, 'UTF-8') ?></td>
                    <td>R$ <?= number_format($item3Preco, CASAS_DECIMAIS, ',', '.') ?></td>
                    <td><?= $item3Qtd ?></td>
                    <td>R$ <?= number_format($item3Subtotal, CASAS_DECIMAIS, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>

        <div class="grid-resumo">
            <section class="card">
                <h2>Resumo Financeiro</h2>
                <ul>
                    <li><span>Subtotal geral</span> <span>R$ <?= number_format($subtotalGeral, CASAS_DECIMAIS, ',', '.') ?></span></li>
                    <li><span>Desconto (<?= DESCONTO_PERCENTUAL ?>%)</span> <span>R$ <?= number_format($valorDesconto, CASAS_DECIMAIS, ',', '.') ?></span></li>
                    <li><span>Subtotal c/ desconto</span> <span>R$ <?= number_format($subtotalComDesconto, CASAS_DECIMAIS, ',', '.') ?></span></li>
                    <li><span>Frete</span> <span>R$ <?= number_format(FRETE, CASAS_DECIMAIS, ',', '.') ?></span></li>
                    <li class="total-destaque"><strong>Total</strong> <strong>R$ <?= number_format($total, CASAS_DECIMAIS, ',', '.') ?></strong></li>
                </ul>
            </section>

            <section class="card">
                <h2>Logística e Métricas</h2>
                <ul>
                    <li><span>Total de itens</span> <span><?= $qtdTotal ?></span></li>
                    <li><span>Peso total</span> <span><?= number_format($pesoTotal, 1, ',', '.') ?> kg</span></li>
                    <li><span>Preço médio</span> <span>R$ <?= number_format($precoMedio, CASAS_DECIMAIS, ',', '.') ?></span></li>
                    <li><span>Item 1 (dúzias)</span> <span><?= $item1Duzias ?> dúzias e <?= $item1Soltas ?> soltas</span></li>
                </ul>
            </section>

            <section class="card">
                <h2>Parcelamento</h2>
                <ul>
                    <li><span>Total em centavos</span> <span><?= $totalCentavos ?></span></li>
                    <li><span>Base · Resto</span> <span><?= $parcelaBaseCentavos ?> · <?= $restoCentavos ?></span></li>
                    <li><span>1ª parcela</span> <span>R$ <?= number_format($primeiraParcelaCentavos / 100, CASAS_DECIMAIS, ',', '.') ?></span></li>
                    <li><span>Demais <?= $demaisParcelasQtd ?> parcelas</span> <span>R$ <?= number_format($parcelaBaseCentavos / 100, CASAS_DECIMAIS, ',', '.') ?> cada</span></li>
                </ul>
            </section>
        </div>

        <h2>Conferência do Parcelamento</h2>
        <pre><?php var_dump($conferencia); ?></pre>

        <h2>Resumo do Pedido</h2>
        <p class="resumo-heredoc"><?= $resumoTexto ?></p>

        <h2>Laboratório de Números</h2>
        <pre><?php
            echo "L1: "; var_dump(4.90 * 30);
            echo "L2: "; var_dump(0.1 + 0.2 == 0.3);
            echo "L3: "; var_dump((int) (19.99 * 100));
            echo "L4: "; var_dump((int) round(19.99 * 100));
            echo "L5: "; var_dump((int) (267.728 * 100), (int) round(267.728 * 100));
            echo "L6: "; var_dump(intdiv(26773, 3), 26773 % 3);
            echo "L7: "; var_dump(10 / 4, 10 / 5, 10 % 4);
        ?></pre>
    </div>
</body>
</html>