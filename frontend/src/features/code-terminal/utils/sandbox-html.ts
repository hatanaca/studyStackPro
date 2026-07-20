/**
 * Gera HTML para renderização em iframe sandboxed.
 * Usado para executar HTML e CSS de forma segura.
 */

/**
 * Cria HTML sandboxed para renderizar código HTML.
 * O código do usuário é inserido diretamente (permitido no contexto de preview).
 * O iframe usa sandbox="allow-scripts" para restringir scripts.
 */
export function createSandboxedHTML(code: string): string {
  return `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: system-ui, sans-serif; padding: 16px; }
  </style>
</head>
<body>
  ${code}
</body>
</html>`
}

/**
 * Cria HTML sandboxed para renderizar código CSS.
 * O código CSS é inserido em uma tag <style>.
 */
export function createSandboxedCSS(code: string): string {
  return `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: system-ui, sans-serif; padding: 16px; }
    ${code}
  </style>
</head>
<body>
  <h1>CSS Preview</h1>
  <p>Este é um parágrafo de exemplo para visualizar os estilos.</p>
  <div class="example">Div com classe example</div>
  <ul>
    <li>Item 1</li>
    <li>Item 2</li>
    <li>Item 3</li>
  </ul>
  <a href="#">Link de exemplo</a>
  <button>Botão</button>
  <input type="text" placeholder="Input de exemplo">
</body>
</html>`
}

/**
 * Cria iframe sandboxed e renderiza código.
 */
export function renderInSandbox(
  container: HTMLElement,
  code: string,
  type: 'html' | 'css'
): { success: boolean; error: string | null } {
  try {
    // Limpar container
    container.innerHTML = ''

    const iframe = document.createElement('iframe')
    iframe.setAttribute('sandbox', 'allow-scripts')
    iframe.setAttribute('title', `Preview ${type.toUpperCase()}`)
    iframe.style.width = '100%'
    iframe.style.height = '100%'
    iframe.style.border = 'none'
    iframe.style.borderRadius = 'var(--radius-md)'

    const html = type === 'html' ? createSandboxedHTML(code) : createSandboxedCSS(code)

    iframe.srcdoc = html
    container.appendChild(iframe)

    return { success: true, error: null }
  } catch (err) {
    return {
      success: false,
      error: err instanceof Error ? err.message : 'Erro ao renderizar no sandbox',
    }
  }
}
