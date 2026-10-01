import { defineStore } from 'pinia'
import { ref } from 'vue'
import apiClient from '@/api/client'
import type { Category, Transaction, FinancialSummary } from '@/types/finance'

export const useFinanceStore = defineStore('finance', () => {
    const categories = ref<Category[]>([])
    const transactions = ref<Transaction[]>([])
    const summary = ref<FinancialSummary | null>(null)
    const loading = ref(false)

    async function fetchDashboardData(startDate?: string, endDate?: string) {
        loading.value = true
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

    async function addTransaction(payload: {
        amount: number
        categoryId: number
        description: string
        transactionDate: string
    }) {
        await apiClient.post('/transactions', payload)
        await fetchDashboardData()
    }

    async function removeTransaction(id: number) {
        await apiClient.delete(`/transactions/${id}`)
        await fetchDashboardData()
    }

    async function addCategory(payload: {
        name: string
        type: 'INCOME' | 'EXPENSE'
        color: string
        icon: string
    }) {
        await apiClient.post('/categories', payload)
        await fetchDashboardData()
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