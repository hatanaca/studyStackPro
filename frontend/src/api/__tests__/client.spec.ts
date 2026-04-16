import { getApiErrorMessage } from '../client'

describe('client', () => {
  describe('getApiErrorMessage', () => {
    it('extracts message from API error response (error.message)', () => {
      const error = {
        response: {
          data: {
            success: false,
            error: { message: 'Credenciais inválidas.' },
          },
        },
      }
      expect(getApiErrorMessage(error)).toBe('Credenciais inválidas.')
    })

    it('extracts message from API error response (data.message)', () => {
      const error = {
        response: {
          data: {
            success: false,
            message: 'Recurso não encontrado.',
          },
        },
      }
      expect(getApiErrorMessage(error)).toBe('Recurso não encontrado.')
    })

    it('prefers error.message over data.message when both exist', () => {
      const error = {
        response: {
          data: {
            error: { message: 'Mensagem específica do erro.' },
            message: 'Mensagem genérica.',
          },
        },
      }
      expect(getApiErrorMessage(error)).toBe('Mensagem específica do erro.')
    })

    it('returns default message for unknown errors', () => {
      expect(getApiErrorMessage(new Error('Network error'))).toBe(
        'Erro na comunicação com o servidor.'
      )
      expect(getApiErrorMessage(null)).toBe('Erro na comunicação com o servidor.')
      expect(getApiErrorMessage(undefined)).toBe('Erro na comunicação com o servidor.')
      expect(getApiErrorMessage({})).toBe('Erro na comunicação com o servidor.')
    })

    it('returns default when response has no message field', () => {
      const error = {
        response: {
          data: { success: false },
        },
      }
      expect(getApiErrorMessage(error)).toBe('Erro na comunicação com o servidor.')
    })
  })
})
