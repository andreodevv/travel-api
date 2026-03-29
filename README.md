# Travel API

Microsserviço desenvolvido em Laravel 13 (PHP 8.4) para gestão de pedidos de viagem corporativa. O projeto expõe uma API RESTful (v1) focada em alta coesão, rastreabilidade e arquitetura sustentável, requisitos indispensáveis para plataformas SaaS de gestão de despesas.

## Os Diferenciais de Engenharia (Over-delivery)

Além do escopo básico de um CRUD, este projeto implementa práticas de nível Sênior/Tech Lead para garantir segurança, performance e facilidade de manutenção:

* Documentação Viva (Swagger/OpenAPI): Geração automática de documentação interativa da API utilizando o pacote Scramble. A documentação lê nativamente os FormRequests e Resources, mantendo o contrato de dados sempre atualizado com o código.
* Compliance e Auditoria (Event Sourcing Lite): Implementação do pacote laravel-auditing para registrar o histórico imutável de alterações (Antes/Depois) de cada pedido. Criado um endpoint dedicado (/audits) que expõe uma Timeline do pedido, identificando autor, IP e data da ação. O acesso a este log é restrito a Administradores via Policies.
* State Guard (Trava de Estado Segura): Implementação rigorosa de bloqueios de edição. O sistema permite que o usuário atualize seu pedido para corrigir erros, mas apenas enquanto estiver no estado inicial ("solicitado"). Tentativas de alterar pedidos processados (Aprovados/Cancelados) são barradas matematicamente pela Policy e pelo Service, garantindo a imutabilidade do histórico.
* Domain Exceptions Customizadas: O projeto não mascara falhas com blocos try/catch genéricos no Controller. Regras de negócio quebradas lançam exceções específicas (ex: InvalidOrderTransitionException), que o framework intercepta na camada de formatação e devolve como um JSON estruturado (HTTP 422), mantendo os Controllers enxutos.
* Dicionário de Dados Embutido: As Migrations utilizam o método ->comment() nativo do banco de dados, documentando a finalidade de cada coluna diretamente no motor do banco (MySQL/PostgreSQL), facilitando a vida de DBAs e auditores de dados.
* Health Check Endpoint: A rota raiz (/) foi transformada em um endpoint de monitoramento de infraestrutura (Headless API), retornando dados vitais da API (versão, framework, timestamp) para integração com ferramentas como AWS Route53 ou UptimeRobot.
* Desacoplamento de Rotas: Utilização intensiva de rotas nomeadas (->name() e ->as('api.v1.')) em todo o sistema. Isso garante que mudanças futuras nas URLs não quebrem testes ou lógicas internas que dependem da geração de links.
* Developer Experience (DX) no Postman: A collection do Postman não é apenas um dump de rotas. Ela contém um Pre-request Script automatizado no endpoint de Login que captura o JWT gerado e o injeta como variável global, autenticando todas as rotas subsequentes instantaneamente.

## Fluxo de Vida do Sistema (Workflow)

O sistema foi desenhado para simular um fluxo real de aprovação corporativa. O caminho dos dados ocorre da seguinte forma:

1. Autenticação (JWT): O usuário envia credenciais e recebe um token assinado.
2. Criação: O usuário comum envia a requisição de viagem. O Controller valida datas e regras via FormRequest. O Service gera o Business Key (TRV-...) e um ULID interno, salvando o status inicial como "solicitado".
3. Edição Segura (Janela de Mutabilidade): Enquanto o pedido estiver pendente ("solicitado"), o usuário pode alterar origem, destino e datas caso tenha errado. Toda alteração é salva na trilha de auditoria.
4. Isolamento (Tenant): Ao listar pedidos, o sistema identifica via Token quem está logado. Usuários veem apenas os próprios registros; Admins veem o panorama geral.
5. Transição de Estado: Um gestor (Admin) avalia o pedido e submete um PATCH alterando o status para "aprovado" ou "cancelado", gerando um carimbo de processamento (processed_at) que torna o pedido permanentemente imutável para o solicitante.
6. Gatilhos em Background: O sistema grava a ação do Admin na tabela de auditoria e despacha uma notificação assíncrona (via Queue) avisando o usuário sobre a mudança de status.

## Arquitetura e Decisões de Design

Para garantir uma aplicação escalável, a arquitetura foi desenhada com base em padrões de Clean Code e separação de responsabilidades:

* Identificadores Seguros (ULID): Substituição do auto-incremento padrão por ULIDs na chave primária. Garante ordenação cronológica nativa, melhora a performance em bancos distribuídos e evita a vulnerabilidade de IDOR.
* Business Keys & Route Binding: Implementação de um order_number amigável (ex: TRV-ABC1234). O Route Model Binding foi sobrescrito para que a API resolva o recurso de forma transparente.
* Service Pattern: Centraliza as regras de negócio complexas e transições de estado dentro de Database Transactions (DB::transaction).
* Autenticação Stateless (JWT) e Rate Limiting: JSON Web Tokens para escalabilidade horizontal. Rotas sensíveis contam com o middleware throttle contra ataques de Brute Force.
* Strict Types e API Resources: Todo o código PHP utiliza declare(strict_types=1). Os Resources atuam como DTOs de saída.

Nota de Infraestrutura: Para o ambiente local de avaliação, a orquestração foi feita via Laravel Sail para garantir o melhor DX (Developer Experience) e ausência de atritos na máquina do revisor. Em um cenário real de deploy, esta camada seria substituída por um Dockerfile multi-stage otimizado (ex: FrankenPHP/Octane) focado em performance.

## Integração Contínua (CI/CD)

O repositório conta com um workflow automatizado configurado no GitHub Actions. A cada Push ou Pull Request na branch main:
* Um ambiente isolado (Ubuntu + PHP 8.4) é provisionado.
* O Composer instala as dependências de forma otimizada para CI.
* A suíte completa de testes (Unitários e Feature) é executada apontando para um banco SQLite em memória (:memory:), garantindo execução rápida e destruição do estado após cada teste.

## Como Executar o Projeto Localmente

A infraestrutura local é orquestrada via Laravel Sail (Docker) e um Makefile.

### Pré-requisitos
* Docker & Docker Compose
* Make (nativo em Linux/macOS ou via WSL no Windows)

### 1. Setup Automatizado
Na raiz do projeto, execute o comando abaixo. Ele criará o .env, subirá os containers, instalará dependências, gerará chaves e rodará as migrations/seeders:

make setup

### 2. Credenciais de Teste (Seeders)
O banco inicializa com dois perfis de teste para validação das regras de acesso (ACL):

Perfil          | E-mail          | Senha    | Permissoes
________________|_________________|__________|_______________________________________________________
Administrador   | admin@email.com | password | Aprova/cancela pedidos, ve todos usuarios e auditoria.
Usuario Comum   | user@email.com  | password | Cria/Edita pedidos e ve estritamente os proprios registros.

## Documentação Interativa da API (Swagger)

A API possui uma documentação OpenAPI gerada dinamicamente. Após subir os containers, você pode explorar e testar os endpoints diretamente pelo navegador acessando:

http://localhost/docs/api

## Testes Automatizados e Alta Performance

A aplicação possui 100% de cobertura nos fluxos críticos. 
* Time Travel Testing: Para testes de integração assíncronos e auditoria, utilizamos manipulação temporal nativa do Laravel (Time Travel) para evitar colisões de timestamp.
* Testes Unitários Puros: A máquina de estados (Enum TravelOrderStatus) possui testes unitários que rodam sem realizar o bootstrap do framework.

Para executar a suíte localmente:
make test

## Comandos Uteis do Makefile

* make up    : Sobe os containers em background.
* make down  : Encerra a execucao dos servicos.
* make shell : Abre uma sessao bash dentro do container da aplicacao (PHP).
* make logs  : Acompanha a saida de logs em tempo real.
* make clean : Remove containers e realiza o expurgo dos volumes do banco de dados.

---
Nota para avaliacao: Para conectar no banco via DBeaver ou TablePlus, utilize: 
Host: localhost, 
Porta: 3306, 
User: sail, 
Pass: password, 
DB:laravel.

O Dicionario de Dados pode ser lido nas propriedades das colunas do proprio banco.
