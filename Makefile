# Define variáveis para não repetir código
SAIL := ./vendor/bin/sail

# Ignora pastas com o mesmo nome dos comandos
.PHONY: help setup up down restart test shell logs clean

# Exibe o menu de ajuda por padrão se o usuário digitar apenas "make"
help:
	@echo "Comandos disponíveis:"
	@echo "  make setup    - Configura o projeto do zero (copia .env, instala dependências, sobe os containers, migra e popula o banco)"
	@echo "  make up       - Sobe os containers em background"
	@echo "  make down     - Derruba os containers"
	@echo "  make restart  - Reinicia os containers"
	@echo "  make test     - Roda a suíte de testes (PHPUnit)"
	@echo "  make shell    - Abre o terminal dentro do container da aplicação (PHP)"
	@echo "  make logs     - Mostra os logs dos containers"
	@echo "  make clean    - Derruba os containers e apaga o banco de dados (volumes)"

setup:
	@echo "🚀 Iniciando o setup do projeto..."
	@if [ ! -f .env ]; then cp .env.example .env; echo "✅ .env criado."; fi
	@echo "📦 Instalando dependências do Composer (isso pode demorar na primeira vez)..."
	@docker run --rm \
		-u "$$(id -u):$$(id -g)" \
		-v "$$(pwd):/var/www/html" \
		-w /var/www/html \
		laravelsail/php83-composer:latest \
		composer install --ignore-platform-reqs
	@echo "🐳 Subindo os containers..."
	@$(SAIL) up -d
	@echo "🔑 Gerando chaves do Laravel e JWT..."
	@$(SAIL) artisan key:generate
	@$(SAIL) artisan jwt:secret --force
	@echo "🗄️ Rodando migrations e seeders..."
	@$(SAIL) artisan migrate:fresh --seed
	@echo "🎉 Setup concluído! A API está rodando em http://localhost"

up:
	@$(SAIL) up -d

down:
	@$(SAIL) down

restart:
	@$(SAIL) down
	@$(SAIL) up -d

test:
	@$(SAIL) artisan test

shell:
	@$(SAIL) shell

logs:
	@$(SAIL) logs -f

clean:
	@echo "⚠️ Destruindo containers e volumes do banco de dados..."
	@$(SAIL) down -v
	@echo "🧹 Limpeza concluída."