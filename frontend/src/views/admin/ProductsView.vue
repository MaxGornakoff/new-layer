<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '@/api/client'
import AppLoader from '@/components/AppLoader.vue'

const products = ref([])
const categories = ref([])
const stockUpdating = ref({})
const stockDrafts = ref({})
const loading = ref(true)

const form = reactive({
  category_id: '',
  name: '',
  sku: '',
  description: '',
  price: 0,
  color: '',
  diameter: 1.75,
  weight_grams: 1000,
  stock_quantity: 0,
  is_active: true,
})

function syncStockDrafts() {
  const drafts = {}
  for (const product of products.value) {
    drafts[product.id] = product.stock_quantity
  }
  stockDrafts.value = drafts
}

async function load() {
  loading.value = true
  try {
    const [productsResponse, categoriesResponse] = await Promise.all([
      api.get('/admin/products'),
      api.get('/categories'),
    ])
    products.value = productsResponse.data.data
    categories.value = categoriesResponse.data.data
    syncStockDrafts()
    if (!form.category_id && categories.value.length) {
      form.category_id = categories.value[0].id
    }
  } finally {
    loading.value = false
  }
}

async function createProduct() {
  await api.post('/admin/products', form)
  Object.assign(form, {
    name: '',
    sku: '',
    description: '',
    price: 0,
    color: '',
    stock_quantity: 0,
  })
  await load()
}

async function applyStockChange(product, delta) {
  if (!delta) return

  stockUpdating.value[product.id] = true

  try {
    const { data } = await api.post(`/admin/products/${product.id}/stock`, {
      quantity: delta,
    })
    product.stock_quantity = data.data.stock_quantity
    stockDrafts.value[product.id] = data.data.stock_quantity
  } catch (err) {
    stockDrafts.value[product.id] = product.stock_quantity
    alert(err.response?.data?.message || 'Не удалось обновить остаток.')
  } finally {
    stockUpdating.value[product.id] = false
  }
}

function commitStockInput(product) {
  const nextQuantity = Math.max(0, Number.parseInt(String(stockDrafts.value[product.id]), 10) || 0)
  if (nextQuantity === product.stock_quantity) {
    stockDrafts.value[product.id] = product.stock_quantity
    return
  }
  stockDrafts.value[product.id] = nextQuantity
  const delta = nextQuantity - product.stock_quantity
  applyStockChange(product, delta)
}

onMounted(load)
</script>

<template>
  <section>
    <h1>Товары</h1>

    <form class="card form" @submit.prevent="createProduct">
      <h3>Добавить товар</h3>
      <label class="field">
        <span>Категория</span>
        <select v-model="form.category_id" required>
          <option v-for="category in categories" :key="category.id" :value="category.id">
            {{ category.name }}
          </option>
        </select>
      </label>
      <label class="field"><span>Название</span><input v-model="form.name" required /></label>
      <label class="field"><span>Артикул</span><input v-model="form.sku" required /></label>
      <label class="field"><span>Цвет</span><input v-model="form.color" required /></label>
      <label class="field"><span>Цена</span><input v-model.number="form.price" type="number" min="0" step="0.01" required /></label>
      <label class="field"><span>Остаток</span><input v-model.number="form.stock_quantity" type="number" min="0" /></label>
      <label class="field"><span>Описание</span><textarea v-model="form.description" rows="2" /></label>
      <button class="btn" type="submit">Сохранить</button>
    </form>

    <div class="card">
      <AppLoader v-if="loading" />

      <template v-else>
        <table v-if="products.length" class="table">
        <thead>
          <tr>
            <th>Название</th>
            <th>Категория</th>
            <th>Цвет</th>
            <th>Цена</th>
            <th>Остаток</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="product in products" :key="product.id">
            <td>{{ product.name }}</td>
            <td>{{ product.category?.name }}</td>
            <td>{{ product.color }}</td>
            <td>{{ Number(product.price).toLocaleString('ru-RU') }} ₽</td>
            <td>
              <input
                v-model.number="stockDrafts[product.id]"
                type="number"
                min="0"
                step="1"
                class="stock-control__input"
                :disabled="stockUpdating[product.id]"
                @change="commitStockInput(product)"
                @keydown.enter.prevent="commitStockInput(product)"
              />
            </td>
          </tr>
        </tbody>
        </table>

        <p v-else class="muted">Товаров пока нет.</p>
      </template>
    </div>
  </section>
</template>

<style scoped>
.form {
  margin-bottom: 1rem;
  max-width: 560px;
}

.stock-control__input {
  width: 5rem;
  padding: 0.35rem 0.5rem;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  text-align: center;
}
</style>
