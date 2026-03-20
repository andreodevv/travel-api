# Corporate Travel API - Desafio Técnico

Microsserviço desenvolvido em **Laravel 13** para gestão de pedidos de viagem corporativa, expondo uma API RESTful completa e segura com foco em alta coesão e arquitetura profissional.

## 🏗️ Arquitetura e Decisões de Design

Para garantir uma aplicação escalável e de fácil manutenção, utilizei padrões de **Clean Code** e **SOLID**:

* **Service Layer (`TravelOrderService`)**: Centraliza as regras de negócio e transições de estado, mantendo os Controllers focados apenas na interface HTTP.
* **Enums (PHP 8.2)**: Uso de Enums nativos para gerenciar os status (`solicitado`, `aprovado`, `cancelado`), garantindo integridade de tipos e centralização de lógica de estado.
* **Policies & Gates**: Autorização granular onde usuários comuns acessam apenas seus dados, enquanto administradores possuem permissões globais.
* **Soft Deletes**: Implementação de exclusão lógica em Pedidos e Usuários para preservação de histórico e auditoria.
* **Notifications (Queued)**: Notificações de alteração de status enviadas via e-mail e processadas em **background jobs (Queue)** para garantir performance na resposta da API.
* **API Resources**: Camada de transformação de dados que isola a estrutura do banco de dados do contrato da API (JSON).

---

## 🚀 Como Executar o Projeto Localmente

Este projeto utiliza o **Laravel Sail (Docker)** e um **Makefile** para automatizar toda a infraestrutura.

### Pré-requisitos
* Docker & Docker Compose
* Make (instalado nativamente na maioria dos sistemas Linux/macOS ou via WSL no Windows)

### 1. Setup Automatizado (O "One Command" Setup)
Clone o repositório e, dentro da pasta do projeto, execute o comando abaixo. Ele irá criar o `.env`, instalar dependências via container temporário, subir os serviços, gerar chaves (Application e JWT) e popular o banco de dados:

make setup

### 2. Credenciais de Teste (Seeders)
O banco de dados já vem populado com dois perfis para validação das regras de negócio:

Perfil          | E-mail            | Senha    | Permissões
________________|___________________|__________|__________________________________________________________________
Administrador   | admin@email.com   | password | Pode aprovar/cancelar qualquer pedido e ver todos os usuários.
Usuário Comum   | user@email.com    | password | Cria pedidos e visualiza apenas os próprios pedidos.

---

## 🧪 Testes Automatizados

A suíte de testes cobre o "Caminho Feliz" (Happy Path), validações de formulário (FormRequests), políticas de acesso, filtros de data e disparo de notificações:

make test

---

## 📬 Postman & Documentação

Na raiz do projeto, você encontrará a pasta `/postman` contendo o arquivo `Travel_API_Final.json`. 
* A collection já utiliza **Bearer Token** via variáveis de coleção.
* Ao realizar o Login, o token JWT é capturado automaticamente via script de teste e aplicado em todas as outras requisições da pasta.
* Os endpoints de consulta e status utilizam **Path Variables** (`:travel_order_id`) para facilitar a troca de IDs durante os testes.

---

## 🛠️ Comandos Úteis do Makefile

* make up : Sobe os containers em background.
* make down : Para os serviços.
* make shell : Abre o terminal dentro do container da aplicação (PHP).
* make logs : Acompanha os logs em tempo real.
* make clean : Remove containers e **limpa os volumes do banco de dados**.

---

### 💡 Dica para o Avaliador (Conexão com Banco)
Para conectar no banco de dados via ferramenta externa (DBeaver, TablePlus, etc):
* **Host**: `localhost` | **Porta**: `3306` (ou `33060` caso a 3306 esteja ocupada)
* **User/Pass**: `sail` / `password` | **Database**: `travel_api`