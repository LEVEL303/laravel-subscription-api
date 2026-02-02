# API REST para gerenciamento de assinaturas
Este projeto consiste em uma API REST focada no gerenciamento de usuários, planos (com funcionalidades) e assinaturas, com possibilidade de integração com gateways de pagamento.

## Tecnologias utilizadas
- Laravel
- Sanctum
- Filament
- MySQL

## Funcionalidades principais

#### Autenticação
- Cadastro
- Login
- Logout
- Recuperação de senha

#### Assinatura
- Criação
- Troca de plano
- Auto renovação
- Cancelamento
- Registro de faturas

#### Painel administrativo
- Gereciamento de usuários (CRUD)
- Gereciamento de planos (CRUD)
- Gerenciamento de funcionalidades (CRUD)
- Vinculação e desvinculação de funcionalidades em planos
- Visualização de assinaturas (com opção de cancelamento)
- Visualização de faturas
- Visualização de logs de webhooks

## Como rodar o projeto

### Pré-requisitos
- PHP >= 8.2
- Composer
- MySQL (ou outro SGBD de sua preferência)
- Postman (para testar as rotas)

### Passos
1. Clone o repositório do projeto:
```bash
    git clone https://github.com/LEVEL303/laravel-subscription-api.git
```
2. Entre no repositório do projeto:
```bash
    cd laravel-subscription-api
```
3. Instale as dependências do projeto:
```bash
    composer install
```
4. Crie o arquivo `.env` (copiar arquivo `.env.example`):
```bash
    cp .env.example .env
```
5. Gere a key da aplicação:
```bash
    php artisan key:generate
```
6. Configure o arquivo .env com as suas credenciais do banco de dados (DB_CONNECTION, DB_DATABASE, DB_USERNAME, DB_PASSWORD).
7. Execute as migrations:
```bash
    php artisan migrate
```
8. Crie um usuário 'admin' no banco para realizar login no painel administrativo.
9. Inicie o servidor de desenvolvimento do Laravel:
```bash
    php artisan serve
```
10. Utilize o Postman para testar as rotas ou acesse o painel administrativo em: http://127.0.0.1:8000/admin/login

---
Este projeto foi desenvolvido com a ajuda de IA (Gemini) como forma de aprendizado de construção de APIs com Laravel.