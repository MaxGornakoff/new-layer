<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import api from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import AppLoader from '@/components/AppLoader.vue'
import ClientOrdersList from '@/components/ClientOrdersList.vue'

const auth = useAuthStore()
const toast = useToastStore()
const orders = ref([])
const loading = ref(true)
const saving = ref(false)
const error = ref('')

const form = reactive({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

function syncFormFromUser() {
  form.name = auth.user?.name ?? ''
  form.email = auth.user?.email ?? ''
  form.phone = auth.user?.phone ?? ''
  form.password = ''
  form.password_confirmation = ''
}

watch(
  () => auth.user,
  () => syncFormFromUser(),
  { immediate: true },
)

async function loadOrders() {
  loading.value = true
  try {
    const { data } = await api.get('/orders')
    orders.value = data.data
  } finally {
    loading.value = false
  }
}

async function saveProfile() {
  error.value = ''
  saving.value = true

  try {
    const payload = {
      name: form.name,
      email: form.email,
      phone: form.phone || null,
    }

    if (form.password) {
      payload.password = form.password
      payload.password_confirmation = form.password_confirmation
    }

    await auth.updateProfile(payload)
    form.password = ''
    form.password_confirmation = ''
    toast.show('Данные профиля сохранены', 'success')
  } catch (err) {
    const messages = err.response?.data?.errors
    if (messages) {
      error.value = Object.values(messages).flat().join(' ')
    } else {
      error.value = err.response?.data?.message || 'Не удалось сохранить профиль.'
    }
  } finally {
    saving.value = false
  }
}

onMounted(loadOrders)
</script>

<template>
  <section class="mx-auto w-full max-w-[1440px]">
    <div class="my-6 flex flex-wrap items-center justify-between gap-3 sm:my-8 lg:my-10">
      <h1 class="m-0 text-2xl font-semibold uppercase sm:text-[28px] lg:text-3xl">
        Личный кабинет
      </h1>
      <button class="btn secondary" type="button" @click="auth.logout()">Выйти</button>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,420px)_1fr] lg:items-start">
      <div class="rounded-[20px] bg-white p-5 sm:p-6">
        <h2 class="m-0 text-lg font-semibold text-[#222222]">Личные данные</h2>
        <p class="muted m-0 mt-1 text-sm">Имя, контакты и пароль.</p>

        <form class="mt-5 grid gap-1" @submit.prevent="saveProfile">
          <label class="field">
            <span>Имя</span>
            <input v-model="form.name" type="text" required autocomplete="name" />
          </label>

          <label class="field">
            <span>Email</span>
            <input v-model="form.email" type="email" required autocomplete="email" />
          </label>

          <label class="field">
            <span>Телефон</span>
            <input v-model="form.phone" type="tel" autocomplete="tel" />
          </label>

          <label class="field">
            <span>Новый пароль</span>
            <input
              v-model="form.password"
              type="password"
              autocomplete="new-password"
              placeholder="Оставьте пустым, если не меняете"
            />
          </label>

          <label class="field">
            <span>Повтор пароля</span>
            <input
              v-model="form.password_confirmation"
              type="password"
              autocomplete="new-password"
            />
          </label>

          <p v-if="error" class="m-0 mb-3 text-sm text-red-600">{{ error }}</p>

          <button class="btn mt-1 w-fit" type="submit" :disabled="saving">
            {{ saving ? 'Сохранение…' : 'Сохранить' }}
          </button>
        </form>
      </div>

      <div class="min-w-0">
        <h2 id="orders" class="m-0 text-xl font-semibold sm:text-2xl">Мои заказы</h2>
        <p class="muted m-0 mt-1 mb-4 text-sm sm:text-base">
          Нажмите на заказ, чтобы посмотреть состав и перейти к товарам.
        </p>

        <AppLoader v-if="loading" />
        <ClientOrdersList v-else-if="orders.length" :orders="orders" />
        <p v-else class="muted">Заказов пока нет.</p>
      </div>
    </div>
  </section>
</template>
