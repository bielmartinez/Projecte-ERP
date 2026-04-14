import { createRouter, createWebHistory } from 'vue-router'

import MainLayout from '@/layouts/MainLayout.vue'
import { useAuthStore } from '@/stores/auth'
import ClientDetailView from '@/views/ClientDetailView.vue'
import CategoriesView from '@/views/CategoriesView.vue'
import ClientsView from '@/views/ClientsView.vue'
import Dashboard from '@/views/Dashboard.vue'
import FacturaDetailView from '@/views/FacturaDetailView.vue'
import FacturaFormView from '@/views/FacturaFormView.vue'
import FacturesView from '@/views/FacturesView.vue'
import LoginView from '@/views/LoginView.vue'
import PlantillaFormView from '@/views/PlantillaFormView.vue'
import PlantillesView from '@/views/PlantillesView.vue'
import MovimentsView from '@/views/MovimentsView.vue'
import QuotaDetailView from '@/views/QuotaDetailView.vue'
import QuotesView from '@/views/QuotesView.vue'
import PerfilView from '@/views/PerfilView.vue'
import RegisterView from '@/views/RegisterView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guestOnly: true }
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterView,
      meta: { guestOnly: true }
    },
    {
      path: '/',
      component: MainLayout,
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: Dashboard
        },
        {
          path: 'perfil',
          name: 'perfil',
          component: PerfilView
        },
        {
          path: 'clients',
          name: 'clients',
          component: ClientsView
        },
        {
          path: 'clients/:id',
          name: 'client-detail',
          component: ClientDetailView
        },
        {
          path: 'categories',
          name: 'categories',
          component: CategoriesView
        },
        {
          path: 'moviments',
          name: 'moviments',
          component: MovimentsView
        },
        {
          path: 'quotes',
          name: 'quotes',
          component: QuotesView
        },
        {
          path: 'quotes/:id',
          name: 'quota-detail',
          component: QuotaDetailView
        },
        {
          path: 'factures',
          name: 'factures',
          component: FacturesView
        },
        {
          path: 'factures/nova',
          name: 'factura-create',
          component: FacturaFormView
        },
        {
          path: 'factures/:id',
          name: 'factura-detail',
          component: FacturaDetailView
        },
        {
          path: 'factures/:id/editar',
          name: 'factura-edit',
          component: FacturaFormView
        },
        {
          path: 'plantilles',
          name: 'plantilles',
          component: PlantillesView
        },
        {
          path: 'plantilles/nova',
          name: 'plantilla-create',
          component: PlantillaFormView
        },
        {
          path: 'plantilles/:id/editar',
          name: 'plantilla-edit',
          component: PlantillaFormView
        }
      ]
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/'
    }
  ]
})

router.beforeEach((to) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return { name: 'dashboard' }
  }

  return true
})

export default router