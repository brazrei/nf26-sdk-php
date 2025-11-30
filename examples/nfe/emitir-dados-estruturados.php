<?php
/**
 * Exemplo de emissão de NF-e usando dados estruturados (formato CloudDFE)
 * 
 * Este exemplo mostra como emitir NF-e fornecendo dados estruturados
 * ao invés de XML pronto. O SDK automaticamente converte para XML.
 * 
 * Compatível com formato CloudDFE para facilitar migração.
 */

require_once(__DIR__ . "/../../bootstrap.php");

use CashNFe\SdkPHP\Nfe;

echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
echo "║         EXEMPLO: EMISSÃO DE NF-e COM DADOS ESTRUTURADOS                     ║\n";
echo "╚══════════════════════════════════════════════════════════════════════════════╝\n\n";

// ============================================================================
// CONFIGURAÇÃO DO SDK
// ============================================================================

$configSDK = [
    "token" => getenv('CASHNFE_TOKEN') ?: 'SEU_TOKEN_AQUI',
    "ambiente" => Nfe::AMBIENTE_HOMOLOGACAO, // 1 = Produção, 2 = Homologação
    "base_url" => getenv('CASHNFE_BASE_URL') ?: "https://nf26.cloud",
    "options" => [
        "debug" => false,
        "timeout" => 120
    ]
];

try {
    $nfe = new Nfe($configSDK);
    echo "✅ SDK configurado com sucesso!\n\n";
    
    // ============================================================================
    // DADOS ESTRUTURADOS (SEM PRECISAR GERAR XML)
    // ============================================================================
    
    echo "📋 Preparando dados estruturados...\n";
    
    $payload = [
        // Dados básicos da nota
        "natureza_operacao" => "VENDA PRODUCAO DO ESTABELECIMENTO",
        "numero" => rand(100, 999), // Número da nota
        "serie" => "1",
        "data_emissao" => date('Y-m-d\TH:i:sP'),
        "tipo_operacao" => 1, // 0=Entrada, 1=Saída
        "finalidade_emissao" => 1, // 1=Normal
        "consumidor_final" => 0, // 0=Não, 1=Sim
        "presenca_comprador" => 1, // 1=Presencial
        
        // Emitente
        "emitente" => [
            "CNPJ" => getenv('CASHNFE_CNPJ') ?: "12345678000190",
            "xNome" => "EMPRESA EXEMPLO LTDA",
            "xFant" => "NOME FANTASIA",
            "IE" => "123456789012",
            "CRT" => "1", // 1=Simples Nacional, 2=Simples Nacional excesso, 3=Regime Normal
            "enderEmit" => [
                "xLgr" => "RUA EXEMPLO",
                "nro" => "123",
                "xBairro" => "CENTRO",
                "cMun" => "3550308", // São Paulo (código IBGE)
                "xMun" => "SAO PAULO",
                "UF" => "SP",
                "CEP" => "01000000",
                "cPais" => "1058", // Brasil
                "fone" => "1133334444"
            ]
        ],
        
        // Destinatário
        "destinatario" => [
            "CNPJ" => "99999999000191",
            "xNome" => $configSDK["ambiente"] == Nfe::AMBIENTE_HOMOLOGACAO 
                ? "NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL"
                : "CLIENTE EXEMPLO LTDA",
            "indIEDest" => "9", // 9=Não contribuinte
            "enderDest" => [
                "xLgr" => "RUA TESTE",
                "nro" => "456",
                "xBairro" => "CENTRO",
                "cMun" => "3550308",
                "xMun" => "SAO PAULO",
                "UF" => "SP",
                "CEP" => "01000000",
                "cPais" => "1058",
                "fone" => "1133335555"
            ]
        ],
        
        // Itens da nota
        "itens" => [
            [
                "cProd" => "0001",
                "xProd" => "PRODUTO DE TESTE HOMOLOGACAO - EXEMPLO 1",
                "NCM" => "84713012",
                "CFOP" => "5102",
                "uCom" => "UN",
                "qCom" => "2.0000",
                "vUnCom" => "150.00",
                "vProd" => "300.00",
                "uTrib" => "UN",
                "qTrib" => "2.0000",
                "vUnTrib" => "150.00",
                "indTot" => "1" // 1=Valor total no total da nota
            ],
            [
                "cProd" => "0002",
                "xProd" => "PRODUTO DE TESTE HOMOLOGACAO - EXEMPLO 2",
                "NCM" => "84713012",
                "CFOP" => "5102",
                "uCom" => "UN",
                "qCom" => "1.0000",
                "vUnCom" => "100.00",
                "vProd" => "100.00",
                "uTrib" => "UN",
                "qTrib" => "1.0000",
                "vUnTrib" => "100.00",
                "indTot" => "1"
            ]
        ],
        
        // Frete (opcional)
        "frete" => [
            "modFrete" => "0", // 0=Por conta do remetente
            "vFrete" => "0.00"
        ],
        
        // Pagamento (opcional)
        "pagamento" => [
            "indPag" => "0", // 0=Pagamento à vista
            "tPag" => "01", // 01=Dinheiro
            "vPag" => "400.00" // Valor total será usado se não especificado
        ],
        
        // Informações adicionais (opcional)
        "infAdic" => [
            "infCpl" => $configSDK["ambiente"] == Nfe::AMBIENTE_HOMOLOGACAO 
                ? "NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL"
                : "Informações adicionais da nota fiscal"
        ],
        
        // CNPJ do certificado digital (obrigatório)
        "cnpjCertificado" => getenv('CASHNFE_CNPJ') ?: "12345678000190"
    ];
    
    echo "✅ Dados estruturados preparados!\n";
    echo "   - Número da nota: {$payload['numero']}\n";
    echo "   - Emitente: {$payload['emitente']['xNome']}\n";
    echo "   - Destinatário: {$payload['destinatario']['xNome']}\n";
    echo "   - Itens: " . count($payload['itens']) . " produto(s)\n";
    echo "   - Total: R$ 400,00\n\n";
    
    // ============================================================================
    // EMITIR NF-e
    // ============================================================================
    
    echo "📤 Enviando NF-e para a API...\n";
    echo "   (O SDK converterá automaticamente os dados estruturados para XML)\n\n";
    
    $resultado = $nfe->cria($payload);
    
    // ============================================================================
    // RESULTADO
    // ============================================================================
    
    if ($resultado->sucesso) {
        echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                    ✅ NF-e EMITIDA COM SUCESSO! ✅                          ║\n";
        echo "╚══════════════════════════════════════════════════════════════════════════════╝\n\n";
        
        echo "📋 Informações da Nota Fiscal:\n";
        echo "   Chave de Acesso: " . ($resultado->chave ?? 'N/A') . "\n";
        echo "   Protocolo: " . ($resultado->protocolo ?? 'N/A') . "\n";
        echo "   Data Autorização: " . ($resultado->data_autorizacao ?? 'N/A') . "\n";
        echo "   Status: AUTORIZADA\n\n";
        
        echo "💡 Vantagens de usar dados estruturados:\n";
        echo "   ✅ Não precisa gerar XML manualmente\n";
        echo "   ✅ Validação automática de campos\n";
        echo "   ✅ Formato compatível com CloudDFE\n";
        echo "   ✅ Mais fácil de manter e atualizar\n\n";
        
    } else {
        echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                    ❌ ERRO AO EMITIR NF-e                                    ║\n";
        echo "╚══════════════════════════════════════════════════════════════════════════════╝\n\n";
        
        echo "Código: " . $resultado->codigo . "\n";
        echo "Mensagem: " . $resultado->mensagem . "\n";
        
        if (!empty($resultado->erros)) {
            echo "\nErros detalhados:\n";
            foreach ($resultado->erros as $erro) {
                if (is_array($erro)) {
                    echo "  - " . json_encode($erro, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                } else {
                    echo "  - " . $erro . "\n";
                }
            }
        }
        
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
    echo "║                    ❌ EXCEÇÃO AO PROCESSAR                                    ║\n";
    echo "╚══════════════════════════════════════════════════════════════════════════════╝\n\n";
    
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    if ($e->getPrevious()) {
        echo "Erro anterior: " . $e->getPrevious()->getMessage() . "\n";
    }
    
    exit(1);
}

echo "✅ Exemplo concluído com sucesso!\n";




