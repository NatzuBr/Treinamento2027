<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

// Constantes das regras de negócio
const PESO_MAXIMO = 30.0;
const ADICIONAL_EXPRESSO = 1.40;
const LIMITE_FRETE_GRATIS_PADRAO = 300.0;
const LIMITE_FRETE_GRATIS_NORTE = 500.0;
const TOLERANCIA_FLOAT = 0.005;

// ============================================================================
// FUNÇÕES DE CÁLCULO
// ============================================================================

function normalizarRegiao(string $regiao): string
{
    return mb_strtolower(trim($regiao), 'UTF-8');
}

function valorBasePorPeso(float $peso): ?float
{
    return match (true) {
        $peso <= 0.0 || $peso > PESO_MAXIMO => null,
        $peso <= 1.0 => 12.0,
        $peso <= 5.0 => 20.0,
        $peso <= 10.0 => 32.0,
        default => 55.0,
    };
}

function multiplicadorDaRegiao(string $regiao): ?float
{
    return match ($regiao) {
        'sudeste' => 1.00,
        'sul' => 1.10,
        'centro-oeste' => 1.25,
        'nordeste' => 1.40,
        'norte' => 1.60,
        default => null,
    };
}

function prazoDaRegiao(string $regiao): int
{
    return match ($regiao) {
        'sudeste' => 2,
        'sul' => 3,
        'centro-oeste' => 4,
        'nordeste' => 6,
        'norte' => 9,
        default => 0,
    };
}

function limiteFreteGratis(string $regiao): float
{
    return match ($regiao) {
        'norte' => LIMITE_FRETE_GRATIS_NORTE,
        default => LIMITE_FRETE_GRATIS_PADRAO,
    };
}

function descreverPrazo(int $dias): string
{
    return $dias === 1 ? '1 dia útil' : "{$dias} dias úteis";
}

function calcularFrete(
    float $peso,
    string $regiao,
    float $valorPedido,
    bool $expresso = false
): array {
    // 1. Validação do peso (retorno antecipado)
    $valorBase = valorBasePorPeso($peso);
    if ($valorBase === null) {
        return [
            'ok' => false,
            'valor' => 0.0,
            'prazo' => null,
            'gratis' => false,
            'mensagem' => 'Peso inválido: informe mais de 0 e até 30 kg.',
        ];
    }

    // 2. Validação da região (retorno antecipado)
    $regiaoNorm = normalizarRegiao($regiao);
    $multiplicador = multiplicadorDaRegiao($regiaoNorm);
    if ($multiplicador === null) {
        return [
            'ok' => false,
            'valor' => 0.0,
            'prazo' => null,
            'gratis' => false,
            'mensagem' => "Região desconhecida: \"{$regiao}\".",
        ];
    }

    // 3. Validação do valor do pedido (retorno antecipado)
    if ($valorPedido < 0.0) {
        return [
            'ok' => false,
            'valor' => 0.0,
            'prazo' => null,
            'gratis' => false,
            'mensagem' => 'O valor do pedido não pode ser negativo.',
        ];
    }

    // 4. Cálculo base de valor e prazo
    $prazo = prazoDaRegiao($regiaoNorm);
    $valor = $valorBase * $multiplicador;

    // 5. Regra de frete grátis (só vale se NÃO for expresso)
    if (!$expresso && $valorPedido >= limiteFreteGratis($regiaoNorm)) {
        $prazoTexto = descreverPrazo($prazo);
        return [
            'ok' => true,
            'valor' => 0.0,
            'prazo' => $prazo,
            'gratis' => true,
            'mensagem' => "Frete grátis! Entrega em até {$prazoTexto}.",
        ];
    }

    // 6. Regra de entrega expressa
    if ($expresso) {
        $valor *= ADICIONAL_EXPRESSO;
        $prazo = max(1, (int) ceil($prazo / 2));
    }

    // 7. Arredondamento único no valor final
    $valorFinal = round($valor, 2);
    $valorFormatado = number_format($valorFinal, 2, ',', '.');
    $prazoTexto = descreverPrazo($prazo);

    $mensagem = "Frete de R$ {$valorFormatado} para a região {$regiaoNorm}, em até {$prazoTexto}.";
    if ($expresso) {
        $mensagem .= ' (expresso)';
    }

    return [
        'ok' => true,
        'valor' => $valorFinal,
        'prazo' => $prazo,
        'gratis' => false,
        'mensagem' => $mensagem,
    ];
}

// ============================================================================
// BATERIA DE TESTES (23 CASOS)
// ============================================================================

$casosDeTeste = [
    ['id' => 1,  'peso' => 0.5,   'regiao' => 'sudeste',      'pedido' => 100.0,  'expresso' => false, 'ok' => true,  'valor' => 12.00,  'prazo' => 2,    'gratis' => false, 'erroCampo' => null],
    ['id' => 2,  'peso' => 1.0,   'regiao' => 'sudeste',      'pedido' => 100.0,  'expresso' => false, 'ok' => true,  'valor' => 12.00,  'prazo' => 2,    'gratis' => false, 'erroCampo' => null],
    ['id' => 3,  'peso' => 1.01,  'regiao' => 'sudeste',      'pedido' => 100.0,  'expresso' => false, 'ok' => true,  'valor' => 20.00,  'prazo' => 2,    'gratis' => false, 'erroCampo' => null],
    ['id' => 4,  'peso' => 5.0,   'regiao' => 'sul',          'pedido' => 100.0,  'expresso' => false, 'ok' => true,  'valor' => 22.00,  'prazo' => 3,    'gratis' => false, 'erroCampo' => null],
    ['id' => 5,  'peso' => 5.01,  'regiao' => 'sul',          'pedido' => 100.0,  'expresso' => false, 'ok' => true,  'valor' => 35.20,  'prazo' => 3,    'gratis' => false, 'erroCampo' => null],
    ['id' => 6,  'peso' => 10.0,  'regiao' => 'nordeste',     'pedido' => 100.0,  'expresso' => false, 'ok' => true,  'valor' => 44.80,  'prazo' => 6,    'gratis' => false, 'erroCampo' => null],
    ['id' => 7,  'peso' => 10.01, 'regiao' => 'nordeste',     'pedido' => 100.0,  'expresso' => false, 'ok' => true,  'valor' => 77.00,  'prazo' => 6,    'gratis' => false, 'erroCampo' => null],
    ['id' => 8,  'peso' => 30.0,  'regiao' => 'norte',        'pedido' => 100.0,  'expresso' => false, 'ok' => true,  'valor' => 88.00,  'prazo' => 9,    'gratis' => false, 'erroCampo' => null],
    ['id' => 9,  'peso' => 30.01, 'regiao' => 'sudeste',      'pedido' => 100.0,  'expresso' => false, 'ok' => false, 'valor' => 0.00,   'prazo' => null, 'gratis' => false, 'erroCampo' => 'peso'],
    ['id' => 10, 'peso' => 0,     'regiao' => 'sul',          'pedido' => 100.0,  'expresso' => false, 'ok' => false, 'valor' => 0.00,   'prazo' => null, 'gratis' => false, 'erroCampo' => 'peso'],
    ['id' => 11, 'peso' => -2,    'regiao' => 'sul',          'pedido' => 100.0,  'expresso' => false, 'ok' => false, 'valor' => 0.00,   'prazo' => null, 'gratis' => false, 'erroCampo' => 'peso'],
    ['id' => 12, 'peso' => 2,     'regiao' => 'lua',          'pedido' => 100.0,  'expresso' => false, 'ok' => false, 'valor' => 0.00,   'prazo' => null, 'gratis' => false, 'erroCampo' => 'região'],
    ['id' => 13, 'peso' => 2,     'regiao' => 'sudeste',      'pedido' => 300.0,  'expresso' => false, 'ok' => true,  'valor' => 0.00,   'prazo' => 2,    'gratis' => true,  'erroCampo' => null],
    ['id' => 14, 'peso' => 2,     'regiao' => 'sudeste',      'pedido' => 299.99, 'expresso' => false, 'ok' => true,  'valor' => 20.00,  'prazo' => 2,    'gratis' => false, 'erroCampo' => null],
    ['id' => 15, 'peso' => 2,     'regiao' => 'norte',        'pedido' => 300.0,  'expresso' => false, 'ok' => true,  'valor' => 32.00,  'prazo' => 9,    'gratis' => false, 'erroCampo' => null],
    ['id' => 16, 'peso' => 2,     'regiao' => 'norte',        'pedido' => 500.0,  'expresso' => false, 'ok' => true,  'valor' => 0.00,   'prazo' => 9,    'gratis' => true,  'erroCampo' => null],
    ['id' => 17, 'peso' => 2,     'regiao' => 'sudeste',      'pedido' => 400.0,  'expresso' => true,  'ok' => true,  'valor' => 28.00,  'prazo' => 1,    'gratis' => false, 'erroCampo' => null],
    ['id' => 18, 'peso' => 7,     'regiao' => 'centro-oeste', 'pedido' => 50.0,   'expresso' => true,  'ok' => true,  'valor' => 56.00,  'prazo' => 2,    'gratis' => false, 'erroCampo' => null],
    ['id' => 19, 'peso' => 15,    'regiao' => 'nordeste',     'pedido' => 80.0,   'expresso' => true,  'ok' => true,  'valor' => 107.80, 'prazo' => 3,    'gratis' => false, 'erroCampo' => null],
    ['id' => 20, 'peso' => 4,     'regiao' => 'sul',          'pedido' => -10.0,  'expresso' => false, 'ok' => false, 'valor' => 0.00,   'prazo' => null, 'gratis' => false, 'erroCampo' => 'valor do pedido'],
    ['id' => 21, 'peso' => 2,     'regiao' => ' Sul ',        'pedido' => 100.0,  'expresso' => false, 'ok' => true,  'valor' => 22.00,  'prazo' => 3,    'gratis' => false, 'erroCampo' => null],
    ['id' => 22, 'peso' => 25,    'regiao' => 'norte',        'pedido' => 600.0,  'expresso' => false, 'ok' => true,  'valor' => 0.00,   'prazo' => 9,    'gratis' => true,  'erroCampo' => null],
    ['id' => 23, 'peso' => 0.001, 'regiao' => 'sul',          'pedido' => 0.0,    'expresso' => false, 'ok' => true,  'valor' => 13.20,  'prazo' => 3,    'gratis' => false, 'erroCampo' => null],
];

$resultadosTestes = [];
$passaram = 0;

foreach ($casosDeTeste as $caso) {
    $obtido = calcularFrete($caso['peso'], $caso['regiao'], $caso['pedido'], $caso['expresso']);

    $okBate     = ($obtido['ok'] === $caso['ok']);
    $valorBate  = (abs($obtido['valor'] - $caso['valor']) < TOLERANCIA_FLOAT);
    $prazoBate  = ($obtido['prazo'] === $caso['prazo']);
    $gratisBate = ($obtido['gratis'] === $caso['gratis']);

    $mensagemBate = true;
    if ($caso['erroCampo'] !== null) {
        $msgLower = mb_strtolower($obtido['mensagem'], 'UTF-8');
        $mensagemBate = str_contains($msgLower, $caso['erroCampo']);
    }

    $passou = $okBate && $valorBate && $prazoBate && $gratisBate && $mensagemBate;
    if ($passou) {
        $passaram++;
    }

    $resultadosTestes[] = [
        'caso' => $caso,
        'obtido' => $obtido,
        'passou' => $passou,
    ];
}

$totalTestes = count($casosDeTeste);

// ============================================================================
// SIMULADOR DE PEDIDOS (5 EXEMPLOS)
// ============================================================================

$pedidosSimulados = [
    ['descricao' => 'Livro técnico',        'peso' => 0.8,  'regiao' => 'Sudeste',      'pedido' => 89.90,  'expresso' => false],
    ['descricao' => 'Monitor 27"',          'peso' => 6.5,  'regiao' => 'Centro-Oeste', 'pedido' => 1250.0, 'expresso' => false],
    ['descricao' => 'Teclado mecânico',     'peso' => 1.4,  'regiao' => 'Nordeste',     'pedido' => 350.0,  'expresso' => true],
    ['descricao' => 'Encomenda espacial',   'peso' => 2.0,  'regiao' => 'lua',          'pedido' => 200.0,  'expresso' => false],
    ['descricao' => 'Geladeira industrial', 'peso' => 45.0, 'regiao' => 'Norte',        'pedido' => 3000.0, 'expresso' => false],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercício 3 — Cálculo de Frete e Testes</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Bateria de Testes — Cálculo de Frete</h1>

        <div class="resumo <?= $passaram === $totalTestes ? 'sucesso' : 'falha' ?>">
            <?= $passaram ?> de <?= $totalTestes ?> testes passaram
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Entrada</th>
                    <th>Esperado</th>
                    <th>Obtido</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultadosTestes as $item): ?>
                    <?php
                        $c = $item['caso'];
                        $o = $item['obtido'];
                        $expLabel = $c['expresso'] ? 'expresso' : 'normal';
                        $espPrazo = $c['prazo'] === null ? 'nulo' : $c['prazo'];
                        $obtPrazo = $o['prazo'] === null ? 'nulo' : $o['prazo'];
                    ?>
                    <tr class="<?= $item['passou'] ? 'ok-row' : 'fail-row' ?>">
                        <td><?= $c['id'] ?></td>
                        <td>
                            <?= $c['peso'] ?> kg ·
                            <code><?= htmlspecialchars($c['regiao'], ENT_QUOTES, 'UTF-8') ?></code> ·
                            R$ <?= number_format($c['pedido'], 2, ',', '.') ?> ·
                            <?= $expLabel ?>
                        </td>
                        <td>
                            ok=<?= $c['ok'] ? 'sim' : 'não' ?> ·
                            R$ <?= number_format($c['valor'], 2, ',', '.') ?> ·
                            prazo=<?= $espPrazo ?> ·
                            grátis=<?= $c['gratis'] ? 'sim' : 'não' ?>
                        </td>
                        <td>
                            ok=<?= $o['ok'] ? 'sim' : 'não' ?> ·
                            R$ <?= number_format($o['valor'], 2, ',', '.') ?> ·
                            prazo=<?= $obtPrazo ?> ·
                            grátis=<?= $o['gratis'] ? 'sim' : 'não' ?>
                        </td>
                        <td>
                            <span class="badge <?= $item['passou'] ? 'badge-ok' : 'badge-fail' ?>">
                                <?= $item['passou'] ? 'OK' : 'FALHOU' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Simulador de Pedidos</h2>
        <table>
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Dados</th>
                    <th>Mensagem retornada</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidosSimulados as $p): ?>
                    <?php $res = calcularFrete($p['peso'], $p['regiao'], $p['pedido'], $p['expresso']); ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['descricao'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td>
                            <?= $p['peso'] ?> kg ·
                            <?= htmlspecialchars($p['regiao'], ENT_QUOTES, 'UTF-8') ?> ·
                            R$ <?= number_format($p['pedido'], 2, ',', '.') ?> ·
                            <?= $p['expresso'] ? 'expresso' : 'normal' ?>
                        </td>
                        <td><?= htmlspecialchars($res['mensagem'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>