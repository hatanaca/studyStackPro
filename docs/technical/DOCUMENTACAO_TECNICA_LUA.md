# Documentacao Tecnica da Integracao Lua

## 1. Objetivo

Este documento registra, de forma tecnica e segura, a integracao de Lua realizada no `StudyTrack Pro`.

O foco aqui nao e apenas listar arquivos alterados, mas explicar:

- o problema que cada alteracao resolveu;
- como Redis Lua, OpenResty e PL/Lua foram encaixados no projeto;
- quais fluxos de negocio passaram a depender dessas pecas;
- quais validacoes foram executadas;
- quais cuidados de seguranca devem ser preservados em manutencoes futuras.

Este documento foi escrito para estudo posterior do projeto. Por isso, ele privilegia contexto, motivacao, arquitetura e comportamento observado.

## 2. Regra de seguranca deste documento

Para nao comprometer a seguranca do projeto, este arquivo **nao** registra:

- senhas reais;
- tokens;
- hashes de tokens capturados em runtime;
- valores secretos de `.env`;
- comandos com credenciais embutidas;
- enderecos internos que so fariam sentido na maquina local de desenvolvimento.

Sempre que uma configuracao depende de segredo, este documento menciona apenas o nome da variavel de ambiente ou o conceito envolvido.

## 3. Escopo da implementacao

A integracao adicionada cobre tres frentes principais:

1. **Redis Lua no backend Laravel**
2. **OpenResty na borda HTTP**
3. **PL/Lua no PostgreSQL**

Tambem foram adicionados testes focados e ajustes de infraestrutura para permitir build, bootstrap e validacao em containers.

## 4. Visao geral da arquitetura resultante

Depois das alteracoes, o fluxo ficou assim:

1. O cliente continua consumindo a API Laravel por `nginx`.
2. O `nginx` agora roda sobre OpenResty e executa logica Lua na borda.
3. O backend Laravel usa scripts Lua no Redis para operacoes que se beneficiam de atomicidade e baixa latencia.
4. O PostgreSQL executa PL/Lua para derivar `productivity_score` diretamente na camada de banco.
5. O sistema preserva estrategia de **fail-open** na maior parte dos pontos Lua operacionais:
  - se Redis Lua falha, a aplicacao tenta seguir com comportamento seguro;
  - se o edge nao consegue consultar Redis, o request nao e derrubado por engano;
  - se a trigger PL/Lua falha internamente, cai para valor seguro em vez de quebrar toda escrita.

Essa abordagem reduz risco de indisponibilidade acidental durante integracoes com componentes dinamicos.

## 5. Inventario do que foi implementado

### 5.1 Scripts Redis Lua

Foram adicionados tres scripts em `redis-scripts/`:

- `job_dedup.lua`
- `sliding_window.lua`
- `streak_update.lua`

#### `job_dedup.lua`

Responsabilidade:

- evitar despacho duplicado de jobs por uma janela curta.

Uso principal:

- deduplicar disparos do `RecalculateMetricsJob` apos mudancas em sessoes.

Estrategia:

- usa `SET key value NX EX ttl` para criar um lock curto;
- retorna `1` quando o lock e criado;
- retorna `0` quando o lock ja existe.

Beneficio:

- evita rajadas de jobs redundantes para o mesmo usuario quando varias alteracoes acontecem em sequencia.

#### `sliding_window.lua`

Responsabilidade:

- aplicar rate limit por janela deslizante com retorno de `retry_after`.

Uso principal:

- proteger endpoints criticos de escrita de sessao de estudo.

Estrategia:

- remove eventos antigos de um `sorted set`;
- conta eventos ainda dentro da janela;
- bloqueia quando o limite e atingido;
- calcula quanto tempo falta para a janela voltar a aceitar requests.

Beneficio:

- evita o comportamento grosseiro de janelas fixas e melhora a precisao do throttling.

#### `streak_update.lua`

Responsabilidade:

- atualizar streak diario de estudo com uma unica ida ao Redis.

Uso principal:

- servir de base para manutencao de streak sem round-trips multiplos.

Estrategia:

- compara `today` e `yesterday` com a ultima data registrada;
- mantem a streak se for o mesmo dia;
- incrementa se houve estudo no dia seguinte;
- reinicia para `1` se houve quebra.

Beneficio:

- reduz acoplamento da regra de streak a varias operacoes separadas em Redis.

## 6. Integracao Redis Lua no Laravel

### 6.1 `App\Services\RedisLuaService`

Arquivo:

- `backend/app/Services/RedisLuaService.php`

Responsabilidades:

- mapear os scripts Lua disponiveis;
- carregar scripts no Redis;
- armazenar SHA em cache;
- executar via `EVALSHA`;
- recarregar automaticamente em caso de `NOSCRIPT`.

Decisoes relevantes:

- a carga inicial acontece por nome de script;
- o SHA e mantido em cache para evitar `SCRIPT LOAD` em toda chamada;
- se o Redis perder o script da memoria, o service recarrega automaticamente;
- a deteccao de `NOSCRIPT` e tratada explicitamente.

Isso transformou o uso de Lua em uma abstracao reaproveitavel, em vez de espalhar chamadas Redis cruas pelo codigo.

### 6.2 `App\Providers\RedisScriptServiceProvider`

Arquivo:

- `backend/app/Providers/RedisScriptServiceProvider.php`

Responsabilidade:

- carregar os scripts no boot da aplicacao.

Caracteristica importante:

- se a carga falhar, o sistema registra log e segue em **fail-open**.

### 6.3 `App\Http\Middleware\SlidingWindowRateLimit`

Arquivo:

- `backend/app/Http/Middleware/SlidingWindowRateLimit.php`

Responsabilidade:

- aplicar rate limit por script Lua em rotas de escrita.

Funcionamento:

- monta uma chave por usuario ou IP e por path;
- usa timestamp em milissegundos;
- consulta `sliding_window.lua`;
- devolve `429` com `Retry-After` quando bloqueado;
- inclui `X-RateLimit-Limit` na resposta.

Comportamento de resiliencia:

- se Redis Lua falhar, registra warning; em seguida, se `services.rate_limit.fail_open` for `true`, a requisicao segue; caso contrario responde `503` com payload JSON de indisponibilidade (ver `config/services.php`).

### 6.4 `App\Services\StreakService`

Arquivo:

- `backend/app/Services/StreakService.php`

Responsabilidade:

- encapsular o uso do script `streak_update.lua`.

Detalhes:

- busca timezone do usuario;
- calcula `today` e `yesterday` no fuso correto;
- delega ao Redis Lua;
- em falha, retorna `0` com log de warning.

### 6.5 Deduplicacao de recalc de metricas

Arquivo principal:

- `backend/app/Listeners/StudySession/DispatchMetricsRecalculation.php`

Alteracao:

- o listener passou a consultar `job_dedup.lua` antes de despachar `RecalculateMetricsJob`.

Efeito pratico:

- varias alteracoes proximas de uma mesma sessao nao geram explosao de jobs iguais para o mesmo usuario.

Importante:

- se a deduplicacao Lua estiver indisponivel, o listener nao aborta o fluxo; apenas faz log e segue.

## 7. Alteracoes na autenticacao e revogacao de token

### 7.1 `TokenService`

Arquivo:

- `backend/app/Modules/Auth/Services/TokenService.php`

Responsabilidades:

- centralizar revogacao de tokens Sanctum;
- enviar o hash persistido do token para blacklist no Redis;
- apagar o token do banco;
- revogar um token ou varios.

Ponto importante:

- o Redis guarda o **hash persistido do token**, nao o Bearer plaintext.
- isso e coerente com a forma como o Sanctum armazena tokens.

### 7.2 `AuthController` e `AuthService`

Arquivos:

- `backend/app/Http/Controllers/V1/AuthController.php`
- `backend/app/Modules/Auth/Services/AuthService.php`

Alteracoes:

- `logout` passou a usar `TokenService`;
- `revokeAllTokens` passou a usar `TokenService`;
- `login` revoga tokens anteriores via `TokenService`;
- `changePassword` revoga tokens anteriores via `TokenService`.

Resultado:

- a revogacao passou a ficar consistente entre backend e edge;
- a regra de blacklist deixou de depender de chamadas isoladas a `tokens()->delete()`.

## 8. Alteracoes nas rotas e no modelo de sessao

### 8.1 Rotas

Arquivo:

- `backend/routes/api.php`

Alteracoes:

- `throttle.sliding` foi aplicado nas rotas de mutacao de `study-sessions` (valores conforme `routes/api.php`):
  - `POST /api/v1/study-sessions/start` — limite deslizante 10
  - `POST /api/v1/study-sessions` — limite deslizante 30
  - `PATCH /api/v1/study-sessions/{id}/end` — limite deslizante 10
  - `PUT /api/v1/study-sessions/{id}` e `PATCH /api/v1/study-sessions/{id}` — limite deslizante 30
  - `DELETE /api/v1/study-sessions/{id}` — limite deslizante 30

Isso faz com que o rate limit mais sensivel use a implementacao Lua, enquanto o restante da API continua usando throttles Laravel ja existentes.

### 8.2 Bootstrap do middleware

Arquivo:

- `backend/bootstrap/app.php`

Alteracao:

- foi registrado o alias `throttle.sliding`.

### 8.3 Registro do provider

Arquivo:

- `backend/bootstrap/providers.php`

Alteracao:

- foi registrado `RedisScriptServiceProvider`.

### 8.4 `StudySession` e `StudySessionResource`

Arquivos:

- `backend/app/Models/StudySession.php`
- `backend/app/Http/Resources/StudySessionResource.php`

Alteracoes:

- `productivity_score` passou a fazer parte do cast do model;
- `productivity_score` passou a ser serializado na resposta da API.

Resultado:

- o campo calculado no banco agora e explicitamente consumivel pela API e pelo frontend.

## 9. Integracao OpenResty no edge

### 9.1 Troca da base do proxy

Arquivos:

- `docker/nginx/Dockerfile`
- `docker/nginx/nginx.conf`
- `docker/nginx/conf.d/studytrack.conf`

Mudanca estrutural:

- o proxy deixou de ser apenas Nginx padrao e passou a usar OpenResty.

Motivacao:

- habilitar logica Lua diretamente na borda HTTP.

### 9.2 O que o edge passou a fazer

#### WAF simples por Lua

No `rewrite_by_lua_block`, o edge verifica:

- user-agents suspeitos;
- padroes simples de URI relacionados a probes triviais.

Resultado:

- requests com agentes reconhecidamente ofensivos podem ser bloqueados antes de chegar ao PHP.

#### Headers de seguranca por Lua

No `header_filter_by_lua_block`, o edge garante:

- `X-Content-Type-Options`
- `X-Frame-Options`
- `Referrer-Policy`
- `X-Request-ID`
- `Permissions-Policy`
- remocao de headers desnecessarios de exposicao

#### Validacao de token revogado no edge

No `access_by_lua_block`, o edge:

- preserva `login` e `register` como rotas publicas;
- exige Bearer token nas rotas privadas de `api/v1`;
- extrai a parte secreta do token Sanctum depois do separador `|`;
- calcula SHA-256 via `resty.sha256`;
- consulta a blacklist no Redis usando o mesmo database e prefixo do Laravel;
- responde `401 {"error":"Token revoked"}` quando encontra revogacao.

### 9.3 Ajustes operacionais importantes no OpenResty

Os seguintes ajustes foram necessarios para a integracao funcionar corretamente:

- logs enviados para `stdout/stderr` em vez de arquivos locais;
- `resolver 127.0.0.11` para o DNS interno do Docker;
- rota interna para resposta de token revogado;
- rota estatica `/nginx-health` para healthcheck do container.

### 9.4 Comportamento de falha

O edge foi desenhado para **fail-open** em varios pontos:

- se nao conseguir carregar libs Lua necessarias;
- se nao conseguir conectar ou autenticar no Redis;
- se nao conseguir consultar a blacklist;
- se ocorrer erro inesperado na validacao.

Isso foi uma escolha operacional para reduzir indisponibilidade por dependencia auxiliar.

## 10. Integracao PL/Lua no PostgreSQL

### 10.1 Build customizado do Postgres

Arquivo:

- `docker/postgres/Dockerfile`

O que foi feito:

- a imagem `postgres` passou a ser buildada localmente;
- o build instala toolchain, headers do PostgreSQL e Lua 5.4;
- o `pllua-ng` e compilado e instalado manualmente.

Ponto tecnico relevante:

- o build precisou fixar explicitamente `LUA`, `LUAC`, `LUA_INCDIR` e `LUALIB` para Lua 5.4.

### 10.2 Habilitacao da extensao

Arquivos:

- `docker/postgres/init/01-extensions-and-schema.sql`
- `backend/database/migrations/transactional/2026_04_04_000005_add_productivity_score_to_study_sessions_table.php`

Alteracoes:

- `CREATE EXTENSION IF NOT EXISTS pllua` foi adicionado no bootstrap SQL;
- a migration tambem cria a extensao, para nao depender apenas do init do container.

Essa duplicidade e intencional:

- o init SQL cobre banco novo;
- a migration cobre ambientes em que o volume ja existia.

### 10.3 `productivity_score` em `study_sessions`

Arquivo:

- `backend/database/migrations/transactional/2026_04_04_000005_add_productivity_score_to_study_sessions_table.php`

O que foi adicionado:

- coluna `productivity_score` em `study_sessions`;
- funcao `public.calculate_study_session_productivity_score()` em PL/Lua;
- trigger `trg_study_session_productivity_score`;
- backfill para linhas existentes.

Regra aplicada:

- sessoes muito curtas recebem peso menor;
- sessoes medias recebem peso padrao ou levemente maior;
- sessoes longas recebem multiplicador maior.

Comportamento defensivo:

- se a logica Lua falhar, o trigger cai para valor seguro no proprio registro.

### 10.4 Adaptacao ao repositorio real

A proposta original previa uso de outra estrutura analitica, mas a implementacao final foi adaptada ao estado real do projeto:

- o score ficou em `public.study_sessions`;
- a trigger opera sobre a tabela transacional que efetivamente existe no repositorio.

## 11. Ajustes de infraestrutura no Docker Compose

Arquivo:

- `docker-compose.yml`

Alteracoes relevantes:

- `nginx` passou a usar build local em OpenResty;
- `postgres` passou a usar build local com PL/Lua;
- `nginx` recebeu variaveis de Redis necessarias para a consulta da blacklist;
- servicos PHP passaram a receber senha e porta do Redis;
- o `nginx` passou a ter healthcheck em `/nginx-health`;
- foi criado um volume novo para a variante de Postgres com `pllua`.

### 11.1 Nota de seguranca sobre compose

O compose atual continua orientado a **desenvolvimento local**.

Isso significa:

- valores padrao e fallbacks de ambiente nao devem ser reutilizados em producao;
- a documentacao deve sempre referenciar variaveis, nao valores;
- qualquer endurecimento para producao precisa mover segredos para mecanismo apropriado de secret management.

## 12. Testes adicionados e validacao executada

### 12.1 Testes adicionados

Pasta:

- `backend/tests/Feature/LuaScripts/`

Arquivos:

- `JobDedupTest.php`
- `SlidingWindowTest.php`
- `StreakTest.php`

### 12.2 O que cada teste cobre

#### `JobDedupTest`

Valida:

- primeiro lock permitido;
- segunda chamada imediata bloqueada;
- comportamento apos TTL, respeitando tambem a unicidade do job.

#### `SlidingWindowTest`

Valida:

- request dentro do limite;
- request bloqueado com `429`;
- request voltando a ser permitido apos reset da janela.

#### `StreakTest`

Valida:

- primeira sessao;
- dia consecutivo;
- quebra de streak;
- repeticao no mesmo dia.

### 12.3 Resultado observado

A suite focada em Lua foi executada em container e passou:

- `10` testes aprovados;
- `27` assertions aprovadas.

### 12.4 Validacoes operacionais feitas

Foram validados em runtime:

- build do Postgres com `pllua`;
- extensao `pllua` ativa no banco;
- migration do `productivity_score` executada;
- `nginx` saudavel;
- rotas publicas de auth preservadas;
- bloqueio `401` em rota privada sem token;
- bloqueio `403` do WAF por user-agent suspeito;
- revogacao de token refletida no edge com `{"error":"Token revoked"}`.

## 13. Revisao tecnica e riscos residuais

Durante a revisao, os seguintes pontos exigiram atencao ou correcao:

### 13.1 Pontos que foram corrigidos durante a validacao

- trigger PL/Lua inicialmente usava API incorreta de trigger e foi ajustada para a sintaxe real do `pllua`;
- build do `pllua-ng` precisou de configuracao explicita para Lua 5.4;
- OpenResty precisou de `resolver` Docker para consultar `redis`;
- a validacao do token revogado precisou alinhar:
  - database Redis;
  - prefixo Redis do Laravel;
  - hash da parte secreta do token Sanctum;
  - biblioteca correta de SHA-256 no OpenResty;
- a healthcheck do `nginx` foi movida para uma rota estatica propria do proxy.

### 13.2 Riscos que ainda merecem observacao futura

- a estrategia de **fail-open** e operacionalmente resiliente, mas deliberadamente permissiva em falhas auxiliares;
- o `postgres` customizado usa `user: "0:0"` no compose local por compatibilidade com volume, o que nao deve ser tratado como desenho ideal para producao;
- a blacklist de token depende de Redis disponivel e coerencia entre prefixo/database do Laravel e do edge;
- a logica de WAF e propositalmente simples e nao substitui protecao especializada.

## 14. Arquivos novos ou alterados por area

### Redis Lua

- `redis-scripts/job_dedup.lua`
- `redis-scripts/sliding_window.lua`
- `redis-scripts/streak_update.lua`
- `backend/app/Services/RedisLuaService.php`
- `backend/app/Providers/RedisScriptServiceProvider.php`
- `backend/app/Services/StreakService.php`
- `backend/app/Http/Middleware/SlidingWindowRateLimit.php`

### Autenticacao e tokens

- `backend/app/Modules/Auth/Services/TokenService.php`
- `backend/app/Modules/Auth/Services/AuthService.php`
- `backend/app/Http/Controllers/V1/AuthController.php`

### Sessao de estudo e analytics

- `backend/app/Listeners/StudySession/DispatchMetricsRecalculation.php`
- `backend/app/Models/StudySession.php`
- `backend/app/Http/Resources/StudySessionResource.php`
- `backend/database/migrations/transactional/2026_04_04_000005_add_productivity_score_to_study_sessions_table.php`

### Bootstrap e rotas

- `backend/bootstrap/app.php`
- `backend/bootstrap/providers.php`
- `backend/routes/api.php`

### Infraestrutura

- `docker/nginx/Dockerfile`
- `docker/nginx/nginx.conf`
- `docker/nginx/conf.d/studytrack.conf`
- `docker/postgres/Dockerfile`
- `docker/postgres/init/01-extensions-and-schema.sql`
- `docker-compose.yml`

### Testes

- `backend/tests/Feature/LuaScripts/JobDedupTest.php`
- `backend/tests/Feature/LuaScripts/SlidingWindowTest.php`
- `backend/tests/Feature/LuaScripts/StreakTest.php`

## 15. Guia de estudo recomendado

Para entender essa integracao com boa ordem pedagogica, a sequencia recomendada e:

1. Ler este documento inteiro.
2. Ler os tres scripts em `redis-scripts/`.
3. Ler `RedisLuaService` e `RedisScriptServiceProvider`.
4. Ler `SlidingWindowRateLimit` e `DispatchMetricsRecalculation`.
5. Ler `TokenService`, `AuthService` e `AuthController`.
6. Ler a migration do `productivity_score`.
7. Ler `docker/nginx/conf.d/studytrack.conf`.
8. Ler os testes em `backend/tests/Feature/LuaScripts/`.

Essa ordem ajuda a entender primeiro o comportamento atomico, depois a integracao na aplicacao, depois a infraestrutura e por fim a prova automatizada.

## 16. Conclusao

A integracao Lua adicionou tres capacidades novas ao projeto:

- **atomicidade e eficiencia no Redis** para deduplicacao, throttling e streak;
- **controle de borda no OpenResty** para seguranca, headers e blacklist de tokens;
- **logica derivada no PostgreSQL com PL/Lua** para `productivity_score`.

O resultado final nao foi apenas um conjunto de scripts isolados. A implementacao passou a fazer parte do fluxo real da aplicacao, com:

- bootstrap;
- middlewares;
- listeners;
- autenticacao;
- infraestrutura Docker;
- testes automatizados;
- validacao em runtime.

Como referencia de estudo, este documento deve ser mantido sincronizado sempre que houver mudanca em:

- scripts Lua;
- contrato de blacklist de tokens;
- estrategia de rate limit;
- trigger de `productivity_score`;
- imagem de `nginx` ou `postgres`;
- variaveis de ambiente relevantes para Redis e edge.