# Checklist e exemplos de prompt

- **Debug (quando algo quebra):** use o checklist rápido e o prompt de diagnóstico em [DEBUG-CHECKLIST-E-PROMPT.md](DEBUG-CHECKLIST-E-PROMPT.md).
- **Throttles da API:** tabela no final deste arquivo.
- **Variáveis de ambiente:** `docs/ENV-VARS.md` e `backend/.env.example` / `frontend/.env.example`.
- **WebSocket:** canais privados `dashboard.{userId}`; eventos `.session.started`, `.session.ended`, `.metrics.updated`, `.metrics.recalculating`. O frontend espera `session.technology.slug` em `.session.started` e `dashboard` (objeto DashboardData) em `.metrics.updated`.

## Checklist antes de entregar

Use este checklist ao implementar ou revisar mudanças no StudyTrackPro.

### Backend (Laravel)

- [ ] Controller thin (delega ao Service; sem lógica de negócio)
- [ ] Validação em Form Request (nunca no controller ou service)
- [ ] Acesso ao banco apenas via Repository (interface em `Contracts/` + binding)
- [ ] DTO com propriedades `readonly` quando aplicável
- [ ] Eventos nomeados no passado; disparados pelo Service
- [ ] Listeners rápidos (invalidação de cache ou dispatch de Job)
- [ ] Job com `ShouldBeUnique` quando for recálculo/operação idempotente
- [ ] Nova rota com rate limiting em `api.php` (auth, search, sensitive, export, recalculate, health)
- [ ] Cache com tags para invalidação granular
- [ ] Broadcast WebSocket: canal autorizado em `channels.php`
- [ ] Feature test cobrindo o fluxo HTTP; Unit test para Service quando fizer sentido
- [ ] Contrato de API (payload + Resource) alinhado ao frontend

### Frontend (Vue)

- [ ] Tipos em `types/`; chamadas HTTP via módulos em `api/`
- [ ] Store/composable não chama API inexistente (ex.: Goals é só localStorage)
- [ ] Tratamento de erro e estados loading/vazio
- [ ] Acessibilidade (aria, labels, contraste)

### Geral

- [ ] Migrations: não alterar já executadas; novas em `transactional/` ou `analytics/`
- [ ] README e docs atualizados se a mudança afetar setup ou decisões
- [ ] Coleção Postman atualizada se houver novo endpoint

---

## Exemplos de uso do prompt

Use estes exemplos ao pedir tarefas para o Composer (agentes do projeto).

### Backend

- *"Implementa endpoint GET /analytics/export com parâmetros start e end (data), validação de intervalo máximo 366 dias e Form Request."*
- *"Adiciona rate limit específico para a rota de export: 30 req/min por usuário."*
- *"Garante que o evento SessionStarted envie o slug da tecnologia no payload para o frontend."*
- *"Unifica os seeders: um único usuário de demo (dev@) com techs e sessões; Documenta a ordem no DatabaseSeeder."*

### Frontend

- *"Ajusta o useWebSocket para usar o slug da tecnologia vindo do backend em vez de usar o id no lugar do slug."*
- *"Documenta que Goals é apenas frontend (localStorage) e que não existe API de goals; atualiza o README."*

### Full-stack / integração

- *"Inclui o endpoint GET /analytics/export na coleção Postman com descrição dos parâmetros e throttle."*
- *"Adiciona Feature test para o export (auth, estrutura da resposta, validação de parâmetros e intervalo máximo)."*
- *"Adiciona Unit test para o AnalyticsService.getExportData delegando ao repositório."*

### Documentação e qualidade

- *"Cria um checklist (testes, contratos, migrations, README) e exemplos de prompt para os agentes em docs/."*
- *"Documenta os throttles da API (auth, search, sensitive, export, recalculate, health) em um arquivo de referência."*

---

## Referência de throttles (API)

| Nome       | Uso                          | Limite        |
|-----------|------------------------------|---------------|
| `auth`    | Login, registro              | 5/min por IP  |
| `sensitive` | Alteração de senha, etc.   | 5/min por user |
| `search`  | Busca de tecnologias, sessão ativa | 120/min por user |
| `export`  | Export de analytics          | 30/min por user |
| `recalculate` | Recálculo de métricas    | 2/min por user |
| `health`  | Healthcheck                  | 300/min por IP |
| Padrão (60,1) | Leitura geral           | 60/min por user |
| Padrão (30,1) | Escrita (sessões, techs, etc.) | 30/min por user |
