# Projeto Pizzaria Premium 🍕

Um sistema completo de pedidos para pizzaria desenvolvido com PHP 8.2, Docker, Javascript (Vanilla) e TailwindCSS.

## 🚀 Como iniciar o projeto

### Opção 1: Usando Docker (Recomendado)

O projeto já vem configurado com Docker e Docker Compose, incluindo banco de dados MySQL e PHPMyAdmin.

1. **Certifique-se de ter Docker e Docker Compose instalados.**
2. Na raiz do projeto, execute:
   ```bash
   docker compose up -d --build
   ```
3. O servidor estará rodando em: `http://localhost:8080`
4. Acesse o PHPMyAdmin em: `http://localhost:8081`

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
4. Instale o TailwindCSS (para gerar os estilos):
   ```bash
   npm install
   npm run build:css
   ```
5. Inicie um servidor PHP:
   ```bash
   cd public
   php -S localhost:8080
   ```

## 🛠 Tecnologias Utilizadas

- **Backend**: PHP 8.2 (Vanilla com MVC simples)
- **Frontend**: HTML5, Javascript, TailwindCSS
- **Banco de Dados**: MySQL
- **Infraestrutura**: Docker & Docker Compose
- **Dependências**:
  - `phpdotenv`: Gestão de variáveis de ambiente
  - `google/apiclient`: Login com Google (Configurar credenciais no .env)

## 📁 Estrutura de Pastas

- `/src`: Código fonte do backend (Controllers, Models, Config)
- `/public`: Ponto de entrada (index.php), assets (CSS/JS compilados)
- `/views`: Arquivos de visualização (HTML/PHP mesclados)
- `/database.sql`: Schema inicial do banco de dados

## ✨ Funcionalidades

- Design Premium e Responsivo
- Cardápio Dinâmico (fácil de gerenciar via DB)
- Personalização de Produtos (escolha de sabores)
- Login/Cadastro (base pronta para Google Auth)
- Carrinho de Compras (base implementada)

---
Criado por Felipe Spengler
