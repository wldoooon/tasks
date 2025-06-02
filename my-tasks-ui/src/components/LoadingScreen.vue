<template>
  <div class="loading-screen" :class="{ 'simple-mode': !fullscreen }">
    <div class="loading-content">
      <div class="loading-logo" v-if="fullscreen">
        <div class="logo-icon">📋</div>
        <h1 class="logo-text">TaskManager</h1>
      </div>

      <div class="loading-spinner">
        <div class="spinner"></div>
      </div>

      <div class="loading-text">
        <p>{{ displayMessage }}</p>
      </div>

      <div class="progress-container" v-if="fullscreen">
        <div class="progress-bar" :style="{ width: progress + '%' }"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
  fullscreen: {
    type: Boolean,
    default: false
  },
  message: {
    type: String,
    default: 'Loading...'
  },
  showProgress: {
    type: Boolean,
    default: true
  }
});

const progress = ref(0);
const loadingMessage = ref('Loading your workspace...');

const messages = [
  'Loading your workspace...',
  'Fetching your tasks...',
  'Almost ready...',
  'Welcome back!'
];

const displayMessage = computed(() => {
  return props.fullscreen ? loadingMessage.value : props.message;
});

onMounted(() => {
  if (props.fullscreen && props.showProgress) {
    const interval = setInterval(() => {
      progress.value += 25;
      
      const messageIndex = Math.floor(progress.value / 25) - 1;
      if (messageIndex >= 0 && messageIndex < messages.length) {
        loadingMessage.value = messages[messageIndex];
      }

      if (progress.value >= 100) {
        clearInterval(interval);
      }
    }, 800); 
  }
});
</script>

<style scoped>
.loading-screen {
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.loading-screen:not(.simple-mode) {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.loading-screen.simple-mode {
  padding: 2rem;
  min-height: 200px;
  color: #333;
}

.loading-content {
  text-align: center;
  max-width: 400px;
  width: 90%;
}

.loading-logo {
  margin-bottom: 2rem;
}

.logo-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  animation: bounce 2s infinite;
}

.logo-text {
  font-size: 2rem;
  font-weight: 700;
  margin: 0;
  color: white;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.loading-spinner {
  margin: 2rem 0;
  display: flex;
  justify-content: center;
}

.spinner {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.simple-mode .spinner {
  border: 3px solid rgba(0, 0, 0, 0.1);
  border-top: 3px solid #42b983;
  width: 40px;
  height: 40px;
}

.loading-screen:not(.simple-mode) .spinner {
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid white;
}

.loading-text {
  margin: 1.5rem 0;
}

.loading-text p {
  margin: 0;
  animation: fade 0.5s ease-in-out;
}

.simple-mode .loading-text p {
  font-size: 1rem;
  color: #333;
}

.loading-screen:not(.simple-mode) .loading-text p {
  font-size: 1.1rem;
  color: rgba(255, 255, 255, 0.9);
}

.progress-container {
  width: 100%;
  height: 4px;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 2px;
  overflow: hidden;
  margin-top: 1rem;
}

.progress-bar {
  height: 100%;
  background: white;
  border-radius: 2px;
  transition: width 0.25s ease-in-out;
  box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}

/* Animations */
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@keyframes bounce {
  0%, 20%, 50%, 80%, 100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-10px);
  }
  60% {
    transform: translateY(-5px);
  }
}

@keyframes fade {
  0% { opacity: 0; }
  100% { opacity: 1; }
}

@media (max-width: 768px) {
  .logo-icon {
    font-size: 3rem;
  }
  
  .logo-text {
    font-size: 1.5rem;
  }
  
  .loading-text p {
    font-size: 1rem;
  }
  
  .spinner {
    width: 40px;
    height: 40px;
  }
}
</style>
