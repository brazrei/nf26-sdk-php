<?php
/**
 * Exemplo básico de como gerar DANFE usando SDK NF26
 * 
 * Este é um exemplo genérico para demonstração.
 * Para exemplos funcionais com dados reais, veja: ../../exemplos-sdk-funcionais/
 */

require_once(__DIR__ . "/../../bootstrap.php");

use CashNFe\SdkPHP\Nfe;

// Configurar SDK
$configSDK = [
    "token" => getenv('CASHNFE_TOKEN') ?: 'SEU_TOKEN_AQUI',
    "ambiente" => 2, // 1 = Produção, 2 = Homologação
    "base_url" => "https://nf26.cloud",
    "options" => [
        "debug" => false,
        "timeout" => 120
    ]
];

try {
    $nfe = new Nfe($configSDK);
    
    // Opção 1: Gerar DANFE por CNPJ e número da nota (recomendado)
    $payload = [
        'cnpj' => '12345678000190', // CNPJ do emitente
        'numeroNota' => '1'          // Número da nota fiscal
    ];
    
    echo "Gerando DANFE...\n";
    $resultado = $nfe->gerarDanfe($payload);
    
    if ($resultado->sucesso) {
        echo "✅ DANFE gerado com sucesso!\n";
        echo "Tamanho: " . number_format($resultado->tamanhoBytes ?? 0) . " bytes\n";
        
        // Salvar PDF
        if (!empty($resultado->danfePdfBase64)) {
            $pdfBytes = base64_decode($resultado->danfePdfBase64);
            $nomeArquivo = "DANFE_{$payload['cnpj']}_{$payload['numeroNota']}.pdf";
            file_put_contents($nomeArquivo, $pdfBytes);
            echo "✅ PDF salvo em: $nomeArquivo\n";
        }
    } else {
        echo "❌ Erro ao gerar DANFE:\n";
        echo "Código: " . ($resultado->codigo ?? 'N/A') . "\n";
        echo "Mensagem: " . ($resultado->mensagem ?? 'Erro desconhecido') . "\n";
    }
    
    /* 
    // Opção 2: Gerar DANFE a partir do XML Final Autorizado
    $payload = [
        'xmlFinalAutorizado' => '<?xml version="1.0"?><nfeProc>...</nfeProc>'
    ];
    
    $resultado = $nfe->gerarDanfe($payload);
    */
    
} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}




