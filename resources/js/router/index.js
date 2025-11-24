import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

// Public Pages
import LandingPage from '@/views/Public/LandingPage.vue';
import PreRegister from '@/views/Public/PreRegister.vue';

// Auth Pages
import Login from '@/views/Auth/Login.vue';
import Register from '@/views/Auth/Register.vue';

// Applicant Pages
import ApplicantDashboard from '@/views/Applicant/Dashboard.vue';
import ApplicationForm from '@/views/Applicant/ApplicationForm.vue';
import ExamPortal from '@/views/Applicant/ExamPortal.vue';

// Admin Pages
import AdminDashboard from '@/views/Admin/Dashboard.vue';
import ApplicationManagement from '@/views/Admin/ApplicationManagement.vue';
import ExamManagement from '@/views/Admin/ExamManagement.vue';
import DispatchManagement from '@/views/Admin/DispatchManagement.vue';

// Chief Pages
import ChiefDashboard from '@/views/Chief/Dashboard.vue';

const routes = [
  // Public routes
  { path: '/', name: 'home', component: LandingPage },
  { path: '/pre-register', name: 'pre-register', component: PreRegister },

  // Auth routes
  { path: '/login', name: 'login', component: Login, meta: { guest: true } },
  { path: '/register', name: 'register', component: Register, meta: { guest: true } },

  // Applicant routes
  {
    path: '/applicant',
    meta: { requiresAuth: true, role: 'applicant' },
    children: [
      { path: 'dashboard', name: 'applicant.dashboard', component: ApplicantDashboard },
      { path: 'application', name: 'applicant.application', component: ApplicationForm },
      { path: 'exam', name: 'applicant.exam', component: ExamPortal },
    ],
  },

  // Admin routes
  {
    path: '/admin',
    meta: { requiresAuth: true, role: 'admin' },
    children: [
      { path: 'dashboard', name: 'admin.dashboard', component: AdminDashboard },
      { path: 'applications', name: 'admin.applications', component: ApplicationManagement },
      { path: 'exams', name: 'admin.exams', component: ExamManagement },
      { path: 'dispatch', name: 'admin.dispatch', component: DispatchManagement },
    ],
  },

  // Chief routes
  {
    path: '/chief',
    meta: { requiresAuth: true, role: 'chief' },
    children: [
      { path: 'dashboard', name: 'chief.dashboard', component: ChiefDashboard },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Navigation guard
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
  const isGuest = to.matched.some(record => record.meta.guest);

  if (requiresAuth && !authStore.isAuthenticated) {
    next('/login');
  } else if (isGuest && authStore.isAuthenticated) {
    next(`/${authStore.user.role}/dashboard`);
  } else if (to.meta.role && authStore.user?.role !== to.meta.role) {
    next('/');
  } else {
    next();
  }
});

export default router;
