<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

// ============================================================================
// FUNÇÕES DE VALIDAÇÃO E APOIO (sem echo, sem global, sem ==)
// ============================================================================

function calcularDigitoCpf(string $base): int
{
    $tamanho = strlen($base);
    $peso = $tamanho + 1;
    $soma = 0;

    for ($i = 0; $i < $tamanho; $i++) {
        $soma += ((int) $base[$i]) * $peso;
        $peso--;
    }

    $resto = ($soma * 10) % 11;
    return $resto === 10 ? 0 : $resto;
}

function todosDigitosIguais(string $digitos): bool
{
    $tamanho = strlen($digitos);
    if ($tamanho === 0) {
        return false;
    }

    $primeiro = $digitos[0];
    for ($i = 1; $i < $tamanho; $i++) {
        if ($digitos[$i] !== $primeiro) {
            return false;
        }
    }

    return true;
}

function formatarCpf(string $digitos): string
{
    return substr($digitos, 0, 3) . '.'
         . substr($digitos, 3, 3) . '.'
         . substr($digitos, 6, 3) . '-'
         . substr($digitos, 9, 2);
}

function mascararCpf(string $digitos): string
{
    return '***.' . substr($digitos, 3, 3) . '.' . substr($digitos, 6, 3) . '-**';
}

function validarCpf(string $entrada): array
{
    $limpa = trim($entrada);

    // 1. Vazio
    if ($limpa === '') {
        return [
            'valido' => false,
            'motivo' => 'vazio',
            'cpf' => '',
            'mensagem' => 'Informe o CPF.',
        ];
    }

    // 2. Forma (11 dígitos puros ou máscara 000.000.000-00)
    $ehOnzeDigitos = preg_match('/^[0-9]{11}$/D', $limpa) === 1;
    $ehComMascara  = preg_match('/^[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{2}$/D', $limpa) === 1;

    if (!$ehOnzeDigitos && !$ehComMascara) {
        // Se tem só dígitos ASCII, o erro é de quantidade; senão, de formato
        if (preg_match('/^[0-9]+$/D', $limpa) === 1) {
            $qtd = strlen($limpa);
            return [
                'valido' => false,
                'motivo' => 'quantidade',
                'cpf' => '',
                'mensagem' => "O CPF deve ter 11 dígitos; foram informados {$qtd}.",
            ];
        }

        return [
            'valido' => false,
            'motivo' => 'formato',
            'cpf' => '',
            'mensagem' => 'Formato inválido: use 11 dígitos ou 000.000.000-00.',
        ];
    }

    // Extrai apenas os 11 dígitos
    $digitos = $ehComMascara ? str_replace(['.', '-'], '', $limpa) : $limpa;

    // 3. Dígitos repetidos
    if (todosDigitosIguais($digitos)) {
        return [
            'valido' => false,
            'motivo' => 'repetidos',
            'cpf' => '',
            'mensagem' => 'CPF inválido: todos os dígitos são iguais.',
        ];
    }

    // 4. Dígitos verificadores (1º e depois 2º)
    $dv1Calculado = calcularDigitoCpf(substr($digitos, 0, 9));
    $dv1Informado = (int) $digitos[9];

    if ($dv1Calculado !== $dv1Informado) {
        return [
            'valido' => false,
            'motivo' => 'digito',
            'cpf' => '',
            'mensagem' => 'CPF inválido: o 1º dígito verificador não confere.',
        ];
    }

    $dv2Calculado = calcularDigitoCpf(substr($digitos, 0, 10));
    $dv2Informado = (int) $digitos[10];

    if ($dv2Calculado !== $dv2Informado) {
        return [
            'valido' => false,
            'motivo' => 'digito',
            'cpf' => '',
            'mensagem' => 'CPF inválido: o 2º dígito verificador não confere.',
        ];
    }

    // 5. Válido
    return [
        'valido' => true,
        'motivo' => 'ok',
        'cpf' => $digitos,
        'mensagem' => 'CPF válido.',
    ];
}

// ============================================================================
// BATERIA DE TESTES — 24 CASOS DE validarCpf()
// ============================================================================

$casosDeTeste = [
    ['id' => 1,  'entrada' => '529.982.247-25',       'valido' => true,  'motivo' => 'ok',         'cpf' => '52998224725', 'msgContem' => null],
    ['id' => 2,  'entrada' => '52998224725',          'valido' => true,  'motivo' => 'ok',         'cpf' => '52998224725', 'msgContem' => null],
    ['id' => 3,  'entrada' => '  529.982.247-25  ',   'valido' => true,  'motivo' => 'ok',         'cpf' => '52998224725', 'msgContem' => null],
    ['id' => 4,  'entrada' => '111.444.777-35',       'valido' => true,  'motivo' => 'ok',         'cpf' => '11144477735', 'msgContem' => null],
    ['id' => 5,  'entrada' => '123.456.789-09',       'valido' => true,  'motivo' => 'ok',         'cpf' => '12345678909', 'msgContem' => null],
    ['id' => 6,  'entrada' => '529.982.247-24',       'valido' => false, 'motivo' => 'digito',     'cpf' => '',            'msgContem' => '2º dígito'],
    ['id' => 7,  'entrada' => '529.982.247-35',       'valido' => false, 'motivo' => 'digito',     'cpf' => '',            'msgContem' => '1º dígito'],
    ['id' => 8,  'entrada' => '111.111.111-11',       'valido' => false, 'motivo' => 'repetidos',  'cpf' => '',            'msgContem' => null],
    ['id' => 9,  'entrada' => '000.000.000-00',       'valido' => false, 'motivo' => 'repetidos',  'cpf' => '',            'msgContem' => null],
    ['id' => 10, 'entrada' => '5299822472',           'valido' => false, 'motivo' => 'quantidade', 'cpf' => '',            'msgContem' => '10'],
    ['id' => 11, 'entrada' => '529982247255',         'valido' => false, 'motivo' => 'quantidade', 'cpf' => '',            'msgContem' => '12'],
    ['id' => 12, 'entrada' => '529.982.247.25',       'valido' => false, 'motivo' => 'formato',    'cpf' => '',            'msgContem' => null],
    ['id' => 13, 'entrada' => '529 982 247 25',       'valido' => false, 'motivo' => 'formato',    'cpf' => '',            'msgContem' => null],
    ['id' => 14, 'entrada' => '52998224A25',          'valido' => false, 'motivo' => 'formato',    'cpf' => '',            'msgContem' => null],
    ['id' => 15, 'entrada' => '',                     'valido' => false, 'motivo' => 'vazio',      'cpf' => '',            'msgContem' => null],
    ['id' => 16, 'entrada' => '   ',                  'valido' => false, 'motivo' => 'vazio',      'cpf' => '',            'msgContem' => null],
    ['id' => 17, 'entrada' => "529.982.247-2\u{FF15}",'valido' => false, 'motivo' => 'formato',    'cpf' => '',            'msgContem' => null],
    ['id' => 18, 'entrada' => '529-982-247.25',       'valido' => false, 'motivo' => 'formato',    'cpf' => '',            'msgContem' => null],
    ['id' => 19, 'entrada' => "52998224725\n",        'valido' => true,  'motivo' => 'ok',         'cpf' => '52998224725', 'msgContem' => null],
    ['id' => 20, 'entrada' => '529.982.24725',        'valido' => false, 'motivo' => 'formato',    'cpf' => '',            'msgContem' => null],
    ['id' => 21, 'entrada' => '00000000000',          'valido' => false, 'motivo' => 'repetidos',  'cpf' => '',            'msgContem' => null],
    ['id' => 22, 'entrada' => '123.456.789-10',       'valido' => false, 'motivo' => 'digito',     'cpf' => '',            'msgContem' => '1º dígito'],
    ['id' => 23, 'entrada' => '987.654.321-00',       'valido' => true,  'motivo' => 'ok',         'cpf' => '98765432100', 'msgContem' => null],
    ['id' => 24, 'entrada' => 'abc',                  'valido' => false, 'motivo' => 'formato',    'cpf' => '',            'msgContem' => null],
];

$resultadosTestes = [];
$passaram = 0;

foreach ($casosDeTeste as $caso) {
    $obtido = validarCpf($caso['entrada']);

    $validoBate = ($obtido['valido'] === $caso['valido']);
    $motivoBate = ($obtido['motivo'] === $caso['motivo']);
    $cpfBate    = ($obtido['cpf'] === $caso['cpf']);
    $msgBate    = $caso['msgContem'] === null || str_contains($obtido['mensagem'], $caso['msgContem']);

    $passou = $validoBate && $motivoBate && $cpfBate && $msgBate;
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
// TESTES DAS FUNÇÕES DE APOIO
// ============================================================================

$testesApoio = [
    ['chamada' => "calcularDigitoCpf('529982247')",       'esperado' => 2,                'obtido' => calcularDigitoCpf('529982247')],
    ['chamada' => "calcularDigitoCpf('5299822472')",      'esperado' => 5,                'obtido' => calcularDigitoCpf('5299822472')],
    ['chamada' => "calcularDigitoCpf('123456789')",       'esperado' => 0,                'obtido' => calcularDigitoCpf('123456789')],
    ['chamada' => "calcularDigitoCpf('1234567890')",      'esperado' => 9,                'obtido' => calcularDigitoCpf('1234567890')],
    ['chamada' => "calcularDigitoCpf('111444777')",       'esperado' => 3,                'obtido' => calcularDigitoCpf('111444777')],
    ['chamada' => "calcularDigitoCpf('1114447773')",      'esperado' => 5,                'obtido' => calcularDigitoCpf('1114447773')],
    ['chamada' => "calcularDigitoCpf('987654321')",       'esperado' => 0,                'obtido' => calcularDigitoCpf('987654321')],
    ['chamada' => "calcularDigitoCpf('9876543210')",      'esperado' => 0,                'obtido' => calcularDigitoCpf('9876543210')],
    ['chamada' => "calcularDigitoCpf('000000000')",       'esperado' => 0,                'obtido' => calcularDigitoCpf('000000000')],
    ['chamada' => "calcularDigitoCpf('111111111')",       'esperado' => 1,                'obtido' => calcularDigitoCpf('111111111')],
    ['chamada' => "calcularDigitoCpf('1111111111')",      'esperado' => 1,                'obtido' => calcularDigitoCpf('1111111111')],
    ['chamada' => "todosDigitosIguais('11111111111')",    'esperado' => true,             'obtido' => todosDigitosIguais('11111111111')],
    ['chamada' => "todosDigitosIguais('11111111112')",    'esperado' => false,            'obtido' => todosDigitosIguais('11111111112')],
    ['chamada' => "todosDigitosIguais('00000000000')",    'esperado' => true,             'obtido' => todosDigitosIguais('00000000000')],
    ['chamada' => "formatarCpf('52998224725')",           'esperado' => '529.982.247-25', 'obtido' => formatarCpf('52998224725')],
    ['chamada' => "mascararCpf('52998224725')",           'esperado' => '***.982.247-**', 'obtido' => mascararCpf('52998224725')],
];

$apoioPassaram = 0;
foreach ($testesApoio as $idx => $t) {
    $ok = ($t['obtido'] === $t['esperado']);
    $testesApoio[$idx]['ok'] = $ok;
    if ($ok) {
        $apoioPassaram++;
    }
}
$totalApoio = count($testesApoio);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercício 5 — Validador de CPF</title>
</head>
<link rel="stylesheet" href="style.css">
<body>
    <div class="container">
        <h1>Validador de CPF — Bateria de Testes</h1>

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
                    <th>Mensagem</th>
                    <th>CPF mascarado</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultadosTestes as $item): ?>
                    <?php
                        $c = $item['caso'];
                        $o = $item['obtido'];
                        $cpfExibido = $o['cpf'] !== '' ? mascararCpf($o['cpf']) : '—';
                    ?>
                    <tr class="<?= $item['passou'] ? 'ok-row' : 'fail-row' ?>">
                        <td><?= $c['id'] ?></td>
                        <td><code>[<?= htmlspecialchars($c['entrada'], ENT_QUOTES, 'UTF-8') ?>]</code></td>
                        <td><?= $c['valido'] ? 'sim' : 'não' ?> · <?= htmlspecialchars($c['motivo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $o['valido'] ? 'sim' : 'não' ?> · <?= htmlspecialchars($o['motivo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($o['mensagem'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><code><?= htmlspecialchars($cpfExibido, ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td>
                            <span class="badge <?= $item['passou'] ? 'badge-ok' : 'badge-fail' ?>">
                                <?= $item['passou'] ? 'OK' : 'FALHOU' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Funções de Apoio (<?= $apoioPassaram ?> de <?= $totalApoio ?> passaram)</h2>
        <table>
            <thead>
                <tr>
                    <th>Chamada</th>
                    <th>Esperado</th>
                    <th>Obtido</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($testesApoio as $t): ?>
                    <?php
                        $esp = is_bool($t['esperado']) ? ($t['esperado'] ? 'sim' : 'não') : (string) $t['esperado'];
                        $obt = is_bool($t['obtido'])   ? ($t['obtido']   ? 'sim' : 'não') : (string) $t['obtido'];
                    ?>
                    <tr class="<?= $t['ok'] ? 'ok-row' : 'fail-row' ?>">
                        <td><code><?= htmlspecialchars($t['chamada'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><code><?= htmlspecialchars($esp, ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><code><?= htmlspecialchars($obt, ENT_QUOTES, 'UTF-8') ?></code></td>
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