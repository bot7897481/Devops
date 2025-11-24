<template>
  <div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Exam Management</h1>
        <p class="text-gray-600 mt-2">Manage exam sessions, questions, and monitor applicant progress</p>
      </div>

      <!-- Tabs -->
      <div class="bg-white rounded-lg shadow-md">
        <div class="border-b border-gray-200">
          <nav class="flex -mb-px">
            <button @click="activeTab = 'sessions'" :class="activeTab === 'sessions' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm">
              Exam Sessions
            </button>
            <button @click="activeTab = 'questions'" :class="activeTab === 'questions' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm">
              Question Bank
            </button>
            <button @click="activeTab = 'monitoring'" :class="activeTab === 'monitoring' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="py-4 px-6 border-b-2 font-medium text-sm">
              Live Monitoring
            </button>
          </nav>
        </div>

        <!-- Sessions Tab -->
        <div v-show="activeTab === 'sessions'" class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-800">Exam Sessions</h2>
            <button @click="showCreateSessionModal = true" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
              </svg>
              Create Session
            </button>
          </div>

          <!-- Sessions List -->
          <div v-if="loading" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600">Loading sessions...</p>
          </div>

          <div v-else-if="examStore.sessions.length === 0" class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="text-gray-600">No exam sessions yet. Create one to get started.</p>
          </div>

          <div v-else class="space-y-4">
            <div v-for="session in examStore.sessions" :key="session.id" class="border rounded-lg p-4 hover:shadow-md transition-shadow">
              <div class="flex justify-between items-start">
                <div class="flex-1">
                  <div class="flex items-center space-x-3 mb-2">
                    <h3 class="text-lg font-semibold text-gray-800">{{ session.name }}</h3>
                    <span :class="getStatusBadgeClass(session.status)" class="px-3 py-1 rounded-full text-xs font-medium">
                      {{ session.status }}
                    </span>
                  </div>
                  <p class="text-gray-600 text-sm mb-3">{{ session.description }}</p>
                  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                      <p class="text-gray-500">Scheduled Date</p>
                      <p class="font-medium">{{ formatDate(session.scheduled_date) }}</p>
                    </div>
                    <div>
                      <p class="text-gray-500">Time per Section</p>
                      <p class="font-medium">{{ session.time_per_section }} minutes</p>
                    </div>
                    <div>
                      <p class="text-gray-500">Applicants</p>
                      <p class="font-medium">{{ session.applicants_count || 0 }}</p>
                    </div>
                    <div>
                      <p class="text-gray-500">Completed</p>
                      <p class="font-medium">{{ session.completed_count || 0 }}</p>
                    </div>
                  </div>
                </div>
                <div class="ml-4 flex flex-col space-y-2">
                  <button v-if="session.status === 'draft'" @click="activateSession(session.id)" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                    Activate
                  </button>
                  <button @click="editSession(session)" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                    Edit
                  </button>
                  <button @click="deleteSession(session.id)" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                    Delete
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Questions Tab -->
        <div v-show="activeTab === 'questions'" class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-800">Question Bank</h2>
            <div class="flex space-x-3">
              <button @click="showBulkImportModal = true" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                Bulk Import
              </button>
              <button @click="showCreateQuestionModal = true" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Question
              </button>
            </div>
          </div>

          <!-- Question Stats -->
          <div v-if="examStore.questionBankStats" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div v-for="section in 5" :key="section" class="bg-blue-50 rounded-lg p-4">
              <p class="text-sm text-gray-600">Section {{ section }}</p>
              <p class="text-2xl font-bold text-blue-600">{{ getSectionQuestionCount(section) }}</p>
              <p class="text-xs text-gray-500">questions</p>
            </div>
          </div>

          <!-- Filter -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Section</label>
            <select v-model="questionFilter" @change="loadQuestions" class="w-48 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="">All Sections</option>
              <option v-for="section in 5" :key="section" :value="section">Section {{ section }}</option>
            </select>
          </div>

          <!-- Questions List -->
          <div v-if="loadingQuestions" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600">Loading questions...</p>
          </div>

          <div v-else-if="examStore.questions.length === 0" class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-gray-600">No questions yet. Add questions or bulk import them.</p>
          </div>

          <div v-else class="space-y-4">
            <div v-for="question in examStore.questions" :key="question.id" class="border rounded-lg p-4 hover:shadow-md transition-shadow">
              <div class="flex justify-between items-start">
                <div class="flex-1">
                  <div class="flex items-center space-x-3 mb-2">
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium">
                      Section {{ question.section }}
                    </span>
                    <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-xs font-medium">
                      {{ question.type }}
                    </span>
                    <span class="text-gray-500 text-xs">Points: {{ question.points }}</span>
                  </div>
                  <p class="text-gray-800 mb-2">{{ question.question_text }}</p>
                  <div v-if="question.type === 'multiple_choice' && question.options" class="text-sm text-gray-600">
                    <p class="font-medium">Options:</p>
                    <ul class="list-disc list-inside ml-2">
                      <li v-for="(option, index) in question.options" :key="index" :class="option === question.correct_answer ? 'text-green-600 font-medium' : ''">
                        {{ option }}
                      </li>
                    </ul>
                  </div>
                  <div v-else-if="question.type === 'true_false'" class="text-sm">
                    <span class="text-gray-600">Correct Answer:</span>
                    <span class="font-medium text-green-600 ml-2">{{ question.correct_answer }}</span>
                  </div>
                </div>
                <div class="ml-4 flex flex-col space-y-2">
                  <button @click="editQuestion(question)" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                    Edit
                  </button>
                  <button @click="deleteQuestion(question.id)" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                    Delete
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Monitoring Tab -->
        <div v-show="activeTab === 'monitoring'" class="p-6">
          <h2 class="text-xl font-semibold text-gray-800 mb-6">Live Exam Monitoring</h2>

          <div v-if="examStore.sessions.filter(s => s.status === 'active').length === 0" class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-gray-600">No active exam sessions to monitor.</p>
          </div>

          <div v-else class="space-y-6">
            <div v-for="session in examStore.sessions.filter(s => s.status === 'active')" :key="'monitor-' + session.id" class="border rounded-lg p-6">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">{{ session.name }}</h3>
                <button @click="loadSessionMonitoring(session.id)" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm flex items-center">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                  </svg>
                  Refresh
                </button>
              </div>

              <div v-if="examStore.sessionMonitoring[session.id]" class="space-y-4">
                <div class="grid grid-cols-4 gap-4">
                  <div class="bg-blue-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600">Total Applicants</p>
                    <p class="text-2xl font-bold text-blue-600">{{ examStore.sessionMonitoring[session.id].total }}</p>
                  </div>
                  <div class="bg-green-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600">In Progress</p>
                    <p class="text-2xl font-bold text-green-600">{{ examStore.sessionMonitoring[session.id].in_progress }}</p>
                  </div>
                  <div class="bg-yellow-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600">Not Started</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ examStore.sessionMonitoring[session.id].not_started }}</p>
                  </div>
                  <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600">Completed</p>
                    <p class="text-2xl font-bold text-gray-600">{{ examStore.sessionMonitoring[session.id].completed }}</p>
                  </div>
                </div>

                <!-- Applicant Progress Table -->
                <div class="mt-6">
                  <h4 class="font-semibold text-gray-800 mb-3">Applicant Progress</h4>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                      <thead class="bg-gray-50">
                        <tr>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applicant</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Section</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Started At</th>
                        </tr>
                      </thead>
                      <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="applicant in examStore.sessionMonitoring[session.id].applicants" :key="applicant.id">
                          <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ applicant.name }}
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="getStatusBadgeClass(applicant.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                              {{ applicant.status }}
                            </span>
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ applicant.current_section || '-' }}
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                              <div class="w-24 bg-gray-200 rounded-full h-2 mr-2">
                                <div class="bg-blue-600 h-2 rounded-full" :style="{ width: applicant.progress + '%' }"></div>
                              </div>
                              <span class="text-sm text-gray-600">{{ applicant.progress }}%</span>
                            </div>
                          </td>
                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ applicant.started_at ? formatTime(applicant.started_at) : '-' }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Session Modal -->
    <div v-if="showCreateSessionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-8 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ editingSession ? 'Edit Session' : 'Create Exam Session' }}</h2>

        <form @submit.prevent="saveSession" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Session Name</label>
            <input v-model="sessionForm.name" type="text" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <textarea v-model="sessionForm.description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Scheduled Date</label>
            <input v-model="sessionForm.scheduled_date" type="date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Time per Section (minutes)</label>
            <input v-model.number="sessionForm.time_per_section" type="number" min="10" max="120" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <button type="button" @click="closeSessionModal" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
              Cancel
            </button>
            <button type="submit" :disabled="savingSession" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
              {{ savingSession ? 'Saving...' : (editingSession ? 'Update' : 'Create') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Create/Edit Question Modal -->
    <div v-if="showCreateQuestionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-8 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">{{ editingQuestion ? 'Edit Question' : 'Add Question' }}</h2>

        <form @submit.prevent="saveQuestion" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Section</label>
              <select v-model.number="questionForm.section" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Select Section</option>
                <option v-for="section in 5" :key="section" :value="section">Section {{ section }}</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Question Type</label>
              <select v-model="questionForm.type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Select Type</option>
                <option value="multiple_choice">Multiple Choice</option>
                <option value="true_false">True/False</option>
                <option value="short_answer">Short Answer</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Question Text</label>
            <textarea v-model="questionForm.question_text" rows="4" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
          </div>

          <!-- Multiple Choice Options -->
          <div v-if="questionForm.type === 'multiple_choice'" class="space-y-3">
            <label class="block text-sm font-medium text-gray-700">Options</label>
            <div v-for="(option, index) in questionForm.options" :key="index" class="flex items-center space-x-2">
              <input v-model="questionForm.options[index]" type="text" :placeholder="'Option ' + (index + 1)" required class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              <button v-if="questionForm.options.length > 2" type="button" @click="questionForm.options.splice(index, 1)" class="p-2 text-red-600 hover:bg-red-50 rounded">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
            <button v-if="questionForm.options.length < 6" type="button" @click="questionForm.options.push('')" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
              + Add Option
            </button>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Correct Answer</label>
              <select v-model="questionForm.correct_answer" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Select Correct Answer</option>
                <option v-for="(option, index) in questionForm.options.filter(o => o)" :key="index" :value="option">{{ option }}</option>
              </select>
            </div>
          </div>

          <!-- True/False Answer -->
          <div v-if="questionForm.type === 'true_false'">
            <label class="block text-sm font-medium text-gray-700 mb-2">Correct Answer</label>
            <select v-model="questionForm.correct_answer" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="">Select Answer</option>
              <option value="True">True</option>
              <option value="False">False</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Points</label>
            <input v-model.number="questionForm.points" type="number" min="1" max="100" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <button type="button" @click="closeQuestionModal" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
              Cancel
            </button>
            <button type="submit" :disabled="savingQuestion" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
              {{ savingQuestion ? 'Saving...' : (editingQuestion ? 'Update' : 'Add') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Bulk Import Modal -->
    <div v-if="showBulkImportModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-8 max-w-2xl w-full mx-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Bulk Import Questions</h2>

        <form @submit.prevent="bulkImportQuestions" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Section</label>
            <select v-model.number="bulkImportForm.section" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="">Select Section</option>
              <option v-for="section in 5" :key="section" :value="section">Section {{ section }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Upload CSV File</label>
            <input @change="handleFileSelect" type="file" accept=".csv" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
            <p class="text-xs text-gray-500 mt-2">CSV Format: question_text, type, options (JSON), correct_answer, points</p>
          </div>

          <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
            <p class="text-sm text-blue-700">
              <strong>CSV Example:</strong><br />
              "What is 2+2?","multiple_choice","[""2"",""3"",""4"",""5""]","4",10
            </p>
          </div>

          <div class="flex justify-end space-x-3 pt-4">
            <button type="button" @click="closeBulkImportModal" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
              Cancel
            </button>
            <button type="submit" :disabled="importing" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50">
              {{ importing ? 'Importing...' : 'Import' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useExamStore } from '@/stores/exam';

const examStore = useExamStore();

const activeTab = ref('sessions');
const loading = ref(true);
const loadingQuestions = ref(false);
const questionFilter = ref('');

// Session Management
const showCreateSessionModal = ref(false);
const editingSession = ref(null);
const savingSession = ref(false);
const sessionForm = ref({
  name: '',
  description: '',
  scheduled_date: '',
  time_per_section: 30,
});

// Question Management
const showCreateQuestionModal = ref(false);
const editingQuestion = ref(null);
const savingQuestion = ref(false);
const questionForm = ref({
  section: '',
  type: '',
  question_text: '',
  options: ['', '', '', ''],
  correct_answer: '',
  points: 10,
});

// Bulk Import
const showBulkImportModal = ref(false);
const importing = ref(false);
const bulkImportForm = ref({
  section: '',
  file: null,
});

onMounted(async () => {
  try {
    await Promise.all([examStore.fetchSessions(), examStore.fetchQuestions()]);
  } catch (error) {
    console.error('Error loading data:', error);
  } finally {
    loading.value = false;
  }
});

// Session Functions
function resetSessionForm() {
  sessionForm.value = {
    name: '',
    description: '',
    scheduled_date: '',
    time_per_section: 30,
  };
  editingSession.value = null;
}

function editSession(session) {
  editingSession.value = session;
  sessionForm.value = {
    name: session.name,
    description: session.description,
    scheduled_date: session.scheduled_date,
    time_per_section: session.time_per_section,
  };
  showCreateSessionModal.value = true;
}

async function saveSession() {
  try {
    savingSession.value = true;

    if (editingSession.value) {
      await examStore.updateSession(editingSession.value.id, sessionForm.value);
    } else {
      await examStore.createSession(sessionForm.value);
    }

    closeSessionModal();
  } catch (error) {
    console.error('Error saving session:', error);
    alert('Failed to save session. Please try again.');
  } finally {
    savingSession.value = false;
  }
}

function closeSessionModal() {
  showCreateSessionModal.value = false;
  resetSessionForm();
}

async function activateSession(sessionId) {
  if (!confirm('Are you sure you want to activate this exam session?')) return;

  try {
    await examStore.activateSession(sessionId);
    alert('Session activated successfully!');
  } catch (error) {
    console.error('Error activating session:', error);
    alert('Failed to activate session. Please try again.');
  }
}

async function deleteSession(sessionId) {
  if (!confirm('Are you sure you want to delete this session? This action cannot be undone.')) return;

  try {
    await examStore.deleteSession(sessionId);
  } catch (error) {
    console.error('Error deleting session:', error);
    alert('Failed to delete session. Please try again.');
  }
}

// Question Functions
function resetQuestionForm() {
  questionForm.value = {
    section: '',
    type: '',
    question_text: '',
    options: ['', '', '', ''],
    correct_answer: '',
    points: 10,
  };
  editingQuestion.value = null;
}

function editQuestion(question) {
  editingQuestion.value = question;
  questionForm.value = {
    section: question.section,
    type: question.type,
    question_text: question.question_text,
    options: question.options || ['', '', '', ''],
    correct_answer: question.correct_answer,
    points: question.points,
  };
  showCreateQuestionModal.value = true;
}

async function saveQuestion() {
  try {
    savingQuestion.value = true;

    const data = { ...questionForm.value };
    if (data.type !== 'multiple_choice') {
      delete data.options;
    }

    if (editingQuestion.value) {
      await examStore.updateQuestion(editingQuestion.value.id, data);
    } else {
      await examStore.createQuestion(data);
    }

    closeQuestionModal();
  } catch (error) {
    console.error('Error saving question:', error);
    alert('Failed to save question. Please try again.');
  } finally {
    savingQuestion.value = false;
  }
}

function closeQuestionModal() {
  showCreateQuestionModal.value = false;
  resetQuestionForm();
}

async function deleteQuestion(questionId) {
  if (!confirm('Are you sure you want to delete this question?')) return;

  try {
    await examStore.deleteQuestion(questionId);
  } catch (error) {
    console.error('Error deleting question:', error);
    alert('Failed to delete question. Please try again.');
  }
}

async function loadQuestions() {
  try {
    loadingQuestions.value = true;
    const filters = questionFilter.value ? { section: questionFilter.value } : {};
    await examStore.fetchQuestions(filters);
  } catch (error) {
    console.error('Error loading questions:', error);
  } finally {
    loadingQuestions.value = false;
  }
}

// Bulk Import
function handleFileSelect(event) {
  bulkImportForm.value.file = event.target.files[0];
}

async function bulkImportQuestions() {
  try {
    importing.value = true;
    await examStore.bulkImportQuestions(
      bulkImportForm.value.file,
      bulkImportForm.value.section
    );
    closeBulkImportModal();
    alert('Questions imported successfully!');
  } catch (error) {
    console.error('Error importing questions:', error);
    alert('Failed to import questions. Please check the file format and try again.');
  } finally {
    importing.value = false;
  }
}

function closeBulkImportModal() {
  showBulkImportModal.value = false;
  bulkImportForm.value = { section: '', file: null };
}

// Monitoring
async function loadSessionMonitoring(sessionId) {
  try {
    await examStore.monitorSession(sessionId);
  } catch (error) {
    console.error('Error loading monitoring data:', error);
    alert('Failed to load monitoring data. Please try again.');
  }
}

// Utility Functions
function getStatusBadgeClass(status) {
  const classes = {
    draft: 'bg-gray-200 text-gray-800',
    active: 'bg-green-200 text-green-800',
    completed: 'bg-blue-200 text-blue-800',
    in_progress: 'bg-yellow-200 text-yellow-800',
    not_started: 'bg-gray-200 text-gray-800',
  };
  return classes[status] || 'bg-gray-200 text-gray-800';
}

function formatDate(date) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

function formatTime(time) {
  if (!time) return '-';
  return new Date(time).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
  });
}

function getSectionQuestionCount(section) {
  if (!examStore.questionBankStats) return 0;
  return examStore.questionBankStats[`section_${section}`] || 0;
}
</script>
