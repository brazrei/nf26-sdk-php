# 📦 Instalação do SDK NF26 PHP

## Instalação via Composer (Recomendado)

### Passo 1: Instalar Composer (se ainda não tiver)

Baixe e instale o Composer em: https://getcomposer.org/download/

### Passo 2: Instalar o SDK

```bash
composer require nf26/sdk-php
```

### Passo 3: Usar no seu código

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use CashNFe\SdkPHP\Nfe;

$config = [
    "token" => "seu_token",
    "ambiente" => 2, // 1=Produção, 2=Homologação
    "base_url" => "https://nf26.cloud"
];

$nfe = new Nfe($config);
// ... usar o SDK
```

## Instalação Manual (Desenvolvimento)

### Passo 1: Clonar o Repositório

```bash
git clone https://github.com/brazrei/nf26-sdk-php.git
cd nf26-sdk-php
```

### Passo 2: Instalar Dependências

```bash
composer install
```

### Passo 3: Incluir no seu projeto

```php
<?php
require_once '/caminho/para/nf26-sdk-php/bootstrap.php';

use CashNFe\SdkPHP\Nfe;
// ... usar o SDK
```

## Verificar Instalação

```bash
# Verificar se o pacote foi instalado
composer show nf26/sdk-php

# Executar testes
cd sdk-php
php tests/test-integracao-simples.php
```

## Atualizar o SDK

```bash
composer update nf26/sdk-php
```

## Requisitos

- PHP >= 7.4
- Extensões PHP: `json`, `curl`, `openssl`
- Composer (para instalação via Packagist)

## Troubleshooting

### Erro: "Package not found"

1. Verifique se o pacote foi publicado no Packagist
2. Tente limpar cache do Composer: `composer clear-cache`
3. Verifique se está usando o nome correto: `nf26/sdk-php`

### Erro: "Class not found"

Certifique-se de incluir o autoloader do Composer:
```php
require_once __DIR__ . '/vendor/autoload.php';
```

