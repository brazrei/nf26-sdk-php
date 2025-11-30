# 📚 Guia de Uso - SDK NF26 PHP

## Instalação

```bash
composer require nf26/sdk-php
```

## Exemplo Básico

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use CashNFe\SdkPHP\Nfe;

// Configurar SDK
$config = [
    "token" => "seu_token_aqui",
    "ambiente" => 2, // 1=Produção, 2=Homologação
    "base_url" => "https://nf26.cloud"
];

// Instanciar
$nfe = new Nfe($config);

// Emitir NF-e
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

## Configuração

### Parâmetros Obrigatórios

- `token`: Token de autenticação (obrigatório)
- `ambiente`: 1 = Produção, 2 = Homologação

### Parâmetros Opcionais

- `base_url`: URL base da API (padrão: `https://nf26.cloud`)
- `options`: Opções avançadas
  - `debug`: Ativar modo debug (padrão: `false`)
  - `timeout`: Timeout em segundos (padrão: `60`)

### Exemplo Completo de Configuração

```php
$config = [
    "token" => "eyJhbGciOiJSUzI1NiIs...",
    "ambiente" => 2,
    "base_url" => "https://nf26.cloud",
    "options" => [
        "debug" => false,
        "timeout" => 120
    ]
];
```

## Métodos Disponíveis

### `cria($payload)` - Emitir NF-e

```php
$payload = [
    "xml" => "<?xml version='1.0'?><NFe>...</NFe>",
    "cnpjCertificado" => "12345678000190"
];

$resp = $nfe->cria($payload);

// Resposta de sucesso
if ($resp->sucesso) {
    $chave = $resp->chave;          // Chave de acesso
    $protocolo = $resp->protocolo;   // Protocolo de autorização
}

// Resposta de erro
if (!$resp->sucesso) {
    $codigo = $resp->codigo;        // Código do erro
    $mensagem = $resp->mensagem;     // Mensagem de erro
    $erros = $resp->erros;          // Array de erros detalhados
}
```

### `consulta($payload)` - Consultar NF-e

```php
$payload = [
    "chave" => "3512345678901234555001000000511...",
    "cnpjCertificado" => "12345678000190"
];

$resp = $nfe->consulta($payload);

if ($resp->sucesso) {
    $status = $resp->status;              // Status da NF-e
    $protocolo = $resp->protocolo;         // Protocolo
    $dataAutorizacao = $resp->data_autorizacao; // Data de autorização
}
```

## Tratamento de Erros

### Códigos de Erro

- `5001`: Campo obrigatório não informado
- `5002`: Erro de validação de dados
- `401`: Não autorizado (token inválido)
- `500`: Erro interno do servidor

### Exemplo de Tratamento

```php
$resp = $nfe->cria($payload);

if ($resp->sucesso) {
    // Sucesso
    echo "NF-e emitida: " . $resp->chave;
} elseif (in_array($resp->codigo, [5001, 5002])) {
    // Erro de validação
    echo "Erro de validação:\n";
    foreach ($resp->erros as $erro) {
        echo "- " . $erro . "\n";
    }
} else {
    // Outro erro
    echo "Erro: " . $resp->mensagem . " (Código: " . $resp->codigo . ")";
}
```

## Migração do CloudDFE SDK

Para migrar do CloudDFE SDK, apenas mude a `base_url`:

```php
// ANTES (CloudDFE)
$config = [
    "token" => "...",
    "ambiente" => 2
    // base_url padrão: https://api.integranotas.com.br
];

// DEPOIS (NF26) - apenas adicione base_url!
$config = [
    "token" => "...",
    "ambiente" => 2,
    "base_url" => "https://nf26.cloud" // ← Única mudança!
];
```

O resto do código permanece **exatamente igual**!

## Exemplos Avançados

### Usar Variáveis de Ambiente

```php
$config = [
    "token" => getenv('CASHNFE_TOKEN'),
    "ambiente" => (int)getenv('CASHNFE_AMBIENTE') ?: 2,
    "base_url" => getenv('CASHNFE_BASE_URL') ?: 'https://nf26.cloud'
];
```

### Modo Debug

```php
$config = [
    "token" => "...",
    "ambiente" => 2,
    "options" => [
        "debug" => true // Mostra requisições HTTP detalhadas
    ]
];
```

### Timeout Personalizado

```php
$config = [
    "token" => "...",
    "ambiente" => 2,
    "options" => [
        "timeout" => 300 // 5 minutos
    ]
];
```

## Suporte

- 📧 Email: suporte@nf26.cloud
- 📖 Documentação: https://nf26.cloud/docs
- 🐛 Issues: https://github.com/brazrei/nf26-sdk-php/issues

