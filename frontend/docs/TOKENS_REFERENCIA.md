# Referência de tokens de design

Lista completa das variáveis CSS disponíveis no projeto e exemplos de uso.

---

## Cores – texto e fundo

| Token | Descrição | Uso típico |
|-------|-----------|------------|
| `--color-text` | Texto principal | Corpo de texto, títulos |
| `--color-text-muted` | Texto secundário | Legendas, placeholders, hints |
| `--color-bg` | Fundo da página | body, main |
| `--color-bg-soft` | Fundo suave | Hover, chips, áreas destacadas |
| `--color-bg-card` | Fundo de cards | Cards, modais, dropdowns |
| `--color-primary` | Cor primária | Botões, links, destaques |
| `--color-primary-hover` | Hover primário | Estado hover em botão primário |
| `--color-primary-soft` | Fundo primário suave | Badges, avatares de iniciais |
| `--color-border` | Bordas | Divisórias, inputs, cards |
| `--color-focus-ring` | Anel de foco | outline em :focus-visible |

Exemplo:
```css
.meu-titulo { color: var(--color-text); }
.meu-hint { color: var(--color-text-muted); }
.meu-card { background: var(--color-bg-card); border: 1px solid var(--color-border); }
```

---

## Cores semânticas

| Token | Descrição |
|-------|------------|
| `--color-success` | Sucesso, confirmação |
| `--color-success-soft` | Fundo de mensagem de sucesso |
| `--color-warning` | Aviso |
| `--color-warning-soft` | Fundo de aviso |
| `--color-error` | Erro, destruição |
| `--color-error-soft` | Fundo de erro |
| `--color-info` | Informação |
| `--color-info-soft` | Fundo de info |

---

## Gradientes

| Token | Descrição |
|-------|------------|
| `--gradient-primary` | Gradiente primário (azul → índigo) |
| `--gradient-accent` | Gradiente de destaque (verde → azul) |
| `--gradient-mesh` | Fundo mesh da área principal |

---

## Espaçamento

| Token | Valor (rem) |
|-------|-------------|
| `--spacing-2xs` | 0.125 |
| `--spacing-xs` | 0.25 |
| `--spacing-sm` | 0.5 |
| `--spacing-md` | 1 |
| `--spacing-lg` | 1.5 |
| `--spacing-xl` | 2 |
| `--spacing-2xl` | 2.5 |
| `--spacing-3xl` | 3 |

Use em margin, padding e gap para consistência.

---

## Raios (border-radius)

| Token | Uso |
|-------|-----|
| `--radius-sm` | Chips, badges pequenos |
| `--radius-md` | Inputs, botões |
| `--radius-lg` | Cards, modais |
| `--radius-xl` | Cards grandes, seções |

---

## Sombras

| Token | Uso |
|-------|-----|
| `--shadow-sm` | Leve elevação |
| `--shadow-md` | Cards, dropdowns |
| `--shadow-lg` | Modais, overlays |
| `--shadow-card-hover` | Estado hover de card |
| `--dropdown-shadow` | Painel de dropdown |

---

## Tipografia

| Token | Tamanho | Uso |
|-------|---------|-----|
| `--font-sans` | Família | Fonte principal |
| `--text-xs` | 0.75rem | Legendas, hints |
| `--text-sm` | 0.875rem | Texto secundário, botões |
| `--text-base` | 1rem | Corpo |
| `--text-lg` | 1.125rem | Subtítulos |
| `--text-xl` | 1.25rem | Títulos de seção |
| `--text-2xl` | 1.5rem | Títulos de página |
| `--text-3xl` | 1.875rem | Hero, destaque |

---

## Motion

| Token | Valor | Uso |
|-------|--------|-----|
| `--ease-out-expo` | cubic-bezier(0.16, 1, 0.3, 1) | Entradas, animações |
| `--ease-in-out` | cubic-bezier(0.4, 0, 0.2, 1) | Transições suaves |
| `--duration-fast` | 150ms | Hover, focus |
| `--duration-normal` | 250ms | Transições de estado |
| `--duration-slow` | 400ms | Entrada de conteúdo |

---

## Componentes específicos

| Token | Uso |
|-------|-----|
| `--sidebar-width` | Largura da sidebar |
| `--input-height-sm/md/lg` | Altura de inputs |
| `--avatar-size-sm/md/lg/xl` | Tamanhos de avatar |
| `--tooltip-offset` | Distância do tooltip ao trigger |
| `--glass-bg` | Fundo glass (backdrop) |
| `--glass-border` | Borda glass |

---

## Tema escuro

Todos os tokens acima são redefinidos em `[data-theme='dark']`. Não use cores fixas em componentes; sempre prefira variáveis para que o tema escuro funcione.

---

## Breakpoints (media queries)

Não são variáveis CSS; use valores fixos ou constantes JS:

- 480px (xs)
- 640px (sm)
- 768px (md)
- 1024px (lg)
- 1280px (xl)

Exemplo:
```css
@media (min-width: 768px) {
  .sidebar { width: var(--sidebar-width); }
}
```
