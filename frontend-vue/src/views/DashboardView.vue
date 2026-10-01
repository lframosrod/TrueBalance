<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useFinanceStore } from '@/stores/finance'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
import {
  Wallet,
  TrendingUp,
  TrendingDown,
  PiggyBank,
  PlusCircle,
  Trash2,
  LogOut,
} from 'lucide-vue-next'

ChartJS.register(ArcElement, Tooltip, Legend)

const router = useRouter()
const authStore = useAuthStore()
const financeStore = useFinanceStore()

const amount = ref<number | null>(null)
const categoryId = ref<number | ''>('')
const description = ref('')
const transactionDate = ref(new Date().toISOString().slice(0, 10))
const saving = ref(false)

onMounted(async () => {
  if (!authStore.user) {
    await authStore.fetchCurrentUser()
  }
  await financeStore.fetchDashboardData()
  if (financeStore.categories.length > 0) {
    categoryId.value = financeStore.categories[0].id
  }
})

const formatCurrency = (val: number) =>
  new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'USD' }).format(val || 0)

const expenseBreakdown = computed(() =>
  (financeStore.summary?.byCategory || []).filter((item) => item.type === 'EXPENSE'),
)

const chartData = computed(() => ({
  labels: expenseBreakdown.value.map((c) => c.categoryName),
  datasets: [
    {
      data: expenseBreakdown.value.map((c) => c.total),
      backgroundColor: expenseBreakdown.value.map((c) => c.color),
      borderWidth: 0,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: { color: '#cbd5e1', padding: 16 },
    },
  },
}

async function handleAddTransaction() {
  if (!amount.value || !categoryId.value) return
  saving.value = true
  try {
    await financeStore.addTransaction({
      amount: Number(amount.value),
      categoryId: Number(categoryId.value),
      description: description.value,
      transactionDate: transactionDate.value,
    })
    amount.value = null
    description.value = ''
  } finally {
    saving.value = false
  }
}

function handleLogout() {
  authStore.logout()
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 pb-12">
    <!-- Topbar -->
    <header class="border-b border-slate-800 bg-slate-900/60 backdrop-blur">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
        <div class="flex items-center gap-3">
          <div
            class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white"
          >
            <Wallet class="h-5 w-5" />
          </div>
          <div>
            <h1 class="text-lg font-bold text-white">TrueBalance</h1>
            <p class="text-xs text-slate-400">Hola, {{ authStore.user?.name || 'Usuario' }}</p>
          </div>
        </div>

        <button
          @click="handleLogout"
          class="flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-medium text-slate-300 transition hover:border-rose-500/50 hover:text-rose-300"
        >
          <LogOut class="h-4 w-4" />
          <span>Cerrar sesión</span>
        </button>
      </div>
    </header>

    <main class="mx-auto max-w-7xl space-y-8 px-6 pt-8">
      <!-- KPI Cards -->
      <section class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
          <div class="flex items-center justify-between text-slate-400">
            <span class="text-xs font-medium uppercase tracking-wider">Balance Neto</span>
            <Wallet class="h-5 w-5 text-indigo-400" />
          </div>
          <p class="mt-3 text-2xl font-bold text-white">
            {{ formatCurrency(financeStore.summary?.totals.netBalance ?? 0) }}
          </p>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
          <div class="flex items-center justify-between text-slate-400">
            <span class="text-xs font-medium uppercase tracking-wider">Ingresos del Mes</span>
            <TrendingUp class="h-5 w-5 text-emerald-400" />
          </div>
          <p class="mt-3 text-2xl font-bold text-emerald-400">
            {{ formatCurrency(financeStore.summary?.totals.totalIncome ?? 0) }}
          </p>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
          <div class="flex items-center justify-between text-slate-400">
            <span class="text-xs font-medium uppercase tracking-wider">Gastos del Mes</span>
            <TrendingDown class="h-5 w-5 text-rose-400" />
          </div>
          <p class="mt-3 text-2xl font-bold text-rose-400">
            {{ formatCurrency(financeStore.summary?.totals.totalExpense ?? 0) }}
          </p>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
          <div class="flex items-center justify-between text-slate-400">
            <span class="text-xs font-medium uppercase tracking-wider">Tasa de Ahorro</span>
            <PiggyBank class="h-5 w-5 text-amber-400" />
          </div>
          <p class="mt-3 text-2xl font-bold text-amber-400">
            {{ financeStore.summary?.totals.savingsRate ?? 0 }}%
          </p>
        </div>
      </section>

      <!-- Formulario + Gráfico -->
      <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Registrar Transacción -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6 lg:col-span-1">
          <h2 class="mb-4 flex items-center gap-2 text-base font-semibold text-white">
            <PlusCircle class="h-5 w-5 text-indigo-400" />
            <span>Nuevo Movimiento</span>
          </h2>

          <form @submit.prevent="handleAddTransaction" class="space-y-4">
            <div>
              <label class="mb-1 block text-xs text-slate-400">Monto ($)</label>
              <input
                v-model="amount"
                type="number"
                step="0.01"
                min="0.01"
                required
                placeholder="0.00"
                class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none"
              />
            </div>

            <div>
              <label class="mb-1 block text-xs text-slate-400">Categoría</label>
              <select
                v-model="categoryId"
                required
                class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none"
              >
                <option v-for="cat in financeStore.categories" :key="cat.id" :value="cat.id">
                  [{{ cat.type === 'INCOME' ? 'Ingreso' : 'Gasto' }}] {{ cat.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-xs text-slate-400">Descripción</label>
              <input
                v-model="description"
                type="text"
                placeholder="Ej. Supermercado o Quincena"
                class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none"
              />
            </div>

            <div>
              <label class="mb-1 block text-xs text-slate-400">Fecha</label>
              <input
                v-model="transactionDate"
                type="date"
                required
                class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white focus:border-indigo-500 focus:outline-none"
              />
            </div>

            <button
              type="submit"
              :disabled="saving"
              class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:opacity-50"
            >
              {{ saving ? 'Guardando...' : 'Registrar Movimiento' }}
            </button>
          </form>
        </div>

        <!-- Gráfico de Gastos por Categoría -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6 lg:col-span-2">
          <h2 class="mb-4 text-base font-semibold text-white">Distribución de Gastos del Mes</h2>
          <div v-if="expenseBreakdown.length > 0" class="h-64">
            <Doughnut :data="chartData" :options="chartOptions" />
          </div>
          <div v-else class="flex h-64 items-center justify-center text-sm text-slate-500">
            Aún no hay gastos registrados en este periodo.
          </div>
        </div>
      </section>

      <!-- Historial de Transacciones -->
      <section class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
        <h2 class="mb-4 text-base font-semibold text-white">Movimientos Recientes</h2>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-800 text-xs uppercase text-slate-400">
              <tr>
                <th class="pb-3">Fecha</th>
                <th class="pb-3">Categoría</th>
                <th class="pb-3">Descripción</th>
                <th class="pb-3 text-right">Monto</th>
                <th class="pb-3 text-right">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr
                v-for="tx in financeStore.transactions"
                :key="tx.id"
                class="hover:bg-slate-800/30"
              >
                <td class="py-3.5 text-slate-400">{{ tx.transactionDate }}</td>
                <td class="py-3.5">
                  <span
                    class="inline-flex items-center gap-2 rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :style="{ backgroundColor: tx.category.color + '20', color: tx.category.color }"
                  >
                    {{ tx.category.name }}
                  </span>
                </td>
                <td class="py-3.5 font-medium text-slate-200">{{ tx.description }}</td>
                <td
                  class="py-3.5 text-right font-semibold"
                  :class="tx.type === 'INCOME' ? 'text-emerald-400' : 'text-rose-400'"
                >
                  {{ tx.type === 'INCOME' ? '+' : '-' }}{{ formatCurrency(tx.amount) }}
                </td>
                <td class="py-3.5 text-right">
                  <button
                    @click="financeStore.removeTransaction(tx.id)"
                    class="rounded p-1 text-slate-500 transition hover:bg-rose-500/10 hover:text-rose-400"
                    title="Eliminar"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </td>
              </tr>
              <tr v-if="financeStore.transactions.length === 0">
                <td colspan="5" class="py-8 text-center text-slate-500">
                  No hay transacciones registradas.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</template>
