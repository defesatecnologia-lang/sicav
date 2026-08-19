# Sistema de Visitantes v1.0

Sistema web para controle de entrada e saída de visitantes com geração de crachás, relatórios e auditoria.

## Funcionalidades

- Cadastro de visitantes com foto via webcam
- Geração de crachá personalizado (impressão)
- Controle de entrada/saída com registro de data/hora
- Relatórios por período com gráficos
- Auditoria de todas as alterações
- Níveis de acesso: admin, supervisor, user
- Bloqueio automático após tentativas de login inválidas
- Proteção CSRF e sanitização XSS
- Sessões seguras com timeout configurável

## Tecnologias

- PHP 8.2
- MySQL 8.0
- Apache com mod_rewrite
- JavaScript (Vanilla + Chart.js)
- Bootstrap 5
- Docker Compose

## Requisitos

- PHP 8.2+
- MySQL 8.0+
- Extensões PHP: PDO_MySQL, GD, ZIP, JSON
- Apache com mod_rewrite

## Instalação local

```bash
# Clone o repositório
git clone https://github.com/GhostTerminals/sistema-visitantes-v1.0.git
cd sistema-visitantes-v1.0

# Configure o ambiente
cp .env.example .env
# Edite .env com suas credenciais do banco

# Instale dependências
composer install --no-dev

# Importe o schema do banco
mysql -u root -p < db/visitorpass.sql

# Configure o VirtualHost para apontar para /public
```

## Deploy com Docker

### Desenvolvimento

```bash
# Copie o arquivo de exemplo
cp .env.example.docker .env

# Inicie os containers (sem phpMyAdmin)
docker compose up -d

# Para incluir o phpMyAdmin (somente desenvolvimento):
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# Acesse:
# - App: http://localhost:8080
# - phpMyAdmin (apenas via override de dev): http://localhost:8081
```

### Produção

```bash
# Configure variáveis seguras
vim .env
# Use senhas fortes para: VISITORPASS_DB_PASS, MYSQL_ROOT_PASSWORD
# E gere a chave de criptografia do MFA (64 hex chars = 32 bytes):
#   echo "VISITORPASS_MFA_KEY=$(openssl rand -hex 32)" >> .env

# Build e inicie (phpMyAdmin e porta do MySQL NAO sao publicados em producao)
docker compose up -d --build
```

### Setup automático

```bash
# Cria .env, builda as imagens, sobe containers, aguarda o banco e
# verifica que os usuarios padrao (admin/supervisor) foram criados
./scripts/setup_docker.sh

# Forca rebuild das imagens
./scripts/setup_docker.sh --build

# Remove volumes e recria do zero (apaga o banco)
./scripts/setup_docker.sh --reset
```

O banco de dados não expõe porta no host. Acessos administrativos devem ser feitos via
`docker compose exec db mysql -u root -p` ou pela rede interna.

## Usuários padrão (Docker)

Os seguintes usuários são criados automaticamente na primeira execução:

| Usuário | Senha | Nível |
|---------|-------|-------|
| admin | Admin@2026Temp! | admin |
| supervisor | Supervisor@2026Temp! | supervisor |

**Altere as senhas imediatamente após o primeiro login.**

## Estrutura do projeto

```
sistema-visitantes-v1.0/
├── api/              # Endpoints REST
├── config/           # Configurações do app
├── db/               # Scripts SQL
├── docker/           # Configurações Docker
├── fotos_visitantes/ # Uploads de fotos (volume Docker)
├── inc/              # Helpers e includes
├── public/           # DocumentRoot (index.php, assets)
├── scripts/          # CRUD e controllers
├── tests/            # Testes unitários
├── vendor/           # Dependências Composer
├── Dockerfile        # Build da imagem PHP
├── docker-compose.yml
├── docker-compose.dev.yml  # Override de desenvolvimento (phpMyAdmin)
└── .env.example
```

## Licença

Uso interno.
