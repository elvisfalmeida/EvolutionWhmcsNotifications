<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

// Carrega as funções comuns – já definidas em whatsapp_common.php
require_once __DIR__ . '/whatsapp_common.php';

/**
 * Carrega as configurações do módulo.
 */
$enableTicketNotifications  = evoWhatsApp_getSetting("enableTicketNotifications");
$enableInvoiceNotifications = evoWhatsApp_getSetting("enableInvoiceNotifications");
$enableOrderNotifications   = evoWhatsApp_getSetting("enableOrderNotifications");
$enableServiceNotifications = evoWhatsApp_getSetting("enableServiceNotifications");
$defaultNumber              = evoWhatsApp_getSetting("defaultNumber");

// Se definido, utiliza o adminNotificationTarget; caso contrário, usa o defaultNumber.
$adminTarget = evoWhatsApp_getSetting("adminNotificationTarget");
if (empty($adminTarget)) {
    $adminTarget = $defaultNumber;
}

/**
 * =================== TICKETS ===================
 */
if ($enableTicketNotifications) {

    // TicketOpen: Envia link com token incluído
    add_hook('TicketOpen', 1, function($vars) use ($adminTarget) {
        evoWhatsApp_log("HOOK TicketOpen disparado. ticketid=" . ($vars['ticketid'] ?? 'N/A'));

        $ticketid = $vars['ticketid'] ?? null;
        if (!$ticketid) return;

        $ticket = Capsule::table('tbltickets')->where('id', $ticketid)->first();
        if (!$ticket) {
            evoWhatsApp_log("TicketOpen: ticket não encontrado");
            return;
        }

        $ticketmask = $ticket->tid;
        $subject    = $ticket->title;
        $userid     = $ticket->userid;
        $priority   = ucfirst(strtolower($vars['priority'] ?? 'Normal'));
        $token      = $ticket->c;

        $systemUrl = rtrim(\WHMCS\Config\Setting::getValue('SystemURL'), '/');
        $ticketLink = $systemUrl . "/viewticket.php?tid={$ticketmask}";
        if ($token) {
            $ticketLink .= "&c={$token}";
        }

        $notification = "🔔 *Novo Ticket Aberto!*\n"
            . "Assunto: {$subject}\n"
            . "Prioridade: {$priority}\n"
            . "Ticket: {$ticketmask}\n"
            . "Link: {$ticketLink}";

        // Envia para admin (usando o adminTarget) e para o cliente separadamente
        evoWhatsApp_sendMessage($notification, $adminTarget);
        $clientNumber = evoWhatsApp_getClientNumber($userid);
        if (!empty($clientNumber) && $clientNumber !== $adminTarget) {
            evoWhatsApp_sendMessage($notification, $clientNumber);
        }
    });

    // TicketUserReply: Notifica o admin que o cliente respondeu
    add_hook('TicketUserReply', 1, function($vars) use ($adminTarget) {
        evoWhatsApp_log("HOOK TicketUserReply disparado. ticketid=" . ($vars['ticketid'] ?? 'N/A'));

        $ticketid = $vars['ticketid'] ?? null;
        if (!$ticketid) return;
        $ticket = Capsule::table('tbltickets')->where('id', $ticketid)->first();
        if (!$ticket) return;
        $ticketmask = $ticket->tid;
        $subject    = $ticket->title;
        $messageBody = $vars['message'] ?? 'Sem mensagem';

        $systemUrl = rtrim(\WHMCS\Config\Setting::getValue('SystemURL'), '/');
        $token = $ticket->c;
        $ticketLink = $systemUrl . "/viewticket.php?tid={$ticketmask}";
        if ($token) {
            $ticketLink .= "&c={$token}";
        }

        $notification = "💬 *Nova Resposta do Cliente!*\n"
            . "Assunto: {$subject}\n"
            . "Ticket: {$ticketmask}\n"
            . "Mensagem: {$messageBody}\n"
            . "Link: {$ticketLink}";

        evoWhatsApp_sendMessage($notification, $adminTarget);
    });

    // TicketAdminReply: Notifica admin e cliente
    add_hook('TicketAdminReply', 1, function($vars) use ($adminTarget) {
        evoWhatsApp_log("HOOK TicketAdminReply disparado. ticketid=" . ($vars['ticketid'] ?? 'N/A'));

        $ticketid = $vars['ticketid'] ?? null;
        if (!$ticketid) return;
        $ticket = Capsule::table('tbltickets')->where('id', $ticketid)->first();
        if (!$ticket) return;
        $ticketmask = $ticket->tid;
        $subject    = $ticket->title;
        $userid     = $ticket->userid;
        $admin      = $vars['admin'] ?? 'Suporte';
        $messageBody= $vars['message'] ?? 'Sem mensagem';

        $systemUrl = rtrim(\WHMCS\Config\Setting::getValue('SystemURL'), '/');
        $token = $ticket->c;
        $ticketLink = $systemUrl . "/viewticket.php?tid={$ticketmask}";
        if ($token) {
            $ticketLink .= "&c={$token}";
        }

        $notification = "👨‍💻 *Resposta do Suporte ({$admin})!*\n"
            . "Assunto: {$subject}\n"
            . "Ticket: {$ticketmask}\n"
            . "Mensagem: {$messageBody}\n"
            . "Link: {$ticketLink}";

        evoWhatsApp_sendMessage($notification, $adminTarget);
        $clientNumber = evoWhatsApp_getClientNumber($userid);
        if (!empty($clientNumber) && $clientNumber !== $adminTarget) {
            evoWhatsApp_sendMessage($notification, $clientNumber);
        }
    });

    // TicketClose: Notifica ambos
    add_hook('TicketClose', 1, function($vars) use ($adminTarget) {
        evoWhatsApp_log("HOOK TicketClose disparado. ticketid=" . ($vars['ticketid'] ?? 'N/A'));

        $ticketid = $vars['ticketid'] ?? null;
        if (!$ticketid) return;
        $ticket = Capsule::table('tbltickets')->where('id', $ticketid)->first();
        if (!$ticket) return;
        $ticketmask = $ticket->tid;
        $subject    = $ticket->title;
        $userid     = $ticket->userid;

        $systemUrl = rtrim(\WHMCS\Config\Setting::getValue('SystemURL'), '/');
        $token = $ticket->c;
        $ticketLink = $systemUrl . "/viewticket.php?tid={$ticketmask}";
        if ($token) {
            $ticketLink .= "&c={$token}";
        }

        $notification = "✅ *Ticket Fechado!*\n"
            . "Assunto: {$subject}\n"
            . "Ticket: {$ticketmask}\n"
            . "Link: {$ticketLink}";

        evoWhatsApp_sendMessage($notification, $adminTarget);
        $clientNumber = evoWhatsApp_getClientNumber($userid);
        if (!empty($clientNumber) && $clientNumber !== $adminTarget) {
            evoWhatsApp_sendMessage($notification, $clientNumber);
        }
    });
}

/**
 * =================== FATURAS ===================
 */
if ($enableInvoiceNotifications) {
    // InvoiceCreation: Inclui a descrição dos itens, se houver
    add_hook('InvoiceCreation', 1, function($vars) use ($adminTarget) {
        evoWhatsApp_log("HOOK InvoiceCreation disparado. invoiceid=" . ($vars['invoiceid'] ?? 'N/A'));

        $invoiceid = $vars['invoiceid'] ?? null;
        if (!$invoiceid) return;
        $invoice = Capsule::table('tblinvoices')->where('id', $invoiceid)->first();
        if (!$invoice) return;
        $invoicenum = $invoice->invoicenum ?: $invoiceid;
        $userid     = $invoice->userid;
        $total      = $invoice->total;

        // Buscar descrição dos itens na fatura
        $descArray = [];
        $items = Capsule::table('tblinvoiceitems')->where('invoiceid', $invoiceid)->get();
        foreach ($items as $item) {
            if (!empty($item->description)) {
                $descArray[] = $item->description;
            }
        }
        $itemDescription = !empty($descArray) ? implode(", ", $descArray) : '';

        $systemUrl = rtrim(\WHMCS\Config\Setting::getValue('SystemURL'), '/');
        $invoiceLink = $systemUrl . "/viewinvoice.php?id={$invoiceid}";

        $notification = "📄 *Nova Fatura Gerada!*\n"
            . "Fatura: #{$invoicenum}\n"
            . ($itemDescription ? "Itens: {$itemDescription}\n" : "")
            . "Valor: R$ {$total}\n"
            . "Link: {$invoiceLink}";

        evoWhatsApp_sendMessage($notification, $adminTarget);
        $clientNumber = evoWhatsApp_getClientNumber($userid);
        if (!empty($clientNumber) && $clientNumber !== $adminTarget) {
            evoWhatsApp_sendMessage($notification, $clientNumber);
        }
    });

    // InvoicePaid: Inclui a descrição dos itens, se houver
    add_hook('InvoicePaid', 1, function($vars) use ($adminTarget) {
        evoWhatsApp_log("HOOK InvoicePaid disparado. invoiceid=" . ($vars['invoiceid'] ?? 'N/A'));

        $invoiceid = $vars['invoiceid'] ?? null;
        if (!$invoiceid) return;
        $invoice = Capsule::table('tblinvoices')->where('id', $invoiceid)->first();
        if (!$invoice) return;
        $invoicenum = $invoice->invoicenum ?: $invoiceid;
        $userid     = $invoice->userid;
        $total      = $invoice->total;

        $descArray = [];
        $items = Capsule::table('tblinvoiceitems')->where('invoiceid', $invoiceid)->get();
        foreach ($items as $item) {
            if (!empty($item->description)) {
                $descArray[] = $item->description;
            }
        }
        $itemDescription = !empty($descArray) ? implode(", ", $descArray) : '';

        $systemUrl = rtrim(\WHMCS\Config\Setting::getValue('SystemURL'), '/');
        $invoiceLink = $systemUrl . "/viewinvoice.php?id={$invoiceid}";

        $notification = "✅ *Fatura Paga!*\n"
            . "Fatura: #{$invoicenum}\n"
            . ($itemDescription ? "Itens: {$itemDescription}\n" : "")
            . "Valor: R$ {$total}\n"
            . "Link: {$invoiceLink}";

        evoWhatsApp_sendMessage($notification, $adminTarget);
        $clientNumber = evoWhatsApp_getClientNumber($userid);
        if (!empty($clientNumber) && $clientNumber !== $adminTarget) {
            evoWhatsApp_sendMessage($notification, $clientNumber);
        }
    });

    // InvoiceCancelled
    add_hook('InvoiceCancelled', 1, function($vars) use ($adminTarget) {
        evoWhatsApp_log("HOOK InvoiceCancelled disparado. invoiceid=" . ($vars['invoiceid'] ?? 'N/A'));

        $invoiceid = $vars['invoiceid'] ?? null;
        if (!$invoiceid) return;
        $invoice = Capsule::table('tblinvoices')->where('id', $invoiceid)->first();
        if (!$invoice) return;
        $invoicenum = $invoice->invoicenum ?: $invoiceid;
        $userid     = $invoice->userid;
        $total      = $invoice->total;

        $descArray = [];
        $items = Capsule::table('tblinvoiceitems')->where('invoiceid', $invoiceid)->get();
        foreach ($items as $item) {
            if (!empty($item->description)) {
                $descArray[] = $item->description;
            }
        }
        $itemDescription = !empty($descArray) ? implode(", ", $descArray) : '';

        $systemUrl = rtrim(\WHMCS\Config\Setting::getValue('SystemURL'), '/');
        $invoiceLink = $systemUrl . "/viewinvoice.php?id={$invoiceid}";

        $notification = "❌ *Fatura Cancelada!*\n"
            . "Fatura: #{$invoicenum}\n"
            . ($itemDescription ? "Itens: {$itemDescription}\n" : "")
            . "Valor: R$ {$total}\n"
            . "Link: {$invoiceLink}";

        evoWhatsApp_sendMessage($notification, $adminTarget);
        $clientNumber = evoWhatsApp_getClientNumber($userid);
        if (!empty($clientNumber) && $clientNumber !== $adminTarget) {
            evoWhatsApp_sendMessage($notification, $clientNumber);
        }
    });
}

/**
 * =================== PEDIDOS (ORDERS) ===================
 */
if ($enableOrderNotifications) {
    add_hook('AcceptOrder', 1, function($vars) use ($adminTarget) {
        evoWhatsApp_log("HOOK AcceptOrder disparado. orderid=" . ($vars['orderid'] ?? 'N/A'));

        $orderid = $vars['orderid'] ?? null;
        if (!$orderid) return;
        $order = Capsule::table('tblorders')->where('id', $orderid)->first();
        if (!$order) return;
        $ordernum = $order->ordernum ?: $orderid;
        $userid   = $order->userid;

        // Buscar itens e total via fatura, se houver
        $descArray = [];
        $invoiceTotal = '0.00';
        if ($order->invoiceid) {
            $invoice = Capsule::table('tblinvoices')->where('id', $order->invoiceid)->first();
            if ($invoice) {
                $invoiceTotal = $invoice->total;
            }
            $items = Capsule::table('tblinvoiceitems')->where('invoiceid', $order->invoiceid)->get();
            foreach ($items as $item) {
                if (!empty($item->description)) {
                    $descArray[] = $item->description;
                }
            }
        }
        $description = !empty($descArray) ? implode(", ", $descArray) : 'Sem descrição';

        // Link: usamos a página de faturas (clientarea.php?action=invoices)
        $systemUrl = rtrim(\WHMCS\Config\Setting::getValue('SystemURL'), '/');
        $orderLink = $systemUrl . "/clientarea.php?action=invoices";

        $notification = "✅ *Pedido Aceito!*\n"
            . "Pedido: #{$ordernum}\n"
            . "Itens: {$description}\n"
            . "Valor: R$ {$invoiceTotal}\n"
            . "Link: {$orderLink}";

        evoWhatsApp_sendMessage($notification, $adminTarget);
        $clientNumber = evoWhatsApp_getClientNumber($userid);
        if (!empty($clientNumber) && $clientNumber !== $adminTarget) {
            evoWhatsApp_sendMessage($notification, $clientNumber);
        }
    });
    // Outros hooks (CancelOrder, OrderPaid, PendingOrder) podem ser adicionados de forma similar.
}

/**
 * =================== SERVIÇOS ===================
 */
if ($enableServiceNotifications) {
    // AfterModuleCreate (Serviço Criado)
    add_hook('AfterModuleCreate', 1, function($vars) use ($adminTarget) {
        evoWhatsApp_log("HOOK AfterModuleCreate disparado. serviceid=" . ($vars['serviceid'] ?? 'N/A'));

        $serviceid = $vars['serviceid'] ?? null;
        if (!$serviceid) return;
        $hosting = Capsule::table('tblhosting')->where('id', $serviceid)->first();
        if (!$hosting) return;
        $userid = $hosting->userid;
        $productName = Capsule::table('tblproducts')
            ->where('id', $hosting->packageid)
            ->value('name') ?: 'N/A';

        $notification = "🚀 *Serviço Criado!*\nProduto: {$productName}\nServiço ID: {$serviceid}";

        evoWhatsApp_sendMessage($notification, $adminTarget);
        $clientNumber = evoWhatsApp_getClientNumber($userid);
        if (!empty($clientNumber) && $clientNumber !== $adminTarget) {
            evoWhatsApp_sendMessage($notification, $clientNumber);
        }
    });

    // AfterModuleSuspend (Serviço Suspenso)
    add_hook('AfterModuleSuspend', 1, function($vars) use ($adminTarget) {
        evoWhatsApp_log("HOOK AfterModuleSuspend disparado. serviceid=" . ($vars['serviceid'] ?? 'N/A'));

        $serviceid = $vars['serviceid'] ?? null;
        if (!$serviceid) return;
        $hosting = Capsule::table('tblhosting')->where('id', $serviceid)->first();
        if (!$hosting) return;
        $userid = $hosting->userid;
        $productName = Capsule::table('tblproducts')
            ->where('id', $hosting->packageid)
            ->value('name') ?: 'N/A';

        $notification = "⏸️ *Serviço Suspenso!*\nProduto: {$productName}\nServiço ID: {$serviceid}";

        evoWhatsApp_sendMessage($notification, $adminTarget);
        $clientNumber = evoWhatsApp_getClientNumber($userid);
        if (!empty($clientNumber) && $clientNumber !== $adminTarget) {
            evoWhatsApp_sendMessage($notification, $clientNumber);
        }
    });

    // AfterModuleUnsuspend (Serviço Reativado)
    add_hook('AfterModuleUnsuspend', 1, function($vars) use ($adminTarget) {
        evoWhatsApp_log("HOOK AfterModuleUnsuspend disparado. serviceid=" . ($vars['serviceid'] ?? 'N/A'));

        $serviceid = $vars['serviceid'] ?? null;
        if (!$serviceid) return;
        $hosting = Capsule::table('tblhosting')->where('id', $serviceid)->first();
        if (!$hosting) return;
        $userid = $hosting->userid;
        $productName = Capsule::table('tblproducts')
            ->where('id', $hosting->packageid)
            ->value('name') ?: 'N/A';

        $notification = "▶️ *Serviço Reativado!*\nProduto: {$productName}\nServiço ID: {$serviceid}";

        evoWhatsApp_sendMessage($notification, $adminTarget);
        $clientNumber = evoWhatsApp_getClientNumber($userid);
        if (!empty($clientNumber) && $clientNumber !== $adminTarget) {
            evoWhatsApp_sendMessage($notification, $clientNumber);
        }
    });

    // AfterModuleTerminate (Serviço Cancelado)
    add_hook('AfterModuleTerminate', 1, function($vars) use ($adminTarget) {
        evoWhatsApp_log("HOOK AfterModuleTerminate disparado. serviceid=" . ($vars['serviceid'] ?? 'N/A'));

        $serviceid = $vars['serviceid'] ?? null;
        if (!$serviceid) return;
        $hosting = Capsule::table('tblhosting')->where('id', $serviceid)->first();
        if (!$hosting) return;
        $userid = $hosting->userid;
        $productName = Capsule::table('tblproducts')
            ->where('id', $hosting->packageid)
            ->value('name') ?: 'N/A';

        $notification = "❌ *Serviço Cancelado!*\nProduto: {$productName}\nServiço ID: {$serviceid}";

        evoWhatsApp_sendMessage($notification, $adminTarget);
        $clientNumber = evoWhatsApp_getClientNumber($userid);
        if (!empty($clientNumber) && $clientNumber !== $adminTarget) {
            evoWhatsApp_sendMessage($notification, $clientNumber);
        }
    });
}
