# Corporate Travel API - Desafio Técnico

Microsserviço desenvolvido em **Laravel** para gestão de pedidos de viagem corporativa, expondo uma API RESTful completa e segura.

## 🏗️ Arquitetura e Decisões de Design

Para garantir que a aplicação seja escalável, testável e de fácil manutenção, utilizei as seguintes abordagens baseadas em **Clean Code** e **Domain-Driven Design (DDD)**:

* **Service Layer (`TravelOrderService`)**: Toda a regra de negócio (como a transição de status e validação de aprovação/cancelamento) foi isolada em serviços, mantendo os Controllers "magros".
* **Enums (PHP 8.1+)**: Utilização de Enums (`TravelOrderStatus`) para garantir integridade de tipo e concentrar regras de estado (ex: método `canCancel()`).
* **API Resources & Form Requests**: Formatação consistente de saída JSON e validação de entrada isolada, cumprindo estritamente as boas práticas do framework.
* **Laravel Policies**: Autorização de acesso descentralizada (usuários só veem seus pedidos, e somente admins alteram status).
* **JWT Auth**: Autenticação stateless ideal para arquitetura de microsserviços.

## 🚀 Como Executar o Projeto Localmente

O projeto utiliza o **Laravel Sail (Docker)** para garantir um ambiente padronizado. Não é necessário ter PHP ou MySQL instalados localmente.

### 1. Clonar e Instalar Dependências
```bash
git clone <seu-link-do-github>
cd travel-api
cp .env.example .env

# Instalar dependências do Composer via container temporário
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs