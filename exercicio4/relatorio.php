<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

$produtos = [
    ['nome' => 'Cidre',           'peso' => 0.75, 'preco' => 18.90, 'estoque' => 24, 'ativo' => true],
    ['nome' => 'Jus de pomme',    'peso' => 1.00, 'preco' => 12.50, 'estoque' => 0,  'ativo' => true],
    ['nome' => 'Vinaigre',        'peso' => 0.50, 'preco' => 9.80,  'estoque' => 15, 'ativo' => false],
    ['nome' => 'Confiture',       'peso' => 0.35, 'preco' => 14.00, 'estoque' => 40, 'ativo' => true],
    ['nome' => 'Moutarde',        'peso' => 0.20, 'preco' => 7.50,  'estoque' => 5,  'ativo' => true],
    ['nome' => 'Miel de lavande', 'peso' => 0.50, 'preco' => 32.00, 'estoque' => 8,  'ativo' => true],
    ['nome' => "Huile d'olive",   'peso' => 1.00, 'preco' => 45.90, 'estoque' => 3,  'ativo' => false],
    ['nome' => 'Épices',          'peso' => 0.10, 'preco' => 6.90,  'estoque' => 60, 'ativo' => true],
    ['nome' => 'Pâté',            'peso' => 0.30, 'preco' => 21.00, 'estoque' => 0,  'ativo' => true],
    ['nome' => 'Tisane',          'peso' => 0.15, 'preco' => 11.30, 'estoque' => 18, 'ativo' => true],
];

function situacaoDoEstoque(int $estoque): string
{
    return match (true) {
        $estoque <= 0 => 'Esgotado',
        $estoque <= 9 => 'Baixo',
        default => 'Normal',
    };
}

function resumirProdutos(array $produtos): array
{
    $ativos = 0;
    $inativos = 0;
    $valorEstoque = 0.0;
    $pesoEstoque = 0.0;
    $somaPrecos = 0.0;
    $maisCaro = null;
    $maisBarato = null;
    $porSituacao = [
        'Esgotado' => 0,
        'Baixo' => 0,
        'Normal' => 0,
    ];

    foreach ($produtos as $p) {
        if (!$p['ativo']) {
            $inativos++;
            continue;
        }

        $ativos++;
        $valorEstoque += $p['preco'] * $p['estoque'];
        $pesoEstoque += $p['peso'] * $p['estoque'];
        $somaPrecos += $p['preco'];

        $situacao = situacaoDoEstoque($p['estoque']);
        $porSituacao[$situacao]++;

        if ($maisCaro === null || $p['preco'] > $maisCaro['preco']) {
            $maisCaro = ['nome' => $p['nome'], 'preco' => $p['preco']];
        }

        if ($maisBarato === null || $p['preco'] < $maisBarato['preco']) {
            $maisBarato = ['nome' => $p['nome'], 'preco' => $p['preco']];
        }
    }

    // Busca o primeiro urgente e para imediatamente com break
    $primeiroUrgente = null;
    foreach ($produtos as $p) {
        if (!$p['ativo']) {
            continue;
        }

        $situacao = situacaoDoEstoque($p['estoque']);
        if ($situacao === 'Esgotado' || $situacao === 'Baixo') {
            $primeiroUrgente = $p['nome'];
            break;
        }
    }

    // Proteção contra divisão por zero
    $precoMedio = $ativos > 0 ? $somaPrecos / $ativos : null;

    return [
        'ativos' => $ativos,
        'inativos' => $inativos,
        'valorEstoque' => $valorEstoque,
        'pesoEstoque' => $pesoEstoque,
        'precoMedio' => $precoMedio,
        'maisCaro' => $maisCaro,
        'maisBarato' => $maisBarato,
        'porSituacao' => $porSituacao,
        'primeiroUrgente' => $primeiroUrgente,
    ];
}

function lotesParaReposicao(int $estoqueAtual, int $tamanhoDoLote, int $meta): array
{
    if ($tamanhoDoLote <= 0) {
        return ['lotes' => 0, 'estoqueFinal' => $estoqueAtual];
    }

    $lotes = 0;
    $estoqueFinal = $estoqueAtual;

    while ($estoqueFinal < $meta) {
        $estoqueFinal += $tamanhoDoLote;
        $lotes++;
    }

    return ['lotes' => $lotes, 'estoqueFinal' => $estoqueFinal];
}

function tabelaDeDescontos(float $preco, int $de, int $ate, int $passo): array
{
    if ($passo <= 0) {
        return [];
    }

    $linhas = [];
    for ($pct = $de; $pct <= $ate; $pct += $passo) {
        $precoComDesconto = round($preco * (1 - $pct / 100), 2);
        $linhas[] = [
            'percentual' => $pct,
            'preco' => $precoComDesconto,
        ];
    }

    return $linhas;
}

$resumo = resumirProdutos($produtos);

// Busca o estoque do primeiro urgente (se existir) para calcular a reposição
$reposicao = null;
if ($resumo['primeiroUrgente'] !== null) {
    $estoqueUrgente = 0;
    foreach ($produtos as $p) {
        if ($p['ativo'] && $p['nome'] === $resumo['primeiroUrgente']) {
            $estoqueUrgente = $p['estoque'];
            break;
        }
    }
    $reposicao = lotesParaReposicao($estoqueUrgente, 12, 30);
}

// Tabela de descontos do produto mais caro (0% a 25%, passo 5)
$descontosMaisCaro = $resumo['maisCaro'] !== null
    ? tabelaDeDescontos($resumo['maisCaro']['preco'], 0, 25, 5)
    : [];

$resVazio = resumirProdutos([]);
$resInativos = resumirProdutos([
    ['nome' => 'Vinaigre',      'peso' => 0.50, 'preco' => 9.80,  'estoque' => 15, 'ativo' => false],
    ['nome' => "Huile d'olive", 'peso' => 1.00, 'preco' => 45.90, 'estoque' => 3,  'ativo' => false],
]);
$resUmItem = resumirProdutos([
    ['nome' => 'Cidre', 'peso' => 0.75, 'preco' => 18.90, 'estoque' => 24, 'ativo' => true],
]);
$resEmpate = resumirProdutos([
    ['nome' => 'Miel de lavande', 'peso' => 0.50, 'preco' => 32.00, 'estoque' => 8, 'ativo' => true],
    ['nome' => 'Truffe',          'peso' => 0.10, 'preco' => 32.00, 'estoque' => 4, 'ativo' => true],
]);

$testes = [
    ['nome' => 'Lista vazia', 'ok' => $resVazio['ativos'] === 0 && $resVazio['inativos'] === 0 && $resVazio['precoMedio'] === null && $resVazio['maisCaro'] === null && $resVazio['primeiroUrgente'] === null],
    ['nome' => 'Só inativos', 'ok' => $resInativos['ativos'] === 0 && $resInativos['inativos'] === 2 && $resInativos['precoMedio'] === null],
    ['nome' => 'Um item (Cidre)', 'ok' => $resUmItem['ativos'] === 1 && abs($resUmItem['valorEstoque'] - 453.60) < 0.005 && $resUmItem['maisCaro']['nome'] === 'Cidre' && $resUmItem['maisBarato']['nome'] === 'Cidre' && $resUmItem['primeiroUrgente'] === null],
    ['nome' => 'Empate de preço (vence o primeiro)', 'ok' => $resEmpate['ativos'] === 2 && $resEmpate['maisCaro']['nome'] === 'Miel de lavande' && $resEmpate['maisBarato']['nome'] === 'Miel de lavande' && $resEmpate['primeiroUrgente'] === 'Miel de lavande'],
    ['nome' => 'lotesParaReposicao(0, 12, 30)',  'ok' => lotesParaReposicao(0, 12, 30)  === ['lotes' => 3, 'estoqueFinal' => 36]],
    ['nome' => 'lotesParaReposicao(30, 12, 30)', 'ok' => lotesParaReposicao(30, 12, 30) === ['lotes' => 0, 'estoqueFinal' => 30]],
    ['nome' => 'lotesParaReposicao(29, 12, 30)', 'ok' => lotesParaReposicao(29, 12, 30) === ['lotes' => 1, 'estoqueFinal' => 41]],
    ['nome' => 'lotesParaReposicao(5, 10, 5)',   'ok' => lotesParaReposicao(5, 10, 5)   === ['lotes' => 0, 'estoqueFinal' => 5]],
    ['nome' => 'lotesParaReposicao(0, 0, 30)',   'ok' => lotesParaReposicao(0, 0, 30)   === ['lotes' => 0, 'estoqueFinal' => 0]],
    ['nome' => 'lotesParaReposicao(0, -5, 30)',  'ok' => lotesParaReposicao(0, -5, 30)  === ['lotes' => 0, 'estoqueFinal' => 0]],
    ['nome' => 'tabelaDeDescontos(32, 0, 25, 5)',   'ok' => count(tabelaDeDescontos(32.00, 0, 25, 5)) === 6],
    ['nome' => 'tabelaDeDescontos(100, 10, 10, 5)', 'ok' => tabelaDeDescontos(100.0, 10, 10, 5) === [['percentual' => 10, 'preco' => 90.00]]],
    ['nome' => 'tabelaDeDescontos(100, 0, 20, 0)',  'ok' => tabelaDeDescontos(100.0, 0, 20, 0) === []],
];

$passaram = 0;
foreach ($testes as $t) {
    if ($t['ok']) {
        $passaram++;
    }
}
$totalTestes = count($testes);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercício 4 — Relatório de Estoque</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Relatório de Estoque — Produtos Franceses</h1>

        <?php if (count($produtos) === 0): ?>
            <p class="aviso">Nenhum produto cadastrado.</p>
        <?php else: ?>
            <h2>Catálogo de Produtos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Preço</th>
                        <th>Peso</th>
                        <th>Estoque</th>
                        <th>Situação</th>
                        <th>Valor em estoque</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $p): ?>
                        <tr class="<?= $p['ativo'] ? '' : 'inativo' ?>">
                            <td><?= htmlspecialchars($p['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
                            <td><?= number_format($p['peso'], 2, ',', '.') ?> kg</td>
                            <td><?= $p['estoque'] ?></td>
                            <td><?= $p['ativo'] ? situacaoDoEstoque($p['estoque']) : '—' ?></td>
                            <td><?= $p['ativo'] ? 'R$ ' . number_format($p['preco'] * $p['estoque'], 2, ',', '.') : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Resumo do Estoque</h2>
            <?php if ($resumo['ativos'] === 0): ?>
                <p class="aviso">Nenhum produto ativo.</p>
            <?php else: ?>
                <ul>
                    <li>Ativos: <?= $resumo['ativos'] ?> · Inativos: <?= $resumo['inativos'] ?></li>
                    <li>Valor total do estoque: R$ <?= number_format($resumo['valorEstoque'], 2, ',', '.') ?></li>
                    <li>Peso total do estoque: <?= number_format($resumo['pesoEstoque'], 1, ',', '.') ?> kg</li>
                    <li>Preço médio: R$ <?= number_format($resumo['precoMedio'], 2, ',', '.') ?></li>
                    <li>Mais caro: <?= htmlspecialchars($resumo['maisCaro']['nome'], ENT_QUOTES, 'UTF-8') ?> (R$ <?= number_format($resumo['maisCaro']['preco'], 2, ',', '.') ?>)</li>
                    <li>Mais barato: <?= htmlspecialchars($resumo['maisBarato']['nome'], ENT_QUOTES, 'UTF-8') ?> (R$ <?= number_format($resumo['maisBarato']['preco'], 2, ',', '.') ?>)</li>
                </ul>

                <h3>Contagem por situação</h3>
                <ul>
                    <?php foreach ($resumo['porSituacao'] as $situacao => $qtd): ?>
                        <li><?= htmlspecialchars($situacao, ENT_QUOTES, 'UTF-8') ?>: <?= $qtd ?></li>
                    <?php endforeach; ?>
                </ul>

                <h2>Reposição Urgente</h2>
                <?php if ($resumo['primeiroUrgente'] === null): ?>
                    <p>Nenhum produto precisa de reposição.</p>
                <?php else: ?>
                    <p>
                        Primeiro produto a repor: <strong><?= htmlspecialchars($resumo['primeiroUrgente'], ENT_QUOTES, 'UTF-8') ?></strong>.
                        Com lotes de 12 unidades, são necessários <?= $reposicao['lotes'] ?> lotes para chegar a pelo menos 30 unidades
                        (estoque final: <?= $reposicao['estoqueFinal'] ?>).
                    </p>
                <?php endif; ?>

                <h2>Simulação de Descontos — <?= htmlspecialchars($resumo['maisCaro']['nome'], ENT_QUOTES, 'UTF-8') ?></h2>
                <table>
                    <thead>
                        <tr>
                            <th>Desconto</th>
                            <th>Preço final</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($descontosMaisCaro as $linha): ?>
                            <tr>
                                <td><?= $linha['percentual'] ?>%</td>
                                <td>R$ <?= number_format($linha['preco'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <h2>Testes</h2>
        <div class="resumo <?= $passaram === $totalTestes ? 'sucesso' : 'falha' ?>">
            <?= $passaram ?> de <?= $totalTestes ?> testes passaram
        </div>
        <table>
            <thead>
                <tr>
                    <th>Teste</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($testes as $t): ?>
                    <tr class="<?= $t['ok'] ? 'ok-row' : 'fail-row' ?>">
                        <td><?= htmlspecialchars($t['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge <?= $t['ok'] ? 'badge-ok' : 'badge-fail' ?>">
                                <?= $t['ok'] ? 'OK' : 'FALHOU' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>