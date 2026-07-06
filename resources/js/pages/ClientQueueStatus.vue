<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import type { Queue, QueueStep } from '@/types';

const props = usePage().props as unknown as { queue: Queue; ahead: number };

const queue = ref<Queue>({ ...props.queue });
const ahead = ref<number>(props.ahead);
const cancelling = ref(false);
let echoChannel: any = null;
let pollTimer: number | null = null;

const statusLabel = computed(() => {
    switch (queue.value.status) {
        case 'waiting':   return 'Waiting in line';
        case 'assigned':  return 'Now Serving — Please proceed!';
        case 'completed': return 'Service Completed';
        case 'cancelled': return 'Cancelled';
        default:          return queue.value.status;
    }
});

const statusColor = computed(() => {
    switch (queue.value.status) {
        case 'waiting':   return 'from-yellow-400 to-yellow-500';
        case 'assigned':  return 'from-blue-500 to-blue-600';
        case 'completed': return 'from-green-500 to-green-600';
        case 'cancelled': return 'from-gray-400 to-gray-500';
        default:          return 'from-gray-400 to-gray-500';
    }
});

const isActive = computed(() => ['waiting', 'assigned'].includes(queue.value.status));

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
        // ignore
    }
}

function cancelQueue() {
    if (!confirm('Are you sure you want to cancel your queue entry?')) return;
    cancelling.value = true;
    router.post(route('queue.cancel', { queue: queue.value.id }), {}, {
        onError: () => { cancelling.value = false; },
    });
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

    // Fallback polling every 15 seconds.
    pollTimer = window.setInterval(refreshStatus, 15000);
});

onUnmounted(() => {
    if (echoChannel) window.Echo?.leave('queues');
    if (pollTimer) clearInterval(pollTimer);
});
</script>

<template>
    <Head title="My Queue Status" />
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-blue-100 dark:from-gray-900 dark:to-blue-950 flex flex-col items-center justify-center p-4">

        <!-- Card -->
        <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden">

            <!-- Coloured status header -->
            <div :class="['bg-gradient-to-r text-white px-6 py-6 text-center', statusColor]">
                <p class="text-xs uppercase tracking-widest opacity-80 mb-1">TAMPCO Queuing System</p>
                <div class="text-7xl font-extrabold leading-none mb-2">
                    {{ queue.queue_number }}
                </div>
                <p class="text-base font-semibold">{{ statusLabel }}</p>
            </div>

            <!-- Body -->
            <div class="px-6 py-5 space-y-4">

                <!-- Called alert -->
                <div v-if="queue.status === 'assigned'"
                     class="rounded-xl bg-blue-50 dark:bg-blue-900/30 border border-blue-300 dark:border-blue-600 p-4 text-center animate-pulse">
                    <p class="text-blue-800 dark:text-blue-200 font-bold text-lg">You are being called!</p>
                    <p class="text-blue-700 dark:text-blue-300 text-sm mt-1">
                        Please proceed to <strong>{{ queue.window?.name }}</strong>.
                    </p>
                </div>

                <!-- Completed notice -->
                <div v-if="queue.status === 'completed'"
                     class="rounded-xl bg-green-50 dark:bg-green-900/30 border border-green-300 dark:border-green-600 p-4 text-center">
                    <p class="text-green-800 dark:text-green-200 font-bold">Thank you!</p>
                    <p class="text-green-700 dark:text-green-300 text-sm mt-1">Your service has been completed.</p>
                </div>

                <!-- Details grid -->
                <dl class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-500">Service</dt>
                        <dd class="font-semibold text-right">{{ queue.service?.name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-500">Window</dt>
                        <dd class="font-semibold text-right">{{ queue.window?.name ?? 'Not yet assigned' }}</dd>
                    </div>
                    <div v-if="queue.status === 'waiting'" class="flex justify-between py-2">
                        <dt class="text-gray-500">People ahead</dt>
                        <dd class="font-semibold text-right">{{ ahead }}</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-500">Registered</dt>
                        <dd class="font-semibold text-right">{{ new Date(queue.created_at).toLocaleTimeString() }}</dd>
                    </div>
                </dl>

                <!-- Multi-step progress -->
                <div v-if="queue.steps && queue.steps.length > 1" class="mt-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Service Steps</p>
                    <ol class="space-y-2">
                        <li v-for="step in queue.steps" :key="step.id"
                            class="flex items-center gap-3 text-sm">
                            <span :class="[
                                'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0',
                                step.status === 'completed' ? 'bg-green-500 text-white'
                                    : step.status === 'waiting' || step.status === 'assigned' ? 'bg-blue-500 text-white'
                                    : 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                            ]">
                                <svg v-if="step.status === 'completed'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span v-else>{{ step.step_order }}</span>
                            </span>
                            <span :class="step.status === 'completed' ? 'line-through text-gray-400' : 'text-gray-700 dark:text-gray-200'">
                                {{ step.service?.name ?? `Step ${step.step_order}` }}
                            </span>
                            <span v-if="step.status === 'waiting' || step.status === 'assigned'"
                                  class="ml-auto text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                                Current
                            </span>
                        </li>
                    </ol>
                </div>

                <!-- Actions -->
                <div class="flex flex-col gap-2 pt-2">
                    <a :href="route('queue.ticket', { queue: queue.id })"
                       class="w-full flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-semibold transition-all text-sm">
                        View Ticket
                    </a>

                    <button v-if="isActive"
                            @click="cancelQueue"
                            :disabled="cancelling"
                            class="w-full flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-700 dark:text-red-400 font-semibold transition-all text-sm disabled:opacity-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        {{ cancelling ? 'Cancelling…' : 'Cancel My Queue' }}
                    </button>

                    <a v-if="!isActive"
                       :href="route('queue.register')"
                       class="w-full flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold transition-all text-sm">
                        Register Again
                    </a>
                </div>
            </div>
        </div>

        <p class="mt-4 text-xs text-blue-900/50 dark:text-blue-100/40">
            This page updates automatically. Keep it open while you wait.
        </p>
    </div>
</template>
