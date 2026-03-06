<script setup lang="ts">
import { computed, watch } from 'vue'
import BaseDropdown from '@/components/ui/BaseDropdown.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import type { NotificationType } from '@/stores/notifications.store'
import { useNotificationsStore } from '@/stores/notifications.store'

const notificationsStore = useNotificationsStore()

const unreadCount = computed(() => notificationsStore.items.filter(n => !n.read).length)

watch(
  () => notificationsStore.items,
  () => notificationsStore.updateUnreadCount(),
  { deep: true }
)

function typeIcon(type: NotificationType): string {
  switch (type) {
    case 'success': return '✓'
    case 'warning': return '!'
    case 'error': return '✕'
    default: return 'i'
  }
}

function typeClass(type: NotificationType): string {
  return `notification-center__item--${type}`
}
</script>

<template>
  <BaseDropdown align="right">
    <template #trigger>
      <button
        type="button"
        class="notification-center__trigger"
        aria-label="Notificações"
      >
        <span class="notification-center__icon">🔔</span>
        <span
          v-if="unreadCount > 0"
          class="notification-center__badge"
        >
          {{ unreadCount > 99 ? '99+' : unreadCount }}
        </span>
      </button>
    </template>
    <div class="notification-center__panel">
      <div class="notification-center__header">
        <span class="notification-center__title">Notificações</span>
        <BaseButton
          v-if="unreadCount > 0"
          variant="ghost"
          size="sm"
          @click="notificationsStore.markAllRead()"
        >
          Marcar todas como lidas
        </BaseButton>
      </div>
      <div class="notification-center__list">
        <template v-if="notificationsStore.items.length">
          <div
            v-for="n in notificationsStore.items"
            :key="n.id"
            class="notification-center__item"
            :class="[typeClass(n.type), { 'notification-center__item--unread': !n.read }]"
          >
            <span class="notification-center__item-icon">{{ typeIcon(n.type) }}</span>
            <div class="notification-center__item-body">
              <strong class="notification-center__item-title">{{ n.title }}</strong>
              <p
                v-if="n.message"
                class="notification-center__item-message"
              >
                {{ n.message }}
              </p>
            </div>
            <BaseButton
              variant="ghost"
              size="sm"
              aria-label="Fechar"
              @click="notificationsStore.remove(n.id)"
            >
              ×
            </BaseButton>
          </div>
        </template>
        <p
          v-else
          class="notification-center__empty"
        >
          Nenhuma notificação.
        </p>
      </div>
    </div>
  </BaseDropdown>
</template>

<style scoped>
.notification-center__trigger {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: var(--input-height-md);
  height: var(--input-height-md);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  font-size: 1.125rem;
  transition: border-color var(--duration-fast) ease, background var(--duration-fast) ease;
}
.notification-center__trigger:hover {
  border-color: var(--color-primary);
  background: var(--color-primary-soft);
}
.notification-center__badge {
  position: absolute;
  top: var(--spacing-2xs);
  right: var(--spacing-2xs);
  min-width: 1.25rem;
  height: 1.25rem;
  padding: 0 var(--spacing-xs);
  font-size: 0.65rem;
  font-weight: 700;
  color: #fff;
  background: var(--color-error);
  border-radius: 9999px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.notification-center__panel {
  min-width: 300px;
  max-width: 380px;
  max-height: 380px;
  display: flex;
  flex-direction: column;
}
.notification-center__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-sm);
  padding: var(--spacing-md) var(--widget-padding);
  border-bottom: 1px solid var(--color-border);
}
.notification-center__title {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-text);
}
.notification-center__list {
  overflow-y: auto;
  max-height: 300px;
}
.notification-center__item {
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-sm);
  padding: var(--spacing-md) var(--widget-padding);
  border-bottom: 1px solid var(--color-border);
  transition: background var(--duration-fast) ease;
}
.notification-center__item--unread {
  background: color-mix(in srgb, var(--color-bg-soft) 70%, var(--color-bg-card));
}
.notification-center__item-icon {
  flex-shrink: 0;
  width: 1.5rem;
  height: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: 0.7rem;
  font-weight: 700;
}
.notification-center__item--info .notification-center__item-icon { background: var(--color-info-soft); color: var(--color-info); }
.notification-center__item--success .notification-center__item-icon { background: var(--color-success-soft); color: var(--color-success); }
.notification-center__item--warning .notification-center__item-icon { background: var(--color-warning-soft); color: var(--color-warning); }
.notification-center__item--error .notification-center__item-icon { background: var(--color-error-soft); color: var(--color-error); }
.notification-center__item-body {
  flex: 1;
  min-width: 0;
}
.notification-center__item-title {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-text);
}
.notification-center__item-message {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: var(--spacing-xs) 0 0;
  line-height: 1.4;
}
.notification-center__empty {
  padding: var(--spacing-xl);
  text-align: center;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0;
  line-height: 1.5;
}
</style>
