<?php

namespace CashNFe\SdkPHP;

use Exception;

/**
 * Classe base do SDK NF26
 * Compatível com SDK CloudDFE para facilitar migração
 * 
 * Usa cURL nativo (sem dependências externas), igual ao CloudDFE SDK
 */
class BaseSdk
{
    protected $token;
    protected $ambiente;
    protected $baseUrl;
    protected $options;

    // Constantes de ambiente (compatível com CloudDFE)
    const AMBIENTE_PRODUCAO = 1;
    const AMBIENTE_HOMOLOGACAO = 2;

    /**
     * @param array $config Configuração do SDK
     *   - token: Token de autenticação (obrigatório)
     *   - ambiente: 1 = Produção, 2 = Homologação (padrão: 2)
     *   - base_url: URL base da API (opcional, padrão: https://nf26.cloud)
     *   - options: Opções adicionais (debug, timeout, port, http_version)
     */
    public function __construct(array $config = [])
    {
        if (empty($config['token'])) {
            throw new Exception('Token de autenticação é obrigatório');
        }

        $this->token = $config['token'];
        $this->ambiente = $config['ambiente'] ?? self::AMBIENTE_HOMOLOGACAO;
        $this->baseUrl = $config['base_url'] ?? ($config['baseUrl'] ?? 'https://nf26.cloud');
        
        // Remove barra final se existir
        $this->baseUrl = rtrim($this->baseUrl, '/');
        
        $this->options = $config['options'] ?? [];

        // Verificar se cURL está disponível
        if (!function_exists('curl_init')) {
            throw new Exception('Extensão cURL não está instalada. Instale php-curl.');
        }
    }

    /**
     * Faz requisição HTTP usando cURL nativo (compatível com CloudDFE SDK)
     * 
     * IMPORTANTE: Qualquer endpoint deve ser passado por getEndpoint() pela subclasse
     * antes de chamar este método, para garantir que o ambiente (homologação/produção)
     * seja respeitado corretamente.
     * 
     * @param string $method Método HTTP (GET, POST, PUT, DELETE, PATCH)
     * @param string $endpoint Endpoint da API (já processado por getEndpoint())
     * @param array $data Dados para enviar (opcional)
     * @return array Resposta da API
     * @throws Exception
     */
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        // Construir URL completa
        $url = $this->baseUrl . $endpoint;
        
        // Inicializar cURL
        $ch = curl_init();
        
        // Headers padrão
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $this->token
        ];
        
        // Configurações básicas
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->options['timeout'] ?? 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
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
        
        // Porta (se especificada)
        if (isset($this->options['port'])) {
            curl_setopt($ch, CURLOPT_PORT, $this->options['port']);
        }
        
        // Versão HTTP (se especificada)
        if (isset($this->options['http_version'])) {
            $httpVersion = $this->options['http_version'];
            if ($httpVersion === 'CURL_HTTP_VERSION_1_0') {
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
            } elseif ($httpVersion === 'CURL_HTTP_VERSION_1_1') {
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            } elseif ($httpVersion === 'CURL_HTTP_VERSION_2_0') {
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
            }
        }
        
        // Método HTTP
        $methodUpper = strtoupper($method);
        switch ($methodUpper) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if (!empty($data)) {
                    $jsonData = $this->encodeJson($data);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                }
                break;
            
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if (!empty($data)) {
                    $jsonData = $this->encodeJson($data);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                }
                break;
            
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($data)) {
                    $jsonData = $this->encodeJson($data);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                }
                break;
            
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                if (!empty($data)) {
                    $jsonData = $this->encodeJson($data);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                }
                break;
            
            default: // GET
                // Para GET, se houver dados, converter para querystring e anexar à URL
                if (!empty($data)) {
                    $queryString = http_build_query($data);
                    $separator = strpos($url, '?') !== false ? '&' : '?';
                    $url = $url . $separator . $queryString;
                    curl_setopt($ch, CURLOPT_URL, $url);
                }
                break;
        }
        
        // Executar requisição
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errorNo = curl_errno($ch);
        
        // Debug mode seguro (sem expor token ou headers sensíveis)
        if (!empty($this->options['debug'])) {
            $responseTime = round(($endTime - $startTime) * 1000, 2); // em ms
            $debugInfo = sprintf(
                "cURL Debug [%s %s]: HTTP %d | Tempo: %sms | Tamanho resposta: %d bytes",
                $methodUpper,
                $endpoint,
                $httpCode,
                $responseTime,
                strlen($response)
            );
            error_log($debugInfo);
            
            // Log de erro cURL se houver
            if ($errorNo !== 0) {
                error_log("cURL Error: {$error} (Código: {$errorNo})");
            }
        }
        
        // Fechar cURL
        curl_close($ch);
        
        // Tratamento de erros
        if ($response === false || $errorNo !== 0) {
            throw new Exception('Erro na requisição cURL: ' . $error . ' (Código: ' . $errorNo . ')');
        }
        
        // Decodificar JSON
        $data = json_decode($response, true);
        
        // Se não conseguir decodificar JSON, retornar como string
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'sucesso' => false,
                'codigo' => $httpCode,
                'mensagem' => $response,
                'raw_response' => $response // Incluir resposta bruta para debug
            ];
        }
        
        // Se resposta não é array, transformar
        if (!is_array($data)) {
            $data = [
                'sucesso' => $httpCode >= 200 && $httpCode < 300,
                'codigo' => $httpCode,
                'mensagem' => is_string($data) ? $data : json_encode($data),
                'raw_response' => $response
            ];
        }
        
        // Adicionar código HTTP se não existir
        if (!isset($data['codigo'])) {
            $data['codigo'] = $httpCode;
        }
        
        // Adicionar sucesso se não existir (baseado no código HTTP)
        if (!isset($data['sucesso'])) {
            $data['sucesso'] = $httpCode >= 200 && $httpCode < 300;
        }
        
        // Para erros HTTP 4xx/5xx, incluir resposta completa
        if ($httpCode >= 400) {
            $data['raw_response'] = $response;
            $data['http_code'] = $httpCode;
            
            // Extrair detalhes de erro se disponíveis
            if (isset($data['error']) && !isset($data['mensagem'])) {
                $data['mensagem'] = $data['error'];
            }
            if (isset($data['details']) && !isset($data['erros'])) {
                $data['erros'] = is_array($data['details']) ? $data['details'] : [$data['details']];
            }
        }
        
        return $data;
    }

    /**
     * Retorna o endpoint base baseado no ambiente
     * 
     * @param string $path Caminho do endpoint
     * @return string Endpoint completo
     */
    protected function getEndpoint(string $path): string
    {
        // Se ambiente é homologação (2) e path começa com /api, usar /hom-api
        if ($this->ambiente == self::AMBIENTE_HOMOLOGACAO && strpos($path, '/api/') === 0) {
            $path = str_replace('/api/', '/hom-api/', $path);
        }
        
        return $path;
    }

    /**
     * Verifica se está em modo debug
     * 
     * @return bool
     */
    protected function isDebug(): bool
    {
        return !empty($this->options['debug']);
    }

    /**
     * Codifica dados para JSON com tratamento de erros
     * 
     * @param mixed $data Dados para codificar
     * @return string JSON codificado
     * @throws Exception Se não conseguir codificar
     */
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
}
