<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

function e(string|int|float|null $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formatarReais(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

// =========================================================================
// DADOS
// =========================================================================

$vendas = [
    ['id' => 1,  'cliente' => 'Ana',   'produto' => 'Cidre',        'categoria' => 'Bebidas',   'valor' => 56.70, 'qtd' => 3, 'data' => '2026-09-03', 'pago' => true],
    ['id' => 2,  'cliente' => 'Bruno', 'produto' => 'Confiture',    'categoria' => 'Mercearia', 'valor' => 28.00, 'qtd' => 2, 'data' => '2026-09-14', 'pago' => true],
    ['id' => 3,  'cliente' => 'Ana',   'produto' => 'Miel',         'categoria' => 'Mercearia', 'valor' => 64.00, 'qtd' => 2, 'data' => '2026-09-21', 'pago' => false],
    ['id' => 4,  'cliente' => 'Carla', 'produto' => 'Jus de pomme', 'categoria' => 'Bebidas',   'valor' => 37.50, 'qtd' => 3, 'data' => '2026-10-02', 'pago' => true],
    ['id' => 5,  'cliente' => 'Bruno', 'produto' => 'Cidre',        'categoria' => 'Bebidas',   'valor' => 94.50, 'qtd' => 5, 'data' => '2026-10-05', 'pago' => true],
    ['id' => 6,  'cliente' => 'Diego', 'produto' => 'Épices',       'categoria' => 'Mercearia', 'valor' => 13.80, 'qtd' => 2, 'data' => '2026-10-11', 'pago' => false],
    ['id' => 7,  'cliente' => 'Carla', 'produto' => 'Tisane',       'categoria' => 'Bebidas',   'valor' => 22.60, 'qtd' => 2, 'data' => '2026-10-19', 'pago' => true],
    ['id' => 8,  'cliente' => 'Ana',   'produto' => 'Pâté',         'categoria' => 'Frios',     'valor' => 63.00, 'qtd' => 3, 'data' => '2026-10-27', 'pago' => true],
    ['id' => 9,  'cliente' => 'Elisa', 'produto' => 'Moutarde',     'categoria' => 'Mercearia', 'valor' => 15.00, 'qtd' => 2, 'data' => '2026-11-04', 'pago' => true],
    ['id' => 10, 'cliente' => 'Diego', 'produto' => 'Pâté',         'categoria' => 'Frios',     'valor' => 42.00, 'qtd' => 2, 'data' => '2026-11-08', 'pago' => true],
    ['id' => 11, 'cliente' => 'Bruno', 'produto' => 'Jus de pomme', 'categoria' => 'Bebidas',   'valor' => 25.00, 'qtd' => 2, 'data' => '2026-11-15', 'pago' => false],
    ['id' => 12, 'cliente' => 'Elisa', 'produto' => 'Cidre',        'categoria' => 'Bebidas',   'valor' => 37.80, 'qtd' => 2, 'data' => '2026-11-22', 'pago' => true],
];

// =========================================================================
// FUNÇÕES DO EXERCÍCIO
// =========================================================================

function vendasPagas(array $vendas): array
{
    $filtradas = array_filter(
        $vendas,
        static fn(array $v): bool => $v['pago'] === true
    );

    return array_values($filtradas);
}

function somarValores(array $vendas): float
{
    return round((float) array_sum(array_column($vendas, 'valor')), 2);
}

function totaisPor(array $vendas, string $campo): array
{
    $totais = [];
    foreach ($vendas as $venda) {
        $chave = (string) $venda[$campo];
        $totais[$chave] = ($totais[$chave] ?? 0.0) + (float) $venda['valor'];
    }

    $totais = array_map(static fn(float $v): float => round($v, 2), $totais);

    $chaves = array_keys($totais);
    usort($chaves, static fn(string $a, string $b): int => [$totais[$b], $a] <=> [$totais[$a], $b]);

    $ordenado = [];
    foreach ($chaves as $chave) {
        $ordenado[$chave] = $totais[$chave];
    }

    return $ordenado;
}

function maioresTotais(array $totais, int $quantidade): array
{
    if ($quantidade <= 0) {
        return [];
    }

    return array_slice($totais, 0, $quantidade, true);
}

function totaisPorMes(array $vendas): array
{
    $totais = [];
    foreach ($vendas as $venda) {
        $mes = substr((string) $venda['data'], 0, 7);
        $totais[$mes] = ($totais[$mes] ?? 0.0) + (float) $venda['valor'];
    }

    $totais = array_map(static fn(float $v): float => round($v, 2), $totais);
    ksort($totais, SORT_STRING);

    return $totais;
}

function ordenarVendas(array $vendas, string $campo, bool $decrescente = false): array
{
    $copia = $vendas;
    usort($copia, static function (array $a, array $b) use ($campo, $decrescente): int {
        $cmp = $a[$campo] <=> $b[$campo];
        return $decrescente ? -$cmp : $cmp;
    });

    return $copia;
}

function ordenarPorClienteEDataDesc(array $vendas): array
{
    $copia = $vendas;
    usort($copia, static function (array $a, array $b): int {
        return [$a['cliente'], $b['data']] <=> [$b['cliente'], $a['data']];
    });

    return $copia;
}

function clientesUnicos(array $vendas): array
{
    return array_values(array_unique(array_column($vendas, 'cliente')));
}

function quantidadePorProduto(array $vendas): array
{
    $quantidades = [];
    foreach ($vendas as $venda) {
        $produto = (string) $venda['produto'];
        $quantidades[$produto] = ($quantidades[$produto] ?? 0) + (int) $venda['qtd'];
    }

    $chaves = array_keys($quantidades);
    usort($chaves, static fn(string $a, string $b): int => [$quantidades[$b], $a] <=> [$quantidades[$a], $b]);

    $ordenado = [];
    foreach ($chaves as $chave) {
        $ordenado[$chave] = $quantidades[$chave];
    }

    return $ordenado;
}

function paginar(array $lista, int $pagina, int $porPagina): array
{
    $porPagina = $porPagina < 1 ? 1 : $porPagina;
    $totalItens = count($lista);

    if ($totalItens === 0) {
        return [
            'itens'        => [],
            'pagina'       => 1,
            'totalPaginas' => 0,
        ];
    }

    $totalPaginas = (int) ceil($totalItens / $porPagina);
    $paginaAjustada = max(1, min($pagina, $totalPaginas));
    $inicio = ($paginaAjustada - 1) * $porPagina;

    return [
        'itens'        => array_slice(array_values($lista), $inicio, $porPagina),
        'pagina'       => $paginaAjustada,
        'totalPaginas' => $totalPaginas,
    ];
}

function resumoGeral(array $vendas): array
{
    $pagas = vendasPagas($vendas);
    $pendentes = array_values(array_filter(
        $vendas,
        static fn(array $v): bool => $v['pago'] === false
    ));

    $qtdPagas = count($pagas);
    $totalGeral = somarValores($vendas);
    $totalPago = somarValores($pagas);
    $totalPendente = somarValores($pendentes);

    $ticketMedio = $qtdPagas > 0 ? round($totalPago / $qtdPagas, 2) : null;

    $maiorVendaPaga = null;
    $menorVendaPaga = null;
    if ($qtdPagas > 0) {
        $ordenadasDesc = ordenarVendas($pagas, 'valor', true);
        $ordenadasAsc = ordenarVendas($pagas, 'valor', false);
        $maiorVendaPaga = $ordenadasDesc[0];
        $menorVendaPaga = $ordenadasAsc[0];
    }

    return [
        'quantidade'     => count($vendas),
        'pagas'          => $qtdPagas,
        'pendentes'      => count($pendentes),
        'totalGeral'     => $totalGeral,
        'totalPago'      => $totalPago,
        'totalPendente'  => $totalPendente,
        'ticketMedio'    => $ticketMedio,
        'maiorVendaPaga' => $maiorVendaPaga,
        'menorVendaPaga' => $menorVendaPaga,
    ];
}

// =========================================================================
// PREPARAÇÃO DOS DADOS PARA A PÁGINA
// =========================================================================

$pagas = vendasPagas($vendas);
$pendentes = array_values(array_filter($vendas, static fn(array $v): bool => $v['pago'] === false));

$resumo = resumoGeral($vendas);
$totaisCategoria = totaisPor($pagas, 'categoria');
$top3Clientes = maioresTotais(totaisPor($pagas, 'cliente'), 3);
$totaisMes = totaisPorMes($pagas);
$clientesPendentes = clientesUnicos($pendentes);
$qtdProdutos = quantidadePorProduto($vendas);

$vendasPorValorDesc = ordenarVendas($vendas, 'valor', true);
$paginaPedida = isset($_GET['pagina']) && is_string($_GET['pagina']) && ctype_digit($_GET['pagina'])
    ? (int) $_GET['pagina']
    : 1;
$paginado = paginar($vendasPorValorDesc, $paginaPedida, 5);

$vendasClienteData = ordenarPorClienteEDataDesc($vendas);

// =========================================================================
// BATERIA DE TESTES AUTOMATIZADOS
// =========================================================================

$listaVazia = [];
$umaVenda = [$vendas[0]];
$soPendentes = array_values(array_filter($vendas, static fn(array $v): bool => $v['pago'] === false));

$resumoVazio = resumoGeral($listaVazia);
$resumoUma = resumoGeral($umaVenda);
$resumoPend = resumoGeral($soPendentes);

$pag1 = paginar($vendasPorValorDesc, 1, 5);
$pag3 = paginar($vendasPorValorDesc, 3, 5);
$pag0 = paginar($vendasPorValorDesc, 0, 5);
$pag99 = paginar($vendasPorValorDesc, 99, 5);
$pagPor0 = paginar($vendasPorValorDesc, 1, 0);
$pagVazia = paginar([], 1, 5);

$testes = [
    ['nome' => 'Resumo: totais e contagens (lista completa)', 'ok' =>
        $resumo['quantidade'] === 12
        && $resumo['pagas'] === 9
        && $resumo['pendentes'] === 3
        && $resumo['totalGeral'] === 499.9
        && $resumo['totalPago'] === 397.1
        && $resumo['totalPendente'] === 102.8
        && $resumo['ticketMedio'] === 44.12
    ],
    ['nome' => 'Resumo: maior (id 5) e menor (id 9) venda paga', 'ok' =>
        $resumo['maiorVendaPaga'] !== null && $resumo['maiorVendaPaga']['id'] === 5
        && $resumo['menorVendaPaga'] !== null && $resumo['menorVendaPaga']['id'] === 9
    ],
    ['nome' => 'Totais por categoria (pagas)', 'ok' =>
        $totaisCategoria === ['Bebidas' => 249.1, 'Frios' => 105.0, 'Mercearia' => 43.0]
    ],
    ['nome' => 'Três maiores clientes (pagas)', 'ok' =>
        $top3Clientes === ['Bruno' => 122.5, 'Ana' => 119.7, 'Carla' => 60.1]
    ],
    ['nome' => 'Totais por mês (pagas)', 'ok' =>
        $totaisMes === ['2026-09' => 84.7, '2026-10' => 217.6, '2026-11' => 94.8]
    ],
    ['nome' => 'Clientes únicos (todas as vendas)', 'ok' =>
        clientesUnicos($vendas) === ['Ana', 'Bruno', 'Carla', 'Diego', 'Elisa']
    ],
    ['nome' => 'Clientes com pendência', 'ok' =>
        $clientesPendentes === ['Ana', 'Diego', 'Bruno']
    ],
    ['nome' => 'Quantidade por produto (com Épices por último)', 'ok' =>
        $qtdProdutos === [
            'Cidre' => 10, 'Jus de pomme' => 5, 'Pâté' => 5,
            'Confiture' => 2, 'Miel' => 2, 'Moutarde' => 2, 'Tisane' => 2, 'Épices' => 2,
        ]
    ],
    ['nome' => 'Ordem por valor decrescente (ids)', 'ok' =>
        array_column($vendasPorValorDesc, 'id') === [5, 3, 8, 1, 10, 12, 4, 2, 11, 7, 9, 6]
    ],
    ['nome' => 'Ordem por cliente e data decrescente (ids)', 'ok' =>
        array_column($vendasClienteData, 'id') === [8, 3, 1, 11, 5, 2, 7, 4, 10, 6, 12, 9]
    ],
    ['nome' => 'Lista original não foi alterada pelas ordenações', 'ok' =>
        array_column($vendas, 'id') === [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]
    ],
    ['nome' => 'Lista vazia: resumo com zeros e nulos, listas vazias', 'ok' =>
        $resumoVazio['quantidade'] === 0
        && $resumoVazio['ticketMedio'] === null
        && $resumoVazio['maiorVendaPaga'] === null
        && $resumoVazio['menorVendaPaga'] === null
        && totaisPor([], 'categoria') === []
        && maioresTotais([], 3) === []
        && totaisPorMes([]) === []
        && clientesUnicos([]) === []
        && quantidadePorProduto([]) === []
        && ordenarVendas([], 'valor') === []
        && $pagVazia === ['itens' => [], 'pagina' => 1, 'totalPaginas' => 0]
    ],
    ['nome' => 'Uma venda (id 1): maior e menor iguais, 1 página', 'ok' =>
        $resumoUma['pagas'] === 1
        && $resumoUma['ticketMedio'] === 56.7
        && $resumoUma['maiorVendaPaga'] === $resumoUma['menorVendaPaga']
        && paginar($umaVenda, 1, 5)['totalPaginas'] === 1
    ],
    ['nome' => 'Só pendentes (ids 3, 6 e 11): nulos e totalPendente 102.8', 'ok' =>
        $resumoPend['pagas'] === 0
        && $resumoPend['ticketMedio'] === null
        && $resumoPend['maiorVendaPaga'] === null
        && $resumoPend['menorVendaPaga'] === null
        && $resumoPend['totalPendente'] === 102.8
    ],
    ['nome' => 'paginar($lista, 1, 5): 5 itens, pág 1 de 3', 'ok' =>
        array_column($pag1['itens'], 'id') === [5, 3, 8, 1, 10]
        && $pag1['pagina'] === 1 && $pag1['totalPaginas'] === 3
    ],
    ['nome' => 'paginar($lista, 3, 5): 2 itens (9 e 6), pág 3', 'ok' =>
        array_column($pag3['itens'], 'id') === [9, 6]
        && $pag3['pagina'] === 3
    ],
    ['nome' => 'paginar($lista, 0, 5): igual à página 1', 'ok' =>
        $pag0 === $pag1
    ],
    ['nome' => 'paginar($lista, 99, 5): igual à página 3', 'ok' =>
        $pag99 === $pag3
    ],
    ['nome' => 'paginar($lista, 1, 0): 1 item, pág 1 de 12', 'ok' =>
        count($pagPor0['itens']) === 1
        && $pagPor0['pagina'] === 1 && $pagPor0['totalPaginas'] === 12
    ],
];

$totalTestes = count($testes);
$testesPassaram = count(array_filter($testes, static fn(array $t): bool => $t['ok'] === true));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercício 6 — Relatório de Vendas</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="container">
    <header>
        <p><a href="../index.php">&larr; Voltar para o painel</a></p>
        <h1>Exercício 6 — Relatório de Vendas</h1>
        <p>Funções de array, agrupamento, ordenação, paginação e testes</p>
    </header>

    <!-- 1. Resumo geral -->
    <section>
        <h2>1. Resumo Geral</h2>
        <table>
            <tbody>
                <tr><th>Total de vendas</th><td><?= e($resumo['quantidade']) ?></td></tr>
                <tr><th>Vendas pagas / pendentes</th><td><?= e($resumo['pagas']) ?> pagas / <?= e($resumo['pendentes']) ?> pendentes</td></tr>
                <tr><th>Total geral</th><td><?= e(formatarReais($resumo['totalGeral'])) ?></td></tr>
                <tr><th>Total pago</th><td><?= e(formatarReais($resumo['totalPago'])) ?></td></tr>
                <tr><th>Total pendente</th><td><?= e(formatarReais($resumo['totalPendente'])) ?></td></tr>
                <tr>
                    <th>Ticket médio (pagas)</th>
                    <td><?= $resumo['ticketMedio'] !== null ? e(formatarReais($resumo['ticketMedio'])) : '—' ?></td>
                </tr>
                <tr>
                    <th>Maior venda paga</th>
                    <td>
                        <?php if ($resumo['maiorVendaPaga'] !== null): ?>
                            ID <?= e($resumo['maiorVendaPaga']['id']) ?> (<?= e($resumo['maiorVendaPaga']['cliente']) ?>, <?= e(formatarReais($resumo['maiorVendaPaga']['valor'])) ?>)
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Menor venda paga</th>
                    <td>
                        <?php if ($resumo['menorVendaPaga'] !== null): ?>
                            ID <?= e($resumo['menorVendaPaga']['id']) ?> (<?= e($resumo['menorVendaPaga']['cliente']) ?>, <?= e(formatarReais($resumo['menorVendaPaga']['valor'])) ?>)
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </section>

    <!-- 2. Totais por categoria (pagas) -->
    <section>
        <h2>2. Totais por Categoria (Vendas Pagas)</h2>
        <table>
            <thead><tr><th>Categoria</th><th>Total</th></tr></thead>
            <tbody>
                <?php foreach ($totaisCategoria as $categoria => $total): ?>
                    <tr>
                        <td><?= e($categoria) ?></td>
                        <td><?= e(formatarReais($total)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- 3. Três maiores clientes (pagas) -->
    <section>
        <h2>3. Três Maiores Clientes (Total Pago)</h2>
        <table>
            <thead><tr><th>Cliente</th><th>Total Pago</th></tr></thead>
            <tbody>
                <?php foreach ($top3Clientes as $cliente => $total): ?>
                    <tr>
                        <td><?= e($cliente) ?></td>
                        <td><?= e(formatarReais($total)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- 4. Vendas por mês (pagas) -->
    <section>
        <h2>4. Vendas por Mês (Pagas)</h2>
        <table>
            <thead><tr><th>Mês</th><th>Total</th></tr></thead>
            <tbody>
                <?php foreach ($totaisMes as $mes => $total): ?>
                    <tr>
                        <td><?= e($mes) ?></td>
                        <td><?= e(formatarReais($total)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- 5. Clientes com pagamento pendente -->
    <section>
        <h2>5. Clientes com Pagamento Pendente</h2>
        <ul>
            <?php foreach ($clientesPendentes as $cliente): ?>
                <li><?= e($cliente) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <!-- 6. Produtos por quantidade vendida -->
    <section>
        <h2>6. Produtos por Quantidade Vendida (Todas as Vendas)</h2>
        <table>
            <thead><tr><th>Produto</th><th>Quantidade</th></tr></thead>
            <tbody>
                <?php foreach ($qtdProdutos as $produto => $qtd): ?>
                    <tr>
                        <td><?= e($produto) ?></td>
                        <td><?= e($qtd) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- 7. Lista ordenada por valor decrescente, paginada -->
    <section>
        <h2>7. Vendas por Valor (Decrescente) — Página <?= e($paginado['pagina']) ?> de <?= e($paginado['totalPaginas']) ?></h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Cliente</th><th>Produto</th><th>Valor</th><th>Data</th><th>Situação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($paginado['itens'] as $v): ?>
                    <tr>
                        <td><?= e($v['id']) ?></td>
                        <td><?= e($v['cliente']) ?></td>
                        <td><?= e($v['produto']) ?></td>
                        <td><?= e(formatarReais($v['valor'])) ?></td>
                        <td><?= e($v['data']) ?></td>
                        <td><?= $v['pago'] ? 'Pago' : 'Pendente' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($paginado['totalPaginas'] > 1): ?>
            <p style="margin-top: 1rem;">
                Páginas:
                <?php for ($p = 1; $p <= $paginado['totalPaginas']; $p++): ?>
                    <?php if ($p === $paginado['pagina']): ?>
                        <strong>[<?= e($p) ?>]</strong>
                    <?php else: ?>
                        <a href="?pagina=<?= e($p) ?>"><?= e($p) ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </p>
        <?php endif; ?>
    </section>

    <!-- 8. Lista ordenada por cliente e data decrescente -->
    <section>
        <h2>8. Vendas por Cliente (Crescente) e Data (Decrescente)</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Cliente</th><th>Produto</th><th>Valor</th><th>Data</th><th>Situação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vendasClienteData as $v): ?>
                    <tr>
                        <td><?= e($v['id']) ?></td>
                        <td><?= e($v['cliente']) ?></td>
                        <td><?= e($v['produto']) ?></td>
                        <td><?= e(formatarReais($v['valor'])) ?></td>
                        <td><?= e($v['data']) ?></td>
                        <td><?= $v['pago'] ? 'Pago' : 'Pendente' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- 9. Laboratório de chaves -->
    <section>
        <h2>9. Laboratório de Chaves (L1 a L4)</h2>

        <h3>L1 — array_keys(array_filter($vendas, pagas)) sem array_values</h3>
        <pre><?php var_dump(array_keys(array_filter($vendas, static fn(array $v): bool => $v['pago'] === true))); ?></pre>

        <h3>L2 — array_keys(vendasPagas($vendas)) com array_values</h3>
        <pre><?php var_dump(array_keys(vendasPagas($vendas))); ?></pre>

        <h3>L3 — Retorno de sort($copia)</h3>
        <pre><?php
            $copiaClientes = array_column($vendas, 'cliente');
            $r = sort($copiaClientes);
            var_dump($r);
        ?></pre>

        <h3>L4 — array_keys(array_unique(array_column($vendas, 'cliente')))</h3>
        <pre><?php var_dump(array_keys(array_unique(array_column($vendas, 'cliente')))); ?></pre>
    </section>

    <!-- 10. Testes -->
    <section>
        <h2>10. Testes (<?= e($testesPassaram) ?> de <?= e($totalTestes) ?> passaram)</h2>
        <table>
            <thead><tr><th>Teste</th><th>Resultado</th></tr></thead>
            <tbody>
                <?php foreach ($testes as $t): ?>
                    <tr>
                        <td><?= e($t['nome']) ?></td>
                        <td>
                            <span class="<?= $t['ok'] ? 'badge-ok' : 'badge-falhou' ?>">
                                <?= $t['ok'] ? 'OK' : 'FALHOU' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>