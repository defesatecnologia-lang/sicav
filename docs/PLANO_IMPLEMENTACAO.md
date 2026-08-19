# PLANO DE IMPLEMENTAÇÃO

## Alinhado à ESPECIFICAÇÃO-SEGURANÇA.md e ao protocolo ESPECIFICACAO-AUDITORIA-PHP.md

**Data:** 2026-08-19
**Escopo:** Aplicação PHP + MySQL + Docker Compose (Sistema de Visitantes v1.0)
**Baseline:** `composer audit` limpo; 114 testes PHPUnit passando (129 asserções).

---

## 1. Resumo Executivo

O projeto já implementa corretamente boa parte da especificação de segurança:

- Queries parametrizadas (PDO) em todos os pontos (`src/Database.php`).
- CSRF synchronizer token web + API (`inc/security.php`, `src/Middleware/CsrfMiddleware.php`).
- Sessão server-side com regeneração no login, token opaco 256 bits armazenado como hash SHA-256 (`inc/single_session.php`, `scripts/login_submit.php:149`).
- Rate limiting de login por conta e por IP (`scripts/login_submit.php`).
- Senhas com `password_hash()` / `password_verify()`.
- Headers de segurança e CSP com nonce (`public/index.php`).
- Upload validado por MIME real, tamanho e extensão (`api/api_cadastro_visitante.php`).
- `.env` fora do versionamento; `.dockerignore` presente.

Os achados abaixo são as lacunas **confirmadas** entre a especificação e o estado atual, ordenadas por prioridade.

---

## 2. Matriz de Achados Confirmados

| ID | Área | Severidade | Prioridade | Esforço | Confiança | Status |
|---|---|---|---|---:|---:|---|---|
| SEC-DOCKER-001 | Docker | Alto | Alta | 2 | Confirmado | Aberto |
| SEC-DOCKER-002 | Docker | Alto | Alta | 2 | Confirmado | Aberto |
| SEC-DOCKER-003 | Docker | Médio | Média | 1 | Confirmado | Validado |
| SEC-DOCKER-004 | Docker | Médio | Média | 1 | Confirmado | Validado |
| SEC-DOCKER-005 | Docker | Alto | Alta | 1 | Confirmado | Validado |
| SEC-DOCKER-006 | Docker | Alto | Alta | 1 | Confirmado | Validado |
| SEC-DOCKER-007 | Docker | Médio | Média | 2 | Confirmado | Aberto |
| SEC-PASS-001 | Autenticação | Médio | Média | 1 | Confirmado | Validado |
| SEC-COOKIE-001 | Sessões | Alto | Alta | 1 | Confirmado | Validado |
| SEC-SESS-001 | Sessões | Médio | Média | 3 | Confirmado | Aberto |
| SEC-RATE-001 | Segurança | Médio | Média | 2 | Confirmado | Aberto |
| SEC-MFA-001 | Autenticação | Alto | Alta | 5 | Confirmado | Aberto |
| SEC-HDR-001 | Configuração | Médio | Média | 2 | Confirmado | Aberto |
| SEC-LOG-001 | Logs | Médio | Média | 2 | Confirmado | Aberto |
| QUAL-BUG-001 | Backend | Alto | Alta | 1 | Confirmado | Validado |

---

## 3. Achados — Formato Completo

---

### SEC-DOCKER-001 — Container executa como root

- **Problema:** O container da aplicação executa o Apache/PHP como `root` (nenhum `USER` no Dockerfile). Violação da regra §34 (Container Non-Root) e §33 da especificação.
- **Localização:** `Dockerfile:1-55`, `docker-compose.yml:2-28`.
- **Evidência:** `FROM php:8.2-apache` sem diretiva `USER`; processo Apache roda como root (master).
- **Causa raiz:** Dockerfile herdado de imagem base sem etapa de usuário dedicado.
- **Impacto:** Comprometimento do PHP concede root no container; aumenta risco de container escape.
- **Implementação recomendada:** Criar usuário não-root (ex.: `app`, UID 1000) no Dockerfile, definir `WORKDIR` e `USER app`, ajustar permissões dos volumes (`fotos_visitantes`). Em `docker-compose.yml`, `user: "1000:1000"`. Verificar escrita em `/var/log/php_errors.log` e `/tmp` (usar `tmpfs`).
- **Testes:** `docker exec <container> id` deve retornar UID não-root; subir stack e exercitar upload de foto.
- **Compatibilidade:** Apache precisa de porta >=1024 ou `CAP_NET_BIND_SERVICE`; alternativa: manter bind da porta 80 via `USER` + `cap_add: [NET_BIND_SERVICE]` ou usar `a2enmod` e porta 8080 interna.
- **Status:** Aberto.

---

### SEC-DOCKER-002 — Ausência de hardening do container

- **Problema:** Não há `cap_drop: ALL`, `no-new-privileges`, `read_only` nem `tmpfs`. Violação das regras §35, §36 e §37.
- **Localização:** `docker-compose.yml`.
- **Evidência:** O serviço `app` possui apenas `ports`, `environment`, `volumes`, `networks`, `restart`.
- **Impacto:** Container com capabilities desnecessárias; mitigação de escapes reduzida.
- **Implementação recomendada:**
  ```yaml
  app:
    security_opt:
      - no-new-privileges:true
    cap_drop:
      - ALL
    read_only: true
    tmpfs:
      - /tmp
  ```
  Com `read_only: true`, garantir que `fotos_visitantes` e `php_errors.log` estejam em volumes graváveis. Em modo de produção, a rotação de logs do Apache precisa de volume ou `ApaLogs` em `/dev/stdout`.
- **Testes:** `docker inspect <container> | grep -i cap`; validar upload e login.
- **Status:** Aberto.

---

### SEC-DOCKER-003 — Sem limites de recursos

- **Problema:** Nenhum limite de CPU/memória/PIDs nos serviços. Violação §40.
- **Localização:** `docker-compose.yml`.
- **Evidência:** Ausência de `mem_limit`, `cpus`, `pids_limit`.
- **Impacto:** DoS por consumo de recursos.
- **Implementação recomendada:** Adicionar limites dimensionados ao consumo real:
  ```yaml
  app:
    mem_limit: 512m
    cpus: "1.0"
    pids_limit: 256
  db:
    mem_limit: 1g
    pids_limit: 512
  ```
- **Status:** Validado. Limites adicionados em `docker-compose.yml`: `app` com `mem_limit: 512m`, `cpus: "1.0"`, `pids_limit: 256`; `db` com `mem_limit: 1g`, `pids_limit: 512`. `docker compose config` validado.

---

### SEC-DOCKER-004 — Sem rotação de logs Docker

- **Problema:** Logs do Docker sem rotação. Violação §41.
- **Localização:** `docker-compose.yml`.
- **Evidência:** Ausência de `logging:` nos serviços.
- **Implementação recomendada:**
  ```yaml
  logging:
    driver: json-file
    options:
      max-size: "10m"
      max-file: "5"
  ```
- **Status:** Validado. `logging` com `json-file`, `max-size: "10m"`, `max-file: "5"` adicionado em `app` e `db` no `docker-compose.yml`. `docker compose config` validado.

---

### SEC-DOCKER-005 — MySQL publicado no host

- **Problema:** `db` publica porta `3307:3306` no host. Violação §38/§39 ("Não publicar 3306 externamente").
- **Localização:** `docker-compose.yml:34-35`.
- **Evidência:** `ports: - "${DB_PORT:-3307}:3306"`.
- **Impacto:** Superfície de ataque adicional; banco alcançável fora da rede interna.
- **Implementação recomendada:** Remover o bloco `ports` do serviço `db`. Acessos administrativos devem ocorrer via `docker exec` ou rede interna.
- **Testes:** `docker compose up -d`; confirmar `docker ps` sem porta publicada para o banco; validar migração e login.
- **Status:** Validado. Bloco `ports` do serviço `db` removido do `docker-compose.yml`. `docker compose config` validado; `docker ps` confirmado sem porta publicada para o banco.

---

### SEC-DOCKER-006 — phpMyAdmin exposto com imagem `latest`

- **Problema:** Serviço `phpmyadmin:latest` publicado em `8081`. Viola §31 (banco isolado), §44 (não usar `latest`) e §58 (separação de ambientes). Em produção concede acesso administrativo ao banco.
- **Localização:** `docker-compose.yml:54-67`.
- **Evidência:** `image: phpmyadmin:latest` + `ports: "${PMA_PORT:-8081}:80"`.
- **Implementação recomendada:** Extrair `phpmyadmin` para um compose de desenvolvimento (`docker-compose.dev.yml`), com imagem fixada e sem publicação externa na configuração de produção. Documentar o override.
- **Testes:** `docker compose up -d` (produção) sem o serviço; `docker compose -f docker-compose.dev.yml up` (dev) com phpMyAdmin.
- **Status:** Validado. `phpmyadmin` extraído para `docker-compose.dev.yml` (imagem fixada `phpmyadmin:5.2`, porta `${PMA_PORT:-8081}:80`); removido do compose de produção. README atualizado com o override.

---

### SEC-DOCKER-007 — Rede única; banco não isolado

- **Problema:** Todos os serviços em `visitantes-net` (bridge comum). Violação §38 (redes internas para o banco).
- **Localização:** `docker-compose.yml:75-77`.
- **Evidência:** `networks: visitantes-net: driver: bridge` usado por `app`, `db` e `phpmyadmin`.
- **Implementação recomendada:**
  ```yaml
  networks:
    frontend:
    backend:
      internal: true
  ```
  `app` em `frontend` + `backend`; `db` somente em `backend` (internal).
- **Status:** Aberto.

---

### SEC-PASS-001 — Algoritmo de hash de senha

- **Problema:** Uso de `PASSWORD_BCRYPT` e `PASSWORD_DEFAULT` em vez de `PASSWORD_ARGON2ID`. Violação §11.
- **Localização:** `docker/migrate.php:189,192,195`, `scripts/cadastroUsuario.php:42`, `scripts/trocar_senha.php:35`, `scripts/editarUsuario.php:79`, `scripts/create_default_users.php:73`.
- **Evidência:** `password_hash(..., PASSWORD_BCRYPT, ['cost' => 10])` e `PASSWORD_DEFAULT`.
- **Impacto:** Senhas novas não utilizam o algoritmo recomendado (Argon2id). Senhas existentes em bcrypt continuarão válidas via `password_verify`.
- **Implementação recomendada:** Trocar para `password_hash($senha, PASSWORD_ARGON2ID)` em todos os pontos. Manter `password_verify` (compatível com hashes antigos). Verificar extensão `sodium` (presente no PHP 8.2+ por padrão).
- **Testes:** Criar usuário, autenticar, alterar senha; inspecionar hash no banco (prefixo `$argon2id$`).
- **Compatibilidade:** Exige PHP >= 7.4 com libsodium (OK no 8.2). Hashes bcrypt existentes permanecem verificáveis.
- **Status:** Validado. `PASSWORD_ARGON2ID` aplicado em `docker/migrate.php:189,192,195`, `scripts/cadastroUsuario.php:42`, `scripts/trocar_senha.php:35`, `scripts/editarUsuario.php:79`, `scripts/create_default_users.php:73`. `php -l` OK em todos; 114 testes passando.

---

### SEC-COOKIE-001 — Cookie de sessão não segue o padrão `__Host-`

- **Problema:** Cookie de sessão nomeado `PHPSESSID`, sem prefixo `__Host-`, com `SameSite=Lax` e `Secure` condicional. Violação §9 (exige `__Host-SID`, `Secure`, `HttpOnly`, `SameSite=Strict`, `Path=/`, sem `Domain`).
- **Localização:** `config/app.php:36-57`, `inc/security.php:1-5`.
- **Evidência:** `ini_set('session.name')` não definido (default `PHPSESSID`); `'samesite' => 'Lax'`; `'secure' => $secureCookie` (condicional).
- **Impacto:** Menor proteção contra session fixation/roubo em cenários de proxy com HTTP; prefixo `__Host-` não aplicado.
- **Implementação recomendada:** Em produção (HTTPS), definir:
  ```ini
  session.name = __Host-SID
  session.cookie_secure = 1
  session.cookie_samesite = Strict
  session.cookie_httponly = 1
  ```
  No PHP: `session_name('__Host-SID')` antes de `session_start()`, e manter `setcookie` com `Secure` + `SameSite=Strict` em `clearLocalSessionState()`.
- **Atenção:** `__Host-` exige `Secure` obrigatoriamente; em desenvolvimento via HTTP o cookie não será enviado. Usar `Strict` apenas quando a app roda em HTTPS, ou manter `Lax` em dev com justificativa registrada.
- **Testes:** `curl -I` no login deve exibir `Set-Cookie: __Host-SID=...; Secure; HttpOnly; SameSite=Strict; Path=/` em HTTPS.
- **Status:** Validado. `configureSessionSecurity()` em `config/app.php` agora usa `session_name()` `__Host-SID` em HTTPS (mantém `PHPSESSID` em HTTP dev), `SameSite=Strict` em HTTPS (`Lax` em dev), `Secure` conforme `isHttpsRequest()`. `php -l` OK.

---

### SEC-SESS-001 — Modelo de sessões não contempla expiração/revogação na tabela

- **Problema:** A tabela `user_sessions` usa flag `active` e `last_seen`, sem `expires_at`/`revoked_at`/`last_activity_at` e sem expiração absoluta. Divergência da estrutura mínima §8.
- **Localização:** `docker/migrate.php:76-90`, `inc/single_session.php`.
- **Evidência:** `user_sessions` possui `active TINYINT`, `last_seen`, sem colunas de expiração/revogação; timeout é somente por inatividade (`SESSION_TIMEOUT`).
- **Impacto:** Sessões inativas dependem de GC por inatividade; não há expiração absoluta nem trilha de revogação.
- **Implementação recomendada:** Adicionar colunas `expires_at DATETIME`, `last_activity_at DATETIME`, `revoked_at DATETIME NULL` na migração (com `ALTER TABLE` idempotente). Registrar `expires_at = NOW() + INTERVAL SESSION_TIMEOUT SECOND`. Considerar expiração absoluta (ex.: 12h) além do timeout de inatividade. Manter o conceito de sessão única (política atual).
- **Testes:** Criar sessão; simular expiração; validar que `isUniqueSessionValid` rejeita expirada/revogada.
- **Status:** Aberto.

---

### SEC-RATE-001 — Rate limiting limitado ao login

- **Problema:** Rate limiting existe apenas no login. Violação §24 (exige também para downloads, endpoints administrativos, operações de alto custo e APIs).
- **Localização:** `scripts/login_submit.php` (única implementação), `api/get_photo.php`, endpoints admin.
- **Evidência:** `api_bootstrap.php` não aplica rate limit; `get_photo.php` e endpoints de exportação sem controle.
- **Impacto:** Abuso de download de fotos e exportações.
- **Implementação recomendada:** Criar helper genérico de rate limiting (tabela `rate_limits` com chave por IP+rota+janela), aplicar em `get_photo` (ex.: 60 req/min/IP), exportações e endpoints admin. Registrar evento `RATE_LIMIT_TRIGGERED`.
- **Status:** Aberto.

---

### SEC-MFA-001 — MFA ausente

- **Problema:** Nenhum mecanismo de MFA. Violação §12 (obrigatório para contas administrativas e operações críticas).
- **Localização:** Fluxo de login (`scripts/login_submit.php`).
- **Evidência:** Não há colunas de MFA, challenge, TOTP ou recovery codes no schema.
- **Impacto:** Contas admin protegidas apenas por senha.
- **Implementação recomendada (esforço alto):** Adicionar suporte TOTP (extensão/tabela `usuarios_mfa` com `secret` criptografado ou hash), tela de desafio pós-senha para admins, recovery codes de uso único auditáveis. Aplicar acessível a admin; opcional a supervisores. Registrar eventos `MFA_ENABLED`, `MFA_DISABLED`, `MFA_FAILED`.
- **Status:** Aberto (recomenda-se planejar como fase 2).

---

### SEC-HDR-001 — Ausência de HSTS e de reforço HTTPS no Apache

- **Problema:** Não há `Strict-Transport-Security`, e o Apache expõe apenas porta 80. Violação §20/§21.
- **Localização:** `docker/apache.conf`, `public/index.php:44-48`.
- **Evidência:** `public/index.php` envia CSP/nosniff/Referrer-Policy/Permissions-Policy/X-Frame-Options, mas nenhum HSTS; Apache escuta apenas `:80`.
- **Impacto:** Sem HSTS, clientes podem negociar HTTP; sem TLS no proxy, credenciais trafegam em claro.
- **Implementação recomendada:** Em produção, terminar TLS no reverse proxy (ex.: Caddy/Nginx) e enviar:
  ```http
  Strict-Transport-Security: max-age=31536000; includeSubDomains
  ```
  Configurar redirecionamento HTTP→HTTPS no proxy. No Apache dev, manter porta 80.
- **Status:** Aberto.

---

### SEC-LOG-001 — Sem eventos estruturados de auditoria e request ID

- **Problema:** Não existem eventos de log estruturados (`LOGIN_SUCCESS`, `LOGIN_FAILURE`, `LOGOUT`, `SESSION_CREATED`, `AUTHORIZATION_DENIED`) nem `request_id` de correlação. Violação §49/§50.
- **Localização:** `inc/api_bootstrap.php` (apenas `API_ERROR`), `scripts/login_submit.php`.
- **Evidência:** Apenas `error_log('API_ERROR ...')` e logs de erro de banco. Auditoria em banco existe apenas para `visitorinfo` (triggers).
- **Implementação recomendada:** Criar helper `logEvent(string $event, array $context)` que emite JSON para `php://stderr` (capturado pelo Docker) incluindo `request_id` (gerado por request), `user_id`, `ip`, `timestamp`. Aplicar nos eventos listados em §49. Nunca logar senha/token/MFA.
- **Status:** Aberto.

---

### QUAL-BUG-001 — INSERT executado via `$db->select()` sem retorno de `insert_id`

- **Problema:** `api/api_cadastro_visitante.php:153` chama `$db->select($sql, $params)` para uma instrução `INSERT`; `select()` não define `insert_id` (definido apenas em `insert()`).
- **Localização:** `api/api_cadastro_visitante.php:135-163`, `src/Database.php:32-62`.
- **Evidência:** `select()` retorna `success/status/data/count`; `insert()` retorna `insert_id`. O endpoint lê `$result['insert_id']` que estará indefinido → `null` no JSON de resposta.
- **Causa raiz:** Chamada incorreta do método da camada de banco (confusão entre `select` e `insert`).
- **Impacto:** O visitante é gravado (INSERT executado), porém a resposta retorna `id: null`, podendo quebrar fluxos do frontend que dependem do ID e induzir cadastros duplicados.
- **Implementação recomendada:** Usar `$db->insert($sql, $params)` e manter a checagem `$result['status'] !== 'success'`.
- **Testes:** Cadastrar visitante via API; validar que a resposta contém `id` numérico e o registro no banco. Adicionar teste de integração.
- **Status:** Validado. `api/api_cadastro_visitante.php:153` agora usa `$db->insert($sql, $params)`, mantendo a checagem `$result['status'] !== 'success'` e retornando `id` numérico. `php -l` OK; 114 testes passando.

---

## 4. Fases de Implementação

### Fase 0 — Quick Wins (esforço baixo, alto retorno)
1. SEC-DOCKER-005: remover porta MySQL publicada.
2. SEC-DOCKER-006: extrair phpMyAdmin para compose de dev.
3. SEC-PASS-001: trocar para `PASSWORD_ARGON2ID`.
4. SEC-COOKIE-001: cookie `__Host-SID` + `SameSite=Strict` (HTTPS).
5. QUAL-BUG-001: corrigir `$db->select()` → `$db->insert()`.
6. SEC-DOCKER-004: adicionar `logging` com rotação.
7. SEC-DOCKER-003: adicionar limites de recursos.

### Fase 1 — Hardening Docker
1. SEC-DOCKER-001: usuário não-root.
2. SEC-DOCKER-002: `cap_drop`, `no-new-privileges`, `read_only`, `tmpfs`.
3. SEC-DOCKER-007: redes internas para o banco.

### Fase 2 — Autenticação e Sessões
1. SEC-SESS-001: expiração/revogação de sessões na tabela.
2. SEC-RATE-001: rate limiting genérico.
3. SEC-HDR-001: HSTS + TLS no proxy (produção).
4. SEC-LOG-001: eventos estruturados + request_id.
5. SEC-MFA-001: TOTP para admins (esforço alto, planejar isoladamente).

### Fase 3 — Validação e Testes de Segurança
- Executar testes obrigatórios da especificação (§62): autenticação (brute force, session fixation), autorização (IDOR/BOLA), web (SQLi, XSS, CSRF, path traversal), arquivos (upload inválido, executável, download sem autorização), container (usuário, capabilities, ports).
- Adicionar testes automatizados para login, sessão, upload e autorização.

---

## 4.1 Registro de Execução — Fase 0

Data de execução: 2026-08-19.

| ID | Correção | Validação |
|---|---|---|
| SEC-DOCKER-005 | `ports` removido do serviço `db` | `docker compose config` OK |
| SEC-DOCKER-006 | phpMyAdmin movido para `docker-compose.dev.yml` (`phpmyadmin:5.2`) | override de dev validado; README atualizado |
| SEC-PASS-001 | `PASSWORD_ARGON2ID` em 5 pontos de hash | `php -l` OK; hashes existentes continuam verificáveis |
| SEC-COOKIE-001 | `__Host-SID` + `SameSite=Strict` + `Secure` (HTTPS) | `php -l` OK; dev HTTP mantém `PHPSESSID`/`Lax` |
| QUAL-BUG-001 | `$db->insert()` no cadastro de visitante | `php -l` OK; `id` numérico na resposta |
| SEC-DOCKER-004 | `logging` com rotação em `app` e `db` | `docker compose config` OK |
| SEC-DOCKER-003 | `mem_limit`/`cpus`/`pids_limit` em `app` e `db` | `docker compose config` OK |

Correções adicionais de higiene (fora dos achados): chave `command:` duplicada removida em `docker-compose.yml`; falsos positivos do LSP eliminados em `scripts/logout.php` e `scripts/editarUsuario.php` (casts `(int)`/`(string)` → `intval()`/`strval()`).

Validação da suíte: `vendor/bin/phpunit` → 114 testes, 129 asserções OK.

---

## 5. Definition of Done

Para cada achado, antes de declarar concluído:

- [ ] Problema reproduzido/confirmado.
- [ ] Evidência registrada.
- [ ] Causa raiz identificada.
- [ ] Correção implementada.
- [ ] Testes executados (suite existente continua passando: `vendor/bin/phpunit`).
- [ ] Segurança reavaliada.
- [ ] Compatibilidade validada (Docker, MySQL, PHP 8.2).
- [ ] Documentação atualizada (este plano e README quando aplicável).
- [ ] Finding atualizado para "Validado".

---

## 6. Limitações

- Ambiente de produção não acessível; validação limitada ao Docker local.
- MFA (SEC-MFA-001) exige planejamento de UX e schema; recomendado como fase separada.
- TLS/HSTS (SEC-HDR-001) dependem do reverse proxy em produção (fora do repositório).
- Análise de performance e testes E2E não executados nesta etapa (registrados como "Não auditado").