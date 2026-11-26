<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

function e(string|int|float|null $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// =========================================================================
// ENTRADAS DE TESTE
// =========================================================================

$textoA = <<<TXT
# Contatos para importar (formato: nome; e-mail; telefone; cidade)
maria da silva; Maria.Silva@Example.com; (11) 98765-4321; são paulo

JOSÉ   DOS SANTOS; jose.santos@example.com; 11 3456-7890; Belo Horizonte
ana paula; ana.paula@example; (21) 99999-0000; Rio de Janeiro
João Pedro; joao.pedro@example.com; 12345; Curitiba
luiza; luiza@example.com; (31) 91234-5678;
maria da silva; MARIA.SILVA@EXAMPLE.COM; (11) 98765-4321; São Paulo
# linha de comentário no meio
carlos eduardo; carlos@example.com; 11 98888-7777
b; bia@example.com; (41) 3333-4444; Londrina
   
Édson Filho; edson.filho@example.com; (62) 3232-1010; Goiânia
fernanda lima; fernanda.lima@example.com; (85) 98765-0000; Fortaleza; extra
TXT;

// Normaliza para \n primeiro para garantir a geração exata de \r\n e \r
$textoUnix    = preg_replace('/\R/', "\n", $textoA) ?? $textoA;
$textoWindows = str_replace("\n", "\r\n", $textoUnix);
$textoMac     = str_replace("\n", "\r", $textoUnix);

// =========================================================================
// FUNÇÕES DO EXERCÍCIO
// =========================================================================

function semAcentos(string $texto): string
{
    $minusculo = mb_strtolower($texto, 'UTF-8');

    $mapa = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
    ];

    return strtr($minusculo, $mapa);
}

function tituloComParticulas(string $texto): string
{
    $limpo = trim($texto);
    if ($limpo === '') {
        return '';
    }

    $limpo = preg_replace('/\s+/u', ' ', $limpo) ?? $limpo;
    $palavras = explode(' ', mb_strtolower($limpo, 'UTF-8'));
    $particulas = ['da', 'de', 'do', 'das', 'dos', 'e'];

    $resultado = [];
    foreach ($palavras as $indice => $palavra) {
        if ($indice > 0 && in_array($palavra, $particulas, true)) {
            $resultado[] = $palavra;
        } else {
            $primeira = mb_strtoupper(mb_substr($palavra, 0, 1, 'UTF-8'), 'UTF-8');
            $resto = mb_substr($palavra, 1, null, 'UTF-8');
            $resultado[] = $primeira . $resto;
        }
    }

    return implode(' ', $resultado);
}

function normalizarEmail(string $email): string
{
    return mb_strtolower(trim($email), 'UTF-8');
}

function normalizarTelefone(string $telefone): ?string
{
    $limpo = trim($telefone);
    if ($limpo === '' || preg_match('/^[0-9\s().-]+$/', $limpo) !== 1) {
        return null;
    }

    $digitos = preg_replace('/\D/', '', $limpo) ?? '';
    $tamanho = strlen($digitos);

    if ($tamanho === 10) {
        return sprintf('(%s) %s-%s', substr($digitos, 0, 2), substr($digitos, 2, 4), substr($digitos, 6, 4));
    }

    if ($tamanho === 11) {
        return sprintf('(%s) %s-%s', substr($digitos, 0, 2), substr($digitos, 2, 5), substr($digitos, 7, 4));
    }

    return null;
}

function separarLinhas(string $texto): array
{
    if ($texto === '') {
        return ['linhas' => [], 'ignoradas' => 0];
    }

    $brutas = preg_split('/\R/', $texto);
    if ($brutas === false) {
        return ['linhas' => [], 'ignoradas' => 0];
    }

    $linhas = [];
    $ignoradas = 0;

    foreach ($brutas as $indice => $linhaBruta) {
        $numero = $indice + 1;
        $limpa = trim($linhaBruta);

        if ($limpa === '' || str_starts_with($limpa, '#')) {
            $ignoradas++;
            continue;
        }

        $linhas[] = [
            'numero' => $numero,
            'texto'  => $limpa,
        ];
    }

    return [
        'linhas'    => $linhas,
        'ignoradas' => $ignoradas,
    ];
}

function processarLinha(array $linha, array $emailsVistos): array
{
    $campos = explode(';', $linha['texto']);
    $qtdCampos = count($campos);

    // 1. Campos
    if ($qtdCampos !== 4) {
        return [
            'ok'       => false,
            'motivo'   => 'campos',
            'mensagem' => "Esperados 4 campos; encontrados {$qtdCampos}.",
        ];
    }

    // 2. Nome
    $nome = tituloComParticulas($campos[0]);
    $tamanhoNome = mb_strlen($nome, 'UTF-8');
    if ($tamanhoNome < 2 || $tamanhoNome > 80) {
        return [
            'ok'       => false,
            'motivo'   => 'nome',
            'mensagem' => 'Nome deve ter de 2 a 80 caracteres.',
        ];
    }

    // 3. E-mail
    $email = normalizarEmail($campos[1]);
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return [
            'ok'       => false,
            'motivo'   => 'email',
            'mensagem' => 'E-mail inválido.',
        ];
    }

    // 4. Telefone
    $telefone = normalizarTelefone($campos[2]);
    if ($telefone === null) {
        return [
            'ok'       => false,
            'motivo'   => 'telefone',
            'mensagem' => 'Telefone deve ter 10 ou 11 dígitos (com DDD).',
        ];
    }

    // 5. Duplicado
    if (array_key_exists($email, $emailsVistos)) {
        $linhaOriginal = $emailsVistos[$email];
        return [
            'ok'       => false,
            'motivo'   => 'duplicado',
            'mensagem' => "E-mail já importado na linha {$linhaOriginal}.",
        ];
    }

    // Cidade (opcional)
    $cidade = tituloComParticulas($campos[3]);
    if ($cidade === '') {
        $cidade = 'não informada';
    }

    return [
        'ok'      => true,
        'contato' => [
            'linha'    => $linha['numero'],
            'nome'     => $nome,
            'email'    => $email,
            'telefone' => $telefone,
            'cidade'   => $cidade,
        ],
    ];
}

function importarContatos(string $texto): array
{
    $separado = separarLinhas($texto);
    $aceitos = [];
    $rejeitados = [];
    $emailsVistos = [];

    foreach ($separado['linhas'] as $linha) {
        $res = processarLinha($linha, $emailsVistos);

        if ($res['ok'] === true) {
            $contato = $res['contato'];
            $aceitos[] = $contato;
            $emailsVistos[$contato['email']] = $contato['linha'];
        } else {
            $rejeitados[] = [
                'linha'    => $linha['numero'],
                'motivo'   => $res['motivo'],
                'mensagem' => $res['mensagem'],
                'texto'    => $linha['texto'],
            ];
        }
    }

    $contagemBruta = array_count_values(array_column($rejeitados, 'motivo'));
    $porMotivo = [
        'campos'    => $contagemBruta['campos'] ?? 0,
        'nome'      => $contagemBruta['nome'] ?? 0,
        'email'     => $contagemBruta['email'] ?? 0,
        'telefone'  => $contagemBruta['telefone'] ?? 0,
        'duplicado' => $contagemBruta['duplicado'] ?? 0,
    ];

    return [
        'aceitos'    => $aceitos,
        'rejeitados' => $rejeitados,
        'ignoradas'  => $separado['ignoradas'],
        'porMotivo'  => $porMotivo,
    ];
}

function ordenarContatos(array $contatos): array
{
    $copia = $contatos;
    usort($copia, static function (array $a, array $b): int {
        return semAcentos($a['nome']) <=> semAcentos($b['nome']);
    });

    return $copia;
}

function buscarContatos(array $contatos, string $termo): array
{
    $termoLimpo = semAcentos(trim($termo));
    if ($termoLimpo === '') {
        return array_values($contatos);
    }

    $encontrados = array_filter($contatos, static function (array $c) use ($termoLimpo): bool {
        return str_contains(semAcentos($c['nome']), $termoLimpo)
            || str_contains(semAcentos($c['email']), $termoLimpo)
            || str_contains(semAcentos($c['cidade']), $termoLimpo);
    });

    return array_values($encontrados);
}

// =========================================================================
// PROCESSAMENTO PARA A PÁGINA
// =========================================================================

$resA       = importarContatos($textoUnix);
$resWindows = importarContatos($textoWindows);
$resMac     = importarContatos($textoMac);

$quebrasIdenticas = ($resA === $resWindows) && ($resA === $resMac);
$aceitosOrdenados = ordenarContatos($resA['aceitos']);

$termosBusca = ['jose', 'SÃO', 'sao paulo', 'example.com', 'goiania', 'DOS', 'xyz', ''];

// =========================================================================
// BATERIA DE TESTES AUTOMATIZADOS (ENTRADAS A A G)
// =========================================================================

$textoB = "a; ana@x.com; 11 99999-0000; Rio\n"
        . "ana souza; ANA@x.com; 11 99999-0000; rio de janeiro\n"
        . "Ana Souza; ana@X.com; 1133334444; ";
$resB = importarContatos($textoB);

$textoC = "# comentario 1\n\n   \n# comentario 2";
$resC = importarContatos($textoC);

$resD = importarContatos('');

$resE = importarContatos($textoUnix . "\n");

$testes = [
    ['nome' => 'Entrada A: 4 aceitos, 6 rejeitados, 4 ignoradas', 'ok' =>
        count($resA['aceitos']) === 4
        && count($resA['rejeitados']) === 6
        && $resA['ignoradas'] === 4
    ],
    ['nome' => 'Entrada A: contagem por motivo', 'ok' =>
        $resA['porMotivo'] === ['campos' => 2, 'nome' => 1, 'email' => 1, 'telefone' => 1, 'duplicado' => 1]
    ],
    ['nome' => 'Entrada A: ordenação sem acentos (Édson primeiro)', 'ok' =>
        array_column($aceitosOrdenados, 'nome') === ['Édson Filho', 'José dos Santos', 'Luiza', 'Maria da Silva']
    ],
    ['nome' => 'Entrada A: buscas (jose, SÃO, sao paulo, example.com, goiania, DOS, xyz, vazio)', 'ok' =>
        array_column(buscarContatos($resA['aceitos'], 'jose'), 'nome') === ['José dos Santos']
        && array_column(buscarContatos($resA['aceitos'], 'SÃO'), 'nome') === ['Maria da Silva']
        && array_column(buscarContatos($resA['aceitos'], 'sao paulo'), 'nome') === ['Maria da Silva']
        && count(buscarContatos($resA['aceitos'], 'example.com')) === 4
        && array_column(buscarContatos($resA['aceitos'], 'goiania'), 'nome') === ['Édson Filho']
        && array_column(buscarContatos($resA['aceitos'], 'DOS'), 'nome') === ['José dos Santos']
        && buscarContatos($resA['aceitos'], 'xyz') === []
        && count(buscarContatos($resA['aceitos'], '')) === 4
    ],
    ['nome' => 'Quebras de linha: \\n, \\r\\n e \\r produzem resultado idêntico (===)', 'ok' =>
        $quebrasIdenticas
    ],
    ['nome' => 'Entrada B: linha recusada não queima o e-mail; duplicado cita linha 2', 'ok' =>
        count($resB['aceitos']) === 1
        && $resB['aceitos'][0]['linha'] === 2
        && $resB['aceitos'][0]['nome'] === 'Ana Souza'
        && $resB['aceitos'][0]['cidade'] === 'Rio de Janeiro'
        && count($resB['rejeitados']) === 2
        && $resB['rejeitados'][0]['motivo'] === 'nome'
        && $resB['rejeitados'][1]['motivo'] === 'duplicado'
        && $resB['rejeitados'][1]['mensagem'] === 'E-mail já importado na linha 2.'
        && $resB['ignoradas'] === 0
    ],
    ['nome' => 'Entrada C: só comentários e linhas em branco (4 ignoradas, 5 zeros)', 'ok' =>
        $resC['aceitos'] === []
        && $resC['rejeitados'] === []
        && $resC['ignoradas'] === 4
        && $resC['porMotivo'] === ['campos' => 0, 'nome' => 0, 'email' => 0, 'telefone' => 0, 'duplicado' => 0]
    ],
    ['nome' => 'Entrada D: texto vazio (tudo zerado, 0 ignoradas)', 'ok' =>
        $resD['aceitos'] === []
        && $resD['rejeitados'] === []
        && $resD['ignoradas'] === 0
    ],
    ['nome' => 'Entrada E: texto A com quebra extra no fim (5 ignoradas)', 'ok' =>
        $resE['aceitos'] === $resA['aceitos']
        && $resE['rejeitados'] === $resA['rejeitados']
        && $resE['ignoradas'] === 5
    ],
    ['nome' => 'Entrada F: telefones de 10 dígitos, 11 dígitos e com letra (null)', 'ok' =>
        normalizarTelefone('11 3456-7890') === '(11) 3456-7890'
        && normalizarTelefone('(11) 98765-4321') === '(11) 98765-4321'
        && normalizarTelefone('(11) 9876a-4321') === null
    ],
    ['nome' => 'Entrada G: partículas em nomes (ana da silva, E MARIA, da, maria e jose)', 'ok' =>
        tituloComParticulas('ana da silva') === 'Ana da Silva'
        && tituloComParticulas('E MARIA') === 'E Maria'
        && tituloComParticulas('da') === 'Da'
        && tituloComParticulas('maria e jose') === 'Maria e Jose'
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
    <title>Exercício 7 — Importador de Contatos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <header>
        <p><a href="../index.php">&larr; Voltar para o painel</a></p>
        <h1>Exercício 7 — Importador de Contatos</h1>
        <p>Normalização com mb_, validação em funil, duplicados e busca sem acentos</p>
    </header>

    <!-- 1. Resumo -->
    <section>
        <h2>1. Resumo da Importação (Entrada A)</h2>
        <table>
            <tbody>
                <tr><th>Contatos aceitos</th><td><?= e(count($resA['aceitos'])) ?></td></tr>
                <tr><th>Linhas rejeitadas</th><td><?= e(count($resA['rejeitados'])) ?></td></tr>
                <tr><th>Linhas ignoradas</th><td><?= e($resA['ignoradas']) ?></td></tr>
            </tbody>
        </table>

        <h3>Rejeições por motivo</h3>
        <table>
            <thead><tr><th>Motivo</th><th>Quantidade</th></tr></thead>
            <tbody>
                <?php foreach ($resA['porMotivo'] as $motivo => $qtd): ?>
                    <tr>
                        <td><?= e($motivo) ?></td>
                        <td><?= e($qtd) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- 2. Contatos aceitos ordenados -->
    <section>
        <h2>2. Contatos Aceitos (Ordenados por Nome sem Acentos)</h2>
        <table>
            <thead>
                <tr><th>Linha</th><th>Nome</th><th>E-mail</th><th>Telefone</th><th>Cidade</th></tr>
            </thead>
            <tbody>
                <?php foreach ($aceitosOrdenados as $c): ?>
                    <tr>
                        <td><?= e($c['linha']) ?></td>
                        <td><?= e($c['nome']) ?></td>
                        <td><?= e($c['email']) ?></td>
                        <td><?= e($c['telefone']) ?></td>
                        <td><?= e($c['cidade']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- 3. Linhas rejeitadas -->
    <section>
        <h2>3. Linhas Rejeitadas</h2>
        <table>
            <thead>
                <tr><th>Linha</th><th>Motivo</th><th>Mensagem</th><th>Texto Original</th></tr>
            </thead>
            <tbody>
                <?php foreach ($resA['rejeitados'] as $r): ?>
                    <tr>
                        <td><?= e($r['linha']) ?></td>
                        <td><?= e($r['motivo']) ?></td>
                        <td><?= e($r['mensagem']) ?></td>
                        <td><code><?= e($r['texto']) ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- 4. Busca -->
    <section>
        <h2>4. Testes de Busca (sobre os Aceitos)</h2>
        <table>
            <thead><tr><th>Termo</th><th>Resultados</th></tr></thead>
            <tbody>
                <?php foreach ($termosBusca as $termo): ?>
                    <?php $achados = buscarContatos($resA['aceitos'], $termo); ?>
                    <tr>
                        <td><code><?= $termo === '' ? '(vazio)' : e($termo) ?></code></td>
                        <td>
                            <?php if ($achados === []): ?>
                                nenhum
                            <?php else: ?>
                                <?= e(implode(', ', array_column($achados, 'nome'))) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- 5. Prova das quebras de linha -->
    <section>
        <h2>5. Prova das Quebras de Linha (\n, \r\n e \r)</h2>
        <p>
            O resultado de <code>importarContatos()</code> para <code>$textoA</code>, <code>$textoWindows</code> e <code>$textoMac</code> é idêntico (<code>===</code>)?
            <span class="<?= $quebrasIdenticas ? 'badge-ok' : 'badge-falhou' ?>">
                <?= $quebrasIdenticas ? 'SIM (IDÊNTICO)' : 'NÃO' ?>
            </span>
        </p>
    </section>

    <!-- 6. Testes -->
    <section>
        <h2>6. Testes (<?= e($testesPassaram) ?> de <?= e($totalTestes) ?> passaram)</h2>
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