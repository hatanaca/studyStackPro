/**
 * @module canvas.types
 * @description Tipos e interfaces utilizados pelo módulo de canvas.
 *
 * Contém as definições de tipo para ferramentas, configurações,
 * histórico, filtros, opções de exportação e propriedades de objetos do canvas.
 */

/**
 * @description Union type que representa todas as ferramentas disponíveis no editor de canvas.
 *
 * - `select` — Ferramenta de seleção padrão
 * - `pencil` — Pincel livre para desenho
 * - `eraser` — Borracha para apagar traços
 * - `line` — Ferramenta de linha reta
 * - `rect` — Retângulo
 * - `circle` — Círculo
 * - `triangle` — Triângulo
 * - `arrow` — Seta (reservado para uso futuro)
 * - `text` — Texto editável inline
 * - `textbox` — Caixa de texto com quebra de linha
 * - `image` — Inserção de imagem
 * - `highlight` — Marcador de destaque semitransparente
 * - `sticky` — Nota adesiva (sticky note)
 */
export type CanvasTool =
  | 'select'
  | 'pencil'
  | 'eraser'
  | 'line'
  | 'rect'
  | 'circle'
  | 'triangle'
  | 'arrow'
  | 'text'
  | 'textbox'
  | 'image'
  | 'highlight'
  | 'sticky'

/**
 * @description Configuração de uma ferramenta exibida na toolbar do canvas.
 */
export interface CanvasToolConfig {
  /** Nome de exibição da ferramenta */
  name: string
  /** Ícone (emoji ou glyph) representando a ferramenta */
  icon: string
  /** Identificador da ferramenta do canvas associada */
  tool: CanvasTool
  /** Grupo categorizador para organização visual na toolbar */
  group: 'draw' | 'shapes' | 'text' | 'image' | 'annotate'
}

/**
 * @description Entrada de um registro no histórico de ações do canvas.
 */
export interface CanvasHistoryEntry {
  /** Serialização JSON do estado completo do canvas */
  json: string
  /** Marca temporal (timestamp Unix) de quando a ação foi registrada */
  timestamp: number
}

/**
 * @description Definição de um filtro aplicável a objetos do canvas.
 */
export interface CanvasFilter {
  /** Identificador único do filtro */
  name: string
  /** Rótulo amigável exibido na interface */
  label: string
  /** Valor mínimo permitido para o parâmetro do filtro */
  min?: number
  /** Valor máximo permitido para o parâmetro do filtro */
  max?: number
  /** Incremento entre valores válidos */
  step?: number
  /** Valor padrão do filtro */
  default: number
}

/**
 * @description Opções de exportação do canvas para diferentes formatos.
 */
export interface ExportOptions {
  /** Formato de saída do arquivo exportado */
  format: 'png' | 'jpeg' | 'svg' | 'json'
  /** Qualidade da compressão (0–1), aplicável a JPEG */
  quality?: number
  /** Multiplicador de resolução para imagens rasterizadas */
  multiplier?: number
  /** Nome do arquivo de saída (sem extensão) */
  filename?: string
}

/**
 * @description Propriedades editáveis de um objeto selecionado no canvas.
 */
export interface CanvasObjectProps {
  /** Cor de preenchimento do objeto (hex, rgb ou nome CSS) */
  fill?: string
  /** Cor do contorno do objeto */
  stroke?: string
  /** Espessura do contorno em pixels */
  strokeWidth?: number
  /** Opacidade do objeto (0–1) */
  opacity?: number
  /** Tamanho da fonte em pixels (para objetos de texto) */
  fontSize?: number
  /** Família tipográfica (para objetos de texto) */
  fontFamily?: string
  /** Propriedades adicionais arbitrárias do objeto Fabric.js */
  [key: string]: unknown
}
