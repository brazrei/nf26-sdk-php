<?php

namespace CashNFe\SdkPHP;

use Exception;
use InvalidArgumentException;

/**
 * Classe para operações de NF-e
 * Compatível com SDK CloudDFE para facilitar migração
 */
class Nfe extends BaseSdk
{
    // Constantes de ambiente herdadas de BaseSdk (compatível com CloudDFE)
    // Usar self::AMBIENTE_PRODUCAO e self::AMBIENTE_HOMOLOGACAO (herdadas)
    
    // Códigos de erro padronizados
    const ERRO_GERAL = 5000;
    const ERRO_CNPJ_OBRIGATORIO = 5001;
    const ERRO_VALIDACAO = 5002;
    const ERRO_CHAVE_OBRIGATORIA = 5003; // Corrigido: era 5001 (duplicado)
    const ERRO_PARAMETROS_INSUFICIENTES = 400;
    /**
     * Cria/emite uma NF-e
     * 
     * Converte o payload do formato CloudDFE para o formato NF26
     * 
     * @param array $payload Payload no formato CloudDFE
     *   - natureza_operacao
     *   - numero
     *   - serie
     *   - data_emissao
     *   - tipo_operacao
     *   - finalidade_emissao
     *   - consumidor_final
     *   - presenca_comprador
     *   - destinatario
     *   - itens
     *   - frete
     *   - pagamento
     * 
     * @return object Resposta da API
     *   - sucesso: bool
     *   - chave: string (chave de acesso)
     *   - codigo: int (código de resposta)
     *   - mensagem: string
     *   - erros: array (se houver erros de validação)
     */
    public function cria(array $payload): object
    {
        try {
            // Validar e obter CNPJ do certificado
            $cnpjCertificado = $this->validarCnpjCertificado($payload);
            
            // Preparar dados para nossa API
            // Se o payload contém XML diretamente, enviar como está
            // Caso contrário, enviar dados estruturados para o servidor converter
            $data = $payload;
            $data['cnpjCertificado'] = $cnpjCertificado;
            $data['ambiente'] = $this->ambiente == self::AMBIENTE_PRODUCAO ? 'producao' : 'homologacao';
            
            $endpoint = $this->getEndpoint('/api/nfe/emitir');
            
            $response = $this->request('POST', $endpoint, $data);
            
            // Normalizar resposta da API
            $result = $this->normalizarResposta($response);
            
            if ($result->sucesso) {
                // Extrair chave de acesso do XML de retorno
                $xmlRetorno = $response['xmlRetorno'] ?? $response['xml_retorno'] ?? '';
                $chaveAcesso = $this->extractChaveAcesso($xmlRetorno);
                
                $result->chave = $chaveAcesso ?? $response['chaveAcesso'] ?? $response['chave_acesso'] ?? '';
                $result->protocolo = $response['protocolo'] ?? '';
                $result->data_autorizacao = $response['dataAutorizacao'] ?? $response['data_autorizacao'] ?? '';
                $result->status = $response['status'] ?? '';
                
                // Se mensagem não foi definida, usar padrão
                if (empty($result->mensagem)) {
                    $result->mensagem = 'NF-e emitida com sucesso';
                }
                
                // Se temos XML de retorno, incluir
                if (!empty($xmlRetorno)) {
                    $result->xmlRetorno = $xmlRetorno;
                }
            } else {
                // Determinar código de erro se não foi definido
                if (!isset($response['codigo']) && !isset($response['http_code'])) {
                    if (function_exists('str_contains')) {
                        $isValidacao = str_contains($result->mensagem, 'validação') || str_contains($result->mensagem, 'valida');
                    } else {
                        $isValidacao = strpos($result->mensagem, 'validação') !== false || strpos($result->mensagem, 'valida') !== false;
                    }
                    
                    $result->codigo = $isValidacao ? self::ERRO_VALIDACAO : self::ERRO_GERAL;
                }
            }
            
            return $result;
            
        } catch (InvalidArgumentException $e) {
            return (object)[
                'sucesso' => false,
                'codigo' => self::ERRO_CNPJ_OBRIGATORIO,
                'mensagem' => $e->getMessage(),
                'erros' => [$e->getMessage()]
            ];
        } catch (Exception $e) {
            // Log do erro real em modo debug
            if ($this->isDebug()) {
                error_log("[SDK Error] Method: cria | Error: " . $e->getMessage());
            }
            
            return (object)[
                'sucesso' => false,
                'codigo' => self::ERRO_GERAL,
                'mensagem' => 'Erro interno do sistema',
                'erros' => [$this->isDebug() ? $e->getMessage() : 'Erro interno. Tente novamente mais tarde.']
            ];
        }
    }

    /**
     * Consulta uma NF-e pela chave de acesso
     * 
     * @param array $payload
     *   - chave: string (chave de acesso da NF-e)
     * 
     * @return object Resposta da API
     */
    public function consulta(array $payload): object
    {
        try {
            $chave = $payload['chave'] ?? '';
            
            if (empty($chave)) {
                return (object)[
                    'sucesso' => false,
                    'codigo' => self::ERRO_CHAVE_OBRIGATORIA,
                    'mensagem' => 'Chave de acesso não informada',
                    'erros' => ['Chave de acesso é obrigatória']
                ];
            }
            
            // Validar e obter CNPJ do certificado
            $cnpjCertificado = $this->validarCnpjCertificado($payload);
            
            $data = [
                'chaveAcesso' => $chave,
                'cnpjCertificado' => $cnpjCertificado,
                'ambiente' => $this->ambiente == self::AMBIENTE_PRODUCAO ? 'producao' : 'homologacao'
            ];
            
            $endpoint = $this->getEndpoint('/api/nfe/consultar-status');
            
            $response = $this->request('POST', $endpoint, $data);
            
            // Normalizar resposta da API
            $result = $this->normalizarResposta($response);
            
            if ($result->sucesso) {
                $result->status = $response['status'] ?? '';
                $result->protocolo = $response['protocolo'] ?? '';
                $result->data_autorizacao = $response['dataAutorizacao'] ?? '';
            }
            
            return $result;
            
        } catch (InvalidArgumentException $e) {
            return (object)[
                'sucesso' => false,
                'codigo' => self::ERRO_CNPJ_OBRIGATORIO,
                'mensagem' => $e->getMessage(),
                'erros' => [$e->getMessage()]
            ];
        } catch (Exception $e) {
            if ($this->isDebug()) {
                error_log("[SDK Error] Method: consulta | Error: " . $e->getMessage());
            }
            
            return (object)[
                'sucesso' => false,
                'codigo' => self::ERRO_GERAL,
                'mensagem' => 'Erro interno do sistema',
                'erros' => [$this->isDebug() ? $e->getMessage() : 'Erro interno. Tente novamente mais tarde.']
            ];
        }
    }


    /**
     * Valida e normaliza CNPJ do certificado
     * 
     * @param array $payload
     * @return string CNPJ validado e limpo
     * @throws InvalidArgumentException Se CNPJ não for fornecido ou inválido
     */
    private function validarCnpjCertificado(array $payload): string
    {
        $cnpj = $payload['cnpj_certificado'] ?? $payload['cnpjCertificado'] ?? null;
        
        if (empty($cnpj)) {
            throw new InvalidArgumentException('CNPJ do certificado é obrigatório');
        }
        
        // Limpar CNPJ (remover caracteres não numéricos)
        $cnpjLimpo = preg_replace('/[^0-9]/', '', (string)$cnpj);
        
        // Validar formato básico (14 dígitos)
        if (strlen($cnpjLimpo) !== 14) {
            throw new InvalidArgumentException('CNPJ inválido. Deve conter 14 dígitos.');
        }
        
        return $cnpjLimpo;
    }
    
    /**
     * Normaliza resposta da API para formato padrão
     * 
     * @param array $response Resposta bruta da API
     * @return object Resposta normalizada
     */
    private function normalizarResposta(array $response): object
    {
        $result = (object)[
            'sucesso' => $response['success'] ?? $response['sucesso'] ?? false,
            'codigo' => $response['codigo'] ?? $response['http_code'] ?? 200,
            'mensagem' => $response['mensagem'] ?? $response['message'] ?? $response['error'] ?? '',
        ];
        
        // Extrair erros detalhados se disponíveis
        $errosDetalhados = [];
        if (isset($response['details']) && is_array($response['details'])) {
            $errosDetalhados = $response['details'];
        } elseif (isset($response['erros']) && is_array($response['erros'])) {
            $errosDetalhados = $response['erros'];
        } elseif (isset($response['details']) && is_string($response['details'])) {
            $errosDetalhados = [$response['details']];
        }
        
        // Se não há erros detalhados e há mensagem, usar mensagem
        if (empty($errosDetalhados) && !empty($result->mensagem)) {
            $errosDetalhados = [$result->mensagem];
        }
        
        $result->erros = $errosDetalhados;
        
        // Incluir código de erro da API se disponível
        if (isset($response['code'])) {
            $result->error_code = $response['code'];
        }
        
        // Incluir resposta bruta para debug se disponível
        if (isset($response['raw_response'])) {
            $result->raw_response = $response['raw_response'];
        }
        
        return $result;
    }

    /**
     * Extrai chave de acesso do XML de retorno
     * 
     * @param string $xml XML de retorno
     * @return string|null Chave de acesso
     */
    private function extractChaveAcesso(string $xml): ?string
    {
        if (empty($xml)) {
            return null;
        }
        
        // Tentar usar DOMDocument primeiro (mais seguro) - apenas se disponível
        if (class_exists('DOMDocument')) {
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            
            if (@$dom->loadXML($xml)) {
                $xpath = new \DOMXPath($dom);
                $xpath->registerNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
                
                // Buscar chNFe
                $nodes = $xpath->query('//nfe:chNFe');
                if ($nodes->length > 0) {
                    $chave = trim($nodes->item(0)->nodeValue);
                    if (strlen($chave) === 44 && ctype_digit($chave)) {
                        return $chave;
                    }
                }
                
                // Tentar sem namespace
                $nodes = $xpath->query('//chNFe');
                if ($nodes->length > 0) {
                    $chave = trim($nodes->item(0)->nodeValue);
                    if (strlen($chave) === 44 && ctype_digit($chave)) {
                        return $chave;
                    }
                }
            }
        }
        
        // Fallback para regex (menos seguro, mas funciona com XML malformado)
        if (preg_match('/<chNFe>(\d{44})<\/chNFe>/', $xml, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/chave[="\']?(\d{44})["\']?/i', $xml, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Consulta o status do serviço (compatível com CloudDFE SDK)
     * 
     * @return object Resposta da API com status do serviço
     */
    public function status(): object
    {
        try {
            $endpoint = $this->getEndpoint('/api/nfe/status');
            
            $response = $this->request('GET', $endpoint);
            
            // Normalizar resposta da API
            $result = $this->normalizarResposta($response);
            
            if ($result->sucesso) {
                $result->status = $response['status'] ?? 'online';
                if (empty($result->mensagem)) {
                    $result->mensagem = 'Serviço disponível';
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            if ($this->isDebug()) {
                error_log("[SDK Error] Method: status | Error: " . $e->getMessage());
            }
            
            return (object)[
                'sucesso' => false,
                'codigo' => self::ERRO_GERAL,
                'mensagem' => 'Erro interno do sistema',
                'erros' => [$this->isDebug() ? $e->getMessage() : 'Erro interno. Tente novamente mais tarde.']
            ];
        }
    }

    /**
     * Gera o PDF do DANFE a partir do XML Final Autorizado ou por CNPJ e número da nota
     * 
     * @param array $payload
     *   - xmlFinalAutorizado: string (XML Final Autorizado - NFeProc) - opcional
     *   - cnpj: string (CNPJ do emitente) - opcional, se não fornecer xmlFinalAutorizado
     *   - numeroNota: string (Número da nota) - opcional, se não fornecer xmlFinalAutorizado
     * 
     * @return object Resposta da API
     *   - sucesso: bool
     *   - danfePdfBase64: string (PDF em base64)
     *   - tamanhoBytes: int (tamanho do PDF em bytes)
     */
    public function gerarDanfe(array $payload): object
    {
        try {
            $xmlFinalAutorizado = $payload['xmlFinalAutorizado'] ?? null;
            $cnpj = $payload['cnpj'] ?? null;
            $numeroNota = $payload['numeroNota'] ?? null;

            // Se tem XML, usar endpoint direto
            if (!empty($xmlFinalAutorizado)) {
                $data = [
                    'xmlFinalAutorizado' => $xmlFinalAutorizado
                ];
                $endpoint = $this->getEndpoint('/api/nfe/gerar-danfe');
            } 
            // Se tem CNPJ e número da nota, usar endpoint que busca automaticamente
            elseif (!empty($cnpj) && !empty($numeroNota)) {
                $data = [
                    'cnpj' => $cnpj,
                    'numeroNota' => (string)$numeroNota
                ];
                $endpoint = $this->getEndpoint('/api/nfe/danfe-por-cnpj-nota');
            } 
            else {
                return (object)[
                    'sucesso' => false,
                    'codigo' => self::ERRO_PARAMETROS_INSUFICIENTES,
                    'mensagem' => 'É necessário fornecer xmlFinalAutorizado ou (cnpj e numeroNota)',
                    'erros' => ['Parâmetros insuficientes para gerar DANFE']
                ];
            }

            $response = $this->request('POST', $endpoint, $data);

            // Normalizar resposta da API
            $result = $this->normalizarResposta($response);

            if ($result->sucesso) {
                $result->danfePdfBase64 = $response['danfePdfBase64'] ?? '';
                $result->tamanhoBytes = $response['tamanhoBytes'] ?? 0;
                
                // Se foi gerado por CNPJ e nota, incluir informações adicionais
                if (!empty($cnpj) && !empty($numeroNota)) {
                    $result->cnpj = $response['cnpj'] ?? $cnpj;
                    $result->numeroNota = $response['numeroNota'] ?? $numeroNota;
                    $result->chaveAcesso = $response['chaveAcesso'] ?? null;
                    $result->arquivo = $response['arquivo'] ?? null;
                }
                
                $result->mensagem = 'DANFE gerado com sucesso';
            } else {
                $result->mensagem = $response['error'] ?? $response['mensagem'] ?? 'Erro ao gerar DANFE';
                $result->erros = [$result->mensagem];
            }

            return $result;

        } catch (Exception $e) {
            if ($this->isDebug()) {
                error_log("[SDK Error] Method: gerarDanfe | Error: " . $e->getMessage());
            }
            
            return (object)[
                'sucesso' => false,
                'codigo' => self::ERRO_GERAL,
                'mensagem' => 'Erro interno do sistema',
                'erros' => [$this->isDebug() ? $e->getMessage() : 'Erro interno. Tente novamente mais tarde.']
            ];
        }
    }
}

