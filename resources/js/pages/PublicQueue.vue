<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { usePage, Head, Link } from '@inertiajs/vue3';
import { createNotificationAudio, type NotificationAudio } from '@/lib/notificationAudio';

const servicesProp = computed(() => (usePage().props.services as any[]) || []);
const servicesState = ref<any[]>([]);
const allServices = computed(() => (usePage().props.allServices as any[]) || []);
const selectedServiceIds = computed(() => (usePage().props.selectedServiceIds as number[]) || []);
const videoUrl = computed(() => (usePage().props.videoUrl as string | null) || '');
const displayedServiceIds = computed(() => (servicesState.value as any[]).map((s: any) => Number(s.id)));

// Optional: Real-time updates via Echo
let notifier: NotificationAudio | null = null;
const soundBlocked = ref(false);
const realtimeState = ref<'connected' | 'connecting' | 'disconnected' | 'unknown'>('unknown');
const lastUpdateAt = ref<number>(Date.now());
const nowTick = ref<number>(Date.now());
const latestWindow = ref<any | null>(null);

const lastPlayedByWindowId = new Map<number, string>();
let pollTimer: number | null = null;
let tickTimer: number | null = null;

function announceQueue(queueNumber: string | number, windowName: string, onEnd?: () => void) {
  const msg = new window.SpeechSynthesisUtterance(`Now serving ${queueNumber} at ${windowName}`);
  if (onEnd) {
    msg.onend = onEnd;
  }
  window.speechSynthesis.speak(msg);
}

function setInitialLatestWindow() {
  const svcList = servicesState.value as any[];
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

function applyWindowUpdate(updatedWindow: any) {
  for (const svc of servicesState.value) {
    if (!svc.windows) continue;
    const index = svc.windows.findIndex((w: any) => Number(w.id) === Number(updatedWindow.id));
    if (index >= 0) {
      svc.windows[index] = { ...svc.windows[index], ...updatedWindow };
      return;
    }
  }
}

function computeLatestWindowFromServices() {
  let best: any | null = null;
  let bestTs = 0;
  for (const svc of servicesState.value) {
    for (const w of svc.windows || []) {
      if (!w?.current_client) continue;
      const ts = w.updated_at ? Date.parse(w.updated_at) : 0;
      if (!best || ts >= bestTs) {
        best = w;
        bestTs = ts;
      }
    }
  }
  return best;
}

function windowClientKey(w: any) {
  const windowId = w?.id ?? '';
  const clientId = w?.current_client?.id ?? '';
  const queueNo = w?.current_client?.queue_number ?? '';
  return `${windowId}:${clientId}:${queueNo}`;
}

function maybePlayForWindow(w: any) {
  const windowId = Number(w?.id);
  if (!Number.isFinite(windowId)) return;
  const key = windowClientKey(w);
  if (lastPlayedByWindowId.get(windowId) === key) return;
  lastPlayedByWindowId.set(windowId, key);

  notifier?.play().then((ok) => {
    if (!ok) soundBlocked.value = true;
  });

  if (w?.current_client?.queue_number && w?.name) {
    announceQueue(w.current_client.queue_number, w.name);
  }
}

function apiUrlForCurrentSelection() {
  const current = new URL(window.location.href);
  const api = new URL('/api/now-serving', current.origin);
  const servicesParam = current.searchParams.get('services');
  if (servicesParam) api.searchParams.set('services', servicesParam);
  return api.toString();
}

async function pollNowServing() {
  try {
    const resp = await fetch(apiUrlForCurrentSelection(), {
      headers: { 'Accept': 'application/json' },
      cache: 'no-store',
    });
    if (!resp.ok) return;
    const data = await resp.json();
    if (Array.isArray(data?.services)) {
      servicesState.value = data.services;
      const computedLatest = computeLatestWindowFromServices();
      if (computedLatest) {
        const prevKey = windowClientKey(latestWindow.value);
        latestWindow.value = computedLatest;
        const nextKey = windowClientKey(computedLatest);
        if (prevKey && nextKey && prevKey !== nextKey) {
          maybePlayForWindow(computedLatest);
        }
      }
      lastUpdateAt.value = Date.now();
    }
  } catch {
    // ignore
  }
}

watch(
  servicesProp,
  (val) => {
    servicesState.value = val;
    setInitialLatestWindow();
  },
  { immediate: true }
);

onMounted(() => {
  // On first load, try to show the currently serving client for the selected services
  setInitialLatestWindow();
  notifier = createNotificationAudio('/sounds/notify.mp3');
  const echoAny = (window as any).Echo;
  const pusher = echoAny?.connector?.pusher;
  if (pusher?.connection?.state) {
    const state = pusher.connection.state;
    realtimeState.value = state === 'connected' ? 'connected' : state === 'connecting' ? 'connecting' : 'disconnected';
    pusher.connection.bind('state_change', (states: any) => {
      const s = states?.current;
      realtimeState.value = s === 'connected' ? 'connected' : s === 'connecting' ? 'connecting' : 'disconnected';
    });
  }

  // Polling fallback when realtime drops or stalls.
  pollTimer = window.setInterval(() => {
    const age = Date.now() - lastUpdateAt.value;
    if (realtimeState.value !== 'connected' || age > 20000) {
      void pollNowServing();
    }
  }, 10000);

  // UI clock for "last update" display.
  tickTimer = window.setInterval(() => {
    nowTick.value = Date.now();
  }, 1000);

  if (echoAny) {
    window.Echo.channel('windows').listen('WindowUpdated', (event: any) => {
      lastUpdateAt.value = Date.now();
      const hasClient = event && event.window && event.window.current_client;
      const eventServiceId = hasClient
        ? Number(
            event.window.current_client?.service?.id ??
              event.window.current_client?.service_id ??
              null
          )
        : null;
      let isSelectedService = true;
      const ids = displayedServiceIds.value;
      if (ids.length > 0 && eventServiceId !== null) {
        isSelectedService = ids.includes(eventServiceId);
      } else if (ids.length > 0 && eventServiceId === null) {
        // If service isn't present in payload, don't block updates (prevents latest call from freezing).
        isSelectedService = true;
      }

      if (event && event.window && isSelectedService) {
        applyWindowUpdate(event.window);
        if (hasClient) latestWindow.value = event.window;
      }

      // Announce without forcing a full page reload.
      if (event && event.window && hasClient && event.window.name && isSelectedService) {
        maybePlayForWindow(event.window);
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
  if (pollTimer) {
    window.clearInterval(pollTimer);
    pollTimer = null;
  }
  if (tickTimer) {
    window.clearInterval(tickTimer);
    tickTimer = null;
  }
});

async function enableSound() {
  if (!notifier) return;
  const ok = await notifier.unlock();
  soundBlocked.value = !ok;
}

function generateServiceUrl(serviceIds: number[]) {
  return `/now-serving?services=${serviceIds.join(',')}`;
}
</script>

<template>
  <Head title="Now Serving" />
  <div class="min-h-screen lg:h-screen lg:overflow-hidden bg-gradient-to-br from-blue-50 to-blue-200 dark:from-gray-900 dark:to-blue-900 flex flex-col lg:flex-row py-4 lg:py-8">
    <!-- Left Panel: Video / Media (kept visible on top while list scrolls) -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-4 lg:p-8 lg:h-full">
      <div class="w-full max-w-3xl aspect-video bg-black rounded-2xl overflow-hidden shadow-2xl flex items-center justify-center lg:sticky lg:top-6">
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
    <div class="w-full lg:w-1/2 flex flex-col items-center justify-start px-4 lg:px-6 py-4 lg:py-6 lg:h-full lg:overflow-y-auto">
      <div v-if="soundBlocked" class="w-full max-w-xl mb-3 rounded-xl border border-amber-300 bg-amber-50 text-amber-900 px-4 py-3 flex items-center justify-between">
        <div class="text-sm">
          Sound is blocked by the browser. Click Enable Sound once.
        </div>
        <button type="button" class="btn btn-warning btn-sm" @click="enableSound">Enable Sound</button>
      </div>
      <div class="w-full max-w-xl mb-3 text-xs text-blue-900/70 dark:text-blue-100/70 flex items-center justify-between">
        <span>Updates: {{ realtimeState }} / last {{ Math.round((nowTick - lastUpdateAt) / 1000) }}s</span>
        <button type="button" class="underline" @click="pollNowServing">Refresh</button>
      </div>
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
          Showing {{ servicesState.length }} of {{ allServices.length }} services
        </div>

        <div v-for="service in servicesState" :key="service.id" class="mb-3">
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
        <div v-if="servicesState.length === 0" class="text-center text-gray-500 dark:text-gray-400 mt-8">
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
