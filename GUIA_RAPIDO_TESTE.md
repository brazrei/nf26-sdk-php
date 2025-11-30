# 🚀 Guia Rápido - Testar SDK Antes de Publicar

## Pré-requisitos

1. **PHP >= 7.4** instalado
2. **Composer** instalado
3. **Token de autenticação** válido
4. **Certificado A1** cadastrado no sistema

## Passo a Passo

### 1. Instalar Composer (se necessário)

```bash
# Ubuntu/Debian
sudo apt install composer

# Ou baixar direto: https://getcomposer.org/download/
```

### 2. Instalar Dependências do SDK

```bash
cd sdk-php
composer install
```

### 3. Obter Token de Autenticação

1. Acesse o painel: https://nf26.cloud/login
2. Faça login
3. Obtenha o token de autenticação (Bearer Token)

### 4. Configurar Variáveis de Ambiente

```bash
export CASHNFE_TOKEN="seu_token_jwt_aqui"
export CASHNFE_CNPJ="12345678000190"  # CNPJ do certificado cadastrado (substitua pelo seu)
export CASHNFE_BASE_URL="https://nf26.cloud"
export CASHNFE_AMBIENTE="2"  # 1=Produção, 2=Homologação
```

### 5. Executar Teste de Emissão

```bash
php examples/nfe/emitir-teste-completo.php
```

## O que o Script Faz

1. ✅ Gera XML de NF-e válido para homologação
2. ✅ Configura SDK com suas credenciais
3. ✅ Envia nota via método `cria()` do SDK
4. ✅ Mostra resultado (sucesso ou erro)

## Resultado Esperado

### ✅ Sucesso

```
✅ NF-e EMITIDA COM SUCESSO! 🎉

📋 Informações da Nota:
   Chave de Acesso: 35123456789012345550010000000011234567890
   Protocolo: 135240001234567
   Status: AUTORIZADA

✅ Teste do SDK concluído com sucesso!
   O SDK está funcionando corretamente.
   Você pode publicar no Packagist com confiança.
```

### ❌ Erro

Se der erro, o script mostrará detalhes:
- Código do erro
- Mensagem de erro
- Sugestões de correção

## Troubleshooting

### Composer não encontrado

```bash
sudo apt install composer
# ou
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Erro 401 (Token inválido)

- Verifique se o token está correto
- Faça login novamente para obter novo token
- Verifique se o token não expirou

### Erro de conexão

- Verifique se a URL base está correta
- Verifique se a API está acessível
- Teste: `curl https://nf26.cloud/health`

## Após Teste Bem-sucedido

Se a emissão funcionou:

✅ SDK está funcionando corretamente  
✅ Pronto para publicar no Packagist  
✅ Veja: `docs/PUBLICAR_SDK_PACKAGIST.md`

## Teste Alternativo (Sem Composer Local)

Se não quiser instalar Composer localmente, você pode testar diretamente na API:

```bash
# Gerar XML manualmente
# Enviar via curl
curl -X POST https://nf26.cloud/hom-api/nfe/emitir \
  -H "Authorization: Bearer seu_token" \
  -H "Content-Type: application/json" \
  -d '{
    "xml": "<?xml version=\"1.0\"?><NFe>...</NFe>",
    "cnpjCertificado": "12345678000190"
  }'
```

Mas o teste via SDK é mais completo e valida toda a integração!

