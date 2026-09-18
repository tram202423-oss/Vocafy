/**
 * Vocabulary Matching Game Component for Alpine.js
 * File: components/matchingGame.js
 */

import { soundEffects } from '../utils/soundEffects';

export default function matchingGame(config = {}) {
  return {
    // Game Flow State: 'lobby' (configure & start) | 'playing' (game active)
    gameState: 'lobby',

    // Configuration & Data
    categories: config.categories || [],
    scope: config.initialScope || 'all', // 'all' | 'category' | 'topic'
    selectedCategoryId: config.selectedCategoryId || '',
    selectedTopicId: config.selectedTopicId || '',
    pairLimit: 6, // 4 | 6 | 8
    gameMode: 'practice', // 'practice' | 'timeAttack'
    csrfToken: config.csrfToken || '',
    isAuth: config.isAuth || false,

    // Game Play State
    cards: [],
    firstSelected: null,
    isChecking: false,
    matchedPairs: 0,
    totalPairs: 0,
    score: 0,
    streak: 0,
    maxStreak: 0,
    totalAttempts: 0,
    correctAttempts: 0,
    isLoading: false,
    isGameOver: false,
    isPaused: false,
    isMuted: soundEffects.getMuted(),

    // Timers
    elapsedSeconds: 0,
    timeAttackSeconds: 60,
    timerInterval: null,

    // Victory celebration
    showVictoryModal: false,

    /**
     * Component Lifecycle Init
     */
    init() {
      // Sync category and topic selection if passed
      if (this.scope === 'category' && this.selectedCategoryId) {
        this.syncCategorySelection(this.selectedCategoryId);
      } else if (this.scope === 'topic') {
        if (!this.selectedCategoryId && this.categories.length > 0) {
          this.selectedCategoryId = this.categories[0].id;
        }
        if (!this.selectedTopicId && this.currentTopics.length > 0) {
          this.selectedTopicId = this.currentTopics[0].id;
        }
      }

      // Auto start if explicitly requested in URL (e.g. ?autostart=1)
      if (config.autoStart) {
        this.startGame();
      }
    },

    /**
     * Start the game from Lobby
     */
    startGame() {
      soundEffects.playClick();
      this.gameState = 'playing';
      this.showVictoryModal = false;
      this.isGameOver = false;
      this.firstSelected = null;
      this.isChecking = false;
      this.streak = 0;
      this.maxStreak = 0;
      this.score = 0;
      this.matchedPairs = 0;
      this.totalAttempts = 0;
      this.correctAttempts = 0;

      if (this.gameMode === 'timeAttack') {
        this.timeAttackSeconds = 60;
      }
      this.elapsedSeconds = 0;

      this.fetchVocabularies();
      this.startTimer();
    },

    /**
     * Return to Lobby / Config screen
     */
    goToLobby() {
      soundEffects.playClick();
      this.stopTimer();
      this.gameState = 'lobby';
      this.showVictoryModal = false;
      this.isGameOver = false;
      this.cards = [];
    },

    /**
     * Computed topics based on currently selected category
     */
    get currentTopics() {
      if (!this.selectedCategoryId) return [];
      const cat = this.categories.find(c => c.id == this.selectedCategoryId);
      return cat ? (cat.topics || []) : [];
    },

    /**
     * Computed Accuracy Percentage
     */
    get accuracy() {
      if (this.totalAttempts === 0) return 100;
      return Math.round((this.correctAttempts / this.totalAttempts) * 100);
    },

    /**
     * Formatted Timer String (MM:SS)
     */
    get formattedTime() {
      const seconds = this.gameMode === 'timeAttack' ? this.timeAttackSeconds : this.elapsedSeconds;
      const mins = Math.floor(seconds / 60);
      const secs = seconds % 60;
      return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    },

    /**
     * Readable label of current scope
     */
    get currentScopeLabel() {
      if (this.scope === 'all') return '🎲 Toàn bộ từ vựng';
      if (this.scope === 'category') {
        const cat = this.categories.find(c => c.id == this.selectedCategoryId);
        return cat ? `📂 ${cat.name}` : '📂 Theo danh mục';
      }
      if (this.scope === 'topic') {
        const top = this.currentTopics.find(t => t.id == this.selectedTopicId);
        return top ? `🎯 ${top.name}` : '🎯 Theo chủ đề';
      }
      return 'Tùy chỉnh';
    },

    /**
     * Số lượng từ vựng của topic đang được chọn
     */
    get currentTopicVocabCount() {
      if (!this.selectedTopicId || this.scope !== 'topic') return 0;
      const top = this.currentTopics.find(t => t.id == this.selectedTopicId);
      return top ? (top.vocabularies_count || 0) : 0;
    },

    /**
     * Kiểm tra xem cấu hình hiện tại có đủ điều kiện để bắt đầu không
     */
    get canStart() {
      if (this.scope === 'topic') return this.currentTopicVocabCount >= 4;
      return true; // 'all' và 'category' luôn đủ từ vì đã filter ở server
    },

    /**
     * Setup a game round with vocabulary pairs
     */
    setupRound(vocabList) {
      if (!vocabList || vocabList.length === 0) {
        this.cards = [];
        return;
      }

      const pairs = vocabList.slice(0, this.pairLimit);
      this.totalPairs = pairs.length;
      this.matchedPairs = 0;
      this.firstSelected = null;
      this.isChecking = false;

      const cardItems = [];

      pairs.forEach(vocab => {
        // English Word Card
        cardItems.push({
          uid: `word-${vocab.id}`,
          vocabId: vocab.id,
          type: 'word',
          text: vocab.word,
          subText: vocab.pronunciation || '',
          audio: vocab.audio || '',
          isSelected: false,
          isMatched: false,
          isFaded: false,
          isMismatched: false,
        });

        // Vietnamese Meaning Card
        cardItems.push({
          uid: `meaning-${vocab.id}`,
          vocabId: vocab.id,
          type: 'meaning',
          text: vocab.meaning,
          subText: '',
          audio: '',
          isSelected: false,
          isMatched: false,
          isFaded: false,
          isMismatched: false,
        });
      });

      // Fisher-Yates Shuffle
      for (let i = cardItems.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [cardItems[i], cardItems[j]] = [cardItems[j], cardItems[i]];
      }

      this.cards = cardItems;
    },

    /**
     * Fetch randomized vocabularies from API
     */
    async fetchVocabularies() {
      this.isLoading = true;
      try {
        const params = new URLSearchParams({
          scope: this.scope,
          limit: this.pairLimit,
        });

        if (this.scope === 'category' && this.selectedCategoryId) {
          params.append('category_id', this.selectedCategoryId);
        } else if (this.scope === 'topic' && this.selectedTopicId) {
          params.append('topic_id', this.selectedTopicId);
        }

        const res = await fetch(`/api/game/vocabularies?${params.toString()}`);
        const data = await res.json();

        if (data.success && data.vocabularies && data.vocabularies.length > 0) {
          this.setupRound(data.vocabularies);
        } else {
          this.cards = [];
        }
      } catch (err) {
        console.error('Failed to fetch vocabularies for game:', err);
      } finally {
        this.isLoading = false;
      }
    },

    /**
     * Handle Card Click
     */
    onCardClick(card) {
      if (
        this.isChecking ||
        this.isGameOver ||
        this.isPaused ||
        card.isMatched ||
        card.isSelected
      ) {
        return;
      }

      soundEffects.playClick();
      card.isSelected = true;

      // First Card Selection
      if (!this.firstSelected) {
        this.firstSelected = card;
        return;
      }

      // Second Card Selection: Evaluate Pair
      this.isChecking = true;
      this.totalAttempts++;

      const isMatch = (
        this.firstSelected.vocabId === card.vocabId &&
        this.firstSelected.type !== card.type
      );

      if (isMatch) {
        this.handleMatchSuccess(this.firstSelected, card);
      } else {
        this.handleMismatch(this.firstSelected, card);
      }
    },

    /**
     * Handle Correct Match
     */
    handleMatchSuccess(cardA, cardB) {
      soundEffects.playMatch();

      // Find the English word card to pronounce
      const wordCard = cardA.type === 'word' ? cardA : cardB;
      this.speakWord(wordCard.text);

      // Combo streak & scoring
      this.streak++;
      if (this.streak > this.maxStreak) {
        this.maxStreak = this.streak;
      }

      if (this.streak >= 2) {
        soundEffects.playCombo(this.streak);
      }

      const comboBonus = (this.streak - 1) * 30;
      const speedBonus = this.gameMode === 'timeAttack' ? 25 : 10;
      this.score += (100 + comboBonus + speedBonus);

      cardA.isMatched = true;
      cardB.isMatched = true;
      this.correctAttempts++;
      this.matchedPairs++;

      // Log progress to server if authenticated
      if (this.isAuth && cardA.vocabId) {
        this.recordReviewProgress(cardA.vocabId);
      }

      // Smooth fade out of matched cards
      setTimeout(() => {
        cardA.isFaded = true;
        cardB.isFaded = true;
      }, 350);

      this.firstSelected = null;
      this.isChecking = false;

      // Check if current round is complete
      if (this.matchedPairs >= this.totalPairs) {
        this.handleRoundComplete();
      }
    },

    /**
     * Handle Mismatch
     */
    handleMismatch(cardA, cardB) {
      soundEffects.playMismatch();
      this.streak = 0;

      cardA.isMismatched = true;
      cardB.isMismatched = true;

      setTimeout(() => {
        cardA.isSelected = false;
        cardB.isSelected = false;
        cardA.isMismatched = false;
        cardB.isMismatched = false;
        this.firstSelected = null;
        this.isChecking = false;
      }, 500);
    },

    /**
     * Round Finished
     */
    handleRoundComplete() {
      if (this.gameMode === 'timeAttack' && this.timeAttackSeconds > 5) {
        // In Time Attack mode, immediately spawn a new round to keep playing!
        soundEffects.playCombo(4);
        setTimeout(() => {
          this.fetchVocabularies();
        }, 400);
      } else {
        // Practice Mode: Victory celebration
        this.stopTimer();
        soundEffects.playVictory();
        this.showVictoryModal = true;
        this.launchConfetti();
      }
    },

    /**
     * Pronounce English Word with Web Speech API
     */
    speakWord(text) {
      if (typeof window === 'undefined' || !('speechSynthesis' in window)) return;
      try {
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'en-US';
        utterance.rate = 0.95;
        window.speechSynthesis.speak(utterance);
      } catch (e) {
        console.warn('Speech synthesis error:', e);
      }
    },

    /**
     * Send review progress to server
     */
    async recordReviewProgress(vocabId) {
      try {
        await fetch(`/vocabulary/${vocabId}/review`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.csrfToken,
            'Accept': 'application/json',
          },
        });
      } catch (err) {
        // Fail silently for background telemetry
      }
    },

    /**
     * Scope & Filter Handlers
     */
    changeScope(newScope) {
      if (this.scope === newScope) return;
      this.scope = newScope;

      if (newScope === 'category' && !this.selectedCategoryId && this.categories.length > 0) {
        this.selectedCategoryId = this.categories[0].id;
      } else if (newScope === 'topic') {
        if (!this.selectedCategoryId && this.categories.length > 0) {
          this.selectedCategoryId = this.categories[0].id;
        }
        if (this.currentTopics.length > 0) {
          this.selectedTopicId = this.currentTopics[0].id;
        }
      }

      if (this.gameState === 'playing') {
        this.resetRound();
      }
    },

    syncCategorySelection(catId) {
      this.selectedCategoryId = catId;
      if (this.currentTopics.length > 0) {
        this.selectedTopicId = this.currentTopics[0].id;
      } else {
        this.selectedTopicId = '';
      }
      if (this.gameState === 'playing') {
        this.resetRound();
      }
    },

    syncTopicSelection(topicId) {
      this.selectedTopicId = topicId;
      if (this.gameState === 'playing') {
        this.resetRound();
      }
    },

    changePairLimit(limit) {
      if (this.pairLimit === limit) return;
      this.pairLimit = limit;
      if (this.gameState === 'playing') {
        this.resetRound();
      }
    },

    changeGameMode(mode) {
      if (this.gameMode === mode) return;
      this.gameMode = mode;
      if (this.gameState === 'playing') {
        this.resetRound();
      }
    },

    toggleMute() {
      this.isMuted = soundEffects.toggleMute();
    },

    /**
     * Reset and start fresh round in active game
     */
    resetRound() {
      this.showVictoryModal = false;
      this.isGameOver = false;
      this.firstSelected = null;
      this.isChecking = false;
      this.streak = 0;
      this.matchedPairs = 0;

      if (this.gameMode === 'timeAttack') {
        this.timeAttackSeconds = 60;
      }
      this.elapsedSeconds = 0;
      this.totalAttempts = 0;
      this.correctAttempts = 0;

      this.startTimer();
      this.fetchVocabularies();
    },

    /**
     * Play Next Round (Keep accumulated score & stats)
     */
    nextRound() {
      this.showVictoryModal = false;
      this.firstSelected = null;
      this.isChecking = false;
      this.startTimer();
      this.fetchVocabularies();
    },

    /**
     * Timers Management
     */
    startTimer() {
      this.stopTimer();
      this.timerInterval = setInterval(() => {
        if (this.isPaused || this.isGameOver || this.showVictoryModal) return;

        if (this.gameMode === 'timeAttack') {
          if (this.timeAttackSeconds > 0) {
            this.timeAttackSeconds--;
          } else {
            this.handleTimeUp();
          }
        } else {
          this.elapsedSeconds++;
        }
      }, 1000);
    },

    stopTimer() {
      if (this.timerInterval) {
        clearInterval(this.timerInterval);
        this.timerInterval = null;
      }
    },

    handleTimeUp() {
      this.stopTimer();
      this.isGameOver = true;
      soundEffects.playVictory();
      this.showVictoryModal = true;
      this.launchConfetti();
    },

    /**
     * Lightweight Confetti celebration
     */
    launchConfetti() {
      const canvas = document.getElementById('victory-confetti-canvas');
      if (!canvas) return;

      const ctx = canvas.getContext('2d');
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;

      const particles = [];
      const colors = ['#4f46e5', '#10b981', '#f59e0b', '#ec4899', '#3b82f6', '#8b5cf6'];

      for (let i = 0; i < 90; i++) {
        particles.push({
          x: canvas.width / 2,
          y: canvas.height / 2 + 80,
          radius: Math.random() * 5 + 3,
          color: colors[Math.floor(Math.random() * colors.length)],
          vx: (Math.random() - 0.5) * 14,
          vy: Math.random() * -12 - 4,
          gravity: 0.25,
          alpha: 1,
          decay: Math.random() * 0.015 + 0.008,
        });
      }

      let animId;
      const render = () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        let alive = false;

        particles.forEach(p => {
          p.x += p.vx;
          p.y += p.vy;
          p.vy += p.gravity;
          p.alpha -= p.decay;

          if (p.alpha > 0) {
            alive = true;
            ctx.save();
            ctx.globalAlpha = p.alpha;
            ctx.fillStyle = p.color;
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
          }
        });

        if (alive) {
          animId = requestAnimationFrame(render);
        } else {
          ctx.clearRect(0, 0, canvas.width, canvas.height);
          cancelAnimationFrame(animId);
        }
      };

      render();
    },
  };
}
