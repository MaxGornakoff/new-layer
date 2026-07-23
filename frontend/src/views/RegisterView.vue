<script setup>
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { validateForm } from '@/lib/formValidation'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

const auth = useAuthStore()
const toast = useToastStore()
const router = useRouter()
const error = ref('')
const consentPersonalData = ref(false)
const consentBox = ref(null)

const form = reactive({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

async function submit(event) {
  if (!validateForm(event?.target)) return

  if (!consentPersonalData.value) {
    toast.warning('Чтобы зарегистрироваться, нужно согласиться на обработку персональных данных', {
      anchor: consentBox.value,
      duration: 4200,
    })
    return
  }

  error.value = ''
  try {
    await auth.register(form)
    router.push('/')
  } catch (err) {
    const message = err.response?.data?.message || 'Не удалось зарегистрироваться.'
    error.value = message
    toast.error(message)
  }
}
</script>

<template>
  <section class="auth py-20">
    <div class="mx-auto max-w-md rounded-[20px] bg-[#ffffff] px-7 py-10">
      <h1 class="m-0 text-center text-2xl font-semibold uppercase leading-tight sm:text-[28px] lg:text-3xl">
        Регистрация
      </h1>
      <form class="pt-6" novalidate @submit.prevent="submit">
        <label class="field">
          <span>Имя</span>
          <input v-model="form.name" name="name" required />
        </label>
        <label class="field">
          <span>Email</span>
          <input v-model="form.email" name="email" type="email" required />
        </label>
        <label class="field">
          <span>Телефон</span>
          <input v-model="form.phone" name="phone" />
        </label>
        <label class="field">
          <span>Пароль</span>
          <input v-model="form.password" name="password" type="password" required />
        </label>
        <label class="field">
          <span>Повтор пароля</span>
          <input
            v-model="form.password_confirmation"
            name="password_confirmation"
            type="password"
            required
          />
        </label>

        <label
          ref="consentBox"
          class="relative mb-5 flex cursor-pointer items-start gap-2.5 text-sm leading-snug text-[#222222]"
        >
          <input
            v-model="consentPersonalData"
            class="peer absolute m-0 size-px overflow-hidden whitespace-nowrap border-0 p-0 opacity-0"
            type="checkbox"
          />
          <span
            class="relative mt-0.5 size-5 shrink-0 rounded-[0.35rem] border-[1.5px] border-slate-300 bg-white transition-[border-color,background-color,box-shadow] after:absolute after:inset-0 after:m-auto after:h-[0.65rem] after:w-[0.35rem] after:rotate-45 after:border-r-2 after:border-b-2 after:border-white after:opacity-0 after:content-[''] peer-checked:border-[#3b72ff] peer-checked:bg-[#3b72ff] peer-checked:after:opacity-100 peer-focus-visible:shadow-[0_0_0_3px_rgba(59,114,255,0.25)] hover:border-slate-400"
            aria-hidden="true"
          />
          <span class="min-w-0">
            Я даю
            <RouterLink
              to="/legal/personal-data-consent"
              class="text-[#3b72ff] no-underline hover:underline"
              target="_blank"
            >
              согласие на обработку персональных данных
            </RouterLink>
          </span>
        </label>

        <p v-if="error" class="m-0 mb-3 text-sm text-red-600">{{ error }}</p>
        <button class="btn mb-4 w-full" type="submit" :disabled="auth.loading">
          Создать аккаунт
        </button>
        <p class="muted text-center text-sm">
          Уже есть аккаунт?
          <RouterLink to="/login" class="text-[#007bff] hover:underline">Вход</RouterLink>
        </p>
      </form>
    </div>
  </section>
</template>
