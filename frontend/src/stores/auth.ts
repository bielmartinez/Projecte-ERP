import { defineStore } from 'pinia'

import api from '@/services/api'

const AUTH_STORAGE_KEY = 'erp_auth_session'

interface Usuari {
  id: number
  email: string
  nom?: string
  cognoms?: string
}

interface LoginPayload {
  email: string
  password: string
  recordar?: boolean
}

interface RegisterPayload {
  email: string
  password: string
  nom: string
  cognoms?: string
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: null as string | null,
    usuari: null as Usuari | null,
    loading: false,
    error: null as string | null
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.token)
  },
  actions: {
    restoreSession() {
      const rawSession = localStorage.getItem(AUTH_STORAGE_KEY)

      if (!rawSession) {
        return
      }

      try {
        const parsedSession = JSON.parse(rawSession) as {
          token?: string
          usuari?: Usuari
        }

        if (parsedSession.token && parsedSession.usuari) {
          this.token = parsedSession.token
          this.usuari = parsedSession.usuari
        }
      } catch {
        localStorage.removeItem(AUTH_STORAGE_KEY)
      }
    },
    persistSession() {
      if (!this.token || !this.usuari) {
        return
      }

      localStorage.setItem(
        AUTH_STORAGE_KEY,
        JSON.stringify({
          token: this.token,
          usuari: this.usuari
        })
      )
    },
    setSession(token: string, usuari: Usuari) {
      this.token = token
      this.usuari = usuari
      this.persistSession()
    },
    clearSession() {
      this.token = null
      this.usuari = null
      localStorage.removeItem(AUTH_STORAGE_KEY)
    },
    async login(payload: LoginPayload) {
      this.loading = true
      this.error = null

      try {
        const { data } = await api.post('/auth/login', payload)
        this.setSession(data.token, data.usuari)
      } catch (error: any) {
        this.error = error?.response?.data?.message ?? 'No s\'ha pogut iniciar sessió.'
        throw error
      } finally {
        this.loading = false
      }
    },
    async register(payload: RegisterPayload) {
      this.loading = true
      this.error = null

      try {
        await api.post('/auth/register', payload)
      } catch (error: any) {
        this.error = error?.response?.data?.message ?? 'No s\'ha pogut completar el registre.'
        throw error
      } finally {
        this.loading = false
      }
    },
    async logout() {
      const previousToken = this.token

      this.clearSession()

      if (!previousToken) {
        return
      }

      try {
        await api.post('/auth/logout', null, {
          headers: {
            Authorization: `Bearer ${previousToken}`
          }
        })
      } catch {
      }
    }
  }
})