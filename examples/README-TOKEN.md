# 🔑 Como Obter Token de Autenticação

## ⚠️ IMPORTANTE

**O token NÃO existe por padrão no sistema.** Você **deve obter** fazendo login primeiro!

## 🚀 Método Rápido: Script Automático

```bash
cd sdk-php
php examples/obter-token.php
```

O script:
1. ✅ Faz login na API
2. ✅ Obtém token JWT
3. ✅ Salva em arquivo `.token`
4. ✅ Mostra comandos para usar

## 📋 Configuração de Credenciais

Configure via variáveis de ambiente ou edite o script:

```bash
export CASHNFE_USERNAME="seu_usuario"
export CASHNFE_PASSWORD="sua_senha"
export CASHNFE_EMPRESA="12345678000190"  # CNPJ ou ID da empresa
```

⚠️ **NUNCA** use credenciais padrão em produção!

## 💻 Método Manual via cURL

```bash
curl -X POST https://nf26.cloud/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "seu_usuario",
    "password": "sua_senha",
    "empresa": "12345678000190"
  }'
```

Resposta esperada:
```json
{
  "access_token": "eyJhbGciOiJSUzI1NiIs...",
  "refresh_token": "...",
  "expires_in": 86400,
  "token_type": "Bearer"
}
```

Copie o `access_token` e use como `CASHNFE_TOKEN`.

## 🌐 Via Frontend Web

1. Acesse: https://nf26.cloud
2. Faça login com suas credenciais
3. Abra DevTools (F12) → Console
4. Execute: `localStorage.getItem('accessToken')`
5. Ou veja a requisição no Network tab

## 📝 Usar o Token

### Variável de Ambiente

```bash
export CASHNFE_TOKEN="eyJhbGciOiJSUzI1NiIs..."
```

### Ou Carregar do Arquivo

```bash
# Após executar obter-token.php
export CASHNFE_TOKEN=$(cat .token)
```

### No Código PHP

```php
$token = file_get_contents(__DIR__ . '/../../.token');
// ou
$token = getenv('CASHNFE_TOKEN');
```

## ⏱️ Validade do Token

- **Access Token**: Expira em 24 horas (86400 segundos)
- **Refresh Token**: Válido por 30 dias

### Renovar Token

```bash
curl -X POST https://nf26.cloud/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{
    "refresh_token": "seu_refresh_token"
  }'
```

## 🔒 Segurança

- ⚠️ **NUNCA** commite tokens no Git
- ⚠️ **NUNCA** compartilhe tokens
- ✅ Use variáveis de ambiente
- ✅ Adicione `.token` no `.gitignore`

## ❓ Token Inválido?

Se receber erro 401:
1. Verifique se o token não expirou (renove se necessário)
2. Faça login novamente
3. Verifique se as credenciais estão corretas
4. Verifique se o usuário tem acesso à empresa

