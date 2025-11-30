<?php
require_once(__DIR__ . "/../../bootstrap.php");

use CashNFe\SdkPHP\Nfe;

try {

    // Variável de configuração para definir parâmetros da requisição
    // COMPATÍVEL COM CLOUDFE SDK - apenas mude a base_url para usar NF26
    $configSDK = [

        // Token de autenticação obtido no painel NF26
        // Para obter: https://nf26.cloud/login
        "token" => "",

        // Em qual ambiente a requisição será feita
        // 1 = Produção / 2 = Homologação
        "ambiente" => 2, // Padrão: Homologação
        
        // URL base da API NF26
        // Para usar CloudDFE SDK, apenas mude esta URL:
        // "base_url" => "https://api.integranotas.com.br"
        // Para usar NF26:
        "base_url" => "https://nf26.cloud", // URL da API NF26
        
        // Opções complementares
        "options" => [
            "debug" => false, // Ativa mensagem de depuração, Default: false
            "timeout" => 120, // Tempo máximo de espera para resposta da API, Default: 60
            "port" => 443, // Porta de conexão, Default: 443
            "http_version" => "" // Versão do HTTP, Default: CURL_HTTP_VERSION_NONE
        ]
    ];

    // Instancia a classe Nfe que possui métodos para realizar requisições à API NF26
    // Interface idêntica ao SDK CloudDFE - apenas mudou a base_url
    $nfe = new Nfe($configSDK);

    // Conforme sua aplicação, você precisa ter salvo o último número e série da NFe
    // para fazer o incremento e reservar o número e série antes de enviar a NFe.
    $numero = 1; // Obtém do banco o número da NFe
    $serie = 1; // Série da NFe

    // Payload no formato CloudDFE (compatível)
    // IMPORTANTE: A API NF26 atualmente recebe XML diretamente
    // Você pode fornecer o XML pronto no campo "xml" OU
    // usar um serviço intermediário que converte este payload para XML
    $payload = [
        // Opção 1: Fornecer XML diretamente (recomendado por enquanto)
        "xml" => "<?xml version='1.0' encoding='UTF-8'?><NFe>...</NFe>",
        
        // Opção 2: Fornecer payload estruturado (conversão será implementada)
        // "natureza_operacao" => "",
        // "numero" => $numero,
        // "serie" => $serie,
        // "data_emissao" => "",
        // "tipo_operacao" => "",
        // "finalidade_emissao" => "",
        // "consumidor_final" => "",
        // "presenca_comprador" => "",
        // "destinatario" => [...],
        // "itens" => [...],
        // "frete" => [...],
        // "pagamento" => [...],
        
        // CNPJ do certificado digital (obrigatório)
        "cnpjCertificado" => "12345678000190"
    ];

    // Enviar a NFe para API NF26
    // Método idêntico ao SDK CloudDFE
    $resp = $nfe->cria($payload);

    if ($resp->sucesso) {
        // Ao entrar nesse bloco significa que a NFe foi para a SEFAZ e aguarda processamento
        
        // Salva a chave no banco de dados para receber depois o resultado
        // se a nota foi autorizada ou rejeitada
        $chave = $resp->chave;
        
        echo "NFe enviada com sucesso! Chave: " . $chave . "\n";
        
        /* Este é um exemplo de como consultar a NFe após o envio
        sleep(15); // Aguarda 15 segundos para consultar a NFe
        
        $payloadConsulta = [
            "chave" => $chave,
            "cnpjCertificado" => "12345678000190"
        ];

        $respConsulta = $nfe->consulta($payloadConsulta);
        
        if ($respConsulta->codigo != 5023) {
            if ($respConsulta->sucesso) {
                // NFe autorizada
                var_dump($respConsulta);
            } else {
                // NFe rejeitada
                var_dump($respConsulta);
            }
        }
        */

    } else if (in_array($resp->codigo, [5001, 5002])) {
        // Erro na validação dos dados enviados
        // Código 5001: Faltou campos obrigatórios
        // Código 5002: Erro na validação dos dados (CNPJ, CPF, etc.)
        echo "Erro de validação:\n";
        var_dump($resp->erros);
    } else {
        // Qualquer outro erro
        echo "Erro ao emitir NFe:\n";
        var_dump($resp);
    }
} catch (\Exception $e) {
    // Em caso de erros será lançado uma exceção com a mensagem de erro
    echo "Erro: " . $e->getMessage() . "\n";
}

