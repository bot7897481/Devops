<template>
  <div class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-primary-700 text-white p-6">
      <div class="container mx-auto">
        <h1 class="text-3xl font-bold">Local 39 Stationary Engineers</h1>
        <p class="text-primary-100">Apprenticeship Program</p>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 container mx-auto px-4 py-12">
      <!-- Countdown Timer -->
      <div v-if="!isOpen" class="card max-w-2xl mx-auto text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Applications Open In:</h2>
        <div class="flex justify-center gap-4 text-4xl font-bold text-primary-600">
          <div class="flex flex-col">
            <span>{{ countdown.days }}</span>
            <span class="text-sm text-gray-600">Days</span>
          </div>
          <div class="flex flex-col">
            <span>{{ countdown.hours }}</span>
            <span class="text-sm text-gray-600">Hours</span>
          </div>
          <div class="flex flex-col">
            <span>{{ countdown.minutes }}</span>
            <span class="text-sm text-gray-600">Minutes</span>
          </div>
          <div class="flex flex-col">
            <span>{{ countdown.seconds }}</span>
            <span class="text-sm text-gray-600">Seconds</span>
          </div>
        </div>
      </div>

      <!-- Program Information -->
      <div class="card max-w-4xl mx-auto">
        <h2 class="text-2xl font-bold mb-6">About the Apprenticeship Program</h2>
        <div class="prose max-w-none">
          <p>The Local 39 Stationary Engineers apprenticeship program offers comprehensive training in building systems, HVAC, boilers, and facility maintenance.</p>
          <!-- Add more content here -->
        </div>

        <div class="mt-8 flex gap-4 justify-center">
          <router-link to="/pre-register" class="btn-primary">
            Pre-Register Now
          </router-link>
          <router-link v-if="isOpen" to="/register" class="btn-outline">
            Apply Now
          </router-link>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white p-6 mt-12">
      <div class="container mx-auto text-center">
        <p>&copy; {{ currentYear }} Local 39 Stationary Engineers. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';

const isOpen = ref(false);
const countdown = ref({ days: 0, hours: 0, minutes: 0, seconds: 0 });
let intervalId = null;

const currentYear = computed(() => new Date().getFullYear());

const fetchCountdown = async () => {
  try {
    const response = await axios.get('/public/countdown');
    isOpen.value = response.data.is_open;
    updateCountdown(response.data.time_remaining);
  } catch (error) {
    console.error('Failed to fetch countdown:', error);
  }
};

const updateCountdown = (seconds) => {
  if (seconds <= 0) {
    isOpen.value = true;
    if (intervalId) clearInterval(intervalId);
    return;
  }

  countdown.value = {
    days: Math.floor(seconds / 86400),
    hours: Math.floor((seconds % 86400) / 3600),
    minutes: Math.floor((seconds % 3600) / 60),
    seconds: Math.floor(seconds % 60),
  };
};

onMounted(() => {
  fetchCountdown();
  intervalId = setInterval(fetchCountdown, 1000);
});

onUnmounted(() => {
  if (intervalId) clearInterval(intervalId);
});
</script>
