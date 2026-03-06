/**
 * Mensagens de UI e validação centralizadas
 */

export const VALIDATION = {
  required: 'Campo obrigatório.',
  email: 'E-mail inválido.',
  minLength: (min: number) => `Mínimo ${min} caracteres.`,
  maxLength: (max: number) => `Máximo ${max} caracteres.`,
  min: (min: number) => `Valor mínimo: ${min}.`,
  max: (max: number) => `Valor máximo: ${max}.`,
  number: 'Deve ser um número.',
  integer: 'Deve ser um número inteiro.',
  positive: 'Deve ser um número positivo.',
  date: 'Data inválida.',
  dateRange: 'Data final deve ser igual ou posterior à data inicial.',
} as const

export const GOALS = {
  createSuccess: 'Meta criada com sucesso.',
  createError: 'Erro ao criar meta.',
  updateSuccess: 'Meta atualizada.',
  updateError: 'Erro ao atualizar meta.',
  deleteSuccess: 'Meta excluída.',
  deleteError: 'Erro ao excluir meta.',
  loadError: 'Erro ao carregar metas.',
  confirmDelete: 'Tem certeza que deseja excluir esta meta? Esta ação não pode ser desfeita.',
} as const

export const PROFILE = {
  updateSuccess: 'Perfil atualizado com sucesso.',
  updateError: 'Erro ao atualizar perfil.',
  passwordSuccess: 'Senha alterada. Você será desconectado.',
  passwordError: 'Erro ao alterar senha.',
  revokeSuccess: 'Sessão(ões) revogada(s).',
  revokeError: 'Erro ao revogar sessões.',
  revokeConfirm: 'Revogar todas as sessões? Você será desconectado de todos os dispositivos.',
} as const

export const EXPORT = {
  success: 'Exportação gerada com sucesso.',
  error: 'Erro ao gerar exportação.',
  preparing: 'Preparando...',
} as const

export const DASHBOARD = {
  loadError: 'Não foi possível carregar o dashboard.',
  noData: 'Nenhum dado ainda. Registre sessões acima para ver métricas.',
  updating: 'Atualizando métricas...',
} as const

export const SESSIONS = {
  loadError: 'Erro ao carregar sessões.',
  saveSuccess: 'Sessão registrada.',
  saveError: 'Erro ao salvar sessão.',
  deleteSuccess: 'Sessão excluída.',
  deleteError: 'Erro ao excluir sessão.',
} as const

export const TECHNOLOGIES = {
  loadError: 'Erro ao carregar tecnologias.',
  createSuccess: 'Tecnologia criada.',
  createError: 'Erro ao criar tecnologia.',
  updateSuccess: 'Tecnologia atualizada.',
  updateError: 'Erro ao atualizar tecnologia.',
  deleteSuccess: 'Tecnologia excluída.',
  deleteError: 'Erro ao excluir tecnologia.',
} as const

export const NOTIFICATIONS = {
  markAllRead: 'Marcar todas como lidas',
  empty: 'Nenhuma notificação.',
} as const

export const COMMON = {
  loading: 'Carregando...',
  retry: 'Tentar novamente',
  cancel: 'Cancelar',
  save: 'Salvar',
  delete: 'Excluir',
  edit: 'Editar',
  close: 'Fechar',
  confirm: 'Confirmar',
  back: 'Voltar',
  next: 'Próximo',
  previous: 'Anterior',
} as const
