<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import type { Queue } from '@/types';

const props = usePage().props as unknown as { queue: Queue; ahead: number };

const queue = ref<Queue>({ ...props.queue });
const ahead = ref<number>(props.ahead);
let echoChannel: any = null;

function printTicket() {
    window.print();
}

function statusLabel(status: string) {
    if (status === 'waiting')   return 'Waiting';
    if (status === 'assigned')  return 'Now Serving';
    if (status === 'completed') return 'Completed';
    if (status === 'cancelled') return 'Cancelled';
    return status;
}

function statusColor(status: string) {
    if (status === 'waiting')   return 'bg-yellow-100 text-yellow-800';
    if (status === 'assigned')  return 'bg-blue-100 text-blue-800 animate-pulse';
    if (status === 'completed') return 'bg-green-100 text-green-800';
    if (status === 'cancelled') return 'bg-red-100 text-red-800';
    return 'bg-gray-100 text-gray-800';
}

async function refreshStatus() {
    try {
        const res = await fetch(`/api/queue/status/${queue.value.id}`, {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        });
        if (!res.ok) return;
        const data = await res.json();
        queue.value = data.queue;
        ahead.value = data.ahead;
    } catch {
        // ignore network errors
    }
}

onMounted(() => {
    const setupEcho = () => {
        if (!window.Echo) { setTimeout(setupEcho, 200); return; }
        echoChannel = window.Echo.channel('queues').listen('QueueUpdated', (event: any) => {
            if (event?.queue?.id === queue.value.id) {
                queue.value = { ...queue.value, ...event.queue };
                refreshStatus();
            }
        });
    };
    setupEcho();
});

onUnmounted(() => {
    if (echoChannel) window.Echo?.leave('queues');
});
</script>

<template>
    <Head title="Queue Ticket" />
    <div class="flex items-center justify-center min-h-screen bg-gray-50 dark:bg-gray-900 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 w-full max-w-sm print:max-w-full print:shadow-none print:bg-white">

            <!-- Header -->
            <div class="text-center mb-6">
                <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">TAMPCO Queuing System</p>
                <h2 class="text-base font-bold text-gray-700 dark:text-gray-200">QUEUE TICKET</h2>
            </div>

            <!-- Queue Number -->
            <div class="flex flex-col items-center mb-6">
                <span class="text-8xl font-extrabold text-blue-700 dark:text-blue-300 leading-none">
                    {{ queue.queue_number }}
                </span>
            </div>

            <!-- Status badge -->
            <div class="flex justify-center mb-5">
                <span :class="['px-4 py-1 rounded-full text-sm font-semibold', statusColor(queue.status)]">
                    {{ statusLabel(queue.status) }}
                </span>
            </div>

            <!-- Details -->
            <div class="space-y-2 text-sm mb-6 border-t border-b border-gray-100 dark:border-gray-700 py-4">
                <div class="flex justify-between">
                    <span class="text-gray-500">Service</span>
                    <span class="font-semibold">{{ queue.service?.name ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Window</span>
                    <span class="font-semibold">{{ queue.window?.name ?? 'Waiting for assignment' }}</span>
                </div>
                <div v-if="queue.status === 'waiting'" class="flex justify-between">
                    <span class="text-gray-500">People ahead</span>
                    <span class="font-semibold">{{ ahead }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Registered at</span>
                    <span class="font-semibold">{{ new Date(queue.created_at).toLocaleTimeString() }}</span>
                </div>
            </div>

            <!-- Now Serving Alert -->
            <div v-if="queue.status === 'assigned'"
                 class="mb-5 rounded-xl bg-blue-50 dark:bg-blue-900/30 border border-blue-300 dark:border-blue-600 p-4 text-center">
                <p class="text-blue-800 dark:text-blue-200 font-bold text-lg">
                    You are being called!
                </p>
                <p class="text-blue-700 dark:text-blue-300 text-sm mt-1">
                    Please proceed to <strong>{{ queue.window?.name }}</strong>.
                </p>
            </div>

            <!-- Buttons -->
            <div class="flex flex-col gap-2 print:hidden">
                <button @click="printTicket"
                    class="w-full flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold shadow transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-6a2 2 0 00-2-2h-2M7 7H5a2 2 0 00-2 2v6a2 2 0 002 2h2m10 0v2a2 2 0 01-2 2H9a2 2 0 01-2-2v-2m10 0H7" />
                    </svg>
                    Print Ticket
                </button>

                <a :href="route('queue.status', { queue: queue.id })"
                    class="w-full flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold shadow transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Track My Queue
                </a>

                <a :href="route('queue.register')"
                    class="w-full flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold shadow transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Registration
                </a>
            </div>
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
