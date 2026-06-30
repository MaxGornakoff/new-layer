<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const error = ref('')

const form = reactive({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

async function submit() {
  error.value = ''
  try {
    await auth.register(form)
    router.push('/')
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось зарегистрироваться.'
  }
}
</script>

<template>
  <section class="card auth">
    <h1>Регистрация</h1>
    <form @submit.prevent="submit">
      <label class="field">
        <span>Имя</span>
        <input v-model="form.name" required />
      </label>
      <label class="field">
        <span>Email</span>
        <input v-model="form.email" type="email" required />
      </label>
      <label class="field">
        <span>Телефон</span>
        <input v-model="form.phone" />
      </label>
      <label class="field">
        <span>Пароль</span>
        <input v-model="form.password" type="password" required />
      </label>
      <label class="field">
        <span>Повтор пароля</span>
        <input v-model="form.password_confirmation" type="password" required />
      </label>
      <p v-if="error" class="error">{{ error }}</p>
      <button class="btn" type="submit" :disabled="auth.loading">Создать аккаунт</button>
    </form>
  </section>
</template>

<style scoped>
.auth {
  max-width: 420px;
}

.error {
  color: #dc2626;
}
</style>
