# 🚀 Guia Rápido - Testes do SDK

## Executar Testes Rápidos

### Opção 1: Script Automático (Recomendado)

```bash
cd sdk-php
./tests/run-tests.sh
```

### Opção 2: Teste Simples Manual

```bash
cd sdk-php
export CASHNFE_TOKEN="seu_token"
php tests/test-integracao-simples.php
```

### Opção 3: Teste Completo Manual

```bash
cd sdk-php
export CASHNFE_TOKEN="seu_token"
export CASHNFE_BASE_URL="https://nf26.cloud"
export CASHNFE_AMBIENTE="2"
php tests/test-nfe-completo.php
```

## Exemplo de Execução

```bash
# 1. Instalar dependências (primeira vez)
cd sdk-php
composer install

# 2. Configurar token
export CASHNFE_TOKEN="eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..."

# 3. Executar teste simples
php tests/test-integracao-simples.php

# Ou executar todos os testes
./tests/run-tests.sh
```

## Saída Esperada

```
🧪 Teste de Integração Simples - SDK NF26 PHP
============================================================

📋 Configuração:
   Base URL: https://nf26.cloud
   Ambiente: Homologação
   Token: eyJhbGciOi...

1️⃣  Instanciando SDK...
   ✅ SDK instanciado com sucesso!

2️⃣  Testando validação de parâmetros...
   ✅ Validação funcionando (CNPJ obrigatório detectado)
      Código: 5001
      Mensagem: CNPJ do certificado não informado

...
```

## Resultado de Sucesso

```
============================================================
📊 RESUMO FINAL
============================================================

✅ Testes passados: 8
❌ Testes falhados: 0
Total: 8

Taxa de sucesso: 100.00%

✅ Todos os testes passaram! 🎉
```

