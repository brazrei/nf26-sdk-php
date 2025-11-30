<?php
/**
 * Teste completo do SDK NF26 PHP
 * 
 * Este script testa todas as funcionalidades do SDK:
 * - Conexão com API
 * - Autenticação
 * - Emissão de NF-e
 * - Consulta de NF-e
 * - Tratamento de erros
 */

require_once(__DIR__ . "/../bootstrap.php");

use CashNFe\SdkPHP\Nfe;

// Cores para output no terminal
class Colors {
    const GREEN = "\033[0;32m";
    const RED = "\033[0;31m";
    const YELLOW = "\033[1;33m";
    const BLUE = "\033[0;34m";
    const CYAN = "\033[0;36m";
    const RESET = "\033[0m";
}

function printSuccess($message) {
    echo Colors::GREEN . "✅ " . $message . Colors::RESET . "\n";
}

function printError($message) {
    echo Colors::RED . "❌ " . $message . Colors::RESET . "\n";
}

function printInfo($message) {
    echo Colors::BLUE . "ℹ️  " . $message . Colors::RESET . "\n";
}

function printWarning($message) {
    echo Colors::YELLOW . "⚠️  " . $message . Colors::RESET . "\n";
}

function printHeader($title) {
    echo "\n" . Colors::CYAN . str_repeat("=", 60) . Colors::RESET . "\n";
    echo Colors::CYAN . "  " . $title . Colors::RESET . "\n";
    echo Colors::CYAN . str_repeat("=", 60) . Colors::RESET . "\n\n";
}

// Contador de testes
$testsPassed = 0;
$testsFailed = 0;

function test($name, $callback) {
    global $testsPassed, $testsFailed;
    
    echo Colors::BLUE . "🧪 Testando: " . $name . Colors::RESET . "\n";
    
    try {
        $result = $callback();
        if ($result) {
            printSuccess("Passou: " . $name);
            $testsPassed++;
            return true;
        } else {
            printError("Falhou: " . $name);
            $testsFailed++;
            return false;
        }
    } catch (Exception $e) {
        printError("Erro em: " . $name . " - " . $e->getMessage());
        $testsFailed++;
        return false;
    }
}

// Configuração do SDK
printHeader("CONFIGURAÇÃO DO SDK");

$token = getenv('CASHNFE_TOKEN') ?: '';
$baseUrl = getenv('CASHNFE_BASE_URL') ?: 'https://nf26.cloud';
$ambiente = (int)(getenv('CASHNFE_AMBIENTE') ?: '2'); // 1=Produção, 2=Homologação

if (empty($token)) {
    printWarning("Token não configurado!");
    echo "Configure a variável de ambiente: export CASHNFE_TOKEN='seu_token'\n";
    echo "Ou edite este arquivo e coloque o token diretamente.\n\n";
    echo "Para testar sem token, os testes de autenticação falharão.\n";
    echo "Deseja continuar mesmo assim? (s/N): ";
    $answer = trim(fgets(STDIN));
    if (strtolower($answer) !== 's') {
        exit(1);
    }
}

printInfo("Base URL: " . $baseUrl);
printInfo("Ambiente: " . ($ambiente == 1 ? "Produção" : "Homologação"));
printInfo("Token: " . (empty($token) ? "Não configurado" : substr($token, 0, 10) . "..."));

$configSDK = [
    "token" => $token,
    "ambiente" => $ambiente,
    "base_url" => $baseUrl,
    "options" => [
        "debug" => false,
        "timeout" => 120
    ]
];

try {
    $nfe = new Nfe($configSDK);
    printSuccess("SDK instanciado com sucesso!");
} catch (Exception $e) {
    printError("Erro ao instanciar SDK: " . $e->getMessage());
    exit(1);
}

// Teste 1: Verificar se SDK foi instanciado corretamente
printHeader("TESTE 1: INSTANCIAÇÃO DO SDK");

test("Instanciação do SDK", function() use ($configSDK) {
    try {
        $nfe = new Nfe($configSDK);
        return $nfe instanceof Nfe;
    } catch (Exception $e) {
        return false;
    }
});

// Teste 2: Validação de parâmetros obrigatórios
printHeader("TESTE 2: VALIDAÇÃO DE PARÂMETROS");

test("Validação - CNPJ obrigatório na emissão", function() use ($nfe) {
    $payload = [
        "xml" => "<?xml version='1.0'?><NFe>...</NFe>"
        // Sem cnpjCertificado
    ];
    
    $resp = $nfe->cria($payload);
    return !$resp->sucesso && ($resp->codigo == 5001 || strpos($resp->mensagem, 'CNPJ') !== false);
});

test("Validação - Chave obrigatória na consulta", function() use ($nfe) {
    $payload = [
        // Sem chave
        "cnpjCertificado" => "12345678000190"
    ];
    
    $resp = $nfe->consulta($payload);
    return !$resp->sucesso && ($resp->codigo == 5001 || strpos($resp->mensagem, 'chave') !== false);
});

// Teste 3: Formato de resposta
printHeader("TESTE 3: FORMATO DE RESPOSTA");

test("Formato de resposta - Método cria() retorna objeto", function() use ($nfe) {
    $payload = [
        "xml" => "<?xml version='1.0'?><NFe>...</NFe>",
        "cnpjCertificado" => "12345678000190"
    ];
    
    $resp = $nfe->cria($payload);
    return is_object($resp) && isset($resp->sucesso) && isset($resp->codigo);
});

test("Formato de resposta - Método consulta() retorna objeto", function() use ($nfe) {
    $payload = [
        "chave" => "12345678901234567890123456789012345678901234",
        "cnpjCertificado" => "12345678000190"
    ];
    
    $resp = $nfe->consulta($payload);
    return is_object($resp) && isset($resp->sucesso) && isset($resp->codigo);
});

// Teste 4: Tratamento de erros HTTP
printHeader("TESTE 4: TRATAMENTO DE ERROS");

test("Tratamento de erro - URL inválida", function() {
    $config = [
        "token" => "test",
        "ambiente" => 2,
        "base_url" => "https://url-inexistente-12345.com.br"
    ];
    
    try {
        $nfe = new Nfe($config);
        $resp = $nfe->cria(["xml" => "<NFe/>", "cnpjCertificado" => "123"]);
        // Deve retornar erro, não lançar exceção
        return !$resp->sucesso;
    } catch (Exception $e) {
        // Exceção também é aceitável
        return true;
    }
});

// Teste 5: Endpoints corretos por ambiente
printHeader("TESTE 5: ENDPOINTS POR AMBIENTE");

test("Endpoint homologação - ambiente 2 usa /hom-api", function() use ($configSDK) {
    $config = $configSDK;
    $config['ambiente'] = 2; // Homologação
    $nfe = new Nfe($config);
    
    // Verificar se a classe internamente usa /hom-api para ambiente 2
    // Como não temos acesso direto, vamos testar através de uma chamada
    // que deve usar o endpoint correto
    $reflection = new ReflectionClass($nfe);
    $method = $reflection->getMethod('getEndpoint');
    $method->setAccessible(true);
    
    $endpoint = $method->invoke($nfe, '/api/nfe/emitir');
    return strpos($endpoint, '/hom-api/') !== false;
});

test("Endpoint produção - ambiente 1 usa /api", function() use ($configSDK) {
    $config = $configSDK;
    $config['ambiente'] = 1; // Produção
    $nfe = new Nfe($config);
    
    $reflection = new ReflectionClass($nfe);
    $method = $reflection->getMethod('getEndpoint');
    $method->setAccessible(true);
    
    $endpoint = $method->invoke($nfe, '/api/nfe/emitir');
    return strpos($endpoint, '/api/') !== false && strpos($endpoint, '/hom-api/') === false;
});

// Teste 6: Integração real (requer token válido)
printHeader("TESTE 6: INTEGRAÇÃO REAL COM API");

if (!empty($token)) {
    test("Health check - Verificar se API está acessível", function() use ($nfe) {
        // Fazer uma requisição simples para verificar conectividade
        $payload = [
            "xml" => "<?xml version='1.0'?><NFe>test</NFe>",
            "cnpjCertificado" => "12345678000190"
        ];
        
        $resp = $nfe->cria($payload);
        // Não importa se falhou (pode ser erro de validação)
        // Importante é que não deu erro de conexão
        return isset($resp->codigo);
    });
} else {
    printWarning("Testes de integração real pulados (token não configurado)");
}

// Teste 7: Extração de chave de acesso
printHeader("TESTE 7: EXTRAÇÃO DE CHAVE DE ACESSO");

test("Extração de chave - XML com tag chNFe", function() use ($nfe) {
    $reflection = new ReflectionClass($nfe);
    $method = $reflection->getMethod('extractChaveAcesso');
    $method->setAccessible(true);
    
    $xml = '<retEnviNFe><chNFe>35123456789012345550010000005111234567890</chNFe></retEnviNFe>';
    $chave = $method->invoke($nfe, $xml);
    
    return $chave === '35123456789012345550010000005111234567890';
});

test("Extração de chave - XML sem chave retorna null", function() use ($nfe) {
    $reflection = new ReflectionClass($nfe);
    $method = $reflection->getMethod('extractChaveAcesso');
    $method->setAccessible(true);
    
    $xml = '<retEnviNFe></retEnviNFe>';
    $chave = $method->invoke($nfe, $xml);
    
    return $chave === null;
});

// Resumo final
printHeader("RESUMO DOS TESTES");

echo Colors::GREEN . "Testes passados: " . $testsPassed . Colors::RESET . "\n";
echo Colors::RED . "Testes falhados: " . $testsFailed . Colors::RESET . "\n";
echo "Total: " . ($testsPassed + $testsFailed) . "\n\n";

$successRate = ($testsPassed / ($testsPassed + $testsFailed)) * 100;
echo "Taxa de sucesso: " . number_format($successRate, 2) . "%\n\n";

if ($testsFailed === 0) {
    printSuccess("Todos os testes passaram! 🎉");
    exit(0);
} else {
    printError("Alguns testes falharam. Revise os detalhes acima.");
    exit(1);
}

