# Evolution WhatsApp Notifications

**Evolution WhatsApp Notifications** é um módulo para WHMCS que integra notificações via WhatsApp utilizando a Evolution API. Ele envia mensagens automáticas para administradores e clientes para eventos relacionados a tickets, faturas, pedidos e serviços. <br> O módulo inclui recursos avançados, como validação de licença com cache, logs detalhados e exibição de informações do ambiente.

---

## 🔍 Tabela de Conteúdos

- [Visão Geral](#visão-geral)
- [Recursos](#recursos)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Fluxo de Operação](#fluxo-de-operação)
- [Estrutura de Arquivos](#estrutura-de-arquivos)
- [Funções Principais](#funções-principais)
- [Logs e Auditoria](#logs-e-auditoria)
- [Sistema de Cache e Informações do Ambiente](#sistema-de-cache-e-informações-do-ambiente)
- [Desenvolvimento e Contribuição](#desenvolvimento-e-contribuição)
- [Licença](#licença)
- [Suporte](#suporte)

---

## 📄 Visão Geral

O **Evolution WhatsApp Notifications** permite a automação de notificações via WhatsApp no WHMCS para eventos como:

- ✉️ **Tickets**: abertura, resposta e fechamento.
- 📄 **Faturas**: criação, pagamento e cancelamento.
- 👨‍🎓 **Pedidos e Serviços**: aceitação, suspensão, reativação e cancelamento.

Possui também:
- **Validação de licença** via servidor remoto com cache para melhor desempenho.
- **Logs detalhados** para auditoria e diagnóstico.
- **Painel informativo** com detalhes do ambiente e status da licença.

---

## 🔧 Recursos

### 💬 Notificações Automatizadas
- Tickets (abertura, respostas, fechamento)
- Faturas (criação, pagamento, cancelamento)
- Pedidos e Serviços (aceitação, suspensão, reativação, cancelamento)

### 🔒 Validação de Licença com Cache
- Valida via API da Evolution
- Cache por 10 minutos para reduzir consultas repetidas
- Opção para forçar a sincronização

### 🗒️ Logs de Auditoria
- Registra todas as ações críticas
- Inclui alterações (valores antigos e novos)

### 📚 Informações do Ambiente
- Exibe versão do PHP, servidor web e status da licença
- Integrado à interface administrativa

---

## ⚙️ Requisitos

- WHMCS compatível com módulos de addon
- PHP 7.2 ou superior
- Extensões **cURL** e **JSON** habilitadas
- Banco de dados **MySQL**
- Acesso à **Evolution API**

---

## 🛠️ Instalação

1. **Upload do Módulo:**
   - Copie a pasta `evolutionwhatsapp` para `modules/addons/` do WHMCS.

2. **Ativação:**
   - No WHMCS, acesse **Setup > Addon Modules**, localize o módulo e clique em **Ativar**.

3. **Configuração da Licença:**
   - Adicione ao `configuration.php`:
     
     ```php
     define('EVOLUTION_WHATSAPP_LICENSE', 'SUA_CHAVE_DE_LICENCA_AQUI');
     ```
---

## 💡 Configuração

No WHMCS, acesse **Setup > Addon Modules** para configurar:
- **API URL**: URL base da Evolution API.
- **Instance ID**: Identificador da instância na API.
- **API Key**: Chave de acesso.
- **Número Padrão (Admin)**: Caso o grupo não esteja definido.
- **Admin Grupo**: ID do grupo do WhatsApp.
- **Notificações**: Habilitar/desabilitar para cada evento.
- **Caminho do Log**: `modules/addons/evolutionwhatsapp/whatsapp.log`

---

## 📊 Fluxo de Operação

1. **Validação de Licença**:
   - `checkLicenseWithCache()` armazena o resultado por 10 minutos.

2. **Envio de Notificações**:
   - Hooks do WHMCS enviam mensagens para o grupo/admin e clientes.

3. **Logs e Auditoria**:
   - Todas as ações são registradas no log `whatsapp.log`.

---

## 🗂️ Estrutura de Arquivos

```
modules/addons/evolutionwhatsapp/
├── evolutionwhatsapp.php  # Arquivo principal
├── hooks.php              # Hooks para eventos do WHMCS
├── whatsapp_common.php    # Funções principais
├── whatsapp.log           # Log do módulo
└── (outros arquivos de suporte)
```

---

## 💡 Desenvolvimento e Contribuição

Contribuições são bem-vindas! Para contribuir:
- **Fork** este repositório
- **Crie uma branch** com suas alterações
- **Envie um pull request**

---

## ✅ Licença

Este projeto é licenciado sob a **MIT License**.

---

## 🔑 Painel de Licenciamento (Necessário)
Para gerenciar licenças de forma centralizada, utilize o [Painel de Licenciamento](#).

O painel permite:
- Criar, editar e renovar licenças.
- Controlar logs e auditoria de acessos.
- Validar licenças de forma otimizada via cache.

Se deseja utilizar o módulo sem verificação de licença, esta etapa pode ser ignorada.

➡️ **Acesse o repositório do Painel de Licenciamento:** [Evolution License Panel](#)

## 🌐 Suporte

Para suporte comercial, entre em contato através do elvis@ebyte.net.br.

