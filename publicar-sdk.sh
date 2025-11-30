#!/bin/bash
# Script para publicar apenas o SDK PHP em repositório Git público
# Sem expor o resto do projeto CashNFe

set -e  # Parar em caso de erro

# Cores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${CYAN}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║   Publicar SDK PHP NF26 - Repositório Público        ║${NC}"
echo -e "${CYAN}╚═══════════════════════════════════════════════════════════╝${NC}"
echo ""

# Configurações
SDK_DIR="$(cd "$(dirname "$0")" && pwd)"
TEMP_DIR="/tmp/nf26-sdk-php-public"
GITHUB_REPO="${1:-}"
SSH_KEY="$HOME/.ssh/nf26_sdk_public"

if [ -z "$GITHUB_REPO" ]; then
    echo -e "${YELLOW}⚠️  Uso: $0 <URL_DO_REPOSITORIO_GITHUB>${NC}"
    echo ""
    echo -e "Exemplo:"
    echo -e "  $0 https://github.com/brazrei/nf26-sdk-php.git"
    echo ""
    exit 1
fi

echo -e "${BLUE}📁 Diretório do SDK:${NC} $SDK_DIR"
echo -e "${BLUE}📁 Diretório temporário:${NC} $TEMP_DIR"
echo -e "${BLUE}🔗 Repositório GitHub:${NC} $GITHUB_REPO"
echo -e "${BLUE}🔑 Chave SSH:${NC} $SSH_KEY"
echo ""

# Verificar se a chave SSH existe
if [ ! -f "$SSH_KEY" ]; then
    echo -e "${YELLOW}⚠️  Chave SSH não encontrada: $SSH_KEY${NC}"
    echo -e "${YELLOW}   Será usada a chave SSH padrão do sistema${NC}"
    echo ""
else
    # Configurar Git para usar a chave SSH específica
    export GIT_SSH_COMMAND="ssh -i $SSH_KEY -o IdentitiesOnly=yes"
    echo -e "${GREEN}✅ Chave SSH configurada para publicação${NC}"
    echo ""
fi

# Verificar se está no diretório correto
if [ ! -f "$SDK_DIR/composer.json" ]; then
    echo -e "${RED}❌ Erro: composer.json não encontrado!${NC}"
    echo -e "${YELLOW}   Certifique-se de executar este script do diretório sdk-php${NC}"
    exit 1
fi

echo -e "${BLUE}🧹 Limpando diretório temporário...${NC}"
rm -rf "$TEMP_DIR"
mkdir -p "$TEMP_DIR"

echo -e "${BLUE}📋 Copiando arquivos (excluindo arquivos sensíveis)...${NC}"

# Copiar arquivos usando rsync (melhor controle)
cd "$SDK_DIR"
rsync -av \
  --exclude='.git' \
  --exclude='.token' \
  --exclude='vendor/' \
  --exclude='composer.lock' \
  --exclude='test-nfe-*.xml' \
  --exclude='DANFE*.pdf' \
  --exclude='*.log' \
  --exclude='.DS_Store' \
  --exclude='.env' \
  --exclude='.idea/' \
  --exclude='.vscode/' \
  --exclude='emitir-teste-completo.php' \
  --exclude='gerar-danfe.php' \
  --exclude='README-TESTE.md' \
  . "$TEMP_DIR/"

echo -e "${GREEN}✅ Arquivos copiados${NC}"

# Ir para diretório temporário
cd "$TEMP_DIR"

# Verificar se já é um repositório Git
if [ ! -d ".git" ]; then
    echo -e "${BLUE}🔧 Inicializando repositório Git...${NC}"
    git init
    git branch -M main
    
    # Adicionar todos os arquivos
    git add .
    
    echo -e "${BLUE}📝 Criando commit inicial...${NC}"
    git commit -m "Initial commit: NF26 SDK PHP v1.0.0"
    echo -e "${GREEN}✅ Repositório inicializado${NC}"
else
    echo -e "${BLUE}🔄 Atualizando repositório existente...${NC}"
    git add .
    
    # Verificar se há mudanças
    if git diff --staged --quiet; then
        echo -e "${YELLOW}⚠️  Nenhuma mudança detectada${NC}"
    else
        git commit -m "Update: $(date '+%Y-%m-%d %H:%M:%S')" || true
        echo -e "${GREEN}✅ Mudanças commitadas${NC}"
    fi
fi

# Configurar remote
if ! git remote | grep -q origin; then
    echo -e "${BLUE}🔗 Adicionando remote origin...${NC}"
    git remote add origin "$GITHUB_REPO"
else
    echo -e "${BLUE}🔄 Atualizando remote origin...${NC}"
    git remote set-url origin "$GITHUB_REPO"
fi

echo ""
echo -e "${CYAN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Preparação concluída!${NC}"
echo ""
echo -e "${YELLOW}⚠️  REVISAR ANTES DE FAZER PUSH:${NC}"
echo ""
echo -e "1. Verifique os arquivos que serão commitados:"
echo -e "   ${BLUE}cd $TEMP_DIR && git status${NC}"
echo ""
echo -e "2. Verifique os arquivos que serão enviados:"
echo -e "   ${BLUE}cd $TEMP_DIR && git log --oneline${NC}"
echo ""
echo -e "3. Quando estiver pronto, faça push:"
echo -e "   ${BLUE}cd $TEMP_DIR && git push -u origin main${NC}"
echo ""
echo -e "${CYAN}═══════════════════════════════════════════════════════════${NC}"

