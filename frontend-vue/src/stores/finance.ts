import { defineStore } from 'pinia'
import { ref } from 'vue'
import apiClient from '@/api/client'
import type { Category, Transaction, FinancialSummary } from '@/types/finance'

export const useFinanceStore = defineStore('finance', () => {
  const categories = ref<Category[]>([])
  const transactions = ref<Transaction[]>([])
  const summary = ref<FinancialSummary | null>(null)
  const loading = ref(false)
  const activeRange = ref<{ startDate?: string; endDate?: string }>({})

  async function fetchDashboardData(startDate?: string, endDate?: string) {
    loading.value = true
    activeRange.value = { startDate, endDate }
    try {
      const params = startDate && endDate ? { startDate, endDate } : {}
      const [catsRes, txRes, sumRes] = await Promise.all([
        apiClient.get<Category[]>('/categories'),
        apiClient.get<Transaction[]>('/transactions', { params }),
        apiClient.get<FinancialSummary>('/transactions/summary', { params }),
      ])
      categories.value = catsRes.data
      transactions.value = txRes.data
      summary.value = sumRes.data
    } finally {
      loading.value = false
    }
  }

  async function refreshCurrentView() {
    await fetchDashboardData(activeRange.value.startDate, activeRange.value.endDate)
  }

  async function addTransaction(payload: {
    amount: number
    categoryId: number
    description: string
    transactionDate: string
  }) {
    await apiClient.post('/transactions', payload)
    await refreshCurrentView()
  }

  async function removeTransaction(id: number) {
    await apiClient.delete(`/transactions/${id}`)
    await refreshCurrentView()
  }

  async function addCategory(payload: {
    name: string
    type: 'INCOME' | 'EXPENSE'
    color: string
    icon: string
  }) {
    const { data } = await apiClient.post<Category>('/categories', payload)
    await refreshCurrentView()
    return data
  }

  return {
    categories,
    transactions,
    summary,
    loading,
    fetchDashboardData,
    addTransaction,
    removeTransaction,
    addCategory,
  }
})
