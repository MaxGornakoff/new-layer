<script setup>
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import api from '@/api/client'
import AdminFormBanner from '@/components/AdminFormBanner.vue'
import AdminIconButton from '@/components/AdminIconButton.vue'
import AppLoader from '@/components/AppLoader.vue'
import { validateForm } from '@/lib/formValidation'
import { scrollAdminFormIntoView } from '@/lib/scrollAdminForm'
import { useToastStore } from '@/stores/toast'

const toast = useToastStore()
const sections = ref([])
const items = ref([])
const loading = ref(true)
const savingSection = ref(false)
const savingItem = ref(false)
const sectionError = ref('')
const itemError = ref('')

const sectionFormOpen = ref(false)
const itemFormOpen = ref(false)
const editingSectionId = ref(null)
const editingItemId = ref(null)
const sectionFormRef = ref(null)
const itemFormRef = ref(null)

const sectionForm = reactive({
  key: '',
  placement: 'header',
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
  is_active: true,
  open_in_new_tab: false,
})

const typeLabelsByPlacement = {
  header: {
    link: 'Ссылка',
    dropdown: 'Выпадающий список',
  },
  footer: {
    link: 'Ссылка',
    dropdown: 'Колонка со списком',
  },
}

const placementLabels = {
  header: 'Шапка',
  footer: 'Подвал',
}

const currentTypeLabels = computed(
  () => typeLabelsByPlacement[sectionForm.placement] || typeLabelsByPlacement.header,
)

function typeLabelFor(section) {
  const labels = typeLabelsByPlacement[section.placement] || typeLabelsByPlacement.header
  return labels[section.type] || section.type
}

const sectionOptions = computed(() =>
  sections.value.map((section) => ({
    key: section.key,
    label: `${section.title} (${placementLabels[section.placement] || section.placement} · ${typeLabelFor(section)})`,
  })),
)

const menuGroups = computed(() => {
  const header = sections.value.filter((section) => section.placement !== 'footer')
  const footer = sections.value.filter((section) => section.placement === 'footer')

  return [
    { key: 'header', title: placementLabels.header, sections: header },
    { key: 'footer', title: placementLabels.footer, sections: footer },
  ].filter((group) => group.sections.length > 0)
})

function resetSectionForm() {
  editingSectionId.value = null
  sectionFormOpen.value = false
  Object.assign(sectionForm, {
    key: '',
    placement: 'header',
    title: '',
    url: '',
    type: 'link',
    include_categories: false,
    sort_order: 0,
    is_active: true,
    open_in_new_tab: false,
  })
  sectionError.value = ''
}

function resetItemForm() {
  editingItemId.value = null
  itemFormOpen.value = false
  Object.assign(itemForm, {
    parent_key: sections.value[0]?.key || '',
    title: '',
    url: '',
    sort_order: 0,
    is_active: true,
    open_in_new_tab: false,
  })
  itemError.value = ''
}

function openCreateSection() {
  resetSectionForm()
  sectionFormOpen.value = true
  nextTick(() => scrollAdminFormIntoView(sectionFormRef.value))
}

function openCreateItem() {
  resetItemForm()
  itemFormOpen.value = true
  nextTick(() => scrollAdminFormIntoView(itemFormRef.value))
}

function startEditSection(section) {
  editingSectionId.value = section.id
  sectionForm.key = section.key
  sectionForm.placement = section.placement || 'header'
  sectionForm.title = section.title
  sectionForm.url = section.url || ''
  sectionForm.type = section.type
  sectionForm.include_categories = Boolean(section.include_categories)
  sectionForm.sort_order = section.sort_order ?? 0
  sectionForm.is_active = section.is_active !== false
  sectionForm.open_in_new_tab = Boolean(section.open_in_new_tab)
  sectionError.value = ''
  sectionFormOpen.value = true
  nextTick(() => scrollAdminFormIntoView(sectionFormRef.value))
}

function startEditItem(item) {
  editingItemId.value = item.id
  itemForm.parent_key = item.parent_key
  itemForm.title = item.title
  itemForm.url = item.url
  itemForm.sort_order = item.sort_order ?? 0
  itemForm.is_active = item.is_active !== false
  itemForm.open_in_new_tab = Boolean(item.open_in_new_tab)
  itemError.value = ''
  itemFormOpen.value = true
  nextTick(() => scrollAdminFormIntoView(itemFormRef.value))
}

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

async function saveSection(event) {
  if (!validateForm(event?.target)) return

  savingSection.value = true
  sectionError.value = ''

  try {
    if (editingSectionId.value) {
      await api.put(`/admin/menu-sections/${editingSectionId.value}`, sectionForm)
    } else {
      await api.post('/admin/menu-sections', sectionForm)
    }

    resetSectionForm()
    await load()
  } catch (err) {
    const validationErrors = err.response?.data?.errors
    const message = validationErrors
      ? Object.values(validationErrors).flat().join(' ')
      : err.response?.data?.message || 'Не удалось сохранить раздел.'
    sectionError.value = message
    toast.error(message)
  } finally {
    savingSection.value = false
  }
}

async function removeSection(section) {
  if (!confirm(`Удалить раздел «${section.title}» и все его подпункты?`)) return

  try {
    await api.delete(`/admin/menu-sections/${section.id}`)
    if (editingSectionId.value === section.id) resetSectionForm()
    await load()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Не удалось удалить раздел.')
  }
}

async function saveItem(event) {
  if (!validateForm(event?.target)) return

  savingItem.value = true
  itemError.value = ''

  try {
    if (editingItemId.value) {
      await api.put(`/admin/menu-items/${editingItemId.value}`, itemForm)
    } else {
      await api.post('/admin/menu-items', itemForm)
    }

    resetItemForm()
    await load()
  } catch (err) {
    const validationErrors = err.response?.data?.errors
    const message = validationErrors
      ? Object.values(validationErrors).flat().join(' ')
      : err.response?.data?.message || 'Не удалось сохранить подпункт.'
    itemError.value = message
    toast.error(message)
  } finally {
    savingItem.value = false
  }
}

async function removeItem(item) {
  if (!confirm(`Удалить «${item.title}»?`)) return

  try {
    await api.delete(`/admin/menu-items/${item.id}`)
    if (editingItemId.value === item.id) resetItemForm()
    await load()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Не удалось удалить подпункт.')
  }
}

function isActiveFlag(value) {
  return value !== false
}

async function toggleSectionActive(section) {
  const nextActive = !isActiveFlag(section.is_active)

  try {
    await api.put(`/admin/menu-sections/${section.id}`, {
      key: section.key,
      placement: section.placement || 'header',
      title: section.title,
      url: section.url || null,
      type: section.type,
      include_categories: Boolean(section.include_categories),
      sort_order: section.sort_order ?? 0,
      open_in_new_tab: Boolean(section.open_in_new_tab),
      is_active: nextActive,
    })
    if (editingSectionId.value === section.id) {
      sectionForm.is_active = nextActive
    }
    await load()
    toast.success(nextActive ? 'Раздел включён' : 'Раздел выключен')
  } catch (err) {
    toast.error(err.response?.data?.message || 'Не удалось изменить статус раздела.')
  }
}

async function toggleItemActive(item) {
  const nextActive = !isActiveFlag(item.is_active)

  try {
    await api.put(`/admin/menu-items/${item.id}`, {
      parent_key: item.parent_key,
      title: item.title,
      url: item.url,
      sort_order: item.sort_order ?? 0,
      open_in_new_tab: Boolean(item.open_in_new_tab),
      is_active: nextActive,
    })
    if (editingItemId.value === item.id) {
      itemForm.is_active = nextActive
    }
    await load()
    toast.success(nextActive ? 'Подпункт включён' : 'Подпункт выключен')
  } catch (err) {
    toast.error(err.response?.data?.message || 'Не удалось изменить статус подпункта.')
  }
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
        Разделы шапки и подвала работают одинаково: тип «Ссылка» — один пункт,
        тип «Выпадающий список / Колонка» — заголовок и вложенные подпункты.
        Для каталога можно включить автоматический вывод категорий.
      </p>
    </header>

    <div class="admin-toolbar">
      <p class="m-0 text-sm text-slate-500">
        {{ sectionFormOpen ? (editingSectionId ? 'Редактирование раздела' : 'Добавление раздела') : 'Разделы меню' }}
      </p>
      <button v-if="!sectionFormOpen" class="btn" type="button" @click="openCreateSection">
        Добавить раздел
      </button>
      <button v-else class="btn secondary" type="button" @click="resetSectionForm">
        Закрыть форму раздела
      </button>
    </div>

    <form
      v-show="sectionFormOpen"
      ref="sectionFormRef"
      class="card admin-form"
      :class="{
        'admin-form--editing': editingSectionId,
        'admin-form--creating': sectionFormOpen && !editingSectionId,
      }"
      novalidate
      @submit.prevent="saveSection"
    >
      <header class="admin-form__header">
        <h3>{{ editingSectionId ? 'Редактировать раздел меню' : 'Добавить раздел меню' }}</h3>
      </header>

      <AdminFormBanner
        v-if="sectionFormOpen"
        :mode="editingSectionId ? 'edit' : 'create'"
        :entity="editingSectionId ? 'раздел меню' : 'нового раздела меню'"
        :title="sectionForm.title"
        @cancel="resetSectionForm"
      />

      <fieldset class="admin-form__section admin-form__section--last">
        <legend class="admin-form__section-title">Параметры раздела</legend>

        <label class="field">
          <span>Ключ (латиница)</span>
          <input v-model="sectionForm.key" placeholder="catalog" required />
        </label>

        <label class="field">
          <span>Расположение</span>
          <select v-model="sectionForm.placement" required>
            <option value="header">{{ placementLabels.header }}</option>
            <option value="footer">{{ placementLabels.footer }}</option>
          </select>
        </label>

        <label class="field">
          <span>Название</span>
          <input v-model="sectionForm.title" required />
        </label>

        <label class="field">
          <span>Тип</span>
          <select v-model="sectionForm.type" required>
            <option value="link">{{ currentTypeLabels.link }}</option>
            <option value="dropdown">{{ currentTypeLabels.dropdown }}</option>
          </select>
          <span class="admin-field-hint">
            <template v-if="sectionForm.placement === 'footer'">
              «Ссылка» — один пункт в футере. «Колонка со списком» — заголовок и вложенные пункты.
            </template>
            <template v-else>
              «Ссылка» — пункт верхнего уровня. «Выпадающий список» — пункт с подменю.
            </template>
          </span>
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

      <p v-if="sectionError" class="admin-error">{{ sectionError }}</p>

      <div class="admin-actions">
        <button class="btn" type="submit" :disabled="savingSection">
          {{ savingSection ? 'Сохранение...' : editingSectionId ? 'Обновить раздел' : 'Сохранить раздел' }}
        </button>
        <button class="btn secondary" type="button" @click="resetSectionForm">
          {{ editingSectionId ? 'Отмена' : 'Закрыть' }}
        </button>
      </div>
    </form>

    <div class="admin-toolbar">
      <p class="m-0 text-sm text-slate-500">
        {{ itemFormOpen ? (editingItemId ? 'Редактирование подпункта' : 'Добавление подпункта') : 'Подпункты меню' }}
      </p>
      <button
        v-if="!itemFormOpen"
        class="btn"
        type="button"
        :disabled="!sections.length"
        @click="openCreateItem"
      >
        Добавить подпункт
      </button>
      <button v-else class="btn secondary" type="button" @click="resetItemForm">
        Закрыть форму подпункта
      </button>
    </div>

    <form
      v-show="itemFormOpen"
      ref="itemFormRef"
      class="card admin-form"
      :class="{
        'admin-form--editing': editingItemId,
        'admin-form--creating': itemFormOpen && !editingItemId,
      }"
      novalidate
      @submit.prevent="saveItem"
    >
      <header class="admin-form__header">
        <h3>{{ editingItemId ? 'Редактировать подпункт' : 'Добавить подпункт' }}</h3>
      </header>

      <AdminFormBanner
        v-if="itemFormOpen"
        :mode="editingItemId ? 'edit' : 'create'"
        :entity="editingItemId ? 'подпункт меню' : 'нового подпункта'"
        :title="itemForm.title"
        @cancel="resetItemForm"
      />

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

        <label class="field admin-checkbox">
          <input v-model="itemForm.is_active" type="checkbox" />
          <span>Активен</span>
        </label>
      </fieldset>

      <p v-if="itemError" class="admin-error">{{ itemError }}</p>

      <div class="admin-actions">
        <button class="btn" type="submit" :disabled="savingItem || !sections.length">
          {{ savingItem ? 'Сохранение...' : editingItemId ? 'Обновить подпункт' : 'Сохранить подпункт' }}
        </button>
        <button class="btn secondary" type="button" @click="resetItemForm">
          {{ editingItemId ? 'Отмена' : 'Закрыть' }}
        </button>
      </div>
    </form>

    <div class="card admin-list-card admin-form--wide">
      <header class="admin-form__header">
        <h3>Текущее меню</h3>
        <p v-if="!loading && sections.length" class="admin-field-hint">{{ sections.length }} разделов</p>
      </header>

      <AppLoader v-if="loading" />

      <template v-else>
        <template v-if="menuGroups.length">
          <section
            v-for="group in menuGroups"
            :key="group.key"
            class="menu-placement-group"
          >
          <div class="flex justify-center">
            <h4 class="uppercase w-auto text-[13px] border-1 m-auto inline-block border-[#45556c] rounded-[20px] bg-white py-2 px-4 text-xl font-bold text-[#45556c]">{{ group.title }}</h4>
            </div>
            
            <div class="menu-sections">
              <article
                v-for="section in group.sections"
                :key="section.id"
                class="menu-section admin-list-item"
                :class="{
                  'admin-list-item--editing': editingSectionId === section.id,
                  'menu-section--inactive': !isActiveFlag(section.is_active),
                }"
              >
                <header class="menu-section__header">
                  <div>
                    <strong>
                      {{ section.title }}
                      <span
                        v-if="!isActiveFlag(section.is_active)"
                        class="menu-status menu-status--off"
                      >выкл</span>
                    </strong>
                    <span class="muted menu-section__meta">
                      {{ typeLabelFor(section) }}
                      <template v-if="section.url"> · {{ section.url }}</template>
                      <template v-if="section.include_categories"> · + категории</template>
                    </span>
                  </div>
                  <div class="menu-section__actions">
                    <button
                      type="button"
                      class="menu-toggle-btn"
                      :class="isActiveFlag(section.is_active) ? 'menu-toggle-btn--on' : 'menu-toggle-btn--off'"
                      @click="toggleSectionActive(section)"
                    >
                      {{ isActiveFlag(section.is_active) ? 'Выкл' : 'Вкл' }}
                    </button>
                    <AdminIconButton
                      icon="pencil"
                      :label="editingSectionId === section.id ? 'Редактируется' : 'Изменить'"
                      :active="editingSectionId === section.id"
                      @click="startEditSection(section)"
                    />
                    <AdminIconButton
                      icon="trash"
                      label="Удалить"
                      variant="danger"
                      @click="removeSection(section)"
                    />
                  </div>
                </header>

                <ul v-if="itemsForSection(section.key).length" class="menu-section__list">
                  <li
                    v-for="item in itemsForSection(section.key)"
                    :key="item.id"
                    :class="{
                      'menu-section__item--editing': editingItemId === item.id,
                      'menu-section__item--inactive': !isActiveFlag(item.is_active),
                    }"
                  >
                    <span>
                      {{ item.title }}
                      <span
                        v-if="!isActiveFlag(item.is_active)"
                        class="menu-status menu-status--off"
                      >выкл</span>
                    </span>
                    <span class="muted">{{ item.url }}</span>
                    <div class="menu-section__item-actions">
                      <button
                        type="button"
                        class="menu-toggle-btn"
                        :class="isActiveFlag(item.is_active) ? 'menu-toggle-btn--on' : 'menu-toggle-btn--off'"
                        @click="toggleItemActive(item)"
                      >
                        {{ isActiveFlag(item.is_active) ? 'Выкл' : 'Вкл' }}
                      </button>
                      <AdminIconButton
                        icon="pencil"
                        :label="editingItemId === item.id ? 'Редактируется' : 'Изменить'"
                        :active="editingItemId === item.id"
                        @click="startEditItem(item)"
                      />
                      <AdminIconButton
                        icon="trash"
                        label="Удалить"
                        variant="danger"
                        @click="removeItem(item)"
                      />
                    </div>
                  </li>
                </ul>

                <p v-else class="muted menu-section__empty">Подпунктов нет</p>
              </article>
            </div>
          </section>
        </template>

        <p v-else class="muted">Разделов меню пока нет.</p>
      </template>
    </div>
  </section>
</template>

<style scoped>
.menu-placement-group {
  display: grid;
  gap: 0.75rem;
}

.menu-placement-group + .menu-placement-group {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e2e8f0;
}

.menu-placement-group__title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 600;
  color: #334155;
}

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

.menu-section__actions,
.menu-section__item-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
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
  padding: 0.5rem 0.65rem;
  border-radius: 12px;
}

.menu-section__item--editing {
  background: #eff6ff;
  box-shadow: 0 0 0 2px #3b72ff;
}

.menu-section--inactive,
.menu-section__item--inactive {
  opacity: 0.55;
}

.menu-status {
  display: inline-block;
  margin-left: 0.4rem;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  vertical-align: middle;
}

.menu-status--off {
  background: #fee2e2;
  color: #b91c1c;
}

.menu-toggle-btn {
  min-width: 3.25rem;
  height: 2.25rem;
  padding: 0 0.65rem;
  border: 0;
  border-radius: 0.5rem;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.15s ease, color 0.15s ease;
}

.menu-toggle-btn--on {
  background: #ecfdf5;
  color: #047857;
}

.menu-toggle-btn--on:hover {
  background: #d1fae5;
}

.menu-toggle-btn--off {
  background: #fef3c7;
  color: #b45309;
}

.menu-toggle-btn--off:hover {
  background: #fde68a;
}

.menu-section__empty {
  margin: 0.75rem 0 0;
}

@media (max-width: 720px) {
  .menu-section__list li {
    grid-template-columns: 1fr;
  }
}
</style>
