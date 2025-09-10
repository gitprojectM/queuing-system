<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue';
import { usePage, Head, router, Link } from '@inertiajs/vue3';

const window = computed(() => usePage().props.window as any);

// Audio and speech functionality
let audio: HTMLAudioElement | null = null;

function announceQueue(queueNumber: string | number, windowName: string, onEnd?: () => void) {
  const msg = new window.SpeechSynthesisUtterance(`Now serving ${queueNumber} at ${windowName}`);
  if (onEnd) {
    msg.onend = onEnd;
  }
  window.speechSynthesis.speak(msg);
}

onMounted(() => {
  audio = new Audio('/sounds/notify.mp3');
  if (window.Echo) {
    window.Echo.channel('windows').listen('WindowUpdated', (event: any) => {
      if (audio && event.window.id === window.value.id) {
        audio.currentTime = 0;
        audio.play();
      }
      // Only announce if it's for this specific window
      if (event && event.window && event.window.id === window.value.id && 
          event.window.current_client) {
        announceQueue(event.window.current_client.queue_number, event.window.name, () => {
          router.reload();
        });
      } else if (event.window.id === window.value.id) {
        router.reload();
      }
    });
  }
});

onUnmounted(() => {
  if (window.Echo) {
    window.Echo.leave('windows');
  }
  audio = null;
});
</script>

<template>
  <Head :title="`Now Serving - ${window.name}`" />
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-blue-200 dark:from-gray-900 dark:to-blue-900 flex flex-col items-center justify-center py-8">
    
    <!-- Header with navigation -->
    <div class="w-full max-w-4xl mb-8 flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <Link href="/now-serving" class="text-blue-600 hover:text-blue-800 flex items-center">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          All Services
        </Link>
        <span class="text-gray-400">|</span>
        <Link :href="`/now-serving/${window.service.id}`" class="text-blue-600 hover:text-blue-800">
          {{ window.service.name }}
        </Link>
      </div>
    </div>

    <!-- Main Window Display -->
    <div class="w-full max-w-4xl">
      <div class="rounded-3xl shadow-2xl bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-700 p-16 flex flex-col items-center relative">
        
        <!-- Service Badge -->
        <div class="absolute top-6 right-6 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-1 rounded-full text-sm font-medium">
          {{ window.service.name }}
        </div>
        
        <!-- Window Name -->
        <div class="text-3xl font-bold text-blue-600 dark:text-blue-300 mb-4">{{ window.name }}</div>
        <div class="text-lg text-gray-500 dark:text-gray-400 mb-12">{{ window.description }}</div>
        
        <!-- Current Queue Display -->
        <div class="text-center mb-12">
          <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">NOW SERVING</div>
          <div class="text-9xl font-extrabold text-blue-800 dark:text-blue-100 mb-4">
            <span v-if="window.current_client" class="animate-pulse">{{ window.current_client.queue_number }}</span>
            <span v-else class="text-gray-400 text-6xl">IDLE</span>
          </div>
          
          <!-- Status Indicator -->
          <div class="flex justify-center mb-8">
            <div class="w-6 h-6 rounded-full" :class="{
              'bg-green-500 animate-pulse': window.current_client,
              'bg-gray-400': !window.current_client
            }"></div>
          </div>
        </div>
        
        <!-- Staff Info -->
        <div v-if="window.current_client && window.current_client.user" 
             class="text-lg text-gray-600 dark:text-gray-400 text-center mb-8">
          <div class="font-medium">Served by</div>
          <div class="text-blue-600 dark:text-blue-300 font-semibold">{{ window.current_client.user.name }}</div>
        </div>
        
        <!-- Window Status -->
        <div class="text-center">
          <div class="text-sm font-medium" :class="{
            'text-green-600 dark:text-green-400': window.current_client,
            'text-gray-500 dark:text-gray-400': !window.current_client
          }">
            {{ window.current_client ? 'ACTIVE' : 'WAITING' }}
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="mt-8 text-center">
      <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        Last updated: {{ new Date().toLocaleTimeString() }}
      </div>
      <div class="text-xs text-gray-400">
        Auto-refresh enabled
      </div>
    </div>
  </div>
</template>
