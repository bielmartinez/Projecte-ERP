import axios from 'axios'

import { useAuthStore } from '@/stores/auth'

const AUTH_REASON_STORAGE_KEY = 'erp_auth_redirect_reason'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8080',
  headers: {
    'Content-Type': 'application/json'
  }
})

api.interceptors.request.use((config) => {
  const authStore = useAuthStore()

  if (authStore.token) {
    config.headers.Authorization = `Bearer ${authStore.token}`
  }

  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error?.response?.status
    const requestUrl = String(error?.config?.url ?? '')
    const isAuthEndpoint = requestUrl.includes('/auth/login') || requestUrl.includes('/auth/register') || requestUrl.includes('/auth/logout')

    if (status === 401 && !isAuthEndpoint) {
      const authStore = useAuthStore()
      authStore.clearSession()
      sessionStorage.setItem(AUTH_REASON_STORAGE_KEY, 'session-expired')

      if (window.location.pathname !== '/login') {
        window.location.assign('/login?reason=session-expired')
      }
    }

    return Promise.reject(error)
  }
)

export default api