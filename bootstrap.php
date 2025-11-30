<?php

/**
 * Bootstrap do SDK NF26 PHP
 * Compatível com estrutura CloudDFE SDK
 * 
 * Este arquivo carrega as classes do SDK.
 * Se o Composer estiver disponível, usa o autoloader do Composer.
 * Caso contrário, usa um autoloader simples.
 */

// Tentar carregar autoload do Composer primeiro
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once(__DIR__ . '/vendor/autoload.php');
} else {
    // Se não tiver Composer, usar autoloader simples
    spl_autoload_register(function ($class) {
        // Namespace base do SDK
        $prefix = 'CashNFe\\SdkPHP\\';
        
        // Verificar se a classe pertence ao nosso namespace
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return; // Não é uma classe do nosso SDK
        }
        
        // Remover o prefixo do namespace
        $relativeClass = substr($class, $len);
        
        // Converter namespace para caminho de arquivo
        // CashNFe\SdkPHP\BaseSdk -> src/BaseSdk.php
        $file = __DIR__ . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';
        
        // Se o arquivo existe, carregá-lo
        if (file_exists($file)) {
            require_once $file;
        }
    });
}
