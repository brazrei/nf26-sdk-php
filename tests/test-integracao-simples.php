<?php
/**
 * Teste de integração simples do SDK NF26 PHP
 * 
 * Teste básico para validar conexão e autenticação
 */

require_once(__DIR__ . "/../bootstrap.php");

use CashNFe\SdkPHP\Nfe;

echo "🧪 Teste de Integração Simples - SDK NF26 PHP\n";
echo str_repeat("=", 60) . "\n\n";

// Configuração
$token = getenv('CASHNFE_TOKEN') ?: '';
$baseUrl = getenv('CASHNFE_BASE_URL') ?: 'https://nf26.cloud';
$ambiente = (int)(getenv('CASHNFE_AMBIENTE') ?: '2');

if (empty($token)) {
    echo "⚠️  Token não configurado!\n";
    echo "Configure: export CASHNFE_TOKEN='seu_token'\n";
    echo "Ou edite este arquivo e coloque o token diretamente.\n\n";
    
    echo "Deseja usar token vazio para testar apenas validações? (s/N): ";
    $answer = trim(fgets(STDIN));
    if (strtolower($answer) !== 's') {
        exit(1);
    }
}

$configSDK = [
    "token" => $token,
    "ambiente" => $ambiente,
    "base_url" => $baseUrl,
    "options" => [
        "debug" => false,
        "timeout" => 30
    ]
];

echo "📋 Configuração:\n";
echo "   Base URL: {$baseUrl}\n";
echo "   Ambiente: " . ($ambiente == 1 ? "Produção" : "Homologação") . "\n";
echo "   Token: " . (empty($token) ? "Não configurado" : substr($token, 0, 10) . "...") . "\n\n";

try {
    // 1. Instanciar SDK
    echo "1️⃣  Instanciando SDK...\n";
    $nfe = new Nfe($configSDK);
    echo "   ✅ SDK instanciado com sucesso!\n\n";
    
    // 2. Testar validação de parâmetros
    echo "2️⃣  Testando validação de parâmetros...\n";
    
    // Teste sem CNPJ
    $payloadSemCNPJ = [
        "xml" => "<?xml version='1.0'?><NFe>test</NFe>"
    ];
    
    $resp = $nfe->cria($payloadSemCNPJ);
    if (!$resp->sucesso) {
        echo "   ✅ Validação funcionando (CNPJ obrigatório detectado)\n";
        echo "      Código: {$resp->codigo}\n";
        echo "      Mensagem: {$resp->mensagem}\n\n";
    } else {
        echo "   ❌ Validação não funcionou como esperado\n\n";
    }
    
    // 3. Testar consulta sem chave
    echo "3️⃣  Testando consulta sem chave...\n";
    $payloadSemChave = [
        "cnpjCertificado" => "12345678000190"
    ];
    
    $resp = $nfe->consulta($payloadSemChave);
    if (!$resp->sucesso) {
        echo "   ✅ Validação funcionando (chave obrigatória detectada)\n";
        echo "      Código: {$resp->codigo}\n";
        echo "      Mensagem: {$resp->mensagem}\n\n";
    } else {
        echo "   ❌ Validação não funcionou como esperado\n\n";
    }
    
    // 4. Testar formato de resposta
    echo "4️⃣  Verificando formato de resposta...\n";
    if (is_object($resp) && isset($resp->sucesso) && isset($resp->codigo)) {
        echo "   ✅ Formato de resposta correto (objeto com sucesso e codigo)\n\n";
    } else {
        echo "   ❌ Formato de resposta incorreto\n\n";
    }
    
    // 5. Se token configurado, testar conexão real
    if (!empty($token)) {
        echo "5️⃣  Testando conexão com API (com token)...\n";
        
        $payloadTeste = [
            "xml" => "<?xml version='1.0'?><NFe>test</NFe>",
            "cnpjCertificado" => "12345678000190"
        ];
        
        echo "   Fazendo requisição de teste...\n";
        $resp = $nfe->cria($payloadTeste);
        
        echo "   Código HTTP: {$resp->codigo}\n";
        
        if (isset($resp->codigo)) {
            echo "   ✅ Conexão estabelecida (código recebido: {$resp->codigo})\n";
            if ($resp->codigo == 401) {
                echo "   ⚠️  Token pode estar inválido ou expirado\n";
            }
        } else {
            echo "   ❌ Não foi possível conectar com a API\n";
        }
    } else {
        echo "5️⃣  Teste de conexão real pulado (token não configurado)\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ Teste básico concluído!\n";
    echo "\nPara testes mais completos, execute: php tests/test-nfe-completo.php\n";
    
} catch (Exception $e) {
    echo "\n❌ Erro: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

