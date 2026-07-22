<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

const STORAGE_KEY = 'cookie_consent_accepted'
const visible = ref(false)

onMounted(() => {
  try {
    if (localStorage.getItem(STORAGE_KEY) !== '1') {
      visible.value = true
    }
  } catch {
    visible.value = true
  }
})

function accept() {
  try {
    localStorage.setItem(STORAGE_KEY, '1')
  } catch {
    // ignore
  }
  visible.value = false
}
</script>

<template>
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="translate-y-3 opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="translate-y-3 opacity-0"
  >
    <div
      v-if="visible"
      class="pointer-events-none fixed inset-x-0 bottom-0 z-[350] px-4 pb-4 pt-3 sm:px-5 sm:pb-5 sm:pt-4"
      role="dialog"
      aria-labelledby="cookie-banner-title"
      aria-describedby="cookie-banner-text"
    >
      <div
        class="pointer-events-auto flex w-full max-w-[640px] flex-col gap-4 rounded-[20px] border border-slate-200 bg-white px-5 py-4 shadow-[0_16px_48px_rgba(15,23,42,0.16)] sm:flex-row sm:items-center sm:gap-5 sm:px-6 sm:py-5"
      >
        <div class="min-w-0 flex-1">
          <h2 id="cookie-banner-title" class="m-0 mb-1.5 text-base font-bold text-[#222222] sm:text-[1.0625rem]">
            Сайт использует файлы cookie
          </h2>
          <p id="cookie-banner-text" class="m-0 text-sm leading-snug text-slate-600 sm:text-[14px]">
            Для повышения удобства сайта мы используем cookies. Оставаясь на сайте, вы соглашаетесь с
            <RouterLink
              to="/legal/cookie-policy"
              class="text-[#3b72ff] no-underline hover:underline"
            >
              политикой их применения
            </RouterLink>.
          </p>
        </div>
        <button
          class="btn w-full justify-center rounded-full bg-[#3b72ff] hover:bg-[#2f63ef] sm:w-auto sm:min-w-[140px] sm:shrink-0"
          type="button"
          @click="accept"
        >
          Понятно
        </button>
      </div>
    </div>
  </Transition>
</template>
