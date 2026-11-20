# Auditoria pré-produção — SICAV

- **Data:** 2026-11-20
- **Repositório:** `defesatecnologia-lang/sicav`
- **Branch auditada:** `fix/dev-development` (commit `5ecd8b8`, PR #2 aberta para `main`)
- **Cenário de implantação:** Ubuntu Server, containers Docker, **uso exclusivo em rede interna isolada**
- **Objetivo:** decidir se o sistema está pronto para ir para produção

---

## 1. Veredito

**❌ Não está pronto ainda — GO condicional.**

O sistema está próximo do aceite: testes, análise estática, CI e os controles de aplicação estão sólidos. Faltam **3 correções bloqueadoras** antes do go-live. O item TLS, que seria P0 em ambiente exposto, foi reclassificado como **risco aceito condicional** por causa do cenário de rede interna.

---

## 2. Verificações automatizadas executadas

| Verificação | Comando | Resultado |
|---|---|---|
| Sintaxe PHP (73 arquivos) | `find ... -name '*.php' -exec php -l` | ✅ sem erros |
| Testes unitários | `vendor/bin/phpunit` | ✅ 167 testes / 256 assertions |
| Análise estática | `vendor/bin/phpstan analyse` | ✅ sem erros |
| Análise estática | `vendor/bin/psalm --no-cache` | ✅ sem erros (187 informações no baseline) |
| Vulnerabilidades de dependências | `composer audit --locked` | ✅ nenhuma advisory |
| Configuração Docker | `docker compose -f docker-compose.yml config` | ✅ válido com `.env` preenchido; falha corretamente sem `.env` (fail-closed) |
| CI da PR #2 | `gh pr checks 2` | ✅ 34 checks passando |
| CI da `main` | `gh run list --branch main` | ✅ workflows Tests / Infra / Security verdes |

> Limitação: não foi executado restore real nem subida de stack em host de produção; a validação foi estática, de testes automatizados e de configuração. O smoke test de containers é feito pelo CI.

---

## 3. Controles de aplicação confirmados

- **SQLi:** acesso a dados sempre parametrizado via PDO (`prepare`/`execute`), sem concatenação de entrada do usuário em SQL — `src/Database.php:48-52`, `api/api_cadastro_visitante.php:79-117`.
- **CSRF:** token aleatório por sessão, comparação com `hash_equals`, middleware global em todo POST e validação adicional nas APIs — `inc/security.php:7-22`, `src/Middleware/CsrfMiddleware.php:11-14`, `public/index.php:178,183`, `inc/api_bootstrap.php:139-146`.
- **Autenticação/autorização:** rotas web e API protegidas por níveis `user/supervisor/admin` — `public/index.php:215-275`, `src/Middleware/AuthMiddleware.php:16-34`, `inc/api_bootstrap.php:77-136`, `config/permissions.php:3-18`, `tests/ApiPermissionsTest.php:30-71`.
- **Senhas e sessão:** `password_hash`/`password_verify` com ARGON2ID, rotação de session ID no login, token de sessão com hash SHA-256, sessão única, expiração por inatividade e vida absoluta — `scripts/login_submit.php:56,94-99`, `scripts/trocar_senha.php:32-35`, `inc/single_session.php:36-38,95-156,168-201`, `config/app.php:12-16,44-66`.
- **Brute force / rate limit:** bloqueio por usuário+IP e por IP, 5/15 tentativas e 15 min, limites por endpoint — `inc/login_security.php:3-5`, `scripts/login_submit.php:33-85`, `inc/rate_limit.php:6-86`.
- **Upload de foto:** valida data URI, MIME real com `getimagesizefromstring`, tamanho máximo de 5 MB, gera nome próprio; arquivos fora do docroot — `inc/foto_helpers.php:21-62`, `api/get_photo.php:15-32,53-57`, `docker/apache.conf:8-19`.
- **Saída e logs:** escape JSON com `JSON_HEX_TAG/AMP`, sanitização de CSV contra formula injection e escape HTML nas páginas administrativas — `inc/api_bootstrap.php:36-45`, `inc/helpers.php:3-16`, `scripts/admin_auditoria.php:430-576`.
- **Arquivos sensíveis:** `.env`/`.git` fora do build e do docroot; produção remove `.env` e markdown da imagem — `.dockerignore:1-2`, `Dockerfile:44-48,75-77`, `docker/apache.conf:8-19`.
- **Infra:** imagens pinadas por digest, container não-root, rootfs somente leitura, `no-new-privileges`, `cap_drop: ALL`, rede do banco `internal`, healthchecks nos 3 serviços, restart `unless-stopped`, backup diário criptografado com GPG, retenção e checksum — `Dockerfile:3,5,60-86,93`, `docker-compose.yml:27,42-46,53-58,60-84,104-108,140-149,164-169`, `scripts/backup.sh:52-76,100-175`.
- **CI/CD efetivo:** compose lint, ShellCheck/Hadolint, coverage, smoke test com `request_id`, Trivy, PHPUnit 8.4/8.5, PHPStan, composer audit, CodeQL, dependency review e Dependabot — `.github/workflows/*.yml`, `.github/dependabot.yml`.
- **Deploy controlado:** `scripts/deploy.sh:16-17` exige preflight; `scripts/release.sh:33-51` só opera em `main` limpa e igual a `origin/main`.

---

## 4. Bloqueadores (P0)

### 4.1 Senha administrativa fixa e conhecida

**Evidência:** `docker/migrate.php:220-225` cria o usuário `admin` com `Admin@2026Temp!` (hash ARGON2ID) em todo deploy novo. O flag `senha_temporaria=1` apenas redireciona no login (`scripts/login_submit.php:96-99`) e mostra aviso no navbar (`inc/navbar.php:59,82,110`); **não há bloqueio nas demais rotas**. A verificação em `scripts/trocar_senha.php:6` só impede a troca quando o flag já é `0`, ou seja, quem entrar primeiro com a senha padrão consegue definir a senha nova.

**Impacto:** comprometimento total da aplicação em um deploy novo; o atacante pode alterar a senha antes do administrador legítimo.

**Correção:** gerar senha aleatória no bootstrap, entregá-la por canal seguro, forçar a troca em middleware global (não só no login) e rotacionar a senha em instalações já existentes.

### 4.2 Falha de migração silenciosa

**Evidência:** `docker/entrypoint.sh:40`:

```bash
php /var/www/html/docker/migrate.php || echo "WARNING: Migration may have failed, check database."
```

O `|| echo` anula o `set -e`. O container sobe e fica `healthy` mesmo com schema desatualizado — o healthcheck (`docker-compose.yml:53-58`) só testa `index.php?page=login`, que não exercita colunas novas. `scripts/deploy.sh:23-29` reporta sucesso.

**Impacto:** deploy aparenta OK, mas INSERT/UPDATE quebram em produção.

**Correção:** remover o `|| echo`; fazer `scripts/deploy.sh` falhar se a migração não concluir; expor o status da migração ao healthcheck (ex.: flag em `/tmp`).

### 4.3 Restore não validado e backup no mesmo drive

**Evidência:** `scripts/backup.sh:38` define `BACKUP_DIR=/mnt/visitantes/backups` por padrão; `.env.example.docker:34` e `README.md:352-355` confirmam o risco. Existe procedimento de restore documentado em `README.md:360-388`, e o próprio README exige:

> Antes do go-live, execute esse restore em um host de teste e registre o RTO/RPO obtidos. Um backup sem restore validado não é backup.

Não há evidência de que esse teste tenha sido executado. O backup remoto (`BACKUP_REMOTE`) é opcional; `scripts/preflight.sh:55-88` não valida `BACKUP_*`.

**Impacto:** falha do drive destrói dados e cópias; RTO/RPO desconhecidos.

**Correção:** exigir `BACKUP_REMOTE` no preflight, executar o restore completo em host de teste e anexar o registro com RTO/RPO medidos.

---

## 5. TLS — risco aceito condicional (cenário de rede interna)

O nginx só escuta na porta 80 (`deploy/nginx/default.conf:24-26`) e o compose publica `8000:80` (`docker-compose.yml:94-96`). Não há 443, redirecionamento HTTP→HTTPS nem HSTS. `config/app.php:25-36,50-58` e `public/index.php:54-57` dependem de `isHttpsRequest()` para usar `__Host-SID`, cookie `Secure`, `SameSite=Strict` e HSTS. Além disso, `deploy/nginx/default.conf:46` força `proxy_set_header X-Forwarded-Proto $scheme`, apagando qualquer `https` sinalizado por um proxy externo.

O README (`README.md:243-247`) já assume HTTP para rede isolada. **Para o cenário informado, aceito o risco desde que:**

1. O firewall restrinja a origem, por exemplo:

   ```bash
   sudo ufw allow from 10.10.10.0/24 to any port 8000 proto tcp
   ```

   e **não** `sudo ufw allow 8000/tcp` para qualquer IP (`README.md:234`).

2. `ss -tulpn` e o firewall perimetral confirmem que a porta 8000 não é acessível fora da LAN/VLAN.

3. A rede seja realmente confiável (VLAN dedicada). Se for LAN corporativa compartilhada, CPF, telefone, e-mail, token CSRF, cookie de sessão e fotos trafegam em claro → TLS volta a ser bloqueador.

4. Mesmo sem TLS agora, corrigir `deploy/nginx/default.conf:46` para preservar `X-Forwarded-Proto` de proxy confiável, evitando que uma futura terminação TLS quebre `Secure`/`__Host-SID`.

---

## 6. Riscos altos a corrigir em seguida (P1)

| Evidência | Impacto | Correção |
|---|---|---|
| `inc/api_bootstrap.php:85` — `$map[$endpoint] ?? 'user'` | Endpoint novo não mapeado cai como `user` (**fail-open**); comportamento codificado em `tests/ApiPermissionsTest.php:74-76` | Exigir entrada explícita no mapa; negar/500 se ausente |
| `docker/migrate.php:11`, `docker/entrypoint.sh:28` — fallback `visitorpassword` | Execução manual sem env conecta com senha fraca | Remover fallback; falhar sem variável |
| `docker-compose.yml:145` — `-p${MYSQL_ROOT_PASSWORD}` | Root password em argv (`docker inspect`/`ps`) | Usar `MYSQL_PWD` ou arquivo de opções `600` |
| `.gitignore:31` — `! /fotos_visitantes/*` (com espaço) | Regra não funciona; fotos com PII podem ser commitadas | `/fotos_visitantes/*` + `!/fotos_visitantes/.gitkeep` |
| `docker/php.ini:1-15` — sem `expose_php`/OPcache | Vazamento da versão do PHP e perda de performance | `expose_php=Off`, `opcache.enable=1`, `validate_timestamps=0` |
| `docker/php.ini:12` (`gc_maxlifetime=3600`) x `docker-compose.yml:38` (`absolute=43200`) | Sessão pode expirar antes do limite absoluto | Alinhar valores ou documentar |
| `scripts/trocar_senha.php:26`, `scripts/cadastroUsuario.php:22-25` — mínimo de 6 caracteres | Senhas fracas | Exigir ≥12 caracteres e validar senhas comuns |
| `scripts/deletarUsuario.php:20`, `scripts/bloquearUsuario.php:20`, `src/Database.php:14-17` | Comparação `int` vs `string` do PDO torna ineficaz a proteção contra autoexclusão/auto-bloqueio | Converter ambos para `int` ou `PDO::ATTR_EMULATE_PREPARES=false` |
| `public/index.php:44` e `:38-40` — `frame-ancestors 'self'`, `unsafe-inline`/CDNs | Clickjacking e dependência de terceiros | `frame-ancestors 'none'`, auto-hospedar assets ou SRI |
| `public/index.php:17-57` — sem `Cache-Control: no-store` | Dados de visitantes podem ficar em cache do navegador | Header `no-store` global nas páginas autenticadas |
| `inc/log_event.php:40`, `inc/api_bootstrap.php:17` — `REQUEST_URI` completo em logs | CPF/nome em query strings vazam para logs | Redacionar parâmetros sensíveis |
| `docs/ESPECIFICAÇÃO-SEGURANÇA.md:373-398` — MFA admin não implementado | Conta admin depende só de senha | Implementar TOTP/WebAuthn ou aceite formal de risco |
| Headers de segurança só no PHP (`public/index.php:48-52`); `deploy/nginx/default.conf:36-57` e `docker/apache.conf:31-39` sem `add_header`/`Header set` | Assets estáticos sem `nosniff`/`Referrer-Policy` | Adicionar headers também no nginx e no Apache |
| `scripts/deploy.sh:20-35` — sem snapshot/rollback | Stack pode ficar parcialmente atualizada | Tag da imagem anterior + rollback automático |
| `scripts/setup_docker.sh:33-38,121-123` — copia `.env.example.docker` com placeholders e imprime `Admin@2026Temp!` | Stack com senhas fracas se rodado no servidor | Guard `APP_ENV` + preflight obrigatório; nunca logar senha |
| `docker-compose.dev.yml:13-17` — phpMyAdmin na 8081 | Exposição do admin do DB se override for aplicado no host | Nunca usar override em produção; detectar no preflight |
| `.github/workflows/ci.yml:219,242,262` — Trivy com `ignore-unfixed: true` | CVEs sem fixe ignoradas silenciosamente | `exit-code: 1` com exceções explicitadas e prazo |
| `.github/workflows/ci.yml:51-55` — Hadolint só no `Dockerfile` | `docker/*.Dockerfile` sem lint | Rodar Hadolint em todos os Dockerfiles |
| `.github/workflows/codeql.yml:6-7` — filtro de branches no `pull_request` | PRs para outros destinos sem análise | Remover filtro |
| `Dockerfile:17-20`, `docker/nginx.Dockerfile:13`, `docker/mysql.Dockerfile:6` — `upgrade` de pacotes | Digests fixados, mas conteúdo muda a cada build | Fixar versões de pacotes ou publicar/assinar artefato e scannear o final |
| `docker-compose.yml:77-81,119-123,153-157` — json-file rotation local | Sem agregação/alerta central | Enviar logs para coletor externo |
| `.trivyignore.yaml:17-130` — 30+ CVEs com `expired_at: 2027-02-28` | Exceção ampla | Reavaliar antes do vencimento |
| Branch `main` sem proteção (confirmado via API GitHub: 404 em `/branches/main/protection`) | Merge sem gate de qualidade | Exigir PR, ≥1 aprovação, status checks Infra/Tests/Security, dismiss stale, bloquear force push |

---

## 7. Ordem recomendada de correção

1. **Senha admin aleatória + enforcement da troca** (§4.1)
2. **Migração falhando de verdade** (§4.2)
3. **Restore testado + backup remoto obrigatório** (§4.3)
4. **Firewall por origem + correção do `X-Forwarded-Proto`** (§5)
5. **Branch protection e itens P1** (§6)

Após os itens 1–4, fazer uma revalidação rápida (testes + CI + preflight no host) e registrar o aceite formal do risco TLS com a descrição da rede interna.

---

## 8. Escopo e metodologia

- Auditoria **somente leitura**: nenhum arquivo de código foi modificado.
- Duas revisões paralelas: infraestrutura/deploy e segurança da aplicação, mais execução local de testes e análise estática.
- Documentos pré-existentes consultados: `docs/ACHADOS-PRODUCAO.md`, `docs/ANALISE-PRONTO-PRODUCAO.md`, `docs/ESPECIFICAÇÃO-SEGURANÇA.md`, `README.md`.
- Este arquivo é o único artefito adicionado ao repositório por esta auditoria.
