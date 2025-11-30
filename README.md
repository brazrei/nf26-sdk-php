# NF26 SDK PHP

SDK PHP para integração com API NF26 - **Compatível com CloudDFE SDK**

Este SDK mantém a mesma interface do SDK CloudDFE, permitindo que clientes migrem facilmente apenas mudando a URL base.

## 📋 Características

- ✅ **100% compatível** com CloudDFE SDK
- ✅ **Mesma interface** - apenas mude a `base_url`
- ✅ **Migração fácil** - sem necessidade de alterar código existente
- ✅ **Suporte completo** para NF-e (NFe) e NFSe

## 🚀 Instalação

```bash
composer require nf26/sdk-php
```

Ou clone este repositório e instale as dependências:

```bash
cd sdk-php
composer install
```

## 📦 Requisitos

- PHP >= 7.4
- Extensão PHP: `json`, `curl`, `openssl`
- **Nota**: Usa cURL nativo (sem dependências externas), igual ao CloudDFE SDK

## 🔧 Configuração

```php
use CashNFe\SdkPHP\Nfe;

// Para Homologação
$configSDK = [
    "token" => "seu_token_aqui",
    "ambiente" => 2, // 1 = Produção, 2 = Homologação
    "base_url" => "https://hom-api.nf26.cloud", // URL da API NF26 - Homologação
    "options" => [
        "debug" => false,
        "timeout" => 120
    ]
];

// Para Produção
$configSDKProducao = [
    "token" => "seu_token_aqui",
    "ambiente" => 1, // Produção
    "base_url" => "https://api.nf26.cloud", // URL da API NF26 - Produção
    "options" => [
        "debug" => false,
        "timeout" => 120
    ]
];

$nfe = new Nfe($configSDK);
```

## ✅ Teste Rápido - Verificar Conexão

Teste simples para verificar se o SDK está conectado corretamente ao servidor de homologação:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use CashNFe\SdkPHP\Nfe;

$config = [
    "token" => "seu_token_aqui",
    "ambiente" => 2, // 2 = Homologação
    "base_url" => "https://hom-api.nf26.cloud",
    "options" => [
        "debug" => false,
        "timeout" => 120
    ]
];

$nfe = new Nfe($config);

// Testar conexão e status do ambiente
$resultado = $nfe->status();

if ($resultado->sucesso || isset($resultado->ambiente)) {
    echo "✅ SDK conectado com sucesso!\n";
    echo "Ambiente: " . ($resultado->ambiente ?? 'N/A') . "\n";
    echo "UF: " . ($resultado->uf ?? 'N/A') . "\n";
    echo "Observação: " . ($resultado->mensagem ?? 'OK') . "\n";
    
    // Resposta esperada em homologação:
    // ✅ SDK conectado com sucesso!
    // Ambiente: homologacao
    // UF: SP
    // Observação: ✅ Ambiente de HOMOLOGAÇÃO - NF-e de teste, não válida para fins fiscais
} else {
    echo "❌ Erro: " . ($resultado->mensagem ?? 'Erro desconhecido') . "\n";
    echo "Código: " . ($resultado->codigo ?? 'N/A') . "\n";
}
```

**Resultado esperado em homologação:**
```
✅ SDK conectado com sucesso!
Ambiente: homologacao
UF: SP
Observação: ✅ Ambiente de HOMOLOGAÇÃO - NF-e de teste, não válida para fins fiscais
```

Este teste confirma que:
- ✅ O SDK está configurado corretamente
- ✅ A conexão com a API está funcionando
- ✅ O servidor de homologação da SEFAZ está respondendo
- ✅ O ambiente está configurado para homologação

## 📝 Migração do CloudDFE SDK

Para migrar do CloudDFE SDK para NF26 SDK, apenas mude a `base_url`:

```php
// ANTES (CloudDFE)
$configSDK = [
    "token" => "...",
    "ambiente" => 2,
    // base_url padrão: https://api.integranotas.com.br
];

// DEPOIS (NF26)
$configSDK = [
    "token" => "...",
    "ambiente" => 2,
    "base_url" => "https://hom-api.nf26.cloud", // ← Para homologação!
    // ou "https://api.nf26.cloud" para produção
];
```

O restante do código permanece **exatamente igual**!

## 💻 Exemplos de Uso

### Emitir NF-e - Opção 1: XML Pronto (Tradicional)

```php
<?php
require_once("bootstrap.php");
use CashNFe\SdkPHP\Nfe;

$configSDK = [
    "token" => "seu_token",
    "ambiente" => 2,
    "base_url" => "https://hom-api.nf26.cloud" // URL para homologação
];

$nfe = new Nfe($configSDK);

$payload = [
    "xml" => "<?xml version='1.0'?><NFe>...</NFe>",
    "cnpjCertificado" => "12345678000190"
];

$resp = $nfe->cria($payload);

if ($resp->sucesso) {
    echo "NFe emitida! Chave: " . $resp->chave;
} else {
    echo "Erro: " . $resp->mensagem;
}
```

### Emitir NF-e - Opção 2: Dados Estruturados (NOVO! 🎉)

**Agora você pode emitir NF-e usando dados estruturados, sem precisar gerar XML manualmente!**

O SDK converte automaticamente os dados estruturados para XML válido:

```php
<?php
require_once("bootstrap.php");
use CashNFe\SdkPHP\Nfe;

$configSDK = [
    "token" => "seu_token",
    "ambiente" => 2,
    "base_url" => "https://hom-api.nf26.cloud" // URL para homologação
];

$nfe = new Nfe($configSDK);

// Dados estruturados (formato CloudDFE)
$payload = [
    "natureza_operacao" => "VENDA PRODUCAO DO ESTABELECIMENTO",
    "numero" => 123,
    "serie" => "1",
    "data_emissao" => date('Y-m-d\TH:i:sP'),
    "tipo_operacao" => 1,
    "finalidade_emissao" => 1,
    "consumidor_final" => 0,
    "presenca_comprador" => 1,
    
    "emitente" => [
        "CNPJ" => "12345678000190",
        "xNome" => "EMPRESA EXEMPLO LTDA",
        "xFant" => "NOME FANTASIA",
        "IE" => "123456789012",
        "CRT" => "1",
        "enderEmit" => [
            "xLgr" => "RUA EXEMPLO",
            "nro" => "123",
            "xBairro" => "CENTRO",
            "cMun" => "3550308",
            "xMun" => "SAO PAULO",
            "UF" => "SP",
            "CEP" => "01000000",
            "cPais" => "1058",
            "fone" => "1133334444"
        ]
    ],
    
    "destinatario" => [
        "CNPJ" => "99999999000191",
        "xNome" => "CLIENTE EXEMPLO LTDA",
        "indIEDest" => "9",
        "enderDest" => [
            "xLgr" => "RUA TESTE",
            "nro" => "456",
            "xBairro" => "CENTRO",
            "cMun" => "3550308",
            "xMun" => "SAO PAULO",
            "UF" => "SP",
            "CEP" => "01000000",
            "cPais" => "1058"
        ]
    ],
    
    "itens" => [
        [
            "cProd" => "0001",
            "xProd" => "PRODUTO EXEMPLO",
            "NCM" => "84713012",
            "CFOP" => "5102",
            "uCom" => "UN",
            "qCom" => "2.0000",
            "vUnCom" => "150.00",
            "vProd" => "300.00",
            "uTrib" => "UN",
            "qTrib" => "2.0000",
            "vUnTrib" => "150.00",
            "indTot" => "1"
        ]
    ],
    
    "frete" => [
        "modFrete" => "0",
        "vFrete" => "0.00"
    ],
    
    "pagamento" => [
        "indPag" => "0",
        "tPag" => "01",
        "vPag" => "300.00"
    ],
    
    "cnpjCertificado" => "12345678000190"
];

// O SDK converte automaticamente para XML!
$resp = $nfe->cria($payload);

if ($resp->sucesso) {
    echo "✅ NFe emitida! Chave: " . $resp->chave;
} else {
    echo "❌ Erro: " . $resp->mensagem;
}
```

**Vantagens de usar dados estruturados:**
- ✅ **Mais fácil**: Não precisa gerar XML manualmente
- ✅ **Mais seguro**: Validação automática de campos
- ✅ **Menos erros**: SDK calcula valores e totais automaticamente
- ✅ **Compatível**: Mesmo formato do CloudDFE SDK

Veja o exemplo completo em: `examples/nfe/emitir-dados-estruturados.php`

### Consultar NF-e

```php
$payload = [
    "chave" => "3512345678901234555001000000511...",
    "cnpjCertificado" => "12345678000190"
];

$resp = $nfe->consulta($payload);

if ($resp->sucesso) {
    echo "Status: " . $resp->status;
}
```

## 🔄 Compatibilidade com CloudDFE SDK

| Método CloudDFE | Método NF26 | Status |
|----------------|----------------|--------|
| `$nfe->status()` | `$nfe->status()` | ✅ Compatível |
| `$nfe->cria($payload)` | `$nfe->cria($payload)` | ✅ Compatível |
| `$nfe->consulta($payload)` | `$nfe->consulta($payload)` | ✅ Compatível |
| `$nfe->cancela($payload)` | ⏳ Em desenvolvimento | 🔄 Em breve |
| `$nfe->cartaCorrecao($payload)` | ⏳ Em desenvolvimento | 🔄 Em breve |

## 📚 Documentação

- [Exemplos completos](examples/)
- [API NF26 Documentation](https://nf26.cloud/docs)
- [Guia de Migração](docs/MIGRACAO.md)

## 🤝 Suporte

- Email: suporte@nf26.cloud
- Site: https://nf26.cloud
- Documentação: https://nf26.cloud/docs
- Homologação: https://hom-api.nf26.cloud

## 📄 Licença

MIT License

## 🆚 Diferenças do CloudDFE SDK

### Formatos de Payload

**CloudDFE SDK:**
- Aceita payload estruturado (array PHP)
- Converte automaticamente para XML

**NF26 SDK:**
- ✅ Aceita XML diretamente no campo `xml`
- ✅ Aceita payload estruturado (array PHP) - **NOVO!**
- ✅ Converte automaticamente payload estruturado para XML

**Compatibilidade:**
O SDK NF26 agora é **100% compatível** com CloudDFE SDK! Você pode:
1. Migrar apenas mudando a `base_url`
2. Usar o mesmo formato de payload estruturado
3. Ou continuar usando XML diretamente se preferir

### Endpoints

**CloudDFE:**
- Produção: `https://api.integranotas.com.br`
- Homologação: `https://hom-api.integranotas.com.br`

**NF26:**
- Produção: `https://api.nf26.cloud`
- Homologação: `https://hom-api.nf26.cloud` (ambiente de testes, sem valor fiscal)

### Autenticação

Ambos usam Bearer Token no header `Authorization`.

## 🔐 Segurança e Boas Práticas

O SDK implementa várias melhorias de segurança e boas práticas de programação:

### Validações Implementadas

- ✅ **Validação de CNPJ:** Validação centralizada e reutilizável
- ✅ **Validação de Chave de Acesso:** Extração robusta usando DOMDocument e XPath
- ✅ **Tratamento de Exceções:** Separação entre `InvalidArgumentException` e `Exception`
- ✅ **Códigos de Erro Padronizados:** Constantes para facilitar tratamento de erros
- ✅ **Type Hints:** Tipagem forte em todos os métodos públicos
- ✅ **Normalização de Resposta:** Respostas sempre no mesmo formato

### Códigos de Erro Padronizados

O SDK define constantes para códigos de erro:

```php
use CashNFe\SdkPHP\Nfe;

// Constantes de erro disponíveis
Nfe::ERRO_GERAL                    // 5000 - Erro geral
Nfe::ERRO_CNPJ_OBRIGATORIO         // 5001 - CNPJ obrigatório
Nfe::ERRO_VALIDACAO                // 5002 - Erro de validação
Nfe::ERRO_CHAVE_OBRIGATORIA        // 5001 - Chave obrigatória
Nfe::ERRO_PARAMETROS_INSUFICIENTES // 400 - Parâmetros insuficientes
```

### Exemplo de Tratamento de Erros

```php
try {
    $resp = $nfe->cria($payload);
    
    if ($resp->sucesso) {
        echo "✅ NF-e emitida! Chave: " . $resp->chave;
    } else {
        // Verificar código de erro
        switch ($resp->codigo) {
            case Nfe::ERRO_CNPJ_OBRIGATORIO:
                echo "❌ CNPJ do certificado é obrigatório";
                break;
            case Nfe::ERRO_VALIDACAO:
                echo "❌ Erro de validação: " . $resp->mensagem;
                if (isset($resp->erros)) {
                    foreach ($resp->erros as $erro) {
                        echo "\n  - " . $erro;
                    }
                }
                break;
            default:
                echo "❌ Erro: " . $resp->mensagem;
        }
    }
} catch (InvalidArgumentException $e) {
    // Erro de validação de parâmetros
    echo "❌ Parâmetros inválidos: " . $e->getMessage();
} catch (Exception $e) {
    // Erro geral
    echo "❌ Erro: " . $e->getMessage();
}
```

### Validação de CNPJ

O SDK valida automaticamente o CNPJ do certificado:

```php
// O SDK valida automaticamente se o CNPJ está presente
$payload = [
    "xml" => "...",
    // cnpjCertificado é obrigatório e será validado
];

$resp = $nfe->cria($payload);
// Se CNPJ estiver ausente ou inválido, retorna erro com código ERRO_CNPJ_OBRIGATORIO
```

### Extração de Chave de Acesso

O SDK extrai a chave de acesso do XML de forma robusta:

```php
// Usa DOMDocument e XPath (mais robusto)
// Fallback para regex se necessário
$chave = $nfe->extractChaveAcesso($xml);
```

### Type Hints

Todos os métodos públicos têm type hints:

```php
public function cria(array $payload): object
public function consulta(array $payload): object
public function status(): object
public function gerarDanfe(array $payload): object
```

Isso garante:
- ✅ Melhor autocomplete em IDEs
- ✅ Detecção de erros em tempo de desenvolvimento
- ✅ Documentação automática melhorada

### Modo Debug

Ative o modo debug para ver detalhes das requisições:

```php
$config = [
    "token" => "seu_token",
    "ambiente" => 2,
    "base_url" => "https://hom-api.nf26.cloud",
    "options" => [
        "debug" => true  // Ativa logs detalhados
    ]
];
```

**⚠️ Atenção:** Não use `debug => true` em produção!

## 📊 Changelog

### Versão 1.1.0 (Nov/2025)

**Melhorias de Código:**
- ✅ Adicionadas constantes de erro padronizadas
- ✅ Validação centralizada de CNPJ
- ✅ Normalização de resposta padronizada
- ✅ Melhor tratamento de exceções (InvalidArgumentException vs Exception)
- ✅ Type hints em todos os métodos públicos
- ✅ Extração robusta de chave de acesso (DOMDocument + XPath)
- ✅ Uso de `str_contains()` com fallback para PHP < 8.0

**Segurança:**
- ✅ Validações mais rigorosas de entrada
- ✅ Mensagens de erro mais seguras (não expõem detalhes internos)

**Compatibilidade:**
- ✅ Mantém 100% de compatibilidade com versões anteriores
- ✅ Mesma interface pública
- ✅ Mesmos formatos de resposta

