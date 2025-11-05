<template>
  <div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Dispatch Management</h1>
        <p class="text-gray-600 mt-2">Manage dispatch requests, match candidates, and send offers</p>
      </div>

      <!-- Tabs -->
      <div class="bg-white rounded-lg shadow-md">
        <div class="border-b border-gray-200">
          <nav class="flex -mb-px">
            <button @click="activeTab = 'requests'" :class="activeTab === 'requests' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm">
              Dispatch Requests
            </button>
            <button @click="activeTab = 'offers'" :class="activeTab === 'offers' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm">
              Dispatch Offers
            </button>
            <button @click="activeTab = 'rankings'" :class="activeTab === 'rankings' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm">
              Rankings
            </button>
            <button @click="activeTab = 'indentures'" :class="activeTab === 'indentures' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm">
              Indentures
            </button>
          </nav>
        </div>

        <!-- Dispatch Requests Tab -->
        <div v-show="activeTab === 'requests'" class="p-6">
          <div class="flex justify-between items-center mb-6">
            <div class="flex space-x-3">
              <select v-model="requestFilters.status" @change="loadDispatchRequests" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
              <input v-model="requestFilters.search" @input="searchDispatchRequests" type="text" placeholder="Search company..." class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
            </div>
            <button @click="loadDispatchRequests" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              Refresh
            </button>
          </div>

          <!-- Requests List -->
          <div v-if="loadingRequests" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600">Loading dispatch requests...</p>
          </div>

          <div v-else-if="dispatchRequests.length === 0" class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="text-gray-600">No dispatch requests found.</p>
          </div>

          <div v-else class="space-y-4">
            <div v-for="request in dispatchRequests" :key="request.id" class="border rounded-lg p-4 hover:shadow-md transition-shadow">
              <div class="flex justify-between items-start">
                <div class="flex-1">
                  <div class="flex items-center space-x-3 mb-2">
                    <h3 class="text-lg font-semibold text-gray-800">{{ request.company_name }}</h3>
                    <span :class="getStatusBadgeClass(request.status)" class="px-3 py-1 rounded-full text-xs font-medium">
                      {{ request.status }}
                    </span>
                  </div>
                  <p class="text-gray-600 text-sm mb-3">{{ request.job_description }}</p>
                  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                      <p class="text-gray-500">Location</p>
                      <p class="font-medium">{{ request.job_location_city }}, {{ request.job_location_state }}</p>
                    </div>
                    <div>
                      <p class="text-gray-500">Job Type</p>
                      <p class="font-medium capitalize">{{ request.job_type }}</p>
                    </div>
                    <div>
                      <p class="text-gray-500">Positions Needed</p>
                      <p class="font-medium">{{ request.positions_needed }}</p>
                    </div>
                    <div>
                      <p class="text-gray-500">Start Date</p>
                      <p class="font-medium">{{ formatDate(request.start_date) }}</p>
                    </div>
                  </div>
                  <div v-if="request.offers_statistics" class="mt-3 flex space-x-4 text-sm">
                    <span class="text-gray-600">Offers: <strong>{{ request.offers_statistics.total_sent }}</strong></span>
                    <span class="text-green-600">Accepted: <strong>{{ request.offers_statistics.accepted }}</strong></span>
                    <span class="text-red-600">Declined: <strong>{{ request.offers_statistics.declined }}</strong></span>
                    <span class="text-yellow-600">Pending: <strong>{{ request.offers_statistics.pending }}</strong></span>
                  </div>
                </div>
                <div class="ml-4 flex flex-col space-y-2">
                  <button @click="findCandidates(request)" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                    Find Candidates
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Offers Tab -->
        <div v-show="activeTab === 'offers'" class="p-6">
          <div class="flex justify-between items-center mb-6">
            <select v-model="offerFilters.status" @change="loadOffers" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="accepted">Accepted</option>
              <option value="declined">Declined</option>
            </select>
            <button @click="loadOffers" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Refresh</button>
          </div>

          <div v-if="loadingOffers" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600">Loading offers...</p>
          </div>

          <div v-else-if="offers.length === 0" class="text-center py-12">
            <p class="text-gray-600">No dispatch offers found.</p>
          </div>

          <div v-else>
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applicant</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Offered At</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="offer in offers" :key="offer.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ offer.applicant?.first_name }} {{ offer.applicant?.last_name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ offer.dispatch_request?.company_name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDateTime(offer.offered_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getOfferStatusBadgeClass(offer.response_status)" class="px-2 py-1 text-xs font-medium rounded-full">
                      {{ offer.response_status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Rankings Tab -->
        <div v-show="activeTab === 'rankings'" class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-800">Applicant Rankings</h2>
            <div class="flex space-x-3">
              <button @click="generateRankings" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                Generate Rankings
              </button>
              <button @click="exportRankings" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Export to Excel
              </button>
            </div>
          </div>

          <div v-if="loadingRankings" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600">Loading rankings...</p>
          </div>

          <div v-else-if="rankings.length === 0" class="text-center py-12">
            <p class="text-gray-600">No rankings available. Generate rankings from completed exams.</p>
          </div>

          <div v-else>
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applicant</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam Score</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="ranking in rankings" :key="ranking.id">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-lg font-bold text-blue-600">#{{ ranking.rank }}</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ ranking.applicant?.first_name }} {{ ranking.applicant?.last_name }}</div>
                    <div class="text-sm text-gray-500">{{ ranking.applicant?.confirmation_number }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ ranking.exam_score }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ ranking.applicant?.status || 'Active' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Indentures Tab -->
        <div v-show="activeTab === 'indentures'" class="p-6">
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Indenture Contracts</h2>
            <button @click="loadIndentures" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Refresh</button>
          </div>

          <div v-if="loadingIndentures" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600">Loading indentures...</p>
          </div>

          <div v-else-if="indentures.length === 0" class="text-center py-12">
            <p class="text-gray-600">No indentures yet.</p>
          </div>

          <div v-else>
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applicant</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="indenture in indentures" :key="indenture.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ indenture.applicant?.first_name }} {{ indenture.applicant?.last_name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ indenture.company_name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(indenture.start_date) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getIndentureStatusBadgeClass(indenture.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                      {{ indenture.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <button @click="downloadIndenture(indenture.id)" class="text-blue-600 hover:text-blue-800">Download PDF</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Find Candidates Modal -->
    <div v-if="showCandidatesModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-8 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Find Candidates for {{ selectedRequest?.company_name }}</h2>

        <div v-if="loadingCandidates" class="text-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p class="text-gray-600">Finding candidates...</p>
        </div>

        <div v-else-if="candidates.length === 0" class="text-center py-12">
          <p class="text-gray-600">No eligible candidates found.</p>
        </div>

        <div v-else>
          <p class="mb-4 text-gray-600">Select candidates to send dispatch offers:</p>
          <div class="space-y-2 mb-6">
            <div v-for="candidate in candidates" :key="candidate.applicant_id" class="border rounded-lg p-4 flex items-center hover:bg-gray-50">
              <input type="checkbox" v-model="selectedCandidates" :value="candidate.applicant_id" class="mr-4 h-5 w-5 text-blue-600" />
              <div class="flex-1">
                <div class="flex items-center space-x-3">
                  <span class="text-lg font-bold text-blue-600">#{{ candidate.rank }}</span>
                  <span class="font-medium text-gray-900">{{ candidate.applicant.first_name }} {{ candidate.applicant.last_name }}</span>
                  <span class="text-sm text-gray-500">({{ candidate.applicant.confirmation_number }})</span>
                </div>
                <div class="text-sm text-gray-600 mt-1">
                  Score: {{ candidate.exam_score }} | Phone: {{ candidate.applicant.phone_primary }}
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end space-x-3">
            <button @click="closeCandidatesModal" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
              Cancel
            </button>
            <button @click="sendOffers" :disabled="selectedCandidates.length === 0" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
              Send Offers ({{ selectedCandidates.length }})
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

const activeTab = ref('requests');
const loadingRequests = ref(false);
const loadingOffers = ref(false);
const loadingRankings = ref(false);
const loadingIndentures = ref(false);
const loadingCandidates = ref(false);

const dispatchRequests = ref([]);
const offers = ref([]);
const rankings = ref([]);
const indentures = ref([]);
const candidates = ref([]);

const requestFilters = ref({ status: '', search: '' });
const offerFilters = ref({ status: '' });

const showCandidatesModal = ref(false);
const selectedRequest = ref(null);
const selectedCandidates = ref([]);

onMounted(() => {
  loadDispatchRequests();
});

watch(activeTab, (newTab) => {
  if (newTab === 'offers' && offers.value.length === 0) loadOffers();
  if (newTab === 'rankings' && rankings.value.length === 0) loadRankings();
  if (newTab === 'indentures' && indentures.value.length === 0) loadIndentures();
});

async function loadDispatchRequests() {
  try {
    loadingRequests.value = true;
    const params = {};
    if (requestFilters.value.status) params.status = requestFilters.value.status;
    if (requestFilters.value.search) params.search = requestFilters.value.search;

    const response = await axios.get('/api/admin/dispatch-requests', { params });
    dispatchRequests.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error loading dispatch requests:', error);
    alert('Failed to load dispatch requests');
  } finally {
    loadingRequests.value = false;
  }
}

function searchDispatchRequests() {
  clearTimeout(window.searchTimeout);
  window.searchTimeout = setTimeout(loadDispatchRequests, 500);
}

async function loadOffers() {
  try {
    loadingOffers.value = true;
    const params = {};
    if (offerFilters.value.status) params.status = offerFilters.value.status;

    const response = await axios.get('/api/admin/dispatch-offers', { params });
    offers.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error loading offers:', error);
    alert('Failed to load offers');
  } finally {
    loadingOffers.value = false;
  }
}

async function loadRankings() {
  try {
    loadingRankings.value = true;
    const response = await axios.get('/api/admin/rankings');
    rankings.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error loading rankings:', error);
    alert('Failed to load rankings');
  } finally {
    loadingRankings.value = false;
  }
}

async function loadIndentures() {
  try {
    loadingIndentures.value = true;
    const response = await axios.get('/api/admin/indentures');
    indentures.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error loading indentures:', error);
    alert('Failed to load indentures');
  } finally {
    loadingIndentures.value = false;
  }
}

async function findCandidates(request) {
  selectedRequest.value = request;
  showCandidatesModal.value = true;
  selectedCandidates.value = [];

  try {
    loadingCandidates.value = true;
    const response = await axios.post(`/api/admin/dispatch-requests/${request.id}/find-candidates`, {
      limit: 20,
    });
    candidates.value = response.data.candidates || [];
  } catch (error) {
    console.error('Error finding candidates:', error);
    alert('Failed to find candidates');
  } finally {
    loadingCandidates.value = false;
  }
}

function closeCandidatesModal() {
  showCandidatesModal.value = false;
  selectedRequest.value = null;
  selectedCandidates.value = [];
  candidates.value = [];
}

async function sendOffers() {
  if (selectedCandidates.value.length === 0) return;

  try {
    await axios.post('/api/admin/dispatch-offers', {
      dispatch_request_id: selectedRequest.value.id,
      applicant_ids: selectedCandidates.value,
      response_deadline_hours: 72,
    });

    alert(`Successfully sent ${selectedCandidates.value.length} dispatch offer(s)!`);
    closeCandidatesModal();
    loadDispatchRequests();
  } catch (error) {
    console.error('Error sending offers:', error);
    alert('Failed to send offers');
  }
}

async function generateRankings() {
  if (!confirm('Generate rankings from all completed exams? This may update existing rankings.')) return;

  try {
    const response = await axios.post('/api/admin/rankings/generate');
    alert(response.data.message);
    loadRankings();
  } catch (error) {
    console.error('Error generating rankings:', error);
    alert('Failed to generate rankings');
  }
}

async function exportRankings() {
  try {
    const response = await axios.get('/api/admin/rankings/export', { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `rankings_${new Date().toISOString().split('T')[0]}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (error) {
    console.error('Error exporting rankings:', error);
    alert('Failed to export rankings');
  }
}

async function downloadIndenture(indentureId) {
  try {
    const response = await axios.get(`/api/admin/indentures/${indentureId}/download`, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `indenture_${indentureId}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (error) {
    console.error('Error downloading indenture:', error);
    alert('Failed to download indenture');
  }
}

function getStatusBadgeClass(status) {
  const classes = {
    pending: 'bg-yellow-200 text-yellow-800',
    in_progress: 'bg-blue-200 text-blue-800',
    completed: 'bg-green-200 text-green-800',
    cancelled: 'bg-red-200 text-red-800',
  };
  return classes[status] || 'bg-gray-200 text-gray-800';
}

function getOfferStatusBadgeClass(status) {
  const classes = {
    pending: 'bg-yellow-200 text-yellow-800',
    accepted: 'bg-green-200 text-green-800',
    declined: 'bg-red-200 text-red-800',
    expired: 'bg-gray-200 text-gray-800',
  };
  return classes[status] || 'bg-gray-200 text-gray-800';
}

function getIndentureStatusBadgeClass(status) {
  const classes = {
    draft: 'bg-gray-200 text-gray-800',
    fully_signed: 'bg-blue-200 text-blue-800',
    active: 'bg-green-200 text-green-800',
  };
  return classes[status] || 'bg-gray-200 text-gray-800';
}

function formatDate(date) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatDateTime(date) {
  if (!date) return '-';
  return new Date(date).toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>
