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
  Calendar,
  Tag,
  X,
} from 'lucide-vue-next'

ChartJS.register(ArcElement, Tooltip, Legend)

const router = useRouter()
const authStore = useAuthStore()
const financeStore = useFinanceStore()

// Estado del filtro de periodo (formato YYYY-MM o 'ALL')
const now = new Date()
const currentYearMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
const selectedMonth = ref<string>(currentYearMonth)
const isAllTime = ref(false)

// Estado del formulario de transacciones
const amount = ref<number | null>(null)
const categoryId = ref<number | ''>('')
const description = ref('')
const transactionDate = ref(now.toISOString().slice(0, 10))
const saving = ref(false)

// Estado del Modal de Nueva Categoría
const showCategoryModal = ref(false)
const newCatName = ref('')
const newCatType = ref<'EXPENSE' | 'INCOME'>('EXPENSE')
const newCatColor = ref('#6366f1')
const savingCategory = ref(false)
const categoryError = ref('')

const colorPalette = [
  '#6366f1', // Indigo
  '#10b981', // Emerald
  '#f43f5e', // Rose
  '#f59e0b', // Amber
  '#06b6d4', // Cyan
  '#8b5cf6', // Violet
  '#ec4899', // Pink
  '#3b82f6', // Blue
]

function getMonthDateRange(yearMonth: string): { startDate: string; endDate: string } {
  const [yearStr, monthStr] = yearMonth.split('-')
  const year = Number(yearStr)
  const month = Number(monthStr)
  const lastDay = new Date(year, month, 0).getDate()
  return {
    startDate: `${yearMonth}-01`,
    endDate: `${yearMonth}-${String(lastDay).padStart(2, '0')}`,
  }
}

async function applyPeriodFilter() {
  if (isAllTime.value) {
    await financeStore.fetchDashboardData('2000-01-01', '2099-12-31')
  } else {
    const { startDate, endDate } = getMonthDateRange(selectedMonth.value)
    await financeStore.fetchDashboardData(startDate, endDate)
  }
}

async function handleMonthChange() {
  isAllTime.value = false
  await applyPeriodFilter()
}

async function toggleAllTime() {
  isAllTime.value = !isAllTime.value
  await applyPeriodFilter()
}

onMounted(async () => {
  if (!authStore.user) {
    await authStore.fetchCurrentUser()
  }
  await applyPeriodFilter()
  if (financeStore.categories.length > 0 && !categoryId.value) {
    categoryId.value = financeStore.categories[0].id
  }
})

const formatCurrency = (val: number) =>
  new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'USD' }).format(val || 0)

const periodLabel = computed(() => {
  if (isAllTime.value) return 'Todo el historial'
  const [year, month] = selectedMonth.value.split('-')
  const date = new Date(Number(year), Number(month) - 1, 1)
  return date.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' })
})

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

async function handleCreateCategory() {
  if (!newCatName.value.trim()) return
  categoryError.value = ''
  savingCategory.value = true
  try {
    const created = await financeStore.addCategory({
      name: newCatName.value.trim(),
      type: newCatType.value,
      color: newCatColor.value,
      icon: newCatType.value === 'INCOME' ? 'trending-up' : 'tag',
    })
    categoryId.value = created.id
    newCatName.value = ''
    showCategoryModal.value = false
  } catch (err: any) {
    categoryError.value =
      err.response?.data?.error || 'No se pudo crear la categoría. Verifica el nombre.'
  } finally {
    savingCategory.value = false
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
      <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-4">
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

        <!-- Controles de Periodo, Categoría y Sesión -->
        <div class="flex flex-wrap items-center gap-3">
          <!-- Selector de Mes -->
          <div
            class="flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-1.5"
          >
            <Calendar class="h-4 w-4 text-indigo-400" />
            <input
              v-model="selectedMonth"
              type="month"
              @change="handleMonthChange"
              class="bg-transparent text-xs font-medium text-slate-200 focus:outline-none"
            />
          </div>

          <!-- Botón Todo el Historial -->
          <button
            type="button"
            @click="toggleAllTime"
            class="rounded-lg border px-3 py-2 text-xs font-medium transition"
            :class="
              isAllTime
                ? 'border-indigo-500 bg-indigo-600/20 text-indigo-300'
                : 'border-slate-700 bg-slate-800 text-slate-300 hover:border-slate-600'
            "
          >
            Todo el historial
          </button>

          <!-- Botón Nueva Categoría -->
          <button
            type="button"
            @click="showCategoryModal = true"
            class="flex items-center gap-1.5 rounded-lg border border-indigo-500/40 bg-indigo-600/15 px-3.5 py-2 text-xs font-medium text-indigo-300 transition hover:bg-indigo-600/25"
          >
            <Tag class="h-3.5 w-3.5" />
            <span>+ Categoría</span>
          </button>

          <!-- Botón Cerrar Sesión -->
          <button
            @click="handleLogout"
            class="flex items-center gap-2 rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-medium text-slate-300 transition hover:border-rose-500/50 hover:text-rose-300"
          >
            <LogOut class="h-4 w-4" />
            <span>Cerrar sesión</span>
          </button>
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-7xl space-y-8 px-6 pt-8">
      <!-- Indicador del periodo activo -->
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-bold capitalize text-white">{{ periodLabel }}</h2>
          <p class="text-xs text-slate-400">
            Resumen financiero y métricas calculadas del periodo seleccionado
          </p>
        </div>
        <span
          class="rounded-full border border-slate-800 bg-slate-900 px-3 py-1 text-xs text-slate-400"
        >
          {{ financeStore.summary?.totals.transactionCount ?? 0 }} movimientos en este periodo
        </span>
      </div>

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
            <span class="text-xs font-medium uppercase tracking-wider">Ingresos del Periodo</span>
            <TrendingUp class="h-5 w-5 text-emerald-400" />
          </div>
          <p class="mt-3 text-2xl font-bold text-emerald-400">
            {{ formatCurrency(financeStore.summary?.totals.totalIncome ?? 0) }}
          </p>
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
          <div class="flex items-center justify-between text-slate-400">
            <span class="text-xs font-medium uppercase tracking-wider">Gastos del Periodo</span>
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
              <div class="mb-1 flex items-center justify-between">
                <label class="text-xs text-slate-400">Categoría</label>
                <button
                  type="button"
                  @click="showCategoryModal = true"
                  class="text-xs font-medium text-indigo-400 hover:text-indigo-300"
                >
                  + Crear nueva
                </button>
              </div>
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
          <h2 class="mb-4 text-base font-semibold text-white">
            Distribución de Gastos ({{ periodLabel }})
          </h2>
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
        <h2 class="mb-4 text-base font-semibold text-white">
          Movimientos del Periodo ({{ periodLabel }})
        </h2>

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
                  No hay transacciones registradas en este periodo.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>

    <!-- Modal para Crear Categoría Personalizada -->
    <div
      v-if="showCategoryModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-4 backdrop-blur-sm"
    >
      <div class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-2xl">
        <div class="mb-5 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div
              class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600/20 text-indigo-400"
            >
              <Tag class="h-5 w-5" />
            </div>
            <div>
              <h3 class="text-base font-bold text-white">Nueva Categoría</h3>
              <p class="text-xs text-slate-400">Personaliza la clasificación de tus finanzas</p>
            </div>
          </div>
          <button
            type="button"
            @click="showCategoryModal = false"
            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <div
          v-if="categoryError"
          class="mb-4 rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-xs text-rose-300"
        >
          {{ categoryError }}
        </div>

        <form @submit.prevent="handleCreateCategory" class="space-y-4">
          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-300"
              >Nombre de la categoría</label
            >
            <input
              v-model="newCatName"
              type="text"
              required
              maxlength="60"
              placeholder="Ej. Inversiones, Gimnasio, Mascotas..."
              class="w-full rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none"
            />
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-medium text-slate-300"
              >Tipo de movimiento</label
            >
            <div class="grid grid-cols-2 gap-3">
              <button
                type="button"
                @click="newCatType = 'EXPENSE'"
                class="rounded-lg border py-2 text-xs font-semibold transition"
                :class="
                  newCatType === 'EXPENSE'
                    ? 'border-rose-500 bg-rose-500/15 text-rose-300'
                    : 'border-slate-700 bg-slate-800 text-slate-400 hover:border-slate-600'
                "
              >
                Gasto (EXPENSE)
              </button>
              <button
                type="button"
                @click="newCatType = 'INCOME'"
                class="rounded-lg border py-2 text-xs font-semibold transition"
                :class="
                  newCatType === 'INCOME'
                    ? 'border-emerald-500 bg-emerald-500/15 text-emerald-300'
                    : 'border-slate-700 bg-slate-800 text-slate-400 hover:border-slate-600'
                "
              >
                Ingreso (INCOME)
              </button>
            </div>
          </div>

          <div>
            <label class="mb-2 block text-xs font-medium text-slate-300">Color distintivo</label>
            <div class="flex items-center gap-2.5">
              <button
                v-for="color in colorPalette"
                :key="color"
                type="button"
                @click="newCatColor = color"
                class="h-7 w-7 rounded-full transition transform"
                :class="
                  newCatColor === color
                    ? 'scale-110 ring-2 ring-white ring-offset-2 ring-offset-slate-900'
                    : 'opacity-75 hover:opacity-100'
                "
                :style="{ backgroundColor: color }"
              />
            </div>
          </div>

          <!-- Vista previa de la insignia -->
          <div class="rounded-lg border border-slate-800 bg-slate-950/60 p-3">
            <span class="block text-[11px] text-slate-500 mb-1.5"
              >Vista previa en tabla y gráficos:</span
            >
            <span
              class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium"
              :style="{ backgroundColor: newCatColor + '20', color: newCatColor }"
            >
              {{ newCatName || 'Nombre de categoría' }}
            </span>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              @click="showCategoryModal = false"
              class="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-xs font-medium text-slate-300 hover:bg-slate-700"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="savingCategory"
              class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
            >
              {{ savingCategory ? 'Creando...' : 'Guardar Categoría' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
