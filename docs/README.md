# Documentação — StudyTrack Pro

Índice central da documentação do repositório. Os READMEs em `backend/`, `frontend/` e `docker/` cobrem setup por pacote; aqui estão os materiais transversais.

---

## Técnico

| Documento | Descrição |
|-----------|-----------|
| [technical/DOCUMENTACAO_TECNICA.md](technical/DOCUMENTACAO_TECNICA.md) | Arquitetura, stack, frontend, backend, infra, fluxos e pontos de atenção |
| [technical/FLUXO_COMPLETO_STUDYTRACK_PRO.md](technical/FLUXO_COMPLETO_STUDYTRACK_PRO.md) | Ordem de execução ponta a ponta: Laravel, Vue, guards, Axios, WebSocket, eventos e broadcast |
| [technical/DOCUMENTACAO_TECNICA_LUA.md](technical/DOCUMENTACAO_TECNICA_LUA.md) | Redis Lua, OpenResty na borda, PL/Lua no PostgreSQL, scripts em `redis-scripts/` |

---

## Testes

| Documento | Descrição |
|-----------|-----------|
| [testing/ESTRATEGIA_TESTES.md](testing/ESTRATEGIA_TESTES.md) | Pirâmide de testes, matriz por módulo, CI e riscos |

O que o **GitHub Actions** corre de facto (PHPUnit, Pint, PHPStan; Vitest, ESLint, build) está descrito na **secção 1.4** desse documento; metas de cobertura elevada, MSW obrigatório ou Playwright como *gate* tratam-se de **roadmap / recomendações**, não de falha automática do CI atual.

---

## Operação, deploy e checklists

| Documento | Descrição |
|-----------|-----------|
| [operations/README.md](operations/README.md) | Índice detalhado da pasta `operations/` |
| [operations/DEPLOY_SECURITY_PASSO_A_PASSO.md](operations/DEPLOY_SECURITY_PASSO_A_PASSO.md) | Segurança em produção |
| [operations/ENV-VARS.md](operations/ENV-VARS.md) | Variáveis de ambiente |
| [operations/GOALS-FRONTEND-ONLY.md](operations/GOALS-FRONTEND-ONLY.md) | Metas apenas no frontend |

---

## API

| Artefato | Descrição |
|----------|-----------|
| [api/StudyTrack_API_Collection.postman.json](api/StudyTrack_API_Collection.postman.json) | Coleção Postman da API v1 |

---

## Prompts para agentes (Cursor / IA)

Arquivos em [agents/](agents/) — contexto para especialização por área (backend, frontend, design, fullstack, integração, melhorias).

---

## Referência histórica / planejamento

Textos de apoio — ver aviso em [reference/README.md](reference/README.md).

| Arquivo |
|---------|
| [reference/StudyTrack_Arquitetura_Completa.txt](reference/StudyTrack_Arquitetura_Completa.txt) |
| [reference/StudyTrack_Endpoints_REST.txt](reference/StudyTrack_Endpoints_REST.txt) |
| [reference/StudyTrack_Estrutura_Pastas.txt](reference/StudyTrack_Estrutura_Pastas.txt) |
| [reference/StudyTrack_Modelagem_BD.txt](reference/StudyTrack_Modelagem_BD.txt) |
| [reference/StudyTrack_Eventos_Jobs.txt](reference/StudyTrack_Eventos_Jobs.txt) |
| [reference/StudyTrack_Estrategia_Cache.txt](reference/StudyTrack_Estrategia_Cache.txt) |
| [reference/StudyTrack_Dashboard.txt](reference/StudyTrack_Dashboard.txt) |
| [reference/StudyTrack_Checklist.txt](reference/StudyTrack_Checklist.txt) |
| [reference/StudyTrack_Plano12Semanas.txt](reference/StudyTrack_Plano12Semanas.txt) |
| [reference/StudyTrack_Testes.txt](reference/StudyTrack_Testes.txt) |

---

## Frontend (design system e UX)

Índice: [../frontend/docs/README.md](../frontend/docs/README.md).

---

## Regras do Cursor

Convenções para o editor: [../.cursor/rules/](../.cursor/rules/)
