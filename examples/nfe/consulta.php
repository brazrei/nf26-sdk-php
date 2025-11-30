<?php
require_once(__DIR__ . "/../../bootstrap.php");

use CashNFe\SdkPHP\Nfe;

try {
    $configSDK = [
        "token" => "",
        "ambiente" => 2, // 1 = Produção, 2 = Homologação
        "base_url" => "https://nf26.cloud",
        "options" => [
            "debug" => false,
            "timeout" => 120
        ]
    ];

    $nfe = new Nfe($configSDK);

    // Consultar NFe pela chave de acesso
    $payload = [
        "chave" => "35123456789012345550010000000011234567890", // Chave de acesso (44 dígitos)
        "cnpjCertificado" => "12345678000190" // CNPJ do certificado
    ];

    $resp = $nfe->consulta($payload);

    if ($resp->sucesso) {
        echo "NFe consultada com sucesso!\n";
        echo "Status: " . ($resp->status ?? 'AUTORIZADA') . "\n";
        echo "Protocolo: " . ($resp->protocolo ?? 'N/A') . "\n";
        echo "Data Autorização: " . ($resp->data_autorizacao ?? 'N/A') . "\n";
        
        var_dump($resp);
    } else {
        echo "Erro ao consultar NFe:\n";
        echo "Código: " . $resp->codigo . "\n";
        echo "Mensagem: " . $resp->mensagem . "\n";
        
        var_dump($resp);
    }
} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

