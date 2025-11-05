<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4 max-w-4xl">
      <!-- Header -->
      <div class="card mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Apprenticeship Application</h1>
        <p class="text-gray-600">Complete all steps to submit your application</p>

        <!-- Progress Bar -->
        <div class="mt-6">
          <div class="flex justify-between mb-2">
            <span v-for="step in 5" :key="step" class="text-sm font-semibold" :class="currentStep >= step ? 'text-primary-600' : 'text-gray-400'">
              Step {{ step }}
            </span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-primary-600 h-2 rounded-full transition-all duration-300" :style="{ width: (currentStep / 5) * 100 + '%' }"></div>
          </div>
        </div>
      </div>

      <!-- Form Steps -->
      <form @submit.prevent="handleSubmit">
        <!-- Step 1: Personal Information -->
        <div v-if="currentStep === 1" class="card">
          <h2 class="text-2xl font-bold mb-6">Step 1: Personal Information</h2>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
              <label class="block text-gray-700 font-semibold mb-2">First Name *</label>
              <input v-model="formData.first_name" type="text" required class="input-field" />
            </div>
            <div>
              <label class="block text-gray-700 font-semibold mb-2">Middle Name</label>
              <input v-model="formData.middle_name" type="text" class="input-field" />
            </div>
            <div>
              <label class="block text-gray-700 font-semibold mb-2">Last Name *</label>
              <input v-model="formData.last_name" type="text" required class="input-field" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-gray-700 font-semibold mb-2">Date of Birth *</label>
              <input v-model="formData.dob" type="date" required class="input-field" />
            </div>
            <div>
              <label class="block text-gray-700 font-semibold mb-2">Last 4 digits of SSN *</label>
              <input v-model="formData.ssn_last4" type="text" maxlength="4" pattern="[0-9]{4}" required class="input-field" placeholder="1234" />
              <p class="text-xs text-gray-500 mt-1">For verification purposes only</p>
            </div>
          </div>

          <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Street Address *</label>
            <input v-model="formData.address_street" type="text" required class="input-field" />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
              <label class="block text-gray-700 font-semibold mb-2">City *</label>
              <input v-model="formData.address_city" type="text" required class="input-field" />
            </div>
            <div>
              <label class="block text-gray-700 font-semibold mb-2">State *</label>
              <select v-model="formData.address_state" required class="input-field">
                <option value="">Select...</option>
                <option value="CA">California</option>
                <option value="NY">New York</option>
              </select>
            </div>
            <div>
              <label class="block text-gray-700 font-semibold mb-2">ZIP Code *</label>
              <input v-model="formData.address_zip" type="text" required class="input-field" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-gray-700 font-semibold mb-2">Primary Phone *</label>
              <input v-model="formData.phone_primary" type="tel" required class="input-field" />
            </div>
            <div>
              <label class="block text-gray-700 font-semibold mb-2">Alternate Phone</label>
              <input v-model="formData.phone_alternate" type="tel" class="input-field" />
            </div>
          </div>
        </div>

        <!-- Step 2: Educational Background -->
        <div v-if="currentStep === 2" class="card">
          <h2 class="text-2xl font-bold mb-6">Step 2: Educational Background</h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-gray-700 font-semibold mb-2">High School Name *</label>
              <input v-model="formData.high_school_name" type="text" required class="input-field" />
            </div>
            <div>
              <label class="block text-gray-700 font-semibold mb-2">Graduation Date *</label>
              <input v-model="formData.hs_graduation_date" type="date" required class="input-field" />
            </div>
          </div>

          <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">High School Diploma or GED Certificate *</label>
            <FileUpload
              document-type="diploma"
              :existing-path="formData.diploma_path"
              @file-uploaded="handleFileUploaded('diploma_path', $event)"
            />
          </div>

          <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Post-Secondary Education (Optional)</label>
            <textarea v-model="formData.post_secondary_text" rows="3" class="input-field"></textarea>
          </div>
        </div>

        <!-- Step 3: Work Experience -->
        <div v-if="currentStep === 3" class="card">
          <h2 class="text-2xl font-bold mb-6">Step 3: Work Experience</h2>

          <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Current Employment Status</label>
            <select v-model="formData.current_employment_status" class="input-field">
              <option value="">Select...</option>
              <option value="employed">Employed</option>
              <option value="unemployed">Unemployed</option>
              <option value="student">Student</option>
            </select>
          </div>

          <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Work History (Optional)</label>
            <textarea v-model="formData.work_experience_text" rows="5" class="input-field"></textarea>
          </div>
        </div>

        <!-- Step 4: Identification Upload -->
        <div v-if="currentStep === 4" class="card">
          <h2 class="text-2xl font-bold mb-6">Step 4: Identification Upload</h2>

          <div class="mb-6">
            <label class="block text-gray-700 font-semibold mb-2">ID Front (Photo Side) *</label>
            <FileUpload
              document-type="id_front"
              :existing-path="formData.id_document_front_path"
              @file-uploaded="handleFileUploaded('id_document_front_path', $event)"
            />
          </div>

          <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">ID Back (if applicable)</label>
            <FileUpload
              document-type="id_back"
              :existing-path="formData.id_document_back_path"
              @file-uploaded="handleFileUploaded('id_document_back_path', $event)"
            />
          </div>
        </div>

        <!-- Step 5: Review & Submit -->
        <div v-if="currentStep === 5" class="card">
          <h2 class="text-2xl font-bold mb-6">Step 5: Review & Submit</h2>

          <div class="mb-6">
            <h3 class="font-bold text-lg mb-3">Personal Information</h3>
            <div class="bg-gray-50 p-4 rounded">
              <p><strong>Name:</strong> {{ formData.first_name }} {{ formData.middle_name }} {{ formData.last_name }}</p>
              <p><strong>DOB:</strong> {{ formData.dob }}</p>
              <p><strong>Address:</strong> {{ formData.address_street }}, {{ formData.address_city }}, {{ formData.address_state }} {{ formData.address_zip }}</p>
            </div>
          </div>

          <div class="mb-6">
            <div class="flex items-start mb-3">
              <input v-model="formData.certify_accuracy" type="checkbox" required class="mt-1 mr-3" />
              <label>I certify that all information is true and accurate. *</label>
            </div>

            <div class="flex items-start mb-3">
              <input v-model="formData.agree_to_terms" type="checkbox" required class="mt-1 mr-3" />
              <label>I agree to the terms and conditions. *</label>
            </div>
          </div>

          <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Digital Signature *</label>
            <input v-model="formData.digital_signature" type="text" required class="input-field" />
          </div>
        </div>

        <!-- Errors -->
        <div v-if="errors.length" class="card bg-red-50 border border-red-200 mb-6">
          <ul class="list-disc list-inside text-red-700">
            <li v-for="error in errors" :key="error">{{ error }}</li>
          </ul>
        </div>

        <!-- Navigation -->
        <div class="card flex justify-between">
          <button v-if="currentStep > 1" @click="previousStep" type="button" class="btn-secondary">
            ← Previous
          </button>
          <div v-else></div>

          <button v-if="currentStep < 5" @click="nextStep" type="button" class="btn-primary">
            Next →
          </button>
          <button v-else type="submit" :disabled="submitting" class="btn-primary">
            {{ submitting ? 'Submitting...' : 'Submit Application' }}
          </button>
        </div>
      </form>

      <!-- Success Modal -->
      <div v-if="showSuccessModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="card max-w-lg">
          <div class="text-center">
            <div class="text-6xl mb-4">✅</div>
            <h2 class="text-2xl font-bold text-green-600 mb-4">Application Submitted!</h2>
            <div class="bg-green-50 border border-green-200 rounded p-4 mb-4">
              <p class="font-bold text-lg mb-2">Confirmation Number:</p>
              <p class="text-2xl font-mono text-green-700">{{ confirmationNumber }}</p>
            </div>
            <button @click="goToDashboard" class="btn-primary w-full">Go to Dashboard</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import FileUpload from '@/components/Shared/FileUpload.vue';

const router = useRouter();

const currentStep = ref(1);
const submitting = ref(false);
const errors = ref([]);
const showSuccessModal = ref(false);
const confirmationNumber = ref('');

const formData = ref({
  first_name: '',
  middle_name: '',
  last_name: '',
  dob: '',
  ssn_last4: '',
  address_street: '',
  address_city: '',
  address_state: '',
  address_zip: '',
  phone_primary: '',
  phone_alternate: '',
  high_school_name: '',
  hs_graduation_date: '',
  diploma_path: '',
  post_secondary_text: '',
  current_employment_status: '',
  work_experience_text: '',
  id_document_front_path: '',
  id_document_back_path: '',
  certify_accuracy: false,
  agree_to_terms: false,
  digital_signature: '',
});

const nextStep = () => {
  errors.value = [];
  if (validateStep(currentStep.value)) {
    currentStep.value++;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

const previousStep = () => {
  currentStep.value--;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const validateStep = (step) => {
  errors.value = [];

  switch (step) {
    case 1:
      if (!formData.value.first_name) errors.value.push('First name is required');
      if (!formData.value.last_name) errors.value.push('Last name is required');
      if (!formData.value.dob) errors.value.push('Date of birth is required');
      if (!formData.value.ssn_last4 || formData.value.ssn_last4.length !== 4) {
        errors.value.push('Last 4 digits of SSN are required');
      }
      if (!formData.value.address_street) errors.value.push('Street address is required');
      if (!formData.value.address_city) errors.value.push('City is required');
      if (!formData.value.address_state) errors.value.push('State is required');
      if (!formData.value.address_zip) errors.value.push('ZIP code is required');
      if (!formData.value.phone_primary) errors.value.push('Primary phone is required');
      break;

    case 2:
      if (!formData.value.high_school_name) errors.value.push('High school name is required');
      if (!formData.value.hs_graduation_date) errors.value.push('Graduation date is required');
      if (!formData.value.diploma_path) errors.value.push('Diploma upload is required');
      break;

    case 4:
      if (!formData.value.id_document_front_path) errors.value.push('ID front upload is required');
      break;

    case 5:
      if (!formData.value.certify_accuracy) errors.value.push('You must certify the accuracy');
      if (!formData.value.agree_to_terms) errors.value.push('You must agree to terms');
      if (!formData.value.digital_signature) errors.value.push('Digital signature is required');
      break;
  }

  return errors.value.length === 0;
};

const handleFileUploaded = (field, path) => {
  formData.value[field] = path;
};

const handleSubmit = async () => {
  if (!validateStep(5)) return;

  submitting.value = true;
  errors.value = [];

  try {
    const workExperience = formData.value.work_experience_text ? [
      { description: formData.value.work_experience_text }
    ] : null;

    const response = await axios.post('/applicant/application', {
      ...formData.value,
      work_experience: workExperience,
    });

    confirmationNumber.value = response.data.data.confirmation_number;
    showSuccessModal.value = true;

  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = Object.values(error.response.data.errors).flat();
    } else {
      errors.value = [error.response?.data?.message || 'Failed to submit application'];
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } finally {
    submitting.value = false;
  }
};

const goToDashboard = () => {
  router.push('/applicant/dashboard');
};

onMounted(() => {
  axios.get('/applicant/application/status')
    .then(response => {
      if (response.data.has_application) {
        router.push('/applicant/dashboard');
      }
    });
});
</script>
