<script setup lang="ts">
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const props = withDefaults(
  defineProps<{
    show: boolean
    title: string
    message: string
    confirmLabel?: string
    cancelLabel?: string
    variant?: 'danger' | 'primary' | 'warning'
    loading?: boolean
  }>(),
  {
    confirmLabel: 'Confirmar',
    cancelLabel: 'Cancelar',
    variant: 'primary',
    loading: false,
  }
)

const emit = defineEmits<{
  close: []
  confirm: []
}>()

function onConfirm() {
  emit('confirm')
}
</script>

<template>
  <BaseModal
    :show="show"
    :title="title"
    @close="emit('close')"
  >
    <p class="confirm-dialog__message">
      {{ message }}
    </p>
    <div class="confirm-dialog__actions">
      <BaseButton
        variant="ghost"
        @click="emit('close')"
      >
        {{ props.cancelLabel }}
      </BaseButton>
      <BaseButton
        :variant="props.variant === 'warning' ? 'outline' : props.variant"
        :disabled="props.loading"
        @click="onConfirm"
      >
        {{ props.loading ? 'Aguarde...' : props.confirmLabel }}
      </BaseButton>
    </div>
  </BaseModal>
</template>

<style scoped>
.confirm-dialog__message {
  margin: 0 0 var(--spacing-xl);
  color: var(--color-text);
  font-size: var(--text-sm);
  line-height: var(--leading-normal);
}
.confirm-dialog__actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-sm);
}
</style>
