# Documentação — StudyTrack Pro

Índice central da documentação do repositório. Os READMEs em `backend/`, `frontend/` e `docker/` cobrem setup por pacote; aqui estão os materiais transversais.

---

## Técnico

| Documento | Descrição |
|-----------|-----------|
| [technical/DOCUMENTACAO_TECNICA.md](technical/DOCUMENTACAO_TECNICA.md) | Arquitetura, stack, frontend, backend, infra, fluxos e pontos de atenção |
| [technical/RESUMO_ARQUITETURAL_STUDYTRACK_PRO.md](technical/RESUMO_ARQUITETURAL_STUDYTRACK_PRO.md) | Resumo denso: stack, rotas, módulos, PG/Redis/filas, eventos, WS, frontend e fluxo sessão→métricas |
| [technical/FLUXO_COMPLETO_STUDYTRACK_PRO.md](technical/FLUXO_COMPLETO_STUDYTRACK_PRO.md) | Ordem de execução ponta a ponta: Laravel, Vue, guards, Axios, WebSocket, eventos e broadcast |
| [technical/DOCUMENTACAO_TECNICA_LUA.md](technical/DOCUMENTACAO_TECNICA_LUA.md) | Redis Lua, OpenResty na borda, PL/Lua no PostgreSQL, scripts em `redis-scripts/` |

---

## Didático (estudo)

| Documento | Descrição |
|-----------|-----------|
| [didatico/README.md](didatico/README.md) | Índice do material de estudo |
| [didatico/GUIA_SEGURANCA_PERFORMANCE_CORRECAO.md](didatico/GUIA_SEGURANCA_PERFORMANCE_CORRECAO.md) | Segurança, performance e correção no contexto do repositório |

---

## Testes

| Documento | Descrição |
|-----------|-----------|
| [testing/ESTRATEGIA_TESTES.md](testing/ESTRATEGIA_TESTES.md) | Pirâmide de testes, matriz por módulo, CI e riscos |

O que o **GitHub Actions** executa de fato (PHPUnit, Pint, PHPStan; Vitest, ESLint, build) está descrito na **seção 1.4** desse documento; metas de cobertura elevada, MSW obrigatório ou Playwright como *gate* tratam-se de **roadmap / recomendações**, não de falha automática do CI atual.

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

## Prompts para agentes (IA)

Arquivos em [agents/](agents/) — contexto para especialização por área (backend, frontend, design, fullstack, integração). Índice operacional: [operations/AGENTS.md](operations/AGENTS.md).

---

## Referência histórica / planejamento

Textos de apoio — ver aviso em [reference/README.md](reference/README.md).

| Arquivo | Descrição |
|---------|-----------|
| [reference/StudyTrack_Arquitetura_Completa.txt](reference/StudyTrack_Arquitetura_Completa.txt) | Visão geral da arquitetura do sistema |
| [reference/StudyTrack_Endpoints_REST.txt](reference/StudyTrack_Endpoints_REST.txt) | Lista de endpoints da API REST |
| [reference/StudyTrack_Estrutura_Pastas.txt](reference/StudyTrack_Estrutura_Pastas.txt) | Estrutura de diretórios do projeto |
| [reference/StudyTrack_Modelagem_BD.txt](reference/StudyTrack_Modelagem_BD.txt) | Modelagem do banco de dados |
| [reference/StudyTrack_Eventos_Jobs.txt](reference/StudyTrack_Eventos_Jobs.txt) | Events, Listeners e Jobs |
| [reference/StudyTrack_Estrategia_Cache.txt](reference/StudyTrack_Estrategia_Cache.txt) | Estratégia de cache com Redis |
| [reference/StudyTrack_Dashboard.txt](reference/StudyTrack_Dashboard.txt) | Especificações do dashboard |
| [reference/StudyTrack_Checklist.txt](reference/StudyTrack_Checklist.txt) | Checklist de desenvolvimento |
| [reference/StudyTrack_Plano12Semanas.txt](reference/StudyTrack_Plano12Semanas.txt) | Plano de estudo de 12 semanas |
| [reference/StudyTrack_Testes.txt](reference/StudyTrack_Testes.txt) | Estratégia de testes |

---

## Frontend (design system e UX)

Índice: [../frontend/docs/README.md](../frontend/docs/README.md).

---

<p align="center">
  <a href="../README.md">← Voltar ao README principal</a>
</p>
