import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import api from '@/api/client'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))
  const loading = ref(false)

  const isAuthenticated = computed(() => Boolean(token.value))
  const isAdmin = computed(() => user.value?.role === 'admin')

  async function fetchUser() {
    if (!token.value) return
    const { data } = await api.get('/user')
    user.value = data.user
  }

  async function login(credentials) {
    loading.value = true
    try {
      const { data } = await api.post('/login', credentials)
      token.value = data.token
      user.value = data.user
      localStorage.setItem('token', data.token)
    } finally {
      loading.value = false
    }
  }

  async function register(payload) {
    loading.value = true
    try {
      const { data } = await api.post('/register', payload)
      token.value = data.token
      user.value = data.user
      localStorage.setItem('token', data.token)
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      if (token.value) {
        await api.post('/logout')
      }
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
    }
  }

  async function bootstrap() {
    if (token.value && !user.value) {
      try {
        await fetchUser()
      } catch {
        await logout()
      }
    }
  }

  async function updateProfile(payload) {
    const { data } = await api.put('/user', payload)
    user.value = data.user
    return data.user
  }

  return {
    user,
    token,
    loading,
    isAuthenticated,
    isAdmin,
    login,
    register,
    logout,
    fetchUser,
    updateProfile,
    bootstrap,
  }
})
