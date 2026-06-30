<script setup>
import { computed, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const error = ref('')

const form = reactive({
  email: '',
  password: '',
})

const adminHint = computed(() => route.query.redirect?.toString().includes('/admin'))

async function submit() {
  error.value = ''
  try {
    await auth.login(form)
    router.push(route.query.redirect || '/')
  } catch {
    error.value = 'Неверный email или пароль.'
  }
}
</script>

<template>
  <section class="card auth">
    <h1>Вход</h1>
    <p v-if="adminHint" class="hint">
      Для админки войдите как <strong>admin@shop.local</strong> / <strong>password</strong>
    </p>
    <form @submit.prevent="submit">
      <label class="field">
        <span>Email</span>
        <input v-model="form.email" type="email" required />
      </label>
      <label class="field">
        <span>Пароль</span>
        <input v-model="form.password" type="password" required />
      </label>
      <p v-if="error" class="error">{{ error }}</p>
      <button class="btn" type="submit" :disabled="auth.loading">Войти</button>
    </form>
    <p class="muted">Нет аккаунта? <RouterLink to="/register">Регистрация</RouterLink></p>
  </section>
</template>

<style scoped>
.auth {
  max-width: 420px;
}

.error {
  color: #dc2626;
}

.hint {
  margin: 0 0 1rem;
  padding: 0.75rem;
  border-radius: 0.5rem;
  background: #eff6ff;
  color: #1e40af;
  font-size: 0.9rem;
}
</style>
