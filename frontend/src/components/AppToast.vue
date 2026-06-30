<script setup>
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
</script>

<template>
  <div class="app-toast-stack" aria-live="polite">
    <TransitionGroup name="app-toast">
      <div
        v-for="item in toast.items"
        :key="item.id"
        class="app-toast"
        :class="`app-toast--${item.type}`"
      >
        <p class="app-toast__message">{{ item.message }}</p>
        <button
          type="button"
          class="app-toast__close"
          aria-label="Закрыть"
          @click="toast.remove(item.id)"
        >
          ×
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.app-toast-stack {
  position: fixed;
  right: 1rem;
  bottom: 1rem;
  z-index: 300;
  display: grid;
  gap: 0.75rem;
  width: min(360px, calc(100vw - 2rem));
}

.app-toast {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  border-radius: 0.75rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.14);
}

.app-toast--success {
  border-color: #86efac;
}

.app-toast--error {
  border-color: #fca5a5;
}

.app-toast__message {
  margin: 0;
  flex: 1;
  font-size: 0.95rem;
}

.app-toast__close {
  flex-shrink: 0;
  width: 1.5rem;
  height: 1.5rem;
  border: none;
  border-radius: 999px;
  background: #f1f5f9;
  cursor: pointer;
  font-size: 1rem;
  line-height: 1;
}

.app-toast-enter-active,
.app-toast-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.app-toast-enter-from,
.app-toast-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
