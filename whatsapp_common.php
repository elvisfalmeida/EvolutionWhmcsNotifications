<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

if (!defined('LICENSE_SERVER')) {
    define('LICENSE_SERVER', 'https://license.meudominio.com.br/api/license_api.php');
}

use WHMCS\Database\Capsule;

/**
 * Retorna uma configuração do módulo armazenada em tbladdonmodules.
 */
function evoWhatsApp_getSetting($setting)
{
    return Capsule::table('tbladdonmodules')
        ->where('module', 'evolutionwhatsapp')
        ->where('setting', $setting)
        ->value('value');
}

/**
 * Registra uma mensagem de log no arquivo configurado.
 */
function evoWhatsApp_log($message)
{
    $logFile = evoWhatsApp_getSetting("logFilePath");
    if (!$logFile) {
        return;
    }
    $date = date("Y-m-d H:i:s");
    $line = "[$date] " . $message . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
}

/**
 * Remove tudo que não for dígito do número de telefone.
 */
function evoWhatsApp_formatPhoneNumber($number)
{
    return preg_replace('/\D/', '', $number);
}

/**
 * Retorna o número do cliente formatado (ou string vazia se não existir).
 */
function evoWhatsApp_getClientNumber($userid)
{
    if (!$userid) {
        return '';
    }
    $phone = Capsule::table('tblclients')->where('id', $userid)->value('phonenumber');
    if ($phone) {
        return evoWhatsApp_formatPhoneNumber($phone);
    }
    return '';
}

/**
 * Função original de verificação da licença, sem cache.
 */
function checkLicense()
{
    global $licenseValid, $licenseError;
    $licenseValid = true;
    
    if (!defined('EVOLUTION_WHATSAPP_LICENSE') || empty(EVOLUTION_WHATSAPP_LICENSE)) {
        $licenseError = "Erro no módulo Evolution WhatsApp Notifications: Licença não configurada. Configure a constante EVOLUTION_WHATSAPP_LICENSE no configuration.php.";
        evoWhatsApp_log($licenseError);
        $licenseValid = false;
        return false;
    }
    
    $licenseKey = EVOLUTION_WHATSAPP_LICENSE;
    $domain     = $_SERVER['SERVER_NAME'];
    $ip         = $_SERVER['SERVER_ADDR'];
    
    evoWhatsApp_log("Verificando licença com: licenseKey=$licenseKey, domain=$domain, ip=$ip");
    
    $data = json_encode([
        "licenseKey" => $licenseKey,
        "domain"     => $domain,
        "ip"         => $ip
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    $ch = curl_init(LICENSE_SERVER);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Content-Length: " . strlen($data)
    ]);
    
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        $licenseError = "Erro no módulo Evolution WhatsApp Notifications: Erro na comunicação com o servidor de licenciamento: $curlError";
        evoWhatsApp_log($licenseError);
        $licenseValid = false;
        return false;
    }
    
    evoWhatsApp_log("Resposta da API de Licença: " . $response);
    
    $result = json_decode($response, true);
    if (!$result || $result['status'] !== 'active') {
        $licenseError = "Erro no módulo Evolution WhatsApp Notifications: Licença inválida ou suspensa. Entre em contato com o suporte.";
        evoWhatsApp_log($licenseError);
        $licenseValid = false;
        return false;
    }
    
    return true;
}

/**
 * Verifica a licença utilizando um sistema de cache.
 * Se $forceSync for true, ignora o cache e sincroniza novamente.
 *
 * @param bool $forceSync
 * @return array Resultado decodificado da API
 */
function checkLicenseWithCache($forceSync = false) {
    $cacheFile = __DIR__ . '/license_cache.json';
    $cacheTime = 600; // 10 minutos

    if (!$forceSync && file_exists($cacheFile)) {
        $cacheData = json_decode(file_get_contents($cacheFile), true);
        if (isset($cacheData['timestamp']) && (time() - $cacheData['timestamp'] < $cacheTime)) {
            return $cacheData['result'];
        }
    }

    if (!defined('EVOLUTION_WHATSAPP_LICENSE') || empty(EVOLUTION_WHATSAPP_LICENSE)) {
        die("Erro no módulo Evolution WhatsApp Notifications: Licença não configurada. Configure a constante EVOLUTION_WHATSAPP_LICENSE no configuration.php.");
    }
    $licenseKey = EVOLUTION_WHATSAPP_LICENSE;
    $domain     = $_SERVER['SERVER_NAME'];
    $ip         = $_SERVER['SERVER_ADDR'];

    evoWhatsApp_log("Verificando licença (cache) com: licenseKey=$licenseKey, domain=$domain, ip=$ip");

    $data = json_encode([
        "licenseKey" => $licenseKey,
        "domain"     => $domain,
        "ip"         => $ip
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init(LICENSE_SERVER);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Content-Length: " . strlen($data)
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        die("Erro no módulo Evolution WhatsApp Notifications: Erro na comunicação com o servidor de licenciamento: $curlError");
    }

    evoWhatsApp_log("Resposta da API de Licença (cache): " . $response);

    $result = json_decode($response, true);

    $cacheData = [
        'timestamp' => time(),
        'result' => $result
    ];
    file_put_contents($cacheFile, json_encode($cacheData));

    return $result;
}

/**
 * Retorna o diretório de instalação do módulo.
 *
 * @return string
 */
function getModuleInstallationDirectory() {
    // Supondo que este arquivo esteja em modules/addons/evolutionwhatsapp/
    return dirname(__DIR__);
}

/**
 * Envia uma mensagem de texto via Evolution API utilizando as configurações do módulo.
 * Verifica a licença antes de enviar.
 */
function evoWhatsApp_sendMessage($message, $destNumber)
{
    // Verifica a licença em pontos críticos
    checkLicense();

    $apiUrl   = rtrim(evoWhatsApp_getSetting("apiUrl"), '/');
    $instance = evoWhatsApp_getSetting("instance");
    $apiKey   = evoWhatsApp_getSetting("apiKey");

    $url = $apiUrl . "/message/sendText/" . $instance;
    $payload = [
        "number" => $destNumber,
        "text"   => $message,
        "options" => [
            "delay"       => 1,
            "linkPreview" => true
        ]
    ];
    $headers = [
        "apikey: " . $apiKey,
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        evoWhatsApp_log("Erro cURL ao enviar WhatsApp: " . $curlError);
        return;
    }

    evoWhatsApp_log("Envio WhatsApp => HTTP $httpCode, Destino: $destNumber, Msg: $message, Resp: $response");
}
