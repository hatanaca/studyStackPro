# Agentes do Projeto StudyTrackPro

Este arquivo descreve os agentes especializados disponíveis para o Composer do Cursor.

---

## Agente Frontend Vue + UI/UX

**Quando usar:** melhorias de interface, componentes Vue, TypeScript no frontend, UX e integração com o backend Laravel.

**Como ativar no Composer:**

1. Inclua no contexto arquivos da pasta `frontend/` (por exemplo um `.vue` ou `.ts` da aplicação), ou
2. Abra um arquivo em `frontend/` antes de abrir o Composer.

Com isso, a regra **Frontend Vue + UI/UX** é aplicada automaticamente e o Composer atua como o agente especialista.

**Regra:** `.cursor/rules/frontend-vue-ui-ux.mdc`  
**Prompt completo:** `docs/prompt-agente-frontend-ui-ux.md`

**Escopo do agente:**

- Vue.js 3 (Composition API, `<script setup>`), TypeScript
- UI/UX: layout, estados (loading/erro/vazio), acessibilidade, design tokens
- Integração com Laravel (API REST, auth, Laravel Echo quando aplicável)
- Uso dos componentes base e de `frontend/src/assets/styles/variables.css`
