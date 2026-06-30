<script setup>
defineProps({
  label: {
    type: String,
    default: 'Загрузка...',
  },
  inline: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <div class="app-loader" :class="{ 'app-loader--inline': inline }" role="status" aria-live="polite">
    <span class="app-loader__spinner" aria-hidden="true" />
    <span v-if="label" class="app-loader__label">{{ label }}</span>
  </div>
</template>

<style scoped>
.app-loader {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2rem 1rem;
}

.app-loader--inline {
  flex-direction: row;
  justify-content: flex-start;
  padding: 1rem 0;
}

.app-loader__spinner {
  width: 2rem;
  height: 2rem;
  border: 3px solid #e2e8f0;
  border-top-color: #2563eb;
  border-radius: 999px;
  animation: app-loader-spin 0.7s linear infinite;
}

.app-loader--inline .app-loader__spinner {
  width: 1.25rem;
  height: 1.25rem;
  border-width: 2px;
}

.app-loader__label {
  color: #64748b;
  font-size: 0.95rem;
}

@keyframes app-loader-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
