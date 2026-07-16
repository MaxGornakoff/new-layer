<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'

const sections = ref([])
const items = ref([])
const loading = ref(true)

const sectionForm = reactive({
  key: '',
  title: '',
  url: '',
  type: 'link',
  include_categories: false,
  sort_order: 0,
  is_active: true,
  open_in_new_tab: false,
})

const itemForm = reactive({
  parent_key: '',
  title: '',
  url: '',
  sort_order: 0,
  open_in_new_tab: false,
})

const typeLabels = {
  link: 'Ссылка',
  dropdown: 'Выпадающий список',
}

const sectionOptions = computed(() =>
  sections.value.map((section) => ({
    key: section.key,
    label: section.title,
  })),
)

async function load() {
  loading.value = true
  try {
    const [sectionsResponse, itemsResponse] = await Promise.all([
      api.get('/admin/menu-sections'),
      api.get('/admin/menu-items'),
    ])

    sections.value = sectionsResponse.data.data
    items.value = itemsResponse.data.data

    if (!itemForm.parent_key && sections.value.length) {
      itemForm.parent_key = sections.value[0].key
    }
  } finally {
    loading.value = false
  }
}

async function createSection() {
  await api.post('/admin/menu-sections', sectionForm)
  Object.assign(sectionForm, {
    key: '',
    title: '',
    url: '',
    type: 'link',
    include_categories: false,
    sort_order: 0,
    is_active: true,
    open_in_new_tab: false,
  })
  await load()
}

async function removeSection(section) {
  if (!confirm(`Удалить раздел «${section.title}» и все его подпункты?`)) return
  await api.delete(`/admin/menu-sections/${section.id}`)
  await load()
}

async function createItem() {
  await api.post('/admin/menu-items', itemForm)
  Object.assign(itemForm, {
    title: '',
    url: '',
    sort_order: 0,
    open_in_new_tab: false,
  })
  await load()
}

async function removeItem(item) {
  if (!confirm(`Удалить «${item.title}»?`)) return
  await api.delete(`/admin/menu-items/${item.id}`)
  await load()
}

function itemsForSection(sectionKey) {
  return items.value.filter((item) => item.parent_key === sectionKey)
}

onMounted(load)
</script>

<template>
  <section class="admin-page">
    <header class="admin-page__header">
      <h1>Меню сайта</h1>
      <p class="admin-page__lead">
        Разделы главного меню и их подпункты. Для каталога можно включить автоматический вывод категорий.
      </p>
    </header>

    <form class="card admin-form" @submit.prevent="createSection">
      <header class="admin-form__header">
        <h3>Добавить раздел меню</h3>
      </header>

      <fieldset class="admin-form__section admin-form__section--last">
        <legend class="admin-form__section-title">Параметры раздела</legend>

      <label class="field">
        <span>Ключ (латиница)</span>
        <input v-model="sectionForm.key" placeholder="catalog" required />
      </label>

      <label class="field">
        <span>Название</span>
        <input v-model="sectionForm.title" required />
      </label>

      <label class="field">
        <span>Тип</span>
        <select v-model="sectionForm.type" required>
          <option value="link">{{ typeLabels.link }}</option>
          <option value="dropdown">{{ typeLabels.dropdown }}</option>
        </select>
      </label>

      <label class="field">
        <span>Ссылка</span>
        <input v-model="sectionForm.url" placeholder="/delivery или /#about-us" />
      </label>

      <label class="field">
        <span>Порядок</span>
        <input v-model.number="sectionForm.sort_order" type="number" min="0" />
      </label>

      <label v-if="sectionForm.type === 'dropdown'" class="field admin-checkbox">
        <input v-model="sectionForm.include_categories" type="checkbox" />
        <span>Добавлять категории каталога в подменю</span>
      </label>

      <label class="field admin-checkbox">
        <input v-model="sectionForm.open_in_new_tab" type="checkbox" />
        <span>Открывать в новой вкладке</span>
      </label>

      <label class="field admin-checkbox">
        <input v-model="sectionForm.is_active" type="checkbox" />
        <span>Активен</span>
      </label>
      </fieldset>

      <div class="admin-actions">
        <button class="btn" type="submit">Сохранить раздел</button>
      </div>
    </form>

    <form class="card admin-form" @submit.prevent="createItem">
      <header class="admin-form__header">
        <h3>Добавить подпункт</h3>
      </header>

      <fieldset class="admin-form__section admin-form__section--last">
        <legend class="admin-form__section-title">Параметры подпункта</legend>

      <label class="field">
        <span>Раздел</span>
        <select v-model="itemForm.parent_key" required>
          <option v-for="option in sectionOptions" :key="option.key" :value="option.key">
            {{ option.label }}
          </option>
        </select>
      </label>

      <label class="field">
        <span>Название</span>
        <input v-model="itemForm.title" required />
      </label>

      <label class="field">
        <span>Ссылка</span>
        <input v-model="itemForm.url" placeholder="/delivery или https://..." required />
      </label>

      <label class="field">
        <span>Порядок</span>
        <input v-model.number="itemForm.sort_order" type="number" min="0" />
      </label>

      <label class="field admin-checkbox">
        <input v-model="itemForm.open_in_new_tab" type="checkbox" />
        <span>Открывать в новой вкладке</span>
      </label>
      </fieldset>

      <div class="admin-actions">
        <button class="btn" type="submit">Сохранить подпункт</button>
      </div>
    </form>

    <div class="card admin-list-card admin-form--wide">
      <header class="admin-form__header">
        <h3>Текущее меню</h3>
        <p v-if="!loading && sections.length" class="admin-field-hint">{{ sections.length }} разделов</p>
      </header>

      <AppLoader v-if="loading" />

      <template v-else>
        <div v-if="sections.length" class="menu-sections">
        <article v-for="section in sections" :key="section.id" class="menu-section admin-list-item">
        <header class="menu-section__header">
          <div>
            <strong>{{ section.title }}</strong>
            <span class="muted menu-section__meta">
              {{ typeLabels[section.type] || section.type }}
              <template v-if="section.url"> · {{ section.url }}</template>
              <template v-if="section.include_categories"> · + категории</template>
            </span>
          </div>
          <button class="btn secondary" type="button" @click="removeSection(section)">
            Удалить
          </button>
        </header>

        <ul v-if="itemsForSection(section.key).length" class="menu-section__list">
          <li v-for="item in itemsForSection(section.key)" :key="item.id">
            <span>{{ item.title }}</span>
            <span class="muted">{{ item.url }}</span>
            <button class="btn secondary" type="button" @click="removeItem(item)">Удалить</button>
          </li>
        </ul>

        <p v-else class="muted menu-section__empty">Подпунктов нет</p>
      </article>
        </div>

        <p v-if="!sections.length" class="muted">Разделов меню пока нет.</p>
      </template>
    </div>
  </section>
</template>

<style scoped>
.menu-sections {
  display: grid;
  gap: 0;
}

.menu-section__header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  align-items: flex-start;
}

.menu-section__meta {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.875rem;
}

.menu-section__list {
  list-style: none;
  margin: 0.75rem 0 0;
  padding: 0;
  display: grid;
  gap: 0.5rem;
}

.menu-section__list li {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 0.75rem;
  align-items: center;
}

.menu-section__empty {
  margin: 0.75rem 0 0;
}
</style>
