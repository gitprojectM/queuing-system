
<script setup lang="ts">
import { usePage, Head } from '@inertiajs/vue3';

interface Queue {
  queue_number: string | number;
  service?: { name?: string };
  window?: { name?: string };
  created_at: string;
}

const queue = usePage().props.queue as Queue;

function printTicket() {
  window.print();
}
</script>

<template>
  <Head title="Queue Ticket" />
  <div class="flex items-center justify-center min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 w-full max-w-xs print:max-w-full print:shadow-none print:bg-white">
      <div class="text-center mb-6">
        <h2 class="text-lg font-bold mb-2">QUEUE TICKET</h2>
        <div class="text-5xl font-extrabold text-blue-700 dark:text-blue-300 mb-2">{{ queue.queue_number }}</div>
        <div class="text-base font-semibold mb-1">Service: {{ queue.service?.name }}</div>
        <div class="text-base mb-1">Window: {{ queue.window?.name || 'Waiting' }}</div>
        <div class="text-xs text-gray-500">{{ new Date(queue.created_at).toLocaleString() }}</div>
      </div>
  <button
    @click="printTicket"
    class="w-full print:hidden mb-3 px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-lg shadow transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2"
  >
    <span class="inline-flex items-center gap-2">
      <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17 17h2a2 2 0 002-2v-6a2 2 0 00-2-2h-2M7 7H5a2 2 0 00-2 2v6a2 2 0 002 2h2m10 0v2a2 2 0 01-2 2H9a2 2 0 01-2-2v-2m10 0H7' /></svg>
      Print Ticket
    </span>
  </button>
  <a
    :href="route('queue.register')"
    class="w-full print:hidden px-5 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold text-lg shadow transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 text-center block"
    style="margin-top: 0.5rem;"
  >
    <span class="inline-flex items-center gap-2">
      <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 19l-7-7 7-7' /></svg>
      Back to Registration
    </span>
  </a>
    </div>
  </div>
</template>

<style>
@media print {
  body { background: #fff !important; }
  .print\:max-w-full { max-width: 100% !important; }
  .print\:shadow-none { box-shadow: none !important; }
  .print\:bg-white { background: #fff !important; }
  .print\:hidden { display: none !important; }
}
</style>
