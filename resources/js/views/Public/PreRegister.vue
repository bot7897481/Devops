<template>
  <div class="min-h-screen bg-gray-50 py-12">
    <div class="container mx-auto px-4">
      <div class="card max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Pre-Register</h1>
        <p class="text-gray-600 mb-8">Get notified when applications open!</p>

        <!-- Success Message -->
        <div v-if="success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
          <p class="font-bold">Pre-registration successful!</p>
          <p>Your reference number is: <strong>{{ referenceNumber }}</strong></p>
          <p class="text-sm mt-2">Check your email for confirmation.</p>
        </div>

        <!-- Pre-registration Form -->
        <form v-else @submit.prevent="submitForm" class="space-y-4">
          <div>
            <label class="block text-gray-700 font-semibold mb-2">First Name *</label>
            <input v-model="form.first_name" type="text" required class="input-field" />
          </div>

          <div>
            <label class="block text-gray-700 font-semibold mb-2">Last Name *</label>
            <input v-model="form.last_name" type="text" required class="input-field" />
          </div>

          <div>
            <label class="block text-gray-700 font-semibold mb-2">Email *</label>
            <input v-model="form.email" type="email" required class="input-field" />
          </div>

          <div>
            <label class="block text-gray-700 font-semibold mb-2">Phone *</label>
            <input v-model="form.phone" type="tel" required class="input-field" />
          </div>

          <div>
            <label class="block text-gray-700 font-semibold mb-2">Upload Resume (Optional)</label>
            <input @change="handleFileUpload" type="file" accept=".pdf,.doc,.docx" class="input-field" />
          </div>

          <!-- Error Messages -->
          <div v-if="errors.length" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc list-inside">
              <li v-for="error in errors" :key="error">{{ error }}</li>
            </ul>
          </div>

          <div class="flex gap-4">
            <button type="submit" :disabled="loading" class="btn-primary flex-1">
              {{ loading ? 'Submitting...' : 'Pre-Register' }}
            </button>
            <router-link to="/" class="btn-secondary">Cancel</router-link>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(false);
const success = ref(false);
const errors = ref([]);
const referenceNumber = ref('');

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  document: null,
});

const handleFileUpload = (event) => {
  form.value.document = event.target.files[0];
};

const submitForm = async () => {
  loading.value = true;
  errors.value = [];

  try {
    const formData = new FormData();
    Object.keys(form.value).forEach(key => {
      if (form.value[key]) formData.append(key, form.value[key]);
    });

    const response = await axios.post('/public/pre-register', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    success.value = true;
    referenceNumber.value = response.data.reference_number;
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = Object.values(error.response.data.errors).flat();
    } else {
      errors.value = ['An error occurred. Please try again.'];
    }
  } finally {
    loading.value = false;
  }
};
</script>
