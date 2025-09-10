<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue';
import { usePage, Head, router, Link } from '@inertiajs/vue3';

const services = computed(() => (usePage().props.services as any[]) || []);
const allServices = computed(() => (usePage().props.allServices as any[]) || []);
const selectedServiceIds = computed(() => (usePage().props.selectedServiceIds as number[]) || []);

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

function generateServiceUrl(serviceIds: number[]) {
  return `/now-serving?services=${serviceIds.join(',')}`;
}
</script>

<template>
  <Head title="Now Serving" />
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-blue-200 dark:from-gray-900 dark:to-blue-900 flex flex-col items-center justify-center py-8">
    <!-- Header with Controls -->
    <div class="w-full max-w-6xl mb-8 flex items-center justify-between">
      <h1 class="text-4xl font-extrabold text-blue-700 dark:text-blue-200 tracking-tight">Now Serving</h1>
      
      <!-- Control Buttons -->
      <div class="flex space-x-3">
        <Link href="/now-serving/select" 
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
          </svg>
          Select Services
        </Link>
        
        <Link v-if="selectedServiceIds.length > 0" href="/now-serving" 
              class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
          View All
        </Link>
      </div>
    </div>

    <!-- Selected Services Info -->
    <div v-if="selectedServiceIds.length > 0" class="w-full max-w-6xl mb-6">
      <div class="bg-blue-100 dark:bg-blue-900 rounded-lg p-4 flex items-center justify-between">
        <div class="flex items-center">
          <svg class="w-5 h-5 text-blue-600 dark:text-blue-300 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span class="text-blue-800 dark:text-blue-200 font-medium">
            Showing {{ services.length }} of {{ allServices.length }} services
          </span>
        </div>
        <div class="flex flex-wrap gap-2">
          <span v-for="service in services" :key="service.id"
                class="bg-blue-200 dark:bg-blue-800 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-sm">
            {{ service.name }}
          </span>
        </div>
      </div>
    </div>

    <!-- Quick Service Filter Buttons -->
    <div v-if="allServices.length > 1" class="w-full max-w-6xl mb-8">
      <div class="flex flex-wrap gap-2 justify-center">
        <Link v-for="service in allServices" :key="service.id"
              :href="generateServiceUrl([service.id])"
              class="bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-700 text-blue-600 dark:text-blue-300 px-3 py-2 rounded-lg text-sm font-medium hover:bg-blue-50 dark:hover:bg-blue-900 transition-colors"
              :class="{
                'bg-blue-600 text-white border-blue-600': selectedServiceIds.length === 1 && selectedServiceIds.includes(service.id)
              }">
          {{ service.name }}
        </Link>
      </div>
    </div>

    <!-- Services Display -->
    <div v-for="service in services" :key="service.id" class="w-full max-w-6xl mb-12">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-blue-800 dark:text-blue-100 border-b border-blue-200 dark:border-blue-700 pb-2">{{ service.name }}</h2>
        <div class="flex space-x-2">
          <Link :href="`/now-serving/${service.id}`" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
            View Service Only
          </Link>
        </div>
      </div>
      <div class="grid gap-8 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
        <div v-for="window in service.windows" :key="window.id" class="rounded-2xl shadow-xl bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-700 p-8 flex flex-col items-center relative group">
          <!-- Window View Link -->
          <Link :href="`/now-serving/window/${window.id}`" 
                class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-full text-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
          </Link>
          
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

    <!-- Empty State -->
    <div v-if="services.length === 0" class="text-center text-gray-500 dark:text-gray-400">
      <div class="text-6xl mb-4">🏢</div>
      <h3 class="text-xl font-semibold mb-2">No Services Selected</h3>
      <p class="mb-4">Select which services you want to monitor</p>
      <Link href="/now-serving/select" 
            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
        Select Services
      </Link>
    </div>
  </div>
</template>
