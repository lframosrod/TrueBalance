export type TransactionType = 'INCOME' | 'EXPENSE'

export interface User {
  id: number
  name: string
  email: string
  roles?: string[]
  createdAt: string
}

export interface Category {
  id: number
  name: string
  type: TransactionType
  color: string
  icon: string
}

export interface Transaction {
  id: number
  amount: number
  type: TransactionType
  description: string
  transactionDate: string
  createdAt: string
  category: Category
}

export interface SummaryTotals {
  totalIncome: number
  totalExpense: number
  netBalance: number
  savingsRate: number
  transactionCount: number
}

export interface CategoryBreakdown {
  categoryId: number
  categoryName: string
  color: string
  icon: string
  type: TransactionType
  total: number
  count: number
}

export interface FinancialSummary {
  period: {
    startDate: string
    endDate: string
  }
  totals: SummaryTotals
  byCategory: CategoryBreakdown[]
}
