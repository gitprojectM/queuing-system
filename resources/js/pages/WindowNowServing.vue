<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { usePage, Head, Link } from '@inertiajs/vue3';
import { createNotificationAudio, type NotificationAudio } from '@/lib/notificationAudio';

const windowProp = computed(() => usePage().props.window as any);
const windowState = ref<any>(windowProp.value);
watch(windowProp, (val) => {
  windowState.value = val;
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

onMounted(() => {
  notifier = createNotificationAudio('/sounds/notify.mp3');
  if ((window as any).Echo) {
    (window as any).Echo.channel('windows').listen('WindowUpdated', (event: any) => {
      if (!event?.window || Number(event.window.id) !== Number(windowState.value?.id)) return;

      windowState.value = { ...windowState.value, ...event.window };
      notifier?.play().then((ok) => {
        if (!ok) soundBlocked.value = true;
      });

      if (event.window.current_client) {
        announceQueue(event.window.current_client.queue_number, event.window.name);
      }
    });
  }
});

onUnmounted(() => {
  if ((window as any).Echo) {
    (window as any).Echo.leave('windows');
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
  <Head :title="`Now Serving - ${windowState.name}`" />
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-blue-200 dark:from-gray-900 dark:to-blue-900 flex flex-col items-center justify-center py-8">
    <div v-if="soundBlocked" class="w-full max-w-4xl mb-4 px-4">
      <div class="rounded-xl border border-amber-300 bg-amber-50 text-amber-900 px-4 py-3 flex items-center justify-between">
        <div class="text-sm">Sound is blocked by the browser. Click Enable Sound once.</div>
        <button type="button" class="btn btn-warning btn-sm" @click="enableSound">Enable Sound</button>
      </div>
    </div>
    
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
        <Link :href="`/now-serving/${windowState.service.id}`" class="text-blue-600 hover:text-blue-800">
          {{ windowState.service.name }}
        </Link>
      </div>
    </div>

    <!-- Main Window Display -->
    <div class="w-full max-w-4xl">
      <div class="rounded-3xl shadow-2xl bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-700 p-16 flex flex-col items-center relative">
        
        <!-- Service Badge -->
        <div class="absolute top-6 right-6 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-1 rounded-full text-sm font-medium">
          {{ windowState.service.name }}
        </div>
        
        <!-- Window Name -->
        <div class="text-3xl font-bold text-blue-600 dark:text-blue-300 mb-4">{{ windowState.name }}</div>
        <div class="text-lg text-gray-500 dark:text-gray-400 mb-12">{{ windowState.description }}</div>
        
        <!-- Current Queue Display -->
        <div class="text-center mb-12">
          <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">NOW SERVING</div>
          <div class="text-9xl font-extrabold text-blue-800 dark:text-blue-100 mb-4">
            <span v-if="windowState.current_client" class="animate-pulse">{{ windowState.current_client.queue_number }}</span>
            <span v-else class="text-gray-400 text-6xl">IDLE</span>
          </div>
          
          <!-- Status Indicator -->
          <div class="flex justify-center mb-8">
            <div class="w-6 h-6 rounded-full" :class="{
              'bg-green-500 animate-pulse': windowState.current_client,
              'bg-gray-400': !windowState.current_client
            }"></div>
          </div>
        </div>
        
        <!-- Staff Info -->
        <div v-if="windowState.current_client && windowState.current_client.user" 
             class="text-lg text-gray-600 dark:text-gray-400 text-center mb-8">
          <div class="font-medium">Served by</div>
          <div class="text-blue-600 dark:text-blue-300 font-semibold">{{ windowState.current_client.user.name }}</div>
        </div>
        
        <!-- Window Status -->
        <div class="text-center">
          <div class="text-sm font-medium" :class="{
            'text-green-600 dark:text-green-400': windowState.current_client,
            'text-gray-500 dark:text-gray-400': !windowState.current_client
          }">
            {{ windowState.current_client ? 'ACTIVE' : 'WAITING' }}
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
