import { createRouter, createWebHistory } from 'vue-router'
import Login from '../pages/Login.vue'
import Dashboard from '../pages/Dashboard.vue'
import Maps from '../pages/Maps.vue'
import OdpList from '../pages/OdpList.vue'

const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', component: Login },
  {
    path: '/dashboard',
    component: Dashboard,
    meta: { layout: 'default', requiresAuth: true }
  },
  {
    path: '/maps',
    component: Maps,
    meta: { layout: 'default', requiresAuth: true }
  },
  {
    path: '/odp',
    component: OdpList,
    meta: { layout: 'default', requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// 👉 guard
router.beforeEach((to, from, next) => {
  const userData = localStorage.getItem('user')
  const isAuthenticated = !!userData

  if (to.meta.requiresAuth && !isAuthenticated) {
    next('/login')
  } else {
    next()
  }
})

export default router