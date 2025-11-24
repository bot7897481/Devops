<template>
  <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-primary-500 transition">
    <!-- Upload Area -->
    <div v-if="!existingPath && !uploadedFile" @click="triggerFileInput" class="cursor-pointer">
      <div class="text-4xl mb-2">📄</div>
      <p class="text-gray-600 mb-2">Click to upload or drag and drop</p>
      <p class="text-xs text-gray-500">PDF, JPG, PNG (max 5MB)</p>
      <input
        ref="fileInput"
        type="file"
        @change="handleFileSelect"
        accept=".pdf,.jpg,.jpeg,.png"
        class="hidden"
      />
    </div>

    <!-- Uploading State -->
    <div v-if="uploading" class="py-4">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-2"></div>
      <p class="text-gray-600">Uploading...</p>
    </div>

    <!-- Uploaded File Display -->
    <div v-if="(existingPath || uploadedFile) && !uploading" class="py-2">
      <div class="text-4xl mb-2">✅</div>
      <p class="text-green-600 font-semibold mb-2">File Uploaded</p>
      <p class="text-sm text-gray-600 mb-3">{{ uploadedFile?.name || 'Document uploaded' }}</p>
      <button @click="removeFile" type="button" class="text-red-600 hover:text-red-700 text-sm font-semibold">
        Remove & Upload Different File
      </button>
    </div>

    <!-- Error Display -->
    <div v-if="error" class="bg-red-50 border border-red-200 rounded p-3 mt-3">
      <p class="text-red-700 text-sm">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
  documentType: {
    type: String,
    required: true
  },
  existingPath: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['file-uploaded']);

const fileInput = ref(null);
const uploadedFile = ref(null);
const uploading = ref(false);
const error = ref('');

const triggerFileInput = () => {
  fileInput.value?.click();
};

const handleFileSelect = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  // Validate file
  error.value = '';

  // Check file size (5MB)
  if (file.size > 5 * 1024 * 1024) {
    error.value = 'File size must be less than 5MB';
    return;
  }

  // Check file type
  const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
  if (!allowedTypes.includes(file.type)) {
    error.value = 'Only PDF, JPG, and PNG files are allowed';
    return;
  }

  // Upload file
  await uploadFile(file);
};

const uploadFile = async (file) => {
  uploading.value = true;
  error.value = '';

  const formData = new FormData();
  formData.append('document', file);
  formData.append('document_type', props.documentType);

  try {
    const response = await axios.post('/applicant/application/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    uploadedFile.value = {
      name: file.name,
      path: response.data.path
    };

    emit('file-uploaded', response.data.path);

  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to upload file. Please try again.';
  } finally {
    uploading.value = false;
  }
};

const removeFile = () => {
  uploadedFile.value = null;
  error.value = '';
  if (fileInput.value) {
    fileInput.value.value = '';
  }
  emit('file-uploaded', '');
};
</script>
