<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { usePage, Head, Link } from '@inertiajs/vue3';
import { createNotificationAudio, type NotificationAudio } from '@/lib/notificationAudio';

const serviceProp = computed(() => usePage().props.service as any);
const serviceState = ref<any>(serviceProp.value);
watch(serviceProp, (val) => {
  serviceState.value = val;
});

// Audio and speech functionality
let notifier: NotificationAudio | null = null;
const soundBlocked = ref(false);

function announceQueue(queueNumber: string | number, windowName: string, onEnd?: () => void) {
  const msg = new window.SpeechSynthesisUtterance(`Now serving ${queueNumber} at ${windowName}`);
  if (onEnd) {
    msg.onend = onEnd;
  }
  window.speechSynthesis.speak(msg);
}

function applyWindowUpdate(updatedWindow: any) {
  if (!serviceState.value?.windows) return;
  const index = serviceState.value.windows.findIndex((w: any) => Number(w.id) === Number(updatedWindow.id));
  if (index >= 0) {
    serviceState.value.windows[index] = { ...serviceState.value.windows[index], ...updatedWindow };
  }
}

onMounted(() => {
  notifier = createNotificationAudio('/sounds/notify.mp3');
  if (window.Echo) {
    window.Echo.channel('windows').listen('WindowUpdated', (event: any) => {
      // Only announce if it's for this service
      if (event && event.window && event.window.current_client && 
          event.window.current_client.service_id === serviceState.value.id) {
        applyWindowUpdate(event.window);
        notifier?.play().then((ok) => {
          if (!ok) soundBlocked.value = true;
        });
        announceQueue(event.window.current_client.queue_number, event.window.name);
        return;
      }

      // Still update the UI if one of our windows changed (e.g., became idle).
      if (event && event.window) {
        applyWindowUpdate(event.window);
      }
    });
  }
});

onUnmounted(() => {
  if (window.Echo) {
    window.Echo.leave('windows');
  }
  notifier?.dispose();
  notifier = null;
});

async function enableSound() {
  if (!notifier) return;
  const ok = await notifier.unlock();
  soundBlocked.value = !ok;
}
</script>

<template>
  <Head :title="`Now Serving - ${serviceState.name}`" />
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-blue-200 dark:from-gray-900 dark:to-blue-900 flex flex-col items-center justify-center py-8">
    <div v-if="soundBlocked" class="w-full max-w-6xl mb-4 px-4">
      <div class="rounded-xl border border-amber-300 bg-amber-50 text-amber-900 px-4 py-3 flex items-center justify-between">
        <div class="text-sm">Sound is blocked by the browser. Click Enable Sound once.</div>
        <button type="button" class="btn btn-warning btn-sm" @click="enableSound">Enable Sound</button>
      </div>
    </div>
    <!-- Header with back button -->
    <div class="w-full max-w-6xl mb-8 flex items-center justify-between">
      <Link href="/now-serving" class="text-blue-600 hover:text-blue-800 flex items-center">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Back to All Services
      </Link>
      <h1 class="text-4xl font-extrabold text-blue-700 dark:text-blue-200 tracking-tight">{{ serviceState.name }}</h1>
      <div></div> <!-- Spacer for flex layout -->
    </div>

    <!-- Windows Grid -->
    <div class="w-full max-w-6xl">
      <div class="grid gap-8 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
        <div v-for="window in serviceState.windows" :key="window.id" 
             class="rounded-2xl shadow-xl bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-700 p-8 flex flex-col items-center relative">
          
          <!-- Window Name -->
          <div class="text-xl font-bold text-blue-600 dark:text-blue-300 mb-2">{{ window.name }}</div>
          <div class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ window.description }}</div>
          
          <!-- Queue Number Display -->
          <div class="text-8xl font-extrabold text-blue-800 dark:text-blue-100 mb-4">
            <span v-if="window.current_client" class="animate-pulse">{{ window.current_client.queue_number }}</span>
            <span v-else class="text-gray-400 text-5xl">Idle</span>
          </div>
          
          <!-- Status Indicator -->
          <div class="w-4 h-4 rounded-full mb-4" :class="{
            'bg-green-500 animate-pulse': window.current_client,
            'bg-gray-400': !window.current_client
          }"></div>
          
          <!-- Staff Info -->
          <div v-if="window.current_client && window.current_client.user" 
               class="text-sm text-gray-500 dark:text-gray-400 text-center">
            Served by: {{ window.current_client.user.name }}
          </div>
        </div>
      </div>
    </div>

    <!-- Footer with timestamp -->
    <div class="mt-8 text-sm text-gray-500 dark:text-gray-400">
      Last updated: {{ new Date().toLocaleTimeString() }}
    </div>
  </div>
</template>
