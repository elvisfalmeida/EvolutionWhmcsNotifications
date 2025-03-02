<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

// Define a URL do servidor de licenciamento (caso não esteja definido em whatsapp_common.php)
if (!defined('LICENSE_SERVER')) {
    define("LICENSE_SERVER", "https://license.meudominio.com.br/api/license_api.php");
}

// Inclui as funções comuns, que já contêm as funções checkLicenseWithCache() e getModuleInstallationDirectory()
require_once __DIR__ . '/whatsapp_common.php';

// Executa a verificação de licença ao carregar o módulo
global $licenseValid, $licenseError;
$licenseValid = checkLicense();

function evolutionwhatsapp_config()
{
    return [
        'name' => 'Evolution WhatsApp Notifications',
        'description' => 'Integra as notificações do WHMCS com a Evolution API do WhatsApp.',
        'version' => '4.0',
        'author' => 'elvisfalmeida',
        'language' => 'portuguese',
        'fields' => [
            'apiUrl' => [
                'FriendlyName' => 'API URL',
                'Type' => 'text',
                'Size' => '50',
                'Default' => 'https://api.meudominio.com.br',
                'Description' => 'URL base da Evolution API.'
            ],
            'instance' => [
                'FriendlyName' => 'Instance ID',
                'Type' => 'text',
                'Size' => '20',
                'Default' => 'Evolution_Bot',
                'Description' => 'Identificador da instância na API.'
            ],
            'apiKey' => [
                'FriendlyName' => 'API Key',
                'Type' => 'password',
                'Size' => '50',
                'Default' => '',
                'Description' => 'Chave de acesso à API.'
            ],
            'defaultNumber' => [
                'FriendlyName' => 'Número Padrão (Admin)',
                'Type' => 'text',
                'Size' => '20',
                'Default' => '',
                'Description' => 'Número usado se não houver grupo definido.'
            ],
            'adminNotificationTarget' => [
                'FriendlyName' => 'Admin Grupo',
                'Type' => 'text',
                'Size' => '50',
                'Default' => '',
                'Description' => 'ID do grupo (ex: 012345678910@g.us). Se preenchido, será usado para notificar um grupo do WhatsApp.'
            ],
            'enableTicketNotifications' => [
                'FriendlyName' => 'Notificações de Tickets',
                'Type' => 'yesno',
                'Default' => '1',
                'Description' => 'Habilitar/desabilitar notificações de tickets.'
            ],
            'enableInvoiceNotifications' => [
                'FriendlyName' => 'Notificações de Faturas',
                'Type' => 'yesno',
                'Default' => '1',
                'Description' => 'Habilitar/desabilitar notificações de faturas.'
            ],
            'enableOrderNotifications' => [
                'FriendlyName' => 'Notificações de Pedidos',
                'Type' => 'yesno',
                'Default' => '1',
                'Description' => 'Habilitar/desabilitar notificações de pedidos.'
            ],
            'enableServiceNotifications' => [
                'FriendlyName' => 'Notificações de Serviços',
                'Type' => 'yesno',
                'Default' => '1',
                'Description' => 'Habilitar/desabilitar notificações de serviços.'
            ],
            'logFilePath' => [
                'FriendlyName' => 'Caminho do Arquivo de Log',
                'Type' => 'text',
                'Size' => '80',
                'Default' => __DIR__ . '/whatsapp.log',
                'Description' => 'Caminho completo para o arquivo de log.'
            ],
        ],
    ];
}

function evolutionwhatsapp_activate()
{
    return [
        'status' => 'success',
        'description' => 'Módulo Evolution WhatsApp ativado com sucesso.'
    ];
}

function evolutionwhatsapp_deactivate()
{
    return [
        'status' => 'success',
        'description' => 'Módulo Evolution WhatsApp desativado com sucesso.'
    ];
}

function evolutionwhatsapp_output($vars)
{
    global $licenseValid, $licenseError;
    $modulelink = $vars['modulelink'];

    echo "<h2>Evolution WhatsApp Notifications</h2>";

    // Exibe status da licença
    if (!$licenseValid) {
        echo "<p style='color:red;'><strong>Erro de Licença:</strong> " . htmlspecialchars($licenseError) . "</p>";
        return;
    } else {
        echo "<p>O módulo está funcionando corretamente.</p>";
    }

    // Menu de opções do módulo
    echo "<ul>";
    echo "<li><a href='{$modulelink}&action=viewlog'>Ver Log de Mensagens</a></li>";
    echo "<li><a href='{$modulelink}&action=systeminfo'>Informações do Sistema</a></li>";
    echo "</ul>";

    // Exibe Log de Mensagens
    if (isset($_GET['action']) && $_GET['action'] == 'viewlog') {
        $logFile = $vars['logFilePath'] ?: (__DIR__ . '/whatsapp.log');
        if (file_exists($logFile)) {
            echo "<h3>Log de Mensagens</h3>";
            echo "<pre>" . htmlspecialchars(file_get_contents($logFile)) . "</pre>";
        } else {
            echo "<p>Nenhum log encontrado em <em>" . htmlspecialchars($logFile) . "</em>.</p>";
        }
    }

    // Exibe Informações do Sistema
    if (isset($_GET['action']) && $_GET['action'] == 'systeminfo') {
        // Obtém informações do ambiente
        $moduleDir = getModuleInstallationDirectory();
        $phpVersion = phpversion();
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'N/A';
        $licenseStatus = checkLicenseWithCache();
        
        echo "<h3>Informações do Sistema</h3>";
        echo "<ul>";
        echo "<li><strong>Diretório de Instalação do Módulo:</strong> " . htmlspecialchars($moduleDir) . "</li>";
        echo "<li><strong>Versão do PHP:</strong> " . htmlspecialchars($phpVersion) . "</li>";
        echo "<li><strong>Servidor Web:</strong> " . htmlspecialchars($serverSoftware) . "</li>";
        echo "<li><strong>Status da Licença (cache):</strong> " . htmlspecialchars($licenseStatus['status'] ?? 'Indisponível') 
             . " - " . htmlspecialchars($licenseStatus['message'] ?? '') . "</li>";
        echo "</ul>";
        
        // Botão para forçar sincronização do cache
        echo "<form method='post' style='margin-bottom:20px;'>
                <input type='hidden' name='force_sync' value='1'>
                <button type='submit' class='btn btn-primary'>Forçar Sincronização da Licença</button>
              </form>";
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['force_sync'])) {
            $forcedStatus = checkLicenseWithCache(true);
            echo "<div class='alert alert-info'>Sincronização forçada concluída. Novo status: " 
                 . htmlspecialchars($forcedStatus['status'] ?? '') 
                 . " - " . htmlspecialchars($forcedStatus['message'] ?? '') . "</div>";
        }
    }
}

if (file_exists(__DIR__ . '/hooks.php')) {
    require_once __DIR__ . '/hooks.php';
}
