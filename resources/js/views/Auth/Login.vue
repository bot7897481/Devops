<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="card max-w-md w-full">
      <h1 class="text-2xl font-bold text-center mb-6">Login</h1>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-gray-700 font-semibold mb-2">Email</label>
          <input v-model="credentials.email" type="email" required class="input-field" />
        </div>

        <div>
          <label class="block text-gray-700 font-semibold mb-2">Password</label>
          <input v-model="credentials.password" type="password" required class="input-field" />
        </div>

        <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {{ error }}
        </div>

        <button type="submit" :disabled="loading" class="btn-primary w-full">
          {{ loading ? 'Logging in...' : 'Login' }}
        </button>
      </form>

      <div class="mt-4 text-center">
        <router-link to="/register" class="text-primary-600 hover:underline">
          Don't have an account? Register
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const loading = ref(false);
const error = ref('');
const credentials = ref({ email: '', password: '' });

const handleLogin = async () => {
  loading.value = true;
  error.value = '';

  try {
    await authStore.login(credentials.value);
    router.push(`/${authStore.userRole}/dashboard`);
  } catch (err) {
    error.value = err.response?.data?.message || 'Login failed. Please try again.';
  } finally {
    loading.value = false;
  }
};
</script>
