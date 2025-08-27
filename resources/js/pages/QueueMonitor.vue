<template>
  <Head title="Queue Monitoring" />
  <div class="flex flex-col items-center min-h-[60vh] bg-gray-50 dark:bg-gray-900 py-8">
    <div class="w-full max-w-5xl bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Queue Monitoring</h1>
        <a href="/dashboard" class="inline-flex items-center gap-1 px-4 py-2 rounded-md bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium shadow transition">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
          Back to Dashboard
        </a>
      </div>
      <div class="flex justify-end mb-4">
        <button @click="nextQueue" class="btn btn-primary px-6 py-2">Next Queue</button>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-100 dark:bg-gray-700">
            <tr>
              <th class="py-3 px-6 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Queue #</th>
              <th class="py-3 px-6 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Name</th>
              <th class="py-3 px-6 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Service</th>
              <th class="py-3 px-6 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Window</th>
              <th class="py-3 px-6 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Status</th>
              <th class="py-3 px-6 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Registered At</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="queue in queues" :key="queue.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
              <td class="py-3 px-6 font-bold text-blue-600 dark:text-blue-300">{{ queue.queue_number }}</td>
              <td class="py-3 px-6">{{ queue.name }}</td>
              <td class="py-3 px-6">{{ queue.service?.name }}</td>
              <td class="py-3 px-6">{{ queue.window?.name || '-' }}</td>
              <td class="py-3 px-6">
                <span :class="statusClass(queue.status)">{{ queue.status }}</span>
                <button v-if="queue.status === 'assigned' && queue.window" @click="completeQueue(queue.window.id)" class="ml-2 btn btn-success btn-xs">Complete</button>
              </td>
              <td class="py-3 px-6">{{ formatDate(queue.created_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';

const { queues = [] } = usePage().props as any;

function statusClass(status: string) {
  if (status === 'waiting') return 'inline-block px-2 py-1 rounded bg-yellow-100 text-yellow-800 text-xs font-semibold';
  if (status === 'assigned') return 'inline-block px-2 py-1 rounded bg-blue-100 text-blue-800 text-xs font-semibold';
  if (status === 'completed') return 'inline-block px-2 py-1 rounded bg-green-100 text-green-800 text-xs font-semibold';
  return '';
}

function formatDate(date: string) {
  return new Date(date).toLocaleString();
}

function nextQueue() {
  router.post('/queue/next', {}, {
    onSuccess: () => router.reload(),
  });
}

function completeQueue(windowId: number) {
  router.post('/queue/complete', { window_id: windowId }, {
    onSuccess: () => router.reload(),
  });
}

onMounted(() => {
  if (window.Echo) {
    window.Echo.channel('queues').listen('QueueUpdated', () => {
      router.reload();
    });
    window.Echo.channel('windows').listen('WindowUpdated', () => {
      router.reload();
    });
  }
});

onUnmounted(() => {
  if (window.Echo) {
    window.Echo.leave('queues');
    window.Echo.leave('windows');
  }
});
</script>
