<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

function e(string|int|float|null $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// =========================================================================
// DADOS
// =========================================================================

$sistema = [
    '00123' => 10,
    '123'   => 4,
    '45'    => 7,
    '045'   => 0,
    '9001'  => 12,
    '7'     => 3,
    'A-100' => 5,
    'B-200' => null, // saldo não informado no sistema
];

$contagem = [
    '00123' => 10,
    '123'   => 6,
    '45'    => 7,
    '9001'  => 11,
    '8'     => 2,
    'A-100' => 5,
    'C-300' => 1,
    'B-200' => 0,
];

// =========================================================================
// FUNÇÕES DO EXERCÍCIO
// =========================================================================

function normalizarSku(int|string $sku): string
{
    return (string) $sku;
}

function listarSkus(array $sistema, array $contagem): array
{
    $skus = [];

    foreach ($sistema as $chave => $_) {
        $skus[] = normalizarSku($chave);
    }

    foreach ($contagem as $chave => $_) {
        $sku = normalizarSku($chave);
        if (!array_key_exists($sku, $sistema)) {
            $skus[] = $sku;
        }
    }

    return $skus;
}

function classificarSku(string $sku, array $sistema, array $contagem): array
{
    $noSistema  = array_key_exists($sku, $sistema);
    $naContagem = array_key_exists($sku, $contagem);

    $valorSistema  = $noSistema ? $sistema[$sku] : null;
    $valorContagem = $naContagem ? $contagem[$sku] : null;

    if ($noSistema && $valorSistema === null) {
        $categoria = 'saldo_ausente';
        $diferenca = null;
    } elseif ($noSistema && $naContagem) {
        $diferenca = $valorContagem - $valorSistema;
        $categoria = $diferenca === 0 ? 'igual' : 'divergencia';
    } elseif ($noSistema) {
        $categoria = 'somente_sistema';
        $diferenca = null;
    } else {
        $categoria = 'somente_contagem';
        $diferenca = null;
    }

    return [
        'sku'       => $sku,
        'categoria' => $categoria,
        'sistema'   => $valorSistema,
        'contagem'  => $valorContagem,
        'diferenca' => $diferenca,
    ];
}

function conciliar(array $sistema, array $contagem): array
{
    $skus = listarSkus($sistema, $contagem);
    usort($skus, static fn(string $a, string $b): int => strcmp($a, $b));

    $itens = [];
    foreach ($skus as $sku) {
        $itens[] = classificarSku($sku, $sistema, $contagem);
    }

    return $itens;
}

function resumirConciliacao(array $itens): array
{
    $resumo = [
        'igual'            => 0,
        'divergencia'      => 0,
        'somente_sistema'  => 0,
        'somente_contagem' => 0,
        'saldo_ausente'    => 0,
        'unidadesAMais'    => 0,
        'unidadesAMenos'   => 0,
    ];

    foreach ($itens as $item) {
        $cat = $item['categoria'];
        if (array_key_exists($cat, $resumo)) {
            $resumo[$cat]++;
        }

        if ($item['diferenca'] !== null) {
            if ($item['diferenca'] > 0) {
                $resumo['unidadesAMais'] += $item['diferenca'];
            } elseif ($item['diferenca'] < 0) {
                $resumo['unidadesAMenos'] += abs($item['diferenca']);
            }
        }
    }

    return $resumo;
}

function formatarDiferenca(?int $dif): string
{
    if ($dif === null) {
        return '—';
    }
    if ($dif > 0) {
        return '+' . $dif;
    }

    return (string) $dif;
}

// Auxiliar para o experimento L7
function receberSkuEstrito(string $sku): string
{
    return $sku;
}

// =========================================================================
// PROCESSAMENTO PRINCIPAL
// =========================================================================

$itens  = conciliar($sistema, $contagem);
$resumo = resumirConciliacao($itens);

// =========================================================================
// BATERIA DE TESTES AUTOMATIZADOS
// =========================================================================

$tIguais  = conciliar(['10' => 5, 'A' => 0], ['10' => 5, 'A' => 0]);
$rIguais  = resumirConciliacao($tIguais);

$tContVaz = conciliar(['1' => 3, '2' => 0, '3' => 8], []);
$rContVaz = resumirConciliacao($tContVaz);

$tSistVaz = conciliar([], ['X' => 2, '05' => 1]);
$rSistVaz = resumirConciliacao($tSistVaz);

$tVazios  = conciliar([], []);
$rVazios  = resumirConciliacao($tVazios);

$tAusente = conciliar(['X-1' => null], []);
$tChave0  = conciliar(['0' => 5], ['0' => 5]);
$tZeros   = conciliar(['9' => 0], ['9' => 0]);
$tMais    = conciliar(['Z' => 1], ['Z' => 5]);
$rMais    = resumirConciliacao($tMais);

$testes = [
    ['nome' => 'Principal: ordem de strcmp dos SKUs', 'ok' =>
        array_column($itens, 'sku') === ['00123', '045', '123', '45', '7', '8', '9001', 'A-100', 'B-200', 'C-300']
    ],
    ['nome' => 'Principal: resumo completo (3, 2, 2, 2, 1, +2, -1)', 'ok' =>
        $resumo === [
            'igual'            => 3,
            'divergencia'      => 2,
            'somente_sistema'  => 2,
            'somente_contagem' => 2,
            'saldo_ausente'    => 1,
            'unidadesAMais'    => 2,
            'unidadesAMenos'   => 1,
        ]
    ],
    ['nome' => 'Principal: B-200 é saldo_ausente e 045 (saldo 0) é somente_sistema', 'ok' =>
        classificarSku('B-200', $sistema, $contagem)['categoria'] === 'saldo_ausente'
        && classificarSku('B-200', $sistema, $contagem)['diferenca'] === null
        && classificarSku('045', $sistema, $contagem)['categoria'] === 'somente_sistema'
    ],
    ['nome' => 'Iguais: todos igual, unidadesAMais 0 e unidadesAMenos 0', 'ok' =>
        $rIguais['igual'] === 2 && $rIguais['unidadesAMais'] === 0 && $rIguais['unidadesAMenos'] === 0
    ],
    ['nome' => 'Contagem vazia: 3 somente_sistema, demais 0', 'ok' =>
        $rContVaz['somente_sistema'] === 3 && $rContVaz['igual'] === 0 && $rContVaz['divergencia'] === 0
    ],
    ['nome' => 'Sistema vazio: 2 somente_contagem', 'ok' =>
        $rSistVaz['somente_contagem'] === 2 && count($tSistVaz) === 2
    ],
    ['nome' => 'Ambos vazios: lista vazia e resumo zerado', 'ok' =>
        $tVazios === []
        && $rVazios === [
            'igual' => 0, 'divergencia' => 0, 'somente_sistema' => 0,
            'somente_contagem' => 0, 'saldo_ausente' => 0,
            'unidadesAMais' => 0, 'unidadesAMenos' => 0,
        ]
    ],
    ['nome' => 'Saldo ausente sozinho: 1 registro saldo_ausente sem diferença', 'ok' =>
        count($tAusente) === 1 && $tAusente[0]['categoria'] === 'saldo_ausente' && $tAusente[0]['diferenca'] === null
    ],
    ['nome' => "Chave '0': tratada como válida (igual)", 'ok' =>
        count($tChave0) === 1 && $tChave0[0]['sku'] === '0' && $tChave0[0]['categoria'] === 'igual'
    ],
    ['nome' => "Zeros: sistema ['9'=>0] e contagem ['9'=>0] -> igual, diferença 0", 'ok' =>
        count($tZeros) === 1 && $tZeros[0]['categoria'] === 'igual' && $tZeros[0]['diferenca'] === 0
    ],
    ['nome' => 'Mais na contagem: divergencia, diferença +4, unidadesAMais 4', 'ok' =>
        $tMais[0]['categoria'] === 'divergencia' && $tMais[0]['diferenca'] === 4 && $rMais['unidadesAMais'] === 4
    ],
];

$totalTestes    = count($testes);
$testesPassaram = count(array_filter($testes, static fn(array $t): bool => $t['ok'] === true));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercício 8 — Conciliação de Estoque</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <header>
        <p><a href="../index.php">&larr; Voltar para o painel</a></p>
        <h1>Exercício 8 — Conciliação de Estoque</h1>
        <p>Armadilha das chaves numéricas, array_key_exists vs isset e ordenação por strcmp</p>
    </header>

    <!-- 1. Tabela de conciliação -->
    <section>
        <h2>1. Tabela de Conciliação (ordem de strcmp)</h2>
        <table>
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Sistema</th>
                    <th>Contagem</th>
                    <th>Diferença</th>
                    <th>Categoria</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $item): ?>
                    <tr>
                        <td><code><?= e($item['sku']) ?></code></td>
                        <td>
                            <?php if ($item['categoria'] === 'saldo_ausente'): ?>
                                <em>(sem saldo)</em>
                            <?php elseif ($item['sistema'] === null): ?>
                                —
                            <?php else: ?>
                                <?= e($item['sistema']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= $item['contagem'] === null ? '—' : e($item['contagem']) ?></td>
                        <td><?= e(formatarDiferenca($item['diferenca'])) ?></td>
                        <td>
                            <span class="cat cat-<?= e($item['categoria']) ?>">
                                <?= e($item['categoria']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- 2. Resumo -->
    <section>
        <h2>2. Resumo da Conciliação</h2>
        <table>
            <tbody>
                <tr><th>Igual</th><td><?= e($resumo['igual']) ?></td></tr>
                <tr><th>Divergência</th><td><?= e($resumo['divergencia']) ?></td></tr>
                <tr><th>Somente no sistema</th><td><?= e($resumo['somente_sistema']) ?></td></tr>
                <tr><th>Somente na contagem</th><td><?= e($resumo['somente_contagem']) ?></td></tr>
                <tr><th>Saldo ausente</th><td><?= e($resumo['saldo_ausente']) ?></td></tr>
                <tr><th>Unidades a mais (+)</th><td><?= e($resumo['unidadesAMais']) ?></td></tr>
                <tr><th>Unidades a menos (-)</th><td><?= e($resumo['unidadesAMenos']) ?></td></tr>
            </tbody>
        </table>
    </section>

    <!-- 3. Laboratório de chaves -->
    <section>
        <h2>3. Laboratório de Chaves (L1 a L7)</h2>

        <h3>L1 — Tipos das chaves de $sistema</h3>
        <pre><?php
            var_dump(array_keys($sistema));
            foreach ($sistema as $k => $_) {
                echo var_export($k, true) . ' => ' . gettype($k) . "\n";
            }
        ?></pre>

        <h3>L2 — count(array_merge($sistema, $contagem)) vs count($sistema + $contagem)</h3>
        <pre><?php
            var_dump(count(array_merge($sistema, $contagem)));
            var_dump(count($sistema + $contagem));
        ?></pre>

        <h3>L3 — $sistema + $contagem para '123', '9001' e 'B-200'</h3>
        <pre><?php
            $uniao = $sistema + $contagem;
            var_dump([
                '123'   => $uniao['123'],
                '9001'  => $uniao['9001'],
                'B-200' => $uniao['B-200'],
            ]);
        ?></pre>

        <h3>L4 — in_array estrito/frouxo vs array_key_exists</h3>
        <pre><?php
            var_dump(in_array('123', array_keys($sistema), true));
            var_dump(in_array('123', array_keys($sistema)));
            var_dump(in_array('0123', array_keys($sistema)));
            var_dump(array_key_exists('0123', $sistema));
        ?></pre>

        <h3>L5 — array_key_exists vs isset para 'B-200' (valor null)</h3>
        <pre><?php
            var_dump(array_key_exists('B-200', $sistema));
            var_dump(isset($sistema['B-200']));
        ?></pre>

        <h3>L6 — ksort($copia) vs ksort($copia, SORT_STRING)</h3>
        <pre><?php
            $c1 = $sistema;
            ksort($c1);
            var_dump(array_keys($c1));

            $c2 = $sistema;
            ksort($c2, SORT_STRING);
            var_dump(array_keys($c2));
        ?></pre>

        <h3>L7 — Passar chave de foreach sem normalizarSku em modo estrito</h3>
        <pre><?php
            foreach ($sistema as $k => $_) {
                try {
                    receberSkuEstrito($k);
                } catch (TypeError $erro) {
                    var_dump([
                        'sku_que_falhou' => $k,
                        'tipo'           => gettype($k),
                        'erro'           => $erro->getMessage(),
                    ]);
                    break;
                }
            }
        ?></pre>
    </section>

    <!-- 4. Testes -->
    <section>
        <h2>4. Testes (<?= e($testesPassaram) ?> de <?= e($totalTestes) ?> passaram)</h2>
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