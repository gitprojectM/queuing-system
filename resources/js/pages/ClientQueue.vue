<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

const queues = computed(() => (usePage().props.queues as any[]) || []);
const userName = computed(() => usePage().props.userName as string);
</script>

<template>
  <Head title="My Queue Status" />
  <AppLayout>
    <div class="p-6 max-w-3xl mx-auto">
      <h1 class="text-3xl font-extrabold mb-2 text-blue-700 dark:text-blue-300 tracking-tight">Welcome, {{ userName }}</h1>
      <h2 class="text-lg font-semibold mb-6 text-gray-700 dark:text-gray-200">Your Active Queue Numbers</h2>
      <div v-if="queues.length" class="grid gap-6 md:grid-cols-2">
        <div v-for="queue in queues" :key="queue.id" class="rounded-2xl shadow-lg bg-gradient-to-br from-white to-blue-50 dark:from-gray-800 dark:to-blue-900 border border-blue-100 dark:border-blue-800 p-6 flex flex-col gap-3 relative overflow-hidden">
          <div class="flex items-center gap-3 mb-2">
            <span class="text-4xl font-bold text-blue-600 dark:text-blue-300">#{{ queue.queue_number }}</span>
            <span class="ml-auto px-3 py-1 rounded-full text-xs font-semibold"
              :class="{
                'bg-green-100 text-green-700': queue.status === 'serving',
                'bg-yellow-100 text-yellow-700': queue.status === 'waiting',
                'bg-gray-200 text-gray-600': queue.status === 'completed',
                'bg-red-100 text-red-700': queue.status === 'cancelled',
              }">
              {{ queue.status.charAt(0).toUpperCase() + queue.status.slice(1) }}
            </span>
          </div>
          <div class="flex flex-col gap-1">
            <div class="text-sm text-gray-500 dark:text-gray-400">Service</div>
            <div class="text-lg font-medium text-gray-900 dark:text-white">{{ queue.service?.name || '-' }}</div>
          </div>
          <div class="flex flex-col gap-1">
            <div class="text-sm text-gray-500 dark:text-gray-400">Window</div>
            <div class="text-base font-semibold text-blue-700 dark:text-blue-200">{{ queue.window?.name || 'Waiting' }}</div>
          </div>
          <div v-if="queue.updated_at" class="absolute bottom-3 right-6 text-xs text-gray-400">Updated: {{ new Date(queue.updated_at).toLocaleString() }}</div>
        </div>
      </div>
      <div v-else class="flex flex-col items-center justify-center py-16">
        <svg width="64" height="64" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="text-blue-200 dark:text-blue-700 mb-4"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 17l4 4 4-4m0-5V3m-8 9v6a2 2 0 002 2h8a2 2 0 002-2v-6"/></svg>
        <div class="text-lg text-gray-500 dark:text-gray-400 font-medium">You have no active queue numbers.</div>
      </div>
    </div>
  </AppLayout>
</template>
