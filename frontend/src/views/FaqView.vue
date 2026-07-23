<script setup>
import { onMounted, ref } from 'vue'
import api from '@/api/client'
import AppIcon from '@/components/AppIcon.vue'
import AppLoader from '@/components/AppLoader.vue'

const items = ref([])
const loading = ref(true)
const openIds = ref({})

function toggle(id) {
  openIds.value[id] = !openIds.value[id]
}

function isOpen(id) {
  return Boolean(openIds.value[id])
}

onMounted(async () => {
  loading.value = true
  try {
    const { data } = await api.get('/faq')
    items.value = data.data ?? []
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="faq-page mx-auto my-8 w-full max-w-[800px] sm:my-10 lg:my-12">
    <header class="mb-6 sm:mb-8">
      <h1 class="m-0 text-2xl font-semibold uppercase leading-tight sm:text-[28px] lg:text-3xl">
        Частые вопросы
      </h1>
      <p class="m-0 mt-3 text-[15px] leading-relaxed text-[#64748b] sm:text-base">
        Ответы на популярные вопросы о заказах, доставке и продукции.
      </p>
    </header>

    <AppLoader v-if="loading" />

    <div v-else-if="items.length" class="faq-list">
      <article
        v-for="item in items"
        :key="item.id"
        class="faq-item"
        :class="{ 'faq-item--open': isOpen(item.id) }"
      >
        <button
          type="button"
          class="faq-item__trigger"
          :aria-expanded="isOpen(item.id)"
          :aria-controls="`faq-panel-${item.id}`"
          @click="toggle(item.id)"
        >
          <span class="faq-item__question">{{ item.question }}</span>
          <span class="faq-item__icon" aria-hidden="true">
            <AppIcon name="chevron-down" :size="18" />
          </span>
        </button>

        <div
          :id="`faq-panel-${item.id}`"
          class="faq-item__panel"
          role="region"
        >
          <div class="faq-item__answer">
            <p>{{ item.answer }}</p>
          </div>
        </div>
      </article>
    </div>

    <p v-else class="muted">Пока нет опубликованных вопросов.</p>
  </section>
</template>

<style scoped>
.faq-list {
  display: grid;
  gap: 0.75rem;
}

.faq-item {
  overflow: hidden;
  border-radius: 20px;
  background: #fff;
}

.faq-item__trigger {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.1rem 1.25rem;
  border: 0;
  background: transparent;
  text-align: left;
  cursor: pointer;
  color: #222222;
}

.faq-item__question {
  font-size: 15px;
  font-weight: 600;
  line-height: 1.4;
}

.faq-item__icon {
  display: inline-flex;
  flex-shrink: 0;
  color: #64748b;
  transition: transform 0.25s ease;
}

.faq-item--open .faq-item__icon {
  transform: rotate(180deg);
}

.faq-item__panel {
  display: grid;
  grid-template-rows: 0fr;
  transition: grid-template-rows 0.28s ease;
}

.faq-item--open .faq-item__panel {
  grid-template-rows: 1fr;
}

.faq-item__answer {
  overflow: hidden;
  min-height: 0;
}

.faq-item__answer p {
  margin: 0;
  padding: 0 1.25rem 1.15rem;
  font-size: 15px;
  line-height: 1.65;
  color: #475569;
  white-space: pre-line;
}

@media (min-width: 640px) {
  .faq-item__trigger {
    padding: 1.25rem 1.5rem;
  }

  .faq-item__question,
  .faq-item__answer p {
    font-size: 1rem;
  }

  .faq-item__answer p {
    padding: 0 1.5rem 1.35rem;
  }
}
</style>
