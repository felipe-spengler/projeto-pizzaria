# 🔐 GUIA COMPLETO: Login com Google

## 📋 PASSO 1: Obter Credenciais do Google

### 1.1 Acesse o Google Cloud Console
Vá para: https://console.cloud.google.com/

### 1.2 Criar um Novo Projeto (se necessário)
1. Clique em "Select a project" no topo
2. Clique em "NEW PROJECT"
3. Nome: "Casa Nova Pizzaria" (ou qualquer nome)
4. Clique em "CREATE"

### 1.3 Ativar a Google+ API
1. No menu lateral, vá em: **APIs & Services → Library**
2. Procure por: "Google+ API"
3. Clique em "Google+ API"
4. Clique em "ENABLE"

### 1.4 Criar Credenciais OAuth 2.0
1. No menu lateral: **APIs & Services → Credentials**
2. Clique em "+ CREATE CREDENTIALS"
3. Selecione "OAuth client ID"
4. Se pedir, configure a "OAuth consent screen":
   - User Type: **External**
   - App name: **Casa Nova Pizzaria**
   - User support email: seu email
   - Developer contact: seu email
   - Clique em "SAVE AND CONTINUE"
   - Em Scopes, clique em "ADD OR REMOVE SCOPES"
   - Adicione: `email`, `profile`, `openid`
   - Clique em "SAVE AND CONTINUE"
   - Em Test users, adicione seu email do Google
   - Clique em "SAVE AND CONTINUE"

5. Volte para Credentials e crie o OAuth client ID:
   - Application type: **Web application**
   - Name: **Casa Nova Pizzaria Web Client**
   - Authorized JavaScript origins:
     - `http://localhost` (para desenvolvimento)
     - `http://seu-dominio.com` (para produção)
   - Authorized redirect URIs:
     - `http://localhost/projeto-pizzaria/public/google-callback.php`
     - `http://seu-dominio.com/google-callback.php`
   - Clique em "CREATE"

### 1.5 COPIE AS CREDENCIAIS!
Você receberá:
- **Client ID**: algo como `123456789-abc.apps.googleusercontent.com`
- **Client Secret**: algo como `GOCSPX-abc123def456`

⚠️ **IMPORTANTE**: Guarde essas credenciais em local seguro!

---

## 📋 PASSO 2: Instalar Biblioteca do Google

Execute este comando na pasta do projeto:

```bash
composer require google/apiclient:"^2.0"
```

---

## 📋 PASSO 3: Configurar Credenciais no Projeto

Crie o arquivo `.env` na raiz do projeto com o seguinte conteúdo:

```env
GOOGLE_CLIENT_ID=SEU_CLIENT_ID_AQUI
GOOGLE_CLIENT_SECRET=SEU_CLIENT_SECRET_AQUI
GOOGLE_REDIRECT_URI=http://localhost/projeto-pizzaria/public/google-callback.php
```

⚠️ **ATENÇÃO**: 
- Substitua `SEU_CLIENT_ID_AQUI` pelo Client ID que você copiou
- Substitua `SEU_CLIENT_SECRET_AQUI` pelo Client Secret que você copiou
- Ajuste a `GOOGLE_REDIRECT_URI` conforme sua URL local/produção

---

## 📋 PASSO 4: SEGURANÇA - Adicionar .env ao .gitignore

Adicione esta linha ao arquivo `.gitignore`:

```
.env
```

Isso evita que suas credenciais sejam enviadas para o GitHub!

---

## 📋 PASSO 5: Os Arquivos Necessários

Já criei todos os arquivos necessários para você:

1. ✅ `src/Auth/GoogleAuth.php` - Classe para gerenciar autenticação
2. ✅ `public/google-callback.php` - Página de callback do Google
3. ✅ `public/login.php` - Atualizado com botão do Google

---

## 🎯 RESUMO - O que você precisa fazer:

1. ☐ Acessar https://console.cloud.google.com/
2. ☐ Criar projeto e ativar Google+ API
3. ☐ Criar credenciais OAuth 2.0
4. ☐ Copiar Client ID e Client Secret
5. ☐ Executar: `composer require google/apiclient:"^2.0"`
6. ☐ Criar arquivo `.env` na raiz com as credenciais
7. ☐ Adicionar `.env` ao `.gitignore`
8. ☐ Testar o login!

---

## 🚀 Como Testar:

1. Acesse: `http://localhost/projeto-pizzaria/public/login.php`
2. Clique no botão "Continuar com Google"
3. Faça login com sua conta Google
4. Pronto! Você será autenticado automaticamente

---

## ⚠️ Problemas Comuns:

**Erro: redirect_uri_mismatch**
- Verifique se a URL em "Authorized redirect URIs" no Google Console está EXATAMENTE igual à configurada no `.env`

**Erro: This app isn't verified**
- Normal durante desenvolvimento
- Clique em "Advanced" → "Go to Casa Nova Pizzaria (unsafe)"
- Em produção, você pode solicitar verificação ao Google

**Erro: Class Google\Client not found**
- Execute: `composer require google/apiclient:"^2.0"`
- Verifique se o `vendor/autoload.php` está sendo carregado

---

## 📱 Exemplo de .env Completo:

```env
# Google OAuth
GOOGLE_CLIENT_ID=123456789-abcdefghijk.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-abc123def456ghi789
GOOGLE_REDIRECT_URI=http://localhost/projeto-pizzaria/public/google-callback.php

# Production (comentado durante desenvolvimento)
# GOOGLE_REDIRECT_URI=https://casanovapizzaria.com.br/google-callback.php
```

---

Qualquer dúvida, me avise! 🍕
