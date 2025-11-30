<?php
/**
 * Exemplo básico de como emitir NF-e usando SDK NF26
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
    
    // Payload com XML da NF-e
    // IMPORTANTE: Você deve gerar o XML completo da NF-e
    // Este é apenas um exemplo de estrutura
    $payload = [
        "xml" => "<?xml version='1.0' encoding='UTF-8'?>
<NFe xmlns=\"http://www.portalfiscal.inf.br/nfe\">
  <infNFe Id=\"NFe3512345678901234555001000000001\" versao=\"4.00\">
    <ide>
      <cUF>35</cUF>
      <cNF>00000001</cNF>
      <natOp>VENDA</natOp>
      <mod>55</mod>
      <serie>1</serie>
      <nNF>1</nNF>
      <dhEmi>" . date('Y-m-d\TH:i:sP') . "</dhEmi>
      <tpNF>1</tpNF>
      <idDest>1</idDest>
      <cMunFG>3550308</cMunFG>
      <tpImp>1</tpImp>
      <tpEmis>1</tpEmis>
      <cDV>1</cDV>
      <tpAmb>2</tpAmb>
      <finNFe>1</finNFe>
      <indFinal>0</indFinal>
      <indPres>1</indPres>
      <procEmi>0</procEmi>
      <verProc>NF26-SDK-PHP/1.0.0</verProc>
    </ide>
    <emit>
      <CNPJ>12345678000190</CNPJ>
      <xNome>EMPRESA EXEMPLO LTDA</xNome>
      <xFant>NOME FANTASIA</xFant>
      <enderEmit>
        <xLgr>RUA EXEMPLO</xLgr>
        <nro>123</nro>
        <xBairro>CENTRO</xBairro>
        <cMun>3550308</cMun>
        <xMun>SAO PAULO</xMun>
        <UF>SP</UF>
        <CEP>01000000</CEP>
        <cPais>1058</cPais>
        <fone>1133334444</fone>
      </enderEmit>
      <IE>123456789012</IE>
      <CRT>1</CRT>
    </emit>
    <dest>
      <CNPJ>99999999000191</CNPJ>
      <xNome>NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL</xNome>
      <enderDest>
        <xLgr>RUA TESTE</xLgr>
        <nro>456</nro>
        <xBairro>CENTRO</xBairro>
        <cMun>3550308</cMun>
        <xMun>SAO PAULO</xMun>
        <UF>SP</UF>
        <CEP>01000000</CEP>
        <cPais>1058</cPais>
      </enderDest>
      <indIEDest>9</indIEDest>
    </dest>
    <det nItem=\"1\">
      <prod>
        <cProd>0001</cProd>
        <xProd>PRODUTO EXEMPLO</xProd>
        <NCM>84713012</NCM>
        <CFOP>5102</CFOP>
        <uCom>UN</uCom>
        <qCom>1.0000</qCom>
        <vUnCom>100.00</vUnCom>
        <vProd>100.00</vProd>
        <uTrib>UN</uTrib>
        <qTrib>1.0000</qTrib>
        <vUnTrib>100.00</vUnTrib>
        <indTot>1</indTot>
      </prod>
      <imposto>
        <vTotTrib>0.00</vTotTrib>
        <ICMS>
          <ICMS00>
            <orig>0</orig>
            <CST>000</CST>
            <modBC>0</modBC>
            <vBC>0.00</vBC>
            <pICMS>0.00</pICMS>
            <vICMS>0.00</vICMS>
          </ICMS00>
        </ICMS>
      </imposto>
    </det>
    <total>
      <ICMSTot>
        <vBC>0.00</vBC>
        <vICMS>0.00</vICMS>
        <vNF>100.00</vNF>
        <vProd>100.00</vProd>
        <vTotTrib>0.00</vTotTrib>
      </ICMSTot>
    </total>
    <transp>
      <modFrete>9</modFrete>
    </transp>
    <pag>
      <detPag>
        <indPag>0</indPag>
        <tPag>01</tPag>
        <vPag>100.00</vPag>
      </detPag>
    </pag>
  </infNFe>
</NFe>",
        "cnpjCertificado" => "12345678000190" // CNPJ do certificado cadastrado
    ];
    
    // Emitir NF-e
    $resultado = $nfe->cria($payload);
    
    if ($resultado->sucesso) {
        echo "✅ NF-e emitida com sucesso!\n";
        echo "Chave de Acesso: " . $resultado->chave . "\n";
        echo "Protocolo: " . ($resultado->protocolo ?? 'N/A') . "\n";
        echo "Status: " . ($resultado->status ?? 'AUTORIZADA') . "\n";
    } else {
        echo "❌ Erro ao emitir NF-e:\n";
        echo "Código: " . $resultado->codigo . "\n";
        echo "Mensagem: " . $resultado->mensagem . "\n";
        if (!empty($resultado->erros)) {
            echo "Erros:\n";
            foreach ($resultado->erros as $erro) {
                echo "  - $erro\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}




