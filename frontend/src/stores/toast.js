import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useToastStore = defineStore('toast', () => {
  const items = ref([])
  let nextId = 0

  function show(message, type = 'success', duration = 3200) {
    const id = ++nextId
    items.value.push({ id, message, type })

    window.setTimeout(() => {
      remove(id)
    }, duration)

    return id
  }

  function remove(id) {
    items.value = items.value.filter((item) => item.id !== id)
  }

  return {
    items,
    show,
    remove,
  }
})
