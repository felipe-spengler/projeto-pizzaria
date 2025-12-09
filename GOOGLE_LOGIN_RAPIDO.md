# 🚀 MÉTODO RÁPIDO - Usando arquivo JSON

## ✅ SUPER FÁCIL! Só 3 passos:

### 1️⃣ Renomear o arquivo JSON
Você baixou este arquivo:
```
client_secret_44526217793-nviblkftcs9cls1087f2j05fdr0bl0k5.apps.googleusercontent.com.json
```

**RENOMEIE para:**
```
google-credentials.json
```

### 2️⃣ Mover para a raiz do projeto
Cole o arquivo `google-credentials.json` na pasta:
```
c:\Users\Felipe\Desktop\projeto-pizzaria\
```

Deve ficar assim:
```
projeto-pizzaria/
├── google-credentials.json  ← AQUI!
├── public/
├── src/
├── vendor/
└── ...
```

### 3️⃣ ADICIONE ao Google Cloud Console
1. Volte para: https://console.cloud.google.com/apis/credentials
2. Clique no seu OAuth 2.0 Client ID
3. Em "Authorized redirect URIs", adicione:
   ```
   http://localhost/projeto-pizzaria/public/google-callback.php
   ```
4. Clique em "SAVE"

### 4️⃣ Instalar biblioteca (se ainda não fez)
```bash
composer require google/apiclient:"^2.0"
```

## 🎉 PRONTO!

Acesse: `http://localhost/projeto-pizzaria/public/login.php`

O botão "Continuar com Google" deve aparecer funcionando! 🚀

---

## 🔒 SEGURANÇA

⚠️ **IMPORTANTE**: Adicione ao `.gitignore`:
```
google-credentials.json
```

Isso já está configurado, mas verifique!

---

## ❓ Se der erro "redirect_uri_mismatch"

Isso significa que a URL configurada no Google não bate com a do seu site.

**Solução:**
1. Vá em: https://console.cloud.google.com/apis/credentials
2. Edite o OAuth 2.0 Client ID
3. Em "Authorized redirect URIs", certifique-se que tem EXATAMENTE:
   - `http://localhost/projeto-pizzaria/public/google-callback.php`
4. Salve e teste novamente

---

## 🎯 Resumo

- ✅ JSON renomeado para `google-credentials.json`
- ✅ JSON na raiz do projeto
- ✅ Redirect URI configurado no Google Console
- ✅ Biblioteca instalada com composer
- ✅ Arquivo no `.gitignore` (já está!)

**Muito mais fácil que usar .env!** 😎
