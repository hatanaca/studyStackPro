# Design System – StudyTrack Pro

Este documento descreve os tokens de design e padrões de UI usados no frontend Vue.

---

## 1. Cores

### Cores base (tema claro)
| Token | Uso |
|-------|-----|
| `--color-text` | Texto principal |
| `--color-text-muted` | Texto secundário, legendas |
| `--color-bg` | Fundo da página |
| `--color-bg-soft` | Fundo de áreas suaves (hover, chips) |
| `--color-bg-card` | Fundo de cards e superfícies elevadas |
| `--color-primary` | Ações principais, links, destaque |
| `--color-primary-hover` | Hover em botão primário |
| `--color-primary-soft` | Fundo de destaque suave |
| `--color-border` | Bordas e divisórias |
| `--color-focus-ring` | Anel de foco (acessibilidade) |

### Cores semânticas
| Token | Uso |
|-------|-----|
| `--color-success` | Sucesso, confirmação |
| `--color-success-soft` | Fundo de mensagem de sucesso |
| `--color-warning` | Aviso |
| `--color-warning-soft` | Fundo de aviso |
| `--color-error` | Erro, destruição |
| `--color-error-soft` | Fundo de erro |
| `--color-info` | Informação neutra |
| `--color-info-soft` | Fundo de info |

---

## 2. Gradientes
- `--gradient-primary`: botões e destaques (azul → índigo).
- `--gradient-accent`: destaques secundários (verde → azul).
- `--gradient-mesh`: fundo sutil da área principal (layout).

---

## 3. Espaçamento
Escala base em `rem`:
- `--spacing-2xs`: 0.125rem
- `--spacing-xs`: 0.25rem
- `--spacing-sm`: 0.5rem
- `--spacing-md`: 1rem
- `--spacing-lg`: 1.5rem
- `--spacing-xl`: 2rem
- `--spacing-2xl`: 2.5rem
- `--spacing-3xl`: 3rem

---

## 4. Tipografia
- **Fonte**: `--font-sans` (Inter + system-ui).
- **Tamanhos**: `--text-xs` (0.75rem) até `--text-3xl` (1.875rem).
- Preferir tokens em vez de valores fixos para manter consistência.

---

## 5. Raios e sombras
- **Raios**: `--radius-sm`, `--radius-md`, `--radius-lg`, `--radius-xl`.
- **Sombras**: `--shadow-sm`, `--shadow-md`, `--shadow-lg`, `--shadow-card-hover`.
- **Dropdown**: `--dropdown-shadow` para menus suspensos.

---

## 6. Motion
- **Durações**: `--duration-fast` (150ms), `--duration-normal` (250ms), `--duration-slow` (400ms).
- **Easing**: `--ease-out-expo`, `--ease-in-out`.
- Respeitar `prefers-reduced-motion` nos estilos globais.

---

## 7. Breakpoints (media queries)
| Nome | Largura |
|------|---------|
| xs   | 480px   |
| sm   | 640px   |
| md   | 768px   |
| lg   | 1024px  |
| xl   | 1280px  |

---

## 8. Componentes base (UI)
- **BaseCard**: container com título opcional e slot de ações.
- **BaseButton**: variantes primary, secondary, ghost, danger, outline; tamanhos sm, md, lg.
- **BaseInput**: campo de texto com label e mensagem de erro.
- **BaseModal**: overlay com blur; animação de escala no conteúdo.
- **BaseToast**: notificações temporárias.
- **BaseBadge**: rótulos e contadores.
- **BaseAvatar**: imagem ou iniciais; tamanhos sm, md, lg, xl.
- **BaseTooltip**: dica ao hover/foco; posições top, bottom, left, right.
- **BaseTabs**: abas (variantes line, pill, enclosed).
- **BaseProgress**: barra de progresso (primary, success, warning, error); opção indeterminada.
- **BaseDropdown**: menu suspenso com slot trigger e conteúdo.
- **BaseAccordion**: itens expansíveis.
- **EmptyState**: estado vazio com ícone, título, descrição e ação opcional.
- **SkeletonLoader**: placeholder de carregamento.
- **ErrorCard**: mensagem de erro com retry.
- **ThemeToggle**: alternância tema claro/escuro.

---

## 9. Utilitários CSS (`utilities.css`)
Classes para margin/padding (m-0..m-5, mt-*, p-*, gap-*), tipografia (text-xs, font-bold, etc.), display e flex (flex, items-center, justify-between), largura/altura (w-full, min-h-screen), posição (relative, absolute, z-10), bordas e radius (rounded-lg, border), sombras (shadow-md), overflow, cursor, opacidade, transições (transition, transition-colors), acessibilidade (sr-only) e visibilidade responsiva (hide-xs, show-md). Container com max-width responsivo.

---

## 10. Animações e transições
- **fade**: opacidade.
- **slide**: translateX.
- **fade-up**: entrada de baixo (opacidade + translateY).
- **modal**: overlay fade + conteúdo com scale.
- **tooltip-fade**: usado pelo BaseTooltip.
- **accordion**: usado pelo BaseAccordion.
- **dropdown**: usado pelo BaseDropdown.

---

## 11. Tema escuro
Ativado com `[data-theme='dark']` no `<html>`. Todas as variáveis de cor, gradiente, sombra e glass são redefinidas para o tema escuro. Scrollbars customizadas para ambos os temas.

---

## 12. Acessibilidade
- Contorno visível com `:focus-visible` usando `--color-primary`.
- Uso de `role`, `aria-label`, `aria-expanded`, `aria-selected` nos componentes interativos.
- Suporte a teclado (Tab, Enter, Escape) em modais e dropdowns.
- Respeito a `prefers-reduced-motion` para reduzir animações.
