<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { usePage, Head, router, Link } from '@inertiajs/vue3';

const services = computed(() => (usePage().props.services as any[]) || []);
const allServices = computed(() => (usePage().props.allServices as any[]) || []);
const selectedServiceIds = computed(() => (usePage().props.selectedServiceIds as number[]) || []);
const videoUrl = computed(() => (usePage().props.videoUrl as string | null) || '');
const displayedServiceIds = computed(() => (services.value as any[]).map((s: any) => Number(s.id)));

// Optional: Real-time updates via Echo
let audio: HTMLAudioElement | null = null;
const latestWindow = ref<any | null>(null);
function announceQueue(queueNumber: string | number, windowName: string, onEnd?: () => void) {
  const msg = new window.SpeechSynthesisUtterance(`Now serving ${queueNumber} at ${windowName}`);
  if (onEnd) {
    msg.onend = onEnd;
  }
  window.speechSynthesis.speak(msg);
}

function setInitialLatestWindow() {
  const svcList = services.value as any[];
  const selectedIds = selectedServiceIds.value || [];
  for (const svc of svcList) {
    if (selectedIds.length && !selectedIds.some((id: any) => Number(id) === Number(svc.id))) {
      continue;
    }
    if (!svc.windows) continue;
    const withClient = svc.windows.find((w: any) => w.current_client);
    if (withClient) {
      latestWindow.value = withClient;
      return;
    }
  }
}
onMounted(() => {
  // On first load, try to show the currently serving client for the selected services
  setInitialLatestWindow();
  audio = new Audio('/sounds/notify.mp3');
  if (window.Echo) {
    window.Echo.channel('windows').listen('WindowUpdated', (event: any) => {
      const hasClient = event && event.window && event.window.current_client;
      const hasService = hasClient && event.window.current_client.service;
      const eventServiceId = hasService ? Number(event.window.current_client.service.id) : null;
      let isSelectedService = true;
      const ids = displayedServiceIds.value;
      if (ids.length > 0 && eventServiceId !== null) {
        isSelectedService = ids.includes(eventServiceId);
      } else if (ids.length > 0 && eventServiceId === null) {
        isSelectedService = false;
      }

      if (audio && isSelectedService && hasClient) {
        audio.currentTime = 0;
        // Handle autoplay restrictions gracefully
        const playPromise = audio.play();
        if (playPromise && typeof playPromise.then === 'function') {
          playPromise.catch(() => {
            // Ignore NotAllowedError when user hasn't interacted yet
          });
        }
      }
      if (event && event.window && hasClient && isSelectedService) {
        latestWindow.value = event.window;
      }
      // Try to extract the latest queue and window from event, fallback to announce all
      if (event && event.window && hasClient && event.window.name && isSelectedService) {
        announceQueue(event.window.current_client.queue_number, event.window.name, () => {
          router.reload();
        });
      } else {
        router.reload();
      }
    });
  }
});

// When Inertia reloads update the props, keep latestWindow in sync
watch(services, () => {
  setInitialLatestWindow();
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
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-blue-200 dark:from-gray-900 dark:to-blue-900 flex flex-col lg:flex-row py-4 lg:py-8">
    <!-- Left Panel: Video / Media -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-4 lg:p-8">
      <div class="w-full max-w-3xl aspect-video bg-black rounded-2xl overflow-hidden shadow-2xl flex items-center justify-center">
        <video
          v-if="videoUrl"
          :src="videoUrl"
          class="w-full h-full object-cover"
          autoplay
          muted
          loop
          playsinline
        >
        </video>
        <div v-else class="flex flex-col items-center justify-center text-white text-center px-6">
          <div class="text-5xl mb-4">🎬</div>
          <h2 class="text-2xl font-bold mb-2">Display Video Area</h2>
          <p class="text-sm opacity-80">Configure a promotional or informational video by providing a videoUrl prop from the backend.</p>
        </div>
      </div>
    </div>

    <!-- Right Panel: Now Serving -->
    <div class="w-full lg:w-1/2 flex flex-col items-center justify-start px-4 lg:px-6 py-4 lg:py-6">
      <!-- Header with Controls -->
      <div class="w-full max-w-xl mb-4 flex items-center justify-between">
        <h1 class="text-3xl font-extrabold text-blue-700 dark:text-blue-200 tracking-tight">Now Serving</h1>
        <div class="flex space-x-2">
          <Link href="/now-serving/select" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-xs font-medium transition-colors flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
            </svg>
            Select
          </Link>
          <Link v-if="selectedServiceIds.length > 0" href="/now-serving" 
                class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-lg text-xs font-medium transition-colors">
            All
          </Link>
        </div>
      </div>

      <!-- Latest Served Highlight -->
      <div v-if="latestWindow && latestWindow.current_client" class="w-full max-w-xl mb-4">
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-blue-300 dark:border-blue-700 p-5 flex flex-col items-center">
          <div class="text-xs uppercase tracking-wide text-blue-500 mb-2">Latest Call</div>
          <div class="text-6xl font-extrabold text-blue-800 dark:text-blue-100 mb-1">
            {{ latestWindow.current_client.queue_number }}
          </div>
          <div class="text-sm text-gray-600 dark:text-gray-300 mb-1">
            Window: <span class="font-semibold">{{ latestWindow.name }}</span>
          </div>
          <div v-if="latestWindow.current_client.service" class="text-xs text-gray-500 dark:text-gray-400">
            Service: {{ latestWindow.current_client.service.name }}
          </div>
        </div>
      </div>

      <!-- Compact list of current windows by service -->
      <div class="w-full max-w-xl flex-1 overflow-y-auto space-y-4">
        <div v-if="selectedServiceIds.length > 0" class="mb-1 text-xs text-blue-900 dark:text-blue-200">
          Showing {{ services.length }} of {{ allServices.length }} services
        </div>

        <div v-for="service in services" :key="service.id" class="mb-3">
          <div class="flex items-center justify-between mb-1">
            <h2 class="text-sm font-semibold text-blue-800 dark:text-blue-100">{{ service.name }}</h2>
            <Link :href="`/now-serving/${service.id}`" 
                  class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-100 underline">
              View
            </Link>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <div v-for="window in service.windows" :key="window.id" class="rounded-xl bg-white dark:bg-gray-800 border border-blue-100 dark:border-blue-700 p-3 flex flex-col">
              <div class="flex items-center justify-between mb-1">
                <div class="text-xs font-semibold text-blue-700 dark:text-blue-300">{{ window.name }}</div>
                <Link :href="`/now-serving/window/${window.id}`" class="text-[10px] text-blue-500 hover:text-blue-700 underline">View</Link>
              </div>
              <div class="text-3xl font-extrabold text-blue-800 dark:text-blue-100 leading-none">
                <span v-if="window.current_client">{{ window.current_client.queue_number }}</span>
                <span v-else class="text-gray-400 text-xl">Idle</span>
              </div>
              <div v-if="window.current_client && window.current_client.user" class="text-[10px] text-gray-500 mt-1">
                Assigned to: {{ window.current_client.user.name }}
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="services.length === 0" class="text-center text-gray-500 dark:text-gray-400 mt-8">
          <div class="text-5xl mb-3">🏢</div>
          <h3 class="text-lg font-semibold mb-1">No Services Selected</h3>
          <p class="mb-3 text-sm">Select which services you want to monitor</p>
          <Link href="/now-serving/select" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-medium text-sm transition-colors">
            Select Services
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
