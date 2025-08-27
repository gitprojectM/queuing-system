<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue';
import { usePage, Head, router } from '@inertiajs/vue3';

const services = computed(() => (usePage().props.services as any[]) || []);

// Optional: Real-time updates via Echo
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
      if (audio) {
        audio.currentTime = 0;
        audio.play();
      }
      // Try to extract the latest queue and window from event, fallback to announce all
      if (event && event.window && event.window.current_client && event.window.name) {
        announceQueue(event.window.current_client.queue_number, event.window.name, () => {
          router.reload();
        });
      } else {
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
  <Head title="Now Serving" />
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-blue-200 dark:from-gray-900 dark:to-blue-900 flex flex-col items-center justify-center py-8">
    <h1 class="text-4xl font-extrabold text-blue-700 dark:text-blue-200 mb-8 tracking-tight">Now Serving</h1>
    <div v-for="service in services" :key="service.id" class="w-full max-w-6xl mb-12">
      <h2 class="text-2xl font-bold text-blue-800 dark:text-blue-100 mb-6 border-b border-blue-200 dark:border-blue-700 pb-2">{{ service.name }}</h2>
      <div class="grid gap-8 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
        <div v-for="window in service.windows" :key="window.id" class="rounded-2xl shadow-xl bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-700 p-8 flex flex-col items-center relative">
          <div class="text-lg font-semibold text-blue-600 dark:text-blue-300 mb-2">{{ window.name }}</div>
          <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ window.description }}</div>
          <div class="text-6xl font-extrabold text-blue-800 dark:text-blue-100 mb-2">
            <span v-if="window.current_client">{{ window.current_client.queue_number }}</span>
            <span v-else class="text-gray-400 text-4xl">Idle</span>
          </div>
          <div v-if="window.current_client && window.current_client.user" class="text-xs text-gray-400 mt-2">Assigned to: {{ window.current_client.user.name }}</div>
        </div>
      </div>
    </div>
  </div>
</template>
