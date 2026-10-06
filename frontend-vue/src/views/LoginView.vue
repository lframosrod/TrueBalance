<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Wallet, ArrowRight, Loader2 } from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const isRegisterMode = ref(false)
const name = ref('')
const email = ref('')
const password = ref('')
const errorMsg = ref('')
const submitting = ref(false)

function toggleMode() {
  isRegisterMode.value = !isRegisterMode.value
  errorMsg.value = ''
  name.value = ''
  email.value = ''
  password.value = ''
}

async function handleSubmit() {
  errorMsg.value = ''
  submitting.value = true
  try {
    if (isRegisterMode.value) {
      await authStore.register(name.value.trim(), email.value.trim(), password.value)
    } else {
      await authStore.login(email.value.trim(), password.value)
    }
    router.push('/')
  } catch (err: any) {
    errorMsg.value =
      err.response?.data?.error ||
      err.response?.data?.message ||
      'Credenciales inválidas o error al procesar la solicitud.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center px-4">
    <div
      class="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900/80 p-8 shadow-2xl backdrop-blur"
    >
      <div class="mb-8 flex items-center gap-3">
        <div
          class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/30"
        >
          <Wallet class="h-6 w-6" />
        </div>
        <div>
          <h1 class="text-xl font-bold tracking-tight text-white">TrueBalance</h1>
          <p class="text-xs text-slate-400">Personal Finance & Analytics</p>
        </div>
      </div>

      <h2 class="mb-2 text-2xl font-semibold text-white">
        {{ isRegisterMode ? 'Crear cuenta nueva' : 'Bienvenido de nuevo' }}
      </h2>
      <p class="mb-6 text-sm text-slate-400">
        {{
          isRegisterMode
            ? 'Configura tu espacio financiero con categorías automáticas.'
            : 'Ingresa tus credenciales para acceder a tu panel financiero.'
        }}
      </p>

      <div
        v-if="errorMsg"
        class="mb-5 rounded-lg border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-300"
      >
        {{ errorMsg }}
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div v-if="isRegisterMode">
          <label class="mb-1.5 block text-xs font-medium text-slate-300">Nombre completo</label>
          <input
            v-model="name"
            type="text"
            required
            autocomplete="name"
            placeholder="Ej. Alex Ramos"
            class="w-full rounded-lg border border-slate-700 bg-slate-800/70 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none"
          />
        </div>

        <div>
          <label class="mb-1.5 block text-xs font-medium text-slate-300">Correo electrónico</label>
          <input
            v-model="email"
            type="email"
            required
            autocomplete="email"
            placeholder="tu@correo.com"
            class="w-full rounded-lg border border-slate-700 bg-slate-800/70 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none"
          />
        </div>

        <div>
          <label class="mb-1.5 block text-xs font-medium text-slate-300">Contraseña</label>
          <input
            v-model="password"
            type="password"
            required
            minlength="6"
            autocomplete="current-password"
            placeholder="Mínimo 6 caracteres"
            class="w-full rounded-lg border border-slate-700 bg-slate-800/70 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:outline-none"
          />
        </div>

        <button
          type="submit"
          :disabled="submitting"
          class="mt-2 flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:opacity-50"
        >
          <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />
          <span>{{ isRegisterMode ? 'Registrarme ahora' : 'Iniciar sesión' }}</span>
          <ArrowRight v-if="!submitting" class="h-4 w-4" />
        </button>
      </form>

      <div class="mt-6 border-t border-slate-800 pt-5 text-center text-sm text-slate-400">
        {{ isRegisterMode ? '¿Ya tienes una cuenta?' : '¿Aún no tienes cuenta?' }}
        <button
          type="button"
          @click="toggleMode"
          class="ml-1 font-medium text-indigo-400 hover:text-indigo-300"
        >
          {{ isRegisterMode ? 'Inicia sesión' : 'Regístrate gratis' }}
        </button>
      </div>
    </div>
  </div>
</template>
