# 🧪 Testes do SDK NF26 PHP

## Testes Disponíveis

### 1. Teste Simples (`test-integracao-simples.php`)

Teste básico para validar conexão e configuração do SDK.

```bash
php tests/test-integracao-simples.php
```

**O que testa:**
- ✅ Instanciação do SDK
- ✅ Validação de parâmetros obrigatórios
- ✅ Formato de resposta
- ✅ Conexão com API (se token configurado)

### 2. Teste Completo (`test-nfe-completo.php`)

Suite completa de testes com validações detalhadas.

```bash
php tests/test-nfe-completo.php
```

**O que testa:**
- ✅ Instanciação do SDK
- ✅ Validação de parâmetros
- ✅ Formato de resposta
- ✅ Tratamento de erros HTTP
- ✅ Endpoints por ambiente
- ✅ Integração real com API
- ✅ Extração de chave de acesso

## Configuração

### Via Variáveis de Ambiente (Recomendado)

```bash
export CASHNFE_TOKEN="seu_token_aqui"
export CASHNFE_BASE_URL="https://nf26.cloud"
export CASHNFE_AMBIENTE="2"  # 1=Produção, 2=Homologação

php tests/test-integracao-simples.php
```

### Via Edição do Arquivo

Edite os arquivos de teste e coloque os valores diretamente:

```php
$token = "seu_token_aqui";
$baseUrl = "https://nf26.cloud";
$ambiente = 2; // 1=Produção, 2=Homologação
```

## Executar Todos os Testes

```bash
# Teste simples
php tests/test-integracao-simples.php

# Teste completo
php tests/test-nfe-completo.php
```

## Interpretando Resultados

### ✅ Teste Passou
```
✅ Passou: Nome do teste
```

### ❌ Teste Falhou
```
❌ Falhou: Nome do teste
```

### Resumo Final
```
Testes passados: X
Testes falhados: Y
Taxa de sucesso: Z%
```

## Requisitos

- PHP >= 7.4
- Composer instalado
- Dependências instaladas: `composer install`
- Token válido (para testes de integração real)

## Troubleshooting

### Erro: "Class not found"
```bash
composer install
```

### Erro: "Token não configurado"
Configure a variável de ambiente ou edite o arquivo de teste.

### Erro: "Connection refused"
- Verifique se a URL base está correta
- Verifique conexão com internet
- Verifique se o servidor está acessível

### Erro 401 (Unauthorized)
- Verifique se o token está correto
- Verifique se o token não expirou
- Verifique se o token tem permissões adequadas

