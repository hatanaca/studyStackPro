# Metas (Goals) — apenas frontend

As **metas** (objetivos de minutos por semana, sessões por semana, streak) são uma funcionalidade **somente no frontend** do StudyTrackPro.

## Decisão

- **Não existe** endpoint de Goals na API Laravel (`/api/v1/goals`).
- O frontend persiste metas em **localStorage** (chave `studytrack.goals`).
- A store Pinia (`goals.store`) e o módulo `api/modules/goals.api.ts` leem e gravam apenas no navegador do usuário.

## Motivação

- Permite lançar a feature de metas sem alterar backend nem migrations.
- Metas são por dispositivo/navegador; não há sincronização entre dispositivos.
- Se no futuro for necessário backend (multi-dispositivo, relatórios), será preciso:
  - migrations para tabela `goals`,
  - módulo Goals no backend (Repository, Service, Controller),
  - rotas CRUD e testes,
  - e então alterar `goals.api.ts` para usar `apiClient` em vez de localStorage.

## Contrato atual (frontend)

- **Tipos:** `Goal`, `CreateGoalPayload`, `UpdateGoalPayload` em `types/goals.types.ts`.
- **API local:** `goalsApi.list()`, `goalsApi.create(payload)`, `goalsApi.update(id, payload)`, `goalsApi.delete(id)` em `api/modules/goals.api.ts` (todos operam sobre localStorage).
- **Rotas:** `/goals` (GoalsView), widget no Dashboard.

## Checklist para futura API de Goals

Se for implementar Goals no backend:

- [ ] Migration `goals` (user_id, type, target_value, current_value, status, start_date, end_date, meta JSON).
- [ ] Model `Goal`, enum para type/status.
- [ ] Módulo `app/Modules/Goals/` (Repository, Service, DTOs).
- [ ] Rotas GET/POST/PUT/DELETE com throttle e auth.
- [ ] Form Requests e Resources.
- [ ] Feature tests e atualizar `goals.api.ts` para chamar a API.
