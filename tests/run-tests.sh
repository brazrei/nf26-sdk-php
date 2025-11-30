#!/bin/bash

# Script para executar todos os testes do SDK NF26 PHP

echo "🧪 Executando Testes do SDK NF26 PHP"
echo "========================================"
echo ""

# Verificar se composer está instalado
if ! command -v composer &> /dev/null; then
    echo "❌ Composer não encontrado. Instale o Composer primeiro."
    exit 1
fi

# Verificar se dependências estão instaladas
if [ ! -d "vendor" ]; then
    echo "📦 Instalando dependências..."
    composer install
    echo ""
fi

# Verificar se PHP está disponível
if ! command -v php &> /dev/null; then
    echo "❌ PHP não encontrado. Instale o PHP >= 7.4."
    exit 1
fi

echo "📋 Verificando configuração..."
echo ""

# Verificar variáveis de ambiente
if [ -z "$CASHNFE_TOKEN" ]; then
    echo "⚠️  CASHNFE_TOKEN não configurado"
    echo "   Configure: export CASHNFE_TOKEN='seu_token'"
    echo ""
fi

if [ -z "$CASHNFE_BASE_URL" ]; then
    echo "ℹ️  Usando CASHNFE_BASE_URL padrão: https://nf26.cloud"
    echo ""
fi

# Executar testes
echo "========================================"
echo "1️⃣  TESTE SIMPLES"
echo "========================================"
echo ""

php tests/test-integracao-simples.php

SIMPLE_TEST_EXIT=$?

echo ""
echo "========================================"
echo "2️⃣  TESTE COMPLETO"
echo "========================================"
echo ""

php tests/test-nfe-completo.php

COMPLETE_TEST_EXIT=$?

echo ""
echo "========================================"
echo "📊 RESUMO FINAL"
echo "========================================"
echo ""

if [ $SIMPLE_TEST_EXIT -eq 0 ] && [ $COMPLETE_TEST_EXIT -eq 0 ]; then
    echo "✅ Todos os testes passaram!"
    exit 0
else
    echo "❌ Alguns testes falharam"
    echo ""
    echo "Teste Simples: " $([ $SIMPLE_TEST_EXIT -eq 0 ] && echo "✅ Passou" || echo "❌ Falhou")
    echo "Teste Completo: " $([ $COMPLETE_TEST_EXIT -eq 0 ] && echo "✅ Passou" || echo "❌ Falhou")
    exit 1
fi

