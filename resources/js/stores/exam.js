import { defineStore } from 'pinia';
import axios from 'axios';

export const useExamStore = defineStore('exam', {
  state: () => ({
    // Applicant exam state
    currentSession: null,
    currentSection: null,
    currentQuestion: null,
    answers: {},
    sectionTimer: null,
    timeRemaining: 0,
    isExamActive: false,
    examStarted: false,

    // Admin state
    sessions: [],
    questions: [],
    selectedSession: null,
    sessionMonitoring: {},
    questionBankStats: null,
  }),

  getters: {
    hasActiveSession: (state) => !!state.currentSession,
    currentSectionNumber: (state) => state.currentSection?.section_number || 0,
    totalSections: (state) => 5, // Fixed 5 sections
    answeredQuestionsCount: (state) => Object.keys(state.answers).length,
    formattedTimeRemaining: (state) => {
      const minutes = Math.floor(state.timeRemaining / 60);
      const seconds = state.timeRemaining % 60;
      return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    },
    isLastSection: (state) => state.currentSection?.section_number === 5,
  },

  actions: {
    // Applicant actions
    async fetchActiveSession() {
      try {
        const response = await axios.get('/api/exams/active-session');
        this.currentSession = response.data.session;
        return response.data;
      } catch (error) {
        if (error.response?.status !== 404) {
          throw error;
        }
        return null;
      }
    },

    async startExam(sessionId) {
      try {
        const response = await axios.post(`/api/exams/sessions/${sessionId}/start`);
        this.currentSession = response.data.session;
        this.examStarted = true;
        this.isExamActive = true;
        await this.loadSection(1);
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    async loadSection(sectionNumber) {
      try {
        const response = await axios.get(
          `/api/exams/sessions/${this.currentSession.id}/sections/${sectionNumber}`
        );
        this.currentSection = response.data.section;
        this.currentQuestion = response.data.question;
        this.timeRemaining = response.data.section.time_limit * 60; // Convert to seconds
        this.startSectionTimer();
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    async submitAnswer(answer) {
      try {
        const response = await axios.post(
          `/api/exams/sessions/${this.currentSession.id}/answers`,
          {
            question_id: this.currentQuestion.id,
            section_number: this.currentSection.section_number,
            answer: answer,
          }
        );
        this.answers[this.currentQuestion.id] = answer;
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    async nextQuestion() {
      try {
        const response = await axios.get(
          `/api/exams/sessions/${this.currentSession.id}/sections/${this.currentSection.section_number}/next-question`
        );

        if (response.data.question) {
          this.currentQuestion = response.data.question;
          return response.data;
        } else {
          // No more questions in this section
          return null;
        }
      } catch (error) {
        throw error;
      }
    },

    async finishSection() {
      try {
        const response = await axios.post(
          `/api/exams/sessions/${this.currentSession.id}/sections/${this.currentSection.section_number}/finish`
        );
        this.stopSectionTimer();
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    async nextSection() {
      const nextSectionNumber = this.currentSection.section_number + 1;
      if (nextSectionNumber <= 5) {
        await this.loadSection(nextSectionNumber);
      }
    },

    async finishExam() {
      try {
        const response = await axios.post(
          `/api/exams/sessions/${this.currentSession.id}/finish`
        );
        this.stopSectionTimer();
        this.isExamActive = false;
        this.currentSession = null;
        this.currentSection = null;
        this.currentQuestion = null;
        this.answers = {};
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    startSectionTimer() {
      this.stopSectionTimer();
      this.sectionTimer = setInterval(() => {
        if (this.timeRemaining > 0) {
          this.timeRemaining--;
        } else {
          this.handleTimeUp();
        }
      }, 1000);
    },

    stopSectionTimer() {
      if (this.sectionTimer) {
        clearInterval(this.sectionTimer);
        this.sectionTimer = null;
      }
    },

    async handleTimeUp() {
      this.stopSectionTimer();
      await this.finishSection();

      if (!this.isLastSection) {
        await this.nextSection();
      } else {
        await this.finishExam();
      }
    },

    // Admin actions
    async fetchSessions(filters = {}) {
      try {
        const response = await axios.get('/api/admin/exam-sessions', {
          params: filters,
        });
        this.sessions = response.data.sessions;
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    async createSession(sessionData) {
      try {
        const response = await axios.post('/api/admin/exam-sessions', sessionData);
        this.sessions.unshift(response.data.session);
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    async updateSession(sessionId, sessionData) {
      try {
        const response = await axios.put(
          `/api/admin/exam-sessions/${sessionId}`,
          sessionData
        );
        const index = this.sessions.findIndex((s) => s.id === sessionId);
        if (index !== -1) {
          this.sessions[index] = response.data.session;
        }
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    async deleteSession(sessionId) {
      try {
        await axios.delete(`/api/admin/exam-sessions/${sessionId}`);
        this.sessions = this.sessions.filter((s) => s.id !== sessionId);
      } catch (error) {
        throw error;
      }
    },

    async activateSession(sessionId) {
      try {
        const response = await axios.post(
          `/api/admin/exam-sessions/${sessionId}/activate`
        );
        const index = this.sessions.findIndex((s) => s.id === sessionId);
        if (index !== -1) {
          this.sessions[index] = response.data.session;
        }
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    async monitorSession(sessionId) {
      try {
        const response = await axios.get(
          `/api/admin/exam-sessions/${sessionId}/monitor`
        );
        this.sessionMonitoring[sessionId] = response.data;
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    // Question bank actions
    async fetchQuestions(filters = {}) {
      try {
        const response = await axios.get('/api/admin/questions', {
          params: filters,
        });
        this.questions = response.data.questions;
        this.questionBankStats = response.data.stats;
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    async createQuestion(questionData) {
      try {
        const response = await axios.post('/api/admin/questions', questionData);
        this.questions.unshift(response.data.question);
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    async updateQuestion(questionId, questionData) {
      try {
        const response = await axios.put(
          `/api/admin/questions/${questionId}`,
          questionData
        );
        const index = this.questions.findIndex((q) => q.id === questionId);
        if (index !== -1) {
          this.questions[index] = response.data.question;
        }
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    async deleteQuestion(questionId) {
      try {
        await axios.delete(`/api/admin/questions/${questionId}`);
        this.questions = this.questions.filter((q) => q.id !== questionId);
      } catch (error) {
        throw error;
      }
    },

    async bulkImportQuestions(file, section) {
      try {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('section', section);

        const response = await axios.post(
          '/api/admin/questions/bulk-import',
          formData,
          {
            headers: { 'Content-Type': 'multipart/form-data' },
          }
        );
        await this.fetchQuestions();
        return response.data;
      } catch (error) {
        throw error;
      }
    },

    // Reset state
    resetExamState() {
      this.currentSession = null;
      this.currentSection = null;
      this.currentQuestion = null;
      this.answers = {};
      this.stopSectionTimer();
      this.timeRemaining = 0;
      this.isExamActive = false;
      this.examStarted = false;
    },
  },
});
