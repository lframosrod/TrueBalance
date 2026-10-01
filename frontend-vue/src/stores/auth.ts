import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import apiClient from '@/api/client'
import type { User } from '@/types/finance'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('tb_token'))
  const user = ref<User | null>(
    localStorage.getItem('tb_user') ? JSON.parse(localStorage.getItem('tb_user')!) : null,
  )

  const isAuthenticated = computed(() => !!token.value)

  async function login(email: string, password: string) {
    const { data } = await apiClient.post<{ token: string }>('/login_check', { email, password })
    token.value = data.token
    localStorage.setItem('tb_token', data.token)
    await fetchCurrentUser()
  }

  async function register(name: string, email: string, password: string) {
    await apiClient.post('/register', { name, email, password })
    await login(email, password)
  }

  async function fetchCurrentUser() {
    const { data } = await apiClient.get<User>('/me')
    user.value = data
    localStorage.setItem('tb_user', JSON.stringify(data))
  }

  function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem('tb_token')
    localStorage.removeItem('tb_user')
  }

  return {
    token,
    user,
    isAuthenticated,
    login,
    register,
    fetchCurrentUser,
    logout,
  }
})
