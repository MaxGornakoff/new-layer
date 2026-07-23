<script setup>
import { computed, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { validateForm } from '@/lib/formValidation'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

const auth = useAuthStore()
const toast = useToastStore()
const router = useRouter()
const route = useRoute()
const error = ref('')

const form = reactive({
  email: '',
  password: '',
})

const adminHint = computed(() => route.query.redirect?.toString().includes('/admin'))

async function submit(event) {
  if (!validateForm(event?.target)) return

  error.value = ''
  try {
    await auth.login(form)
    router.push(route.query.redirect || '/')
  } catch {
    const message = 'Неверный email или пароль.'
    error.value = message
    toast.error(message)
  }
}
</script>

<template>
  <section class="auth py-20">
    <div class="mx-auto max-w-md rounded-[20px] bg-[#ffffff] px-7 py-10">
      <h1 class="m-0 text-center text-2xl font-semibold uppercase leading-tight sm:text-[28px] lg:text-3xl">
        Вход
      </h1>
      <form class="pb-4 pt-6" novalidate @submit.prevent="submit">
        <label class="field">
          <span>Email</span>
          <input v-model="form.email" name="email" type="email" required />
        </label>
        <label class="field">
          <span>Пароль</span>
          <input v-model="form.password" name="password" type="password" required />
        </label>
        <p v-if="error" class="m-0 mb-3 text-sm text-red-600">{{ error }}</p>
        <button class="btn w-full" type="submit" :disabled="auth.loading">Войти</button>
      </form>
      <p class="muted text-center text-sm">
        Нет аккаунта?
        <RouterLink to="/register" class="text-[#007bff] hover:underline">Регистрация</RouterLink>
      </p>
    </div>
  </section>
</template>
