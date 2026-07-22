<script setup>
import { computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppIcon from '@/components/AppIcon.vue'

const auth = useAuthStore()
const router = useRouter()

const profileLink = computed(() => (auth.isAuthenticated ? '/profile' : '/login'))
const iconName = computed(() => (auth.isAuthenticated ? 'user-in' : 'user'))
const profileTitle = computed(() => auth.user?.name || 'Войти')

const cabinetLinks = computed(() => {
  if (!auth.isAuthenticated) {
    return [
      { to: '/login', label: 'Войти' },
      { to: '/register', label: 'Регистрация' },
    ]
  }

  const links = [
    { to: '/profile', label: 'Личный кабинет' },
    { to: '/profile#orders', label: 'Мои заказы' },
  ]

  if (auth.isAdmin) {
    links.push({ to: '/admin', label: 'Админка' })
  }

  return links
})

async function logout() {
  await auth.logout()
  router.push({ name: 'home' })
}
</script>

<template>
  <div class="header-profile flex items-center">
    <RouterLink
      :to="profileLink"
      class="header-profile__link"
      :aria-label="auth.isAuthenticated ? 'Профиль' : 'Войти'"
    >
      <AppIcon :name="iconName" />
    </RouterLink>

    <!-- Невидимый мост шириной с модалку — комфортный переход курсора с иконки -->
    <span class="header-profile__bridge" aria-hidden="true" />

    <div class="header-profile__preview">
      <p v-if="auth.isAuthenticated" class="header-profile__title">
        <RouterLink
          
          :to="profileLink"
          class="header-profile__title-link"
        >
          {{ profileTitle }}
        </RouterLink>
      
      </p>

      <nav class="header-profile__nav" aria-label="Меню профиля">
        <ul class="header-profile__menu">
          <li v-for="link in cabinetLinks" :key="link.to">
            <RouterLink :to="link.to" class="header-profile__menu-link">
              {{ link.label }}
            </RouterLink>
          </li>
          <li v-if="auth.isAuthenticated">
            <button type="button" class="header-profile__menu-link header-profile__logout" @click="logout">
              Выйти
            </button>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<style scoped>
.header-profile {
  position: relative;
}

.header-profile__link {
  position: relative;
  z-index: 2;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 25px;
  height: 25px;
  color: inherit;
}

.header-profile__bridge {
  display: none;
  position: absolute;
  top: -0.35rem;
  right: 0;
  z-index: 1;
  width: min(280px, calc(100vw - 2rem));
  height: calc(100% + 1rem);
}

.header-profile__preview {
  display: none;
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  width: min(280px, calc(100vw - 2rem));
  padding: 0.75rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12);
  z-index: 120;
}

.header-profile:hover .header-profile__bridge,
.header-profile:hover .header-profile__preview,
.header-profile__preview:hover {
  display: block;
}

.header-profile__title {
  margin: 0 0 0.5rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #f1f5f9;
  font-weight: 600;
  padding-left: 10px;
}

.header-profile__title-link {
  display: block;
  color: inherit;
}

.header-profile__title-link:hover {
  color: #2563eb;
}

.header-profile__title-text {
  display: block;
  color: inherit;
}

.header-profile__menu {
  list-style: none;
  margin: 0;
  padding: 0;
}

.header-profile__menu-link {
  display: block;
  width: 100%;
  padding: 0.65rem 0.5rem;
  border: none;
  border-radius: 0.5rem;
  background: none;
  color: inherit;
  text-align: left;
  font: inherit;
  cursor: pointer;
}

.header-profile__menu-link:hover {
  background: #f8fafc;
}

.header-profile__logout {
  color: #dc2626;
}

@media (max-width: 992px) {
  .header-profile__link {
    width: 20px;
    height: 20px;
  }
}

@media (max-width: 991px) {
  .header-profile__bridge,
  .header-profile__preview {
    display: none !important;
  }
}
</style>
