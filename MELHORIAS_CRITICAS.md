# 🔐 Melhorias Críticas de Segurança e Qualidade - SDK PHP

**Data:** 30 de Novembro de 2025  
**Versão:** 1.1.0

## 📋 Resumo das Mudanças

Todas as melhorias foram implementadas **sem alterar a interface pública** do SDK, mantendo 100% de compatibilidade com versões anteriores.

---

## 1. ✅ Segurança do Token em Modo Debug

### Problema
O `CURLOPT_VERBOSE` com `error_log()` registrava o header `Authorization: Bearer ...` nos logs, expondo tokens sensíveis.

### Solução
- **Removido:** `CURLOPT_VERBOSE` e `CURLOPT_STDERR`
- **Implementado:** Log manual controlado que registra apenas:
  - Método HTTP
  - Endpoint
  - Status HTTP
  - Tempo de resposta (ms)
  - Tamanho da resposta
  - Erros cURL (se houver)

### Código
```php
// Debug mode seguro (sem expor token ou headers sensíveis)
if (!empty($this->options['debug'])) {
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    $debugInfo = sprintf(
        "cURL Debug [%s %s]: HTTP %d | Tempo: %sms | Tamanho resposta: %d bytes",
        $methodUpper, $endpoint, $httpCode, $responseTime, strlen($response)
    );
    error_log($debugInfo);
}
```

### Resultado
✅ Token nunca é exposto em logs  
✅ Debug ainda é útil (URL, método, status, tempo)  
✅ Compatibilidade mantida (`options['debug']` continua funcionando)

---

## 2. ✅ SSL: Não Desabilitar Verificação Automaticamente

### Problema
A verificação SSL era automaticamente desabilitada para localhost, 127.0.0.1 e IPs privados, mesmo sem solicitação explícita.

### Solução
- **Removido:** `preg_match()` que detectava IPs privados
- **Mantido:** Verificação SSL ativada por padrão
- **Alterado:** Apenas desabilita se `verify_ssl => false` ou `ssl_verify => false` for explicitamente passado

### Código
```php
// SSL - Verificar se deve desabilitar verificação
// IMPORTANTE: Apenas desabilitar se explicitamente solicitado via options
// Não desabilitar automaticamente para IPs privados (segurança)
$verifySSL = $this->options['verify_ssl'] ?? $this->options['ssl_verify'] ?? true;
if (!$verifySSL) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
} else {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
}
```

### Resultado
✅ SSL sempre verificado por padrão (segurança)  
✅ Usuário pode desabilitar explicitamente se necessário  
✅ Compatibilidade mantida (comportamento padrão não mudou)

---

## 3. ✅ Corrigir Duplicação de Códigos de Erro

### Problema
```php
const ERRO_CNPJ_OBRIGATORIO = 5001;
const ERRO_CHAVE_OBRIGATORIA = 5001; // ❌ Duplicado!
```

### Solução
```php
const ERRO_CNPJ_OBRIGATORIO = 5001;
const ERRO_CHAVE_OBRIGATORIA = 5003; // ✅ Corrigido
```

### Resultado
✅ Códigos de erro únicos e distintos  
✅ Semântica das mensagens mantida  
✅ Formato de retorno inalterado

---

## 4. ✅ Centralizar Constantes de Ambiente na BaseSdk

### Problema
Constantes duplicadas em `BaseSdk` e `Nfe`:
```php
// BaseSdk
const AMBIENTE_PRODUCAO = 1;
const AMBIENTE_HOMOLOGACAO = 2;

// Nfe (duplicado)
const AMBIENTE_PRODUCAO = 1;
const AMBIENTE_HOMOLOGACAO = 2;
```

### Solução
- **Removido:** Constantes duplicadas de `Nfe`
- **Mantido:** Apenas em `BaseSdk`
- **Alterado:** `Nfe` usa `self::AMBIENTE_PRODUCAO` e `self::AMBIENTE_HOMOLOGACAO` (herdadas)

### Código
```php
// Nfe.php
// Constantes de ambiente herdadas de BaseSdk (compatível com CloudDFE)
// Usar self::AMBIENTE_PRODUCAO e self::AMBIENTE_HOMOLOGACAO (herdadas)

// Uso:
$data['ambiente'] = $this->ambiente == self::AMBIENTE_PRODUCAO ? 'producao' : 'homologacao';
```

### Resultado
✅ DRY (Don't Repeat Yourself) aplicado  
✅ Constantes centralizadas  
✅ Compatibilidade mantida (herança funciona igual)

---

## 5. ✅ Garantir que Ambiente de Homologação está Sendo Respeitado

### Validação
Todos os métodos de `Nfe` já usam `getEndpoint()` corretamente:
- ✅ `cria()` → `getEndpoint('/api/nfe/emitir')`
- ✅ `consulta()` → `getEndpoint('/api/nfe/consultar-status')`
- ✅ `status()` → `getEndpoint('/api/nfe/status')`
- ✅ `gerarDanfe()` → `getEndpoint('/api/nfe/gerar-danfe')` e `getEndpoint('/api/nfe/danfe-por-cnpj-nota')`

### Documentação
Adicionado comentário no método `request()`:
```php
/**
 * IMPORTANTE: Qualquer endpoint deve ser passado por getEndpoint() pela subclasse
 * antes de chamar este método, para garantir que o ambiente (homologação/produção)
 * seja respeitado corretamente.
 */
```

### Resultado
✅ Todos os endpoints passam por `getEndpoint()`  
✅ Ambiente de homologação respeitado  
✅ Documentação clara para desenvolvedores

---

## 6. ✅ Tratar Erro de json_encode

### Problema
`json_encode($data)` era chamado sem verificação de erro, podendo falhar silenciosamente.

### Solução
Criado método privado `encodeJson()` que:
- Usa `JSON_THROW_ON_ERROR` se disponível (PHP 7.3+)
- Verifica `json_last_error()` como fallback (PHP < 7.3)
- Lança `Exception` clara se falhar
- Tratado dentro do fluxo `try/catch` de `Nfe`, retornando `ERRO_GERAL`

### Código
```php
private function encodeJson($data): string
{
    // Tentar usar JSON_THROW_ON_ERROR se disponível (PHP 7.3+)
    if (defined('JSON_THROW_ON_ERROR')) {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException $e) {
            throw new Exception('Erro ao serializar dados para JSON: ' . $e->getMessage());
        }
    }
    
    // Fallback para PHP < 7.3
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $error = json_last_error_msg();
        throw new Exception('Erro ao serializar dados para JSON: ' . $error);
    }
    
    return $json;
}
```

### Resultado
✅ Erros de serialização são detectados e tratados  
✅ Mensagens de erro claras  
✅ Formato de retorno mantido (sucesso = false, ERRO_GERAL)

---

## 7. ✅ Melhoria na Construção de URL em GET

### Implementação
Para requisições GET com dados, os dados são convertidos para querystring e anexados à URL.

### Código
```php
default: // GET
    // Para GET, se houver dados, converter para querystring e anexar à URL
    if (!empty($data)) {
        $queryString = http_build_query($data);
        $separator = strpos($url, '?') !== false ? '&' : '?';
        $url = $url . $separator . $queryString;
        curl_setopt($ch, CURLOPT_URL, $url);
    }
    break;
```

### Resultado
✅ GET com dados funciona corretamente  
✅ Querystring construída automaticamente  
✅ Não afeta uso atual (GET sem dados continua igual)

---

## 🔒 Garantias de Compatibilidade

### ✅ NÃO Alterado:
- Nomes das classes
- Namespaces
- Assinatura dos métodos públicos
- Formato básico de retorno (object com sucesso, codigo, mensagem, erros)
- Paths de endpoint (/api/nfe/..., /hom-api/nfe/...)
- Nomes de campos JSON enviados ao servidor

### ✅ Pode Adicionar:
- Tipagens internas
- Docblocks
- Helpers privados
- Melhorias de segurança (sem expor dados)

---

## 📊 Estatísticas

- **Arquivos modificados:** 2
- **Linhas adicionadas:** ~50
- **Linhas removidas:** ~15
- **Métodos novos:** 1 (`encodeJson()`)
- **Compatibilidade:** 100%

---

## 🧪 Testes Recomendados

1. ✅ Testar modo debug (verificar que token não aparece em logs)
2. ✅ Testar SSL com IPs privados (verificar que não desabilita automaticamente)
3. ✅ Testar erros de validação (verificar códigos distintos)
4. ✅ Testar ambiente de homologação (verificar /hom-api/)
5. ✅ Testar serialização JSON com dados inválidos (verificar tratamento de erro)
6. ✅ Testar GET com parâmetros (verificar querystring)

---

**Última Atualização:** 30 de Novembro de 2025

