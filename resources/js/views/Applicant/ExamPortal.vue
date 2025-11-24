<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <p class="text-gray-600">Loading exam...</p>
      </div>
    </div>

    <!-- No Active Session -->
    <div v-else-if="!examStore.hasActiveSession && !loading" class="container mx-auto px-4 py-8">
      <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
          <svg class="w-24 h-24 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <h2 class="text-2xl font-bold text-gray-800 mb-2">No Active Exam Session</h2>
          <p class="text-gray-600 mb-6">There is currently no active exam session available. Please check back later or contact your administrator.</p>
          <router-link to="/applicant/dashboard" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            Back to Dashboard
          </router-link>
        </div>
      </div>
    </div>

    <!-- Exam Not Started - Instructions -->
    <div v-else-if="!examStore.examStarted" class="container mx-auto px-4 py-8">
      <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-8">
          <h1 class="text-3xl font-bold text-gray-800 mb-6">Exam Instructions</h1>

          <div class="prose max-w-none mb-8">
            <h2 class="text-xl font-semibold mb-4">Important Information:</h2>
            <ul class="space-y-2 text-gray-700">
              <li>This exam consists of <strong>5 sections</strong></li>
              <li>Each section has a <strong>time limit of {{ sessionTimeLimit }} minutes</strong></li>
              <li>You cannot go back to previous sections once completed</li>
              <li>Questions are delivered <strong>one at a time</strong></li>
              <li>You must answer each question before moving to the next</li>
              <li>When the timer expires, the section will automatically end</li>
              <li>Make sure you have a stable internet connection</li>
              <li>Do not refresh the page during the exam</li>
            </ul>

            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mt-6">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm text-yellow-700">
                    Once you start the exam, you cannot pause or stop it. Make sure you are ready before clicking the "Start Exam" button.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-center space-x-4">
            <router-link to="/applicant/dashboard" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
              Cancel
            </router-link>
            <button @click="startExam" :disabled="startingExam" class="px-8 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
              <span v-if="startingExam">Starting...</span>
              <span v-else>Start Exam</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Exam -->
    <div v-else-if="examStore.isExamActive" class="min-h-screen bg-gray-100">
      <!-- Exam Header -->
      <div class="bg-white shadow-md sticky top-0 z-10">
        <div class="container mx-auto px-4 py-4">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-xl font-bold text-gray-800">
                Section {{ examStore.currentSectionNumber }} of 5
              </h1>
              <p class="text-sm text-gray-600">
                Question {{ currentQuestionIndex }} of {{ totalQuestionsInSection }}
              </p>
            </div>

            <!-- Timer -->
            <div class="flex items-center space-x-4">
              <div class="text-right">
                <p class="text-sm text-gray-600">Time Remaining</p>
                <p class="text-2xl font-bold" :class="timeWarningClass">
                  {{ examStore.formattedTimeRemaining }}
                </p>
              </div>
              <svg class="w-8 h-8" :class="timeWarningClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="mt-4">
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" :style="{ width: progressPercentage + '%' }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Question Display -->
      <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
          <div class="bg-white rounded-lg shadow-md p-8">
            <div v-if="examStore.currentQuestion" class="space-y-6">
              <!-- Question Text -->
              <div class="border-b pb-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Question {{ currentQuestionIndex }}</h2>
                <p class="text-lg text-gray-700 leading-relaxed whitespace-pre-wrap">{{ examStore.currentQuestion.question_text }}</p>
              </div>

              <!-- Multiple Choice Options -->
              <div v-if="examStore.currentQuestion.type === 'multiple_choice'" class="space-y-3">
                <label v-for="(option, index) in examStore.currentQuestion.options" :key="index" class="flex items-start p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" :class="selectedAnswer === option ? 'border-blue-600 bg-blue-50' : 'border-gray-300'">
                  <input type="radio" :value="option" v-model="selectedAnswer" class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                  <span class="ml-3 text-gray-700">{{ option }}</span>
                </label>
              </div>

              <!-- True/False Options -->
              <div v-else-if="examStore.currentQuestion.type === 'true_false'" class="space-y-3">
                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" :class="selectedAnswer === 'True' ? 'border-blue-600 bg-blue-50' : 'border-gray-300'">
                  <input type="radio" value="True" v-model="selectedAnswer" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                  <span class="ml-3 text-gray-700">True</span>
                </label>
                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" :class="selectedAnswer === 'False' ? 'border-blue-600 bg-blue-50' : 'border-gray-300'">
                  <input type="radio" value="False" v-model="selectedAnswer" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                  <span class="ml-3 text-gray-700">False</span>
                </label>
              </div>

              <!-- Short Answer -->
              <div v-else-if="examStore.currentQuestion.type === 'short_answer'">
                <textarea v-model="selectedAnswer" rows="6" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Type your answer here..."></textarea>
              </div>

              <!-- Action Buttons -->
              <div class="flex justify-between pt-6 border-t">
                <button v-if="hasMoreQuestions" @click="submitAndNext" :disabled="!selectedAnswer || submitting" class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium">
                  <span v-if="submitting">Submitting...</span>
                  <span v-else>Submit & Next Question</span>
                </button>
                <button v-else @click="finishSection" :disabled="!selectedAnswer || submitting" class="px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium">
                  <span v-if="submitting">Finishing...</span>
                  <span v-else>Finish Section {{ examStore.currentSectionNumber }}</span>
                </button>
              </div>
            </div>

            <!-- Loading Next Question -->
            <div v-else class="text-center py-12">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
              <p class="text-gray-600">Loading next question...</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Exam Completed -->
    <div v-else class="container mx-auto px-4 py-8">
      <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
          <svg class="w-24 h-24 mx-auto text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <h2 class="text-3xl font-bold text-gray-800 mb-4">Exam Completed!</h2>
          <p class="text-gray-600 mb-8">You have successfully completed all sections of the exam. Your responses have been submitted for grading.</p>
          <router-link to="/applicant/dashboard" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 font-medium">
            Return to Dashboard
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useExamStore } from '@/stores/exam';
import { useRouter } from 'vue-router';

const examStore = useExamStore();
const router = useRouter();

const loading = ref(true);
const startingExam = ref(false);
const selectedAnswer = ref(null);
const submitting = ref(false);
const currentQuestionIndex = ref(1);
const totalQuestionsInSection = ref(20); // Default, will be updated
const hasMoreQuestions = ref(true);

const sessionTimeLimit = computed(() => {
  return examStore.currentSession?.time_per_section || 30;
});

const progressPercentage = computed(() => {
  if (!totalQuestionsInSection.value) return 0;
  return (currentQuestionIndex.value / totalQuestionsInSection.value) * 100;
});

const timeWarningClass = computed(() => {
  if (examStore.timeRemaining < 60) {
    return 'text-red-600';
  } else if (examStore.timeRemaining < 300) {
    return 'text-orange-600';
  }
  return 'text-blue-600';
});

async function startExam() {
  try {
    startingExam.value = true;
    await examStore.startExam(examStore.currentSession.id);
    currentQuestionIndex.value = 1;
    selectedAnswer.value = null;
  } catch (error) {
    console.error('Error starting exam:', error);
    alert('Failed to start exam. Please try again.');
  } finally {
    startingExam.value = false;
  }
}

async function submitAndNext() {
  if (!selectedAnswer.value) return;

  try {
    submitting.value = true;
    await examStore.submitAnswer(selectedAnswer.value);

    const result = await examStore.nextQuestion();

    if (result && result.question) {
      currentQuestionIndex.value++;
      selectedAnswer.value = null;
      hasMoreQuestions.value = true;
    } else {
      hasMoreQuestions.value = false;
    }
  } catch (error) {
    console.error('Error submitting answer:', error);
    alert('Failed to submit answer. Please try again.');
  } finally {
    submitting.value = false;
  }
}

async function finishSection() {
  if (!selectedAnswer.value) return;

  try {
    submitting.value = true;

    // Submit the last answer
    await examStore.submitAnswer(selectedAnswer.value);

    // Finish the current section
    await examStore.finishSection();

    // Check if there are more sections
    if (!examStore.isLastSection) {
      // Move to next section
      await examStore.nextSection();
      currentQuestionIndex.value = 1;
      selectedAnswer.value = null;
      hasMoreQuestions.value = true;
    } else {
      // Finish the entire exam
      await examStore.finishExam();
    }
  } catch (error) {
    console.error('Error finishing section:', error);
    alert('Failed to finish section. Please try again.');
  } finally {
    submitting.value = false;
  }
}

onMounted(async () => {
  try {
    await examStore.fetchActiveSession();
  } catch (error) {
    console.error('Error fetching active session:', error);
  } finally {
    loading.value = false;
  }
});

onUnmounted(() => {
  // Don't reset exam state on unmount in case of accidental navigation
  // examStore.stopSectionTimer();
});

// Prevent accidental page refresh
window.addEventListener('beforeunload', (e) => {
  if (examStore.isExamActive) {
    e.preventDefault();
    e.returnValue = '';
  }
});
</script>
