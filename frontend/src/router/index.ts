import { createRouter, createWebHistory } from 'vue-router'

import MainLayout from '@/layouts/MainLayout.vue'
import { useAuthStore } from '@/stores/auth'
import ClientDetailView from '@/views/clients/ClientDetailView.vue'
import ClientFormView from '@/views/clients/ClientFormView.vue'
import CategoriesView from '@/views/CategoriesView.vue'
import ClientsView from '@/views/clients/ClientsView.vue'
import Dashboard from '@/views/Dashboard.vue'
import FacturaDetailView from '@/views/factures/FacturaDetailView.vue'
import FacturaFormView from '@/views/factures/FacturaFormView.vue'
import FacturesView from '@/views/factures/FacturesView.vue'
import LoginView from '@/views/auth/LoginView.vue'
import MovimentFormView from '@/views/moviments/MovimentFormView.vue'
import PlantillaFormView from '@/views/plantilles/PlantillaFormView.vue'
import PlantillesView from '@/views/plantilles/PlantillesView.vue'
import MovimentsView from '@/views/moviments/MovimentsView.vue'
import QuotaFormView from '@/views/quotes/QuotaFormView.vue'
import QuotaDetailView from '@/views/quotes/QuotaDetailView.vue'
import QuotesView from '@/views/quotes/QuotesView.vue'
import VerifactuDetailView from '@/views/verifactu/VerifactuDetailView.vue'
import VerifactuView from '@/views/verifactu/VerifactuView.vue'
import PerfilView from '@/views/PerfilView.vue'
import RegisterView from '@/views/auth/RegisterView.vue'

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
          path: 'clients/nou',
          name: 'client-create',
          component: ClientFormView
        },
        {
          path: 'clients/:id/editar',
          name: 'client-edit',
          component: ClientFormView
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
          path: 'moviments/nou',
          name: 'moviment-create',
          component: MovimentFormView
        },
        {
          path: 'moviments/:id/editar',
          name: 'moviment-edit',
          component: MovimentFormView
        },
        {
          path: 'moviments',
          name: 'moviments',
          component: MovimentsView
        },
        {
          path: '/informes',
          name: 'informes',
          component: () => import('@/views/informes/InformesView.vue'),
        },
        {
          path: 'quotes',
          name: 'quotes',
          component: QuotesView
        },
        {
          path: 'quotes/nova',
          name: 'quota-create',
          component: QuotaFormView
        },
        {
          path: 'quotes/:id/editar',
          name: 'quota-edit',
          component: QuotaFormView
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
        },
        {
          path: 'verifactu',
          name: 'verifactu',
          component: VerifactuView
        },
        {
          path: 'verifactu/:id',
          name: 'verifactu-detail',
          component: VerifactuDetailView
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