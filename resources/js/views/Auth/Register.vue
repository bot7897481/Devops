<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="card max-w-md w-full">
      <h1 class="text-2xl font-bold text-center mb-6">Register</h1>

      <form @submit.prevent="handleRegister" class="space-y-4">
        <div>
          <label class="block text-gray-700 font-semibold mb-2">Email</label>
          <input v-model="form.email" type="email" required class="input-field" />
        </div>

        <div>
          <label class="block text-gray-700 font-semibold mb-2">Password</label>
          <input v-model="form.password" type="password" required minlength="8" class="input-field" />
          <p class="text-sm text-gray-600 mt-1">Min 8 characters, include uppercase, number, special character</p>
        </div>

        <div>
          <label class="block text-gray-700 font-semibold mb-2">Confirm Password</label>
          <input v-model="form.password_confirmation" type="password" required class="input-field" />
        </div>

        <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {{ error }}
        </div>

        <button type="submit" :disabled="loading" class="btn-primary w-full">
          {{ loading ? 'Creating account...' : 'Register' }}
        </button>
      </form>

      <div class="mt-4 text-center">
        <router-link to="/login" class="text-primary-600 hover:underline">
          Already have an account? Login
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
const form = ref({
  email: '',
  password: '',
  password_confirmation: '',
});

const handleRegister = async () => {
  loading.value = true;
  error.value = '';

  try {
    await authStore.register(form.value);
    router.push('/applicant/dashboard');
  } catch (err) {
    error.value = err.response?.data?.message || 'Registration failed. Please try again.';
  } finally {
    loading.value = false;
  }
};
</script>
