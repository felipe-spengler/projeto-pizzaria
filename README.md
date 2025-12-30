# Casa Nova Pizzaria 🍕

Sistema completo de pedidos online para pizzaria desenvolvido com PHP 8.2, MySQL, TailwindCSS e integração WhatsApp.

## 🚀 Como iniciar o projeto

### Opção 1: Usando Docker (Recomendado)

O projeto já vem configurado com Docker e Docker Compose, incluindo banco de dados MySQL e PHPMyAdmin.

1. **Certifique-se de ter Docker e Docker Compose instalados.**
2. Na raiz do projeto, execute:
   ```bash
   docker compose up -d --build
   ```
3. O servidor estará rodando em: `http://localhost:5000` (Porta interna 5000 exposta)
4. Acesse o PHPMyAdmin em: `http://localhost:5001`

**Observação:** A primeira vez que você rodar o docker-compose, o banco de dados será inicializado automaticamente com o schema definido em `database.sql`.

### Opção 2: Rodando Localmente (Sem Docker)

Se preferir rodar sem Docker:

1. Instale as dependências do PHP:
   ```bash
   composer install
   ```
2. Crie o arquivo `.env`:
   ```bash
   cp .env.example .env
   ```
   E configure as credenciais do seu banco de dados local.
3. Importe o arquivo `database.sql` no seu banco de dados MySQL.
4. Inicie um servidor PHP:
   ```bash
   cd public
   php -S localhost:8080
   ```

## 🔑 Acesso Admin

Para acessar o painel administrativo:
- **URL**: `/admin.php`
- **Usuário**: `admin`
- **Senha**: `admin123`

## 🛠 Tecnologias Utilizadas

- **Backend**: PHP 8.2 (OOP com PDO)
- **Frontend**: HTML5, Javascript (Vanilla), TailwindCSS
- **Banco de Dados**: MySQL 8.0
- **Infraestrutura**: Docker & Docker Compose
- **Dependências**:
  - `phpdotenv`: Gestão de variáveis de ambiente

## 📁 Estrutura de Pastas

```
projeto-pizzaria/
├── src/              # Classes PHP (Config, Database)
├── public/           # Ponto de entrada (index.php, admin.php, cart.php)
│   └── api/         # Endpoints AJAX (check_orders.php)
├── views/           # Templates PHP
│   ├── layouts/     # Header e Footer
│   └── admin/       # Templates do painel admin
├── database.sql     # Schema completo do banco
└── docker-compose.yml
```

## ✨ Funcionalidades Implementadas

### 🛒 **Sistema de Pedidos Completo**
- ✅ Cardápio dinâmico com categorias (Pizzas, Calzones, Combos, Bebidas)
- ✅ **Combos personalizáveis** com múltiplas etapas:
  - Seleção de sabores de pizza (salgadas)
  - Seleção de broto doce
  - Escolha de bebida com acréscimo opcional
- ✅ Sistema de sabores com preços adicionais (ex: Gourmet +R$ 5,00)
- ✅ Carrinho de compras inteligente com cálculo automático
- ✅ **Gerenciamento de endereços**:
  - Salvamento automático de endereços usados
  - Seleção rápida de endereços anteriores
  - Opção de "Entrega" ou "Retirada no balcão"
- ✅ Múltiplas formas de pagamento (PIX, Cartão, Dinheiro)
- ✅ Campo de observações para customização

### 📱 **Integração WhatsApp**
- ✅ Confirmação de pedido via WhatsApp
- ✅ Mensagem detalhada com:
  - Informações do cliente
  - Endereço de entrega completo
  - Lista de itens com sabores escolhidos
  - Forma de pagamento
  - Observações
  - Total do pedido

### 👨‍💼 **Painel Administrativo Avançado**
- ✅ **Dashboard em tempo real**:
  - Estatísticas do dia (faturamento, pedidos)
  - Lista de pedidos recentes
- ✅ **Sistema de notificações**:
  - Verificação automática de novos pedidos (a cada 10s)
  - Som de campainha para alertar novos pedidos
  - Destaque visual para pedidos não visualizados (negrito + badge "NOVO")
  - Background amarelo para pedidos não lidos
- ✅ **Gerenciamento de status**:
  - Pending → Preparing → Out for Delivery → Delivered
  - Opção de cancelamento
- ✅ **Impressão térmica**:
  - Layout otimizado para impressoras de 80mm (ESC/POS)
  - Formato tipo cupom iFood
  - Impressão com todos os detalhes do pedido

### 🎨 **Interface Premium**
- ✅ Design moderno e responsivo
- ✅ Tailwind CSS com tema customizado
- ✅ Animações suaves e micro-interações
- ✅ Otimizado para mobile e desktop
- ✅ Font Awesome para ícones

### 🔐 **Autenticação**
- ✅ Sistema de login/cadastro
- ✅ Sessões seguras
- ✅ Proteção de rotas administrativas
- ✅ Login social com **Google OAuth**
- ✅ Diferenciação de roles (admin/customer)

## 📊 Banco de Dados

### Tabelas Principais

- **`users`**: Clientes e administradores
- **`addresses`**: Endereços de entrega salvos por usuário
- **`products`**: Produtos do cardápio
- **`categories`**: Categorias de produtos
- **`flavors`**: Sabores disponíveis (salgados, doces, bebidas)
- **`orders`**: Pedidos realizados
- **`order_items`**: Itens de cada pedido
- **`order_item_flavors`**: Sabores escolhidos por item

### Migrations Disponíveis

Se você já tem um banco rodando, use estes scripts SQL para atualizar:

- `update_addresses.sql` - Adiciona tabela de endereços
- `update_orders_table.sql` - Adiciona colunas de método de entrega e pagamento
- `add_viewed_column.sql` - Adiciona controle de pedidos visualizados
- `update_drink_prices.sql` - Atualiza preços das bebidas nos combos

## 🚀 Deployment (Coolify/Docker/VPS)

O projeto está otimizado para deploy em VPS usando Docker.

### Configuração de Portas
- **Porta Interna (Container)**: `5000`
- **Porta Externa (Host)**: `5000` (ou mapeie `80:5000` via proxy reverso)

### Configuração de Domínio e Subdomínio
1. Crie um registro **Tipo A** no seu gerenciador de DNS (Cloudflare, Hostinger, etc).
   - **Nome**: `pizzaria` (para `pizzaria.seudominio.com`)
   - **Destino**: `IP_DA_SUA_VPS`
2. Configure seu Proxy/Painel (ex: Coolify):
   - **Domains**: `https://pizzaria.seudominio.com`
   - **Port Exposes**: `5000`

### Variáveis de Ambiente (Google Login)
Para o login com Google funcionar, configure estas variáveis no seu painel:

```bash
GOOGLE_CLIENT_ID=seu_client_id_google
GOOGLE_CLIENT_SECRET=seu_client_secret_google
GOOGLE_REDIRECT_URI=https://pizzaria.seudominio.com/google-callback.php
```
> **Nota:** Certifique-se de adicionar a URL autorizada no Console do Google Cloud.

### Usando o Script de Deploy
O projeto inclui um script `deploy.sh` otimizado:

```bash
# Deploy rápido (usa cache)
./deploy.sh --quick

# Deploy apenas de arquivos alterados (git diff)
./deploy.sh --changed

# Rebuild completo sem cache
./deploy.sh --no-cache
```

## 📝 TODO / Melhorias Futuras

- [ ] Implementar hashing de senhas (bcrypt)
- [ ] Sistema de cupons de desconto
- [ ] Histórico completo de pedidos do cliente
- [ ] Relatórios de vendas (gráficos)
- [ ] Notificações push para clientes
- [ ] API REST completa
- [ ] Modo escuro

## 🐛 Troubleshooting

### Erro: "Column 'viewed' not found"
Execute o script: `add_viewed_column.sql`

### Erro: "Column 'delivery_method' not found"
Execute o script: `update_orders_table.sql`

### Bebidas nos combos não mostram preço adicional
Execute o script: `update_drink_prices.sql`

---

**Desenvolvido por Felipe Spengler** | Casa Nova Pizzaria 2024
