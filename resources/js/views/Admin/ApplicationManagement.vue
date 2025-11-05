<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Application Management</h1>
        <button @click="exportApplications" class="btn-primary">
          📊 Export to Excel
        </button>
      </div>

      <!-- Filters -->
      <div class="card mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <input v-model="filters.search" @input="loadApplications" type="text" placeholder="Search by name, email, or confirmation #" class="input-field" />

          <select v-model="filters.status" @change="loadApplications" class="input-field">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="validated">Validated</option>
            <option value="exam_scheduled">Exam Scheduled</option>
            <option value="exam_completed">Exam Completed</option>
          </select>

          <select v-model="filters.is_validated" @change="loadApplications" class="input-field">
            <option value="">All</option>
            <option value="true">Validated</option>
            <option value="false">Not Validated</option>
          </select>

          <button @click="resetFilters" class="btn-secondary">Reset Filters</button>
        </div>
      </div>

      <!-- Applications Table -->
      <div class="card overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-100 border-b">
            <tr>
              <th class="text-left p-3">Confirmation #</th>
              <th class="text-left p-3">Name</th>
              <th class="text-left p-3">Email</th>
              <th class="text-left p-3">Application Date</th>
              <th class="text-left p-3">Status</th>
              <th class="text-left p-3">Validated</th>
              <th class="text-left p-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading" class="border-b">
              <td colspan="7" class="text-center p-8 text-gray-500">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
                <p class="mt-2">Loading applications...</p>
              </td>
            </tr>
            <tr v-else-if="applications.length === 0" class="border-b">
              <td colspan="7" class="text-center p-8 text-gray-500">
                No applications found
              </td>
            </tr>
            <tr v-else v-for="app in applications" :key="app.id" class="border-b hover:bg-gray-50">
              <td class="p-3 font-mono text-sm">{{ app.confirmation_number }}</td>
              <td class="p-3">{{ app.first_name }} {{ app.last_name }}</td>
              <td class="p-3 text-sm">{{ app.email }}</td>
              <td class="p-3 text-sm">{{ formatDate(app.application_timestamp) }}</td>
              <td class="p-3">
                <span class="px-2 py-1 rounded text-xs font-semibold" :class="getStatusClass(app.status)">
                  {{ app.status }}
                </span>
              </td>
              <td class="p-3">
                <span v-if="app.is_validated" class="text-green-600">✓ Yes</span>
                <span v-else class="text-gray-400">✗ No</span>
              </td>
              <td class="p-3">
                <button @click="viewApplication(app)" class="text-primary-600 hover:text-primary-700 text-sm font-semibold">
                  View Details
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="pagination.total > 0" class="flex justify-between items-center p-4 border-t">
          <p class="text-sm text-gray-600">
            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} applications
          </p>
          <div class="flex gap-2">
            <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="btn-secondary text-sm">
              Previous
            </button>
            <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="btn-secondary text-sm">
              Next
            </button>
          </div>
        </div>
      </div>

      <!-- Application Detail Modal -->
      <div v-if="selectedApplication" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="card max-w-4xl w-full my-8">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold">Application Details</h2>
            <button @click="selectedApplication = null" class="text-gray-500 hover:text-gray-700">
              ✕
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
              <h3 class="font-bold mb-2">Personal Information</h3>
              <div class="bg-gray-50 p-4 rounded text-sm space-y-1">
                <p><strong>Name:</strong> {{ selectedApplication.first_name }} {{ selectedApplication.middle_name }} {{ selectedApplication.last_name }}</p>
                <p><strong>DOB:</strong> {{ selectedApplication.dob }}</p>
                <p><strong>Phone:</strong> {{ selectedApplication.phone_primary }}</p>
                <p><strong>Email:</strong> {{ selectedApplication.email }}</p>
                <p><strong>Address:</strong> {{ selectedApplication.address_street }}, {{ selectedApplication.address_city }}, {{ selectedApplication.address_state }} {{ selectedApplication.address_zip }}</p>
              </div>
            </div>

            <div>
              <h3 class="font-bold mb-2">Education</h3>
              <div class="bg-gray-50 p-4 rounded text-sm space-y-1">
                <p><strong>High School:</strong> {{ selectedApplication.high_school_name }}</p>
                <p><strong>Graduation:</strong> {{ selectedApplication.hs_graduation_date }}</p>
              </div>
            </div>
          </div>

          <!-- Documents -->
          <div class="mb-6">
            <h3 class="font-bold mb-2">Uploaded Documents</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="bg-gray-50 p-4 rounded text-center">
                <p class="text-sm font-semibold mb-2">Diploma</p>
                <button v-if="selectedApplication.diploma_path" @click="downloadDocument('diploma')" class="text-primary-600 hover:text-primary-700 text-sm">
                  📄 Download
                </button>
                <p v-else class="text-gray-400 text-sm">Not uploaded</p>
              </div>
              <div class="bg-gray-50 p-4 rounded text-center">
                <p class="text-sm font-semibold mb-2">ID Front</p>
                <button v-if="selectedApplication.id_document_front_path" @click="downloadDocument('id_front')" class="text-primary-600 hover:text-primary-700 text-sm">
                  📄 Download
                </button>
                <p v-else class="text-gray-400 text-sm">Not uploaded</p>
              </div>
              <div class="bg-gray-50 p-4 rounded text-center">
                <p class="text-sm font-semibold mb-2">ID Back</p>
                <button v-if="selectedApplication.id_document_back_path" @click="downloadDocument('id_back')" class="text-primary-600 hover:text-primary-700 text-sm">
                  📄 Download
                </button>
                <p v-else class="text-gray-400 text-sm">Not uploaded</p>
              </div>
            </div>
          </div>

          <!-- Validation Section -->
          <div v-if="!selectedApplication.is_validated" class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-6">
            <h3 class="font-bold mb-3">Validate Application</h3>
            <div class="space-y-3">
              <div class="flex items-center">
                <input v-model="validationForm.documents_verified" type="checkbox" class="mr-2" />
                <label>Documents verified and match physical copies</label>
              </div>
              <div class="flex items-center">
                <input v-model="validationForm.id_verified" type="checkbox" class="mr-2" />
                <label>ID verified and authentic</label>
              </div>
              <textarea v-model="validationForm.notes" rows="2" class="input-field" placeholder="Optional notes"></textarea>
              <button @click="validateApplication" :disabled="!validationForm.documents_verified || !validationForm.id_verified" class="btn-primary">
                ✓ Mark as Validated
              </button>
            </div>
          </div>

          <div v-else class="bg-green-50 border border-green-200 rounded p-4 mb-6">
            <p class="text-green-700">
              ✓ Application validated on {{ formatDate(selectedApplication.validation_timestamp) }}
            </p>
          </div>

          <!-- Biometric Capture -->
          <div class="mb-6">
            <h3 class="font-bold mb-3">Biometric Data</h3>
            <div class="bg-gray-50 p-4 rounded">
              <div v-if="selectedApplication.photo_biometric_path" class="mb-3">
                <p class="text-green-600 text-sm">✓ Photo captured</p>
              </div>
              <div v-else>
                <input ref="photoInput" type="file" accept="image/*" @change="handlePhotoCapture" class="hidden" />
                <button @click="$refs.photoInput.click()" class="btn-secondary text-sm">
                  📷 Capture Photo
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(false);
const applications = ref([]);
const selectedApplication = ref(null);
const photoInput = ref(null);

const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0
});

const filters = ref({
  search: '',
  status: '',
  is_validated: ''
});

const validationForm = ref({
  documents_verified: false,
  id_verified: false,
  notes: ''
});

const loadApplications = async (page = 1) => {
  loading.value = true;
  try {
    const response = await axios.get('/admin/applications', {
      params: {
        page,
        ...filters.value
      }
    });

    applications.value = response.data.data;
    pagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      from: response.data.from,
      to: response.data.to,
      total: response.data.total
    };
  } catch (error) {
    console.error('Failed to load applications:', error);
  } finally {
    loading.value = false;
  }
};

const viewApplication = async (app) => {
  try {
    const response = await axios.get(`/admin/applications/${app.id}`);
    selectedApplication.value = response.data.data;
    validationForm.value = {
      documents_verified: false,
      id_verified: false,
      notes: ''
    };
  } catch (error) {
    console.error('Failed to load application details:', error);
  }
};

const validateApplication = async () => {
  try {
    await axios.post(`/admin/applications/${selectedApplication.value.id}/validate`, validationForm.value);
    alert('Application validated successfully!');
    selectedApplication.value.is_validated = true;
    selectedApplication.value.status = 'validated';
    loadApplications(pagination.value.current_page);
  } catch (error) {
    alert('Failed to validate application: ' + (error.response?.data?.message || 'Unknown error'));
  }
};

const handlePhotoCapture = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('photo', file);
  formData.append('biometric_type', 'photo');

  try {
    await axios.post(`/admin/applications/${selectedApplication.value.id}/capture-biometric`, formData);
    alert('Photo captured successfully!');
    selectedApplication.value.photo_biometric_path = 'captured';
  } catch (error) {
    alert('Failed to capture photo: ' + (error.response?.data?.message || 'Unknown error'));
  }
};

const downloadDocument = (type) => {
  window.open(`/api/admin/applications/${selectedApplication.value.id}/document/${type}`, '_blank');
};

const exportApplications = () => {
  window.open('/api/admin/applications/export?' + new URLSearchParams(filters.value).toString(), '_blank');
};

const resetFilters = () => {
  filters.value = { search: '', status: '', is_validated: '' };
  loadApplications();
};

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    loadApplications(page);
  }
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    validated: 'bg-green-100 text-green-800',
    exam_scheduled: 'bg-blue-100 text-blue-800',
    exam_completed: 'bg-purple-100 text-purple-800',
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

onMounted(() => {
  loadApplications();
});
</script>
