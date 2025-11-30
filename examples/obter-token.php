<?php
/**
 * Script para obter token de autenticação
 * 
 * Este script faz login na API e retorna o token JWT necessário
 * para usar o SDK e emitir notas fiscais.
 * 
 * Este script usa apenas funções nativas do PHP (sem dependências externas).
 */

// Cores para output
class Colors {
    const GREEN = "\033[0;32m";
    const RED = "\033[0;31m";
    const YELLOW = "\033[1;33m";
    const BLUE = "\033[0;34m";
    const CYAN = "\033[0;36m";
    const RESET = "\033[0m";
}

function printSuccess($msg) { echo Colors::GREEN . "✅ " . $msg . Colors::RESET . "\n"; }
function printError($msg) { echo Colors::RED . "❌ " . $msg . Colors::RESET . "\n"; }
function printInfo($msg) { echo Colors::BLUE . "ℹ️  " . $msg . Colors::RESET . "\n"; }
function printWarning($msg) { echo Colors::YELLOW . "⚠️  " . $msg . Colors::RESET . "\n"; }
function printHeader($title) {
    echo "\n" . Colors::CYAN . str_repeat("=", 70) . Colors::RESET . "\n";
    echo Colors::CYAN . "  " . $title . Colors::RESET . "\n";
    echo Colors::CYAN . str_repeat("=", 70) . Colors::RESET . "\n\n";
}

printHeader("OBTER TOKEN DE AUTENTICAÇÃO - NF26");

// ============================================================================
// CONFIGURAÇÃO
// ============================================================================

$baseUrl = getenv('CASHNFE_BASE_URL') ?: 'https://nf26.cloud';
$username = getenv('CASHNFE_USERNAME') ?: ''; // Configure via variável de ambiente
$password = getenv('CASHNFE_PASSWORD') ?: ''; // Configure via variável de ambiente
$empresa = getenv('CASHNFE_EMPRESA') ?: ''; // CNPJ ou ID da empresa (configure via variável de ambiente)

printInfo("Base URL: " . $baseUrl);
printInfo("Usuário: " . $username);
printInfo("Empresa: " . $empresa);

// ============================================================================
// FAZER LOGIN (usando file_get_contents com stream context)
// ============================================================================

printHeader("FAZENDO LOGIN");

$loginUrl = rtrim($baseUrl, '/') . '/api/auth/login';

$postData = json_encode([
    'username' => $username,
    'password' => $password,
    'empresa' => $empresa
]);

// Configurar contexto HTTP para POST
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Content-Length: ' . strlen($postData)
        ],
        'content' => $postData,
        'timeout' => 30,
        'ignore_errors' => true // Para capturar códigos de erro HTTP
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

// Fazer requisição
$response = @file_get_contents($loginUrl, false, $context);

if ($response === false) {
    $error = error_get_last();
    printError("Erro na conexão: " . ($error['message'] ?? 'Erro desconhecido'));
    exit(1);
}

// Extrair código HTTP da resposta
preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0] ?? '', $matches);
$httpCode = isset($matches[1]) ? (int)$matches[1] : 0;

if ($httpCode !== 200) {
    printError("Erro HTTP: " . $httpCode);
    echo "Resposta: " . $response . "\n";
    exit(1);
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    printError("Erro ao decodificar resposta JSON");
    echo "Resposta: " . $response . "\n";
    exit(1);
}

if (!isset($data['access_token'])) {
    printError("Token não encontrado na resposta");
    echo "Resposta completa:\n";
    print_r($data);
    exit(1);
}

// ============================================================================
// EXIBIR TOKEN
// ============================================================================

printHeader("TOKEN OBTIDO COM SUCESSO!");

$accessToken = $data['access_token'];
$refreshToken = $data['refresh_token'] ?? '';
$expiresIn = $data['expires_in'] ?? 0;
$tokenType = $data['token_type'] ?? 'Bearer';

printSuccess("Login realizado com sucesso!");
echo "\n";
echo "📋 Informações do Token:\n";
echo "   Tipo: " . $tokenType . "\n";
echo "   Expira em: " . $expiresIn . " segundos (" . round($expiresIn / 60, 1) . " minutos)\n";
echo "\n";

// ============================================================================
// SALVAR TOKEN EM ARQUIVO
// ============================================================================

$tokenFile = __DIR__ . '/.token';
file_put_contents($tokenFile, $accessToken);

printSuccess("Token salvo em: " . $tokenFile);

// ============================================================================
// GERAR COMANDOS PARA USAR
// ============================================================================

printHeader("PRÓXIMOS PASSOS");

echo "1️⃣  Exportar token como variável de ambiente:\n";
echo Colors::GREEN . "   export CASHNFE_TOKEN=\"" . $accessToken . "\"" . Colors::RESET . "\n";
echo "\n";

echo "2️⃣  Ou carregar do arquivo:\n";
echo Colors::GREEN . "   export CASHNFE_TOKEN=\$(cat " . $tokenFile . ")" . Colors::RESET . "\n";
echo "\n";

echo "3️⃣  Testar emissão de NF-e:\n";
echo Colors::GREEN . "   php examples/nfe/emitir-teste-completo.php" . Colors::RESET . "\n";
echo "\n";

echo "📝 Token completo (copiar se necessário):\n";
echo Colors::YELLOW . $accessToken . Colors::RESET . "\n";
echo "\n";

if (!empty($refreshToken)) {
    echo "🔄 Refresh Token (para renovar acesso):\n";
    echo Colors::YELLOW . substr($refreshToken, 0, 50) . "..." . Colors::RESET . "\n";
    echo "\n";
}

// ============================================================================
// TESTAR TOKEN
// ============================================================================

printHeader("TESTANDO TOKEN");

// Fazer uma requisição de teste com o token
$testUrl = rtrim($baseUrl, '/') . '/api/cert/list';

$testContext = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ],
        'timeout' => 10,
        'ignore_errors' => true
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$testResponse = @file_get_contents($testUrl, false, $testContext);

if ($testResponse !== false) {
    preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0] ?? '', $testMatches);
    $testHttpCode = isset($testMatches[1]) ? (int)$testMatches[1] : 0;
    
    if ($testHttpCode === 200) {
        printSuccess("Token válido! Conseguiu acessar API protegida.");
        echo "   Teste: GET /api/cert/list retornou 200 OK\n";
    } else {
        printWarning("Token obtido, mas teste de acesso retornou: " . $testHttpCode);
        echo "   Isso pode ser normal se não houver certificados cadastrados.\n";
    }
} else {
    printWarning("Não foi possível testar o token (mas ele foi obtido com sucesso).");
}

echo "\n";
printSuccess("Token obtido e pronto para uso!");
echo "   Você pode agora testar o SDK com: php examples/nfe/emitir-teste-completo.php\n";
