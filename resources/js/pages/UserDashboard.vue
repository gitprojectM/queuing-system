<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { usePage, Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const windows = ref<any[]>((usePage().props.windows as any[]) || []);
const services = ref<any[]>((usePage().props.services as any[]) || []);
const userName = computed(() => usePage().props.userName as string);

let queuesListener: any;
let windowsListener: any;

onMounted(() => {
	// Wait for Echo to be available
	const setupListeners = () => {
		if (window.Echo) {
			queuesListener = window.Echo.channel('queues')
				.listen('QueueUpdated', (event: any) => {
				if (event && event.queue) {
						// Remove the queue from all services first
						services.value = services.value.map(service => {
							let newQueues = service.queues.filter((q: any) => q.id !== event.queue.id);
							// Use == to handle string/number mismatch for service_id
							if (service.id == event.queue.service_id && event.queue.status === 'waiting') {
								newQueues = [...newQueues, event.queue];
							}
							return {
								...service,
								queues: newQueues.sort((a: any, b: any) => a.queue_number - b.queue_number)
							};
						});
						// Update windows: always sync waiting_queues
						windows.value = windows.value.map(window => {
							if (window.waiting_queues) {
								let newWaiting = window.waiting_queues.filter((q: any) => q.id !== event.queue.id);
								// Only add/update if this queue belongs to this window and is waiting
								if (event.queue.status === 'waiting' && event.queue.window_id === window.id) {
									newWaiting = [...newWaiting, event.queue];
								}
								return {
									...window,
									waiting_queues: newWaiting.sort((a: any, b: any) => a.queue_number - b.queue_number)
								};
							}
							return window;
						});
					}
				});
			windowsListener = window.Echo.channel('windows')
				.listen('WindowUpdated', (event: any) => {
					// Update the current client for the window
					if (event && event.window) {
						windows.value = windows.value.map(window => {
							if (window.id === event.window.id) {
								return {
									...window,
									current_client: event.window.current_client,
								};
							}
							return window;
						});
					}
				});
		} else {
			setTimeout(setupListeners, 100);
		}
	};
	setupListeners();
});

function nextQueue(windowId: number) {
	router.post(route('queue.next'), { window_id: windowId }, {
		preserveScroll: true,
		preserveState: true,
	});
}

function completeQueue(windowId: number) {
	router.post(route('queue.complete'), { window_id: windowId }, {
		preserveScroll: true,
		preserveState: true,
	});
}

// ...template starts after this line...
</script>

<template>
	<Head title="User Dashboard" />
	<AppLayout>
		<div class="p-6 relative">
			<h1 class="text-2xl font-bold mb-2">Welcome, {{ userName }}</h1>
			<div v-if="windows && windows.length" class="mb-2">
				<span class="font-semibold">My Windows:</span>
				<span v-for="(window, idx) in windows" :key="window.id">
					{{ window.name }}<span v-if="idx < windows.length - 1">, </span>
				</span>
			</div>
			<div v-if="services && services.length" class="mb-8">
				<div class="font-semibold text-lg mb-2">Waiting Queues by Service:</div>
				<div v-for="service in services" :key="service.id" class="mb-6">
					<div class="font-semibold text-blue-700 dark:text-blue-200 mb-2">{{ service.name }}</div>
					<div v-if="service.queues && service.queues.length" class="flex gap-2 flex-wrap">
						<div v-for="queue in service.queues" :key="queue.id"
							class="animate-pulse bg-yellow-100 border-2 border-yellow-400 text-yellow-900 font-bold rounded-lg px-4 py-2 shadow text-lg flex items-center justify-center"
							style="min-width: 64px; min-height: 48px; animation: pulse 1.5s infinite;">
							#{{ queue.queue_number }}
						</div>
					</div>
					<div v-else class="text-xs text-gray-400">No waiting clients for this service.</div>
				</div>
			</div>
			<div v-if="windows.length">
				<div v-for="window in windows" :key="window.id" class="mb-6 p-4 border rounded-xl bg-white dark:bg-gray-800 shadow">
					<div class="font-semibold text-lg text-blue-700 dark:text-blue-300 mb-2">Window: {{ window.name }}</div>
					<div class="text-gray-500 mb-2">{{ window.description }}</div>
					<div>
						<span class="font-semibold">Current Serving Client:</span>
						<span v-if="window.current_client">
							Queue #{{ window.current_client.queue_number }} -
							<span v-if="window.current_client.user && window.current_client.user.name">
								{{ window.current_client.user.name }}
							</span>
							<span v-else>
								{{ window.current_client.name || 'No name' }}
							</span>
						</span>
						<span v-else class="text-gray-400">No client assigned</span>
					</div>

					<!-- Waiting Queues Tiles -->
					<div v-if="window.waiting_queues && window.waiting_queues.length" class="mt-4">
						<div class="mb-2 font-semibold text-sm text-gray-700 dark:text-gray-200">Waiting Queues:</div>
						<div class="flex gap-2 flex-wrap">
							<div v-for="queue in window.waiting_queues" :key="queue.id"
								class="animate-pulse bg-yellow-100 border-2 border-yellow-400 text-yellow-900 font-bold rounded-lg px-4 py-2 shadow text-lg flex items-center justify-center"
								style="min-width: 64px; min-height: 48px; animation: pulse 1.5s infinite;">
								#{{ queue.queue_number }}
							</div>
						</div>
					</div>
					<div v-else class="mt-4 text-xs text-gray-400">No waiting clients for this window.</div>

					<!-- Next & Complete Buttons -->
					<div class="mt-4 flex gap-2">
						<button
							type="button"
							class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
							@click="nextQueue(window.id)"
						>
							Next
						</button>
						<button
							type="button"
							class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition"
							@click="completeQueue(window.id)"
							:disabled="!window.current_client"
						>
							Complete
						</button>
					</div>
				</div>
			</div>
			<div v-else class="text-gray-500">You have no assigned windows.</div>
		</div>
	</AppLayout>
</template>
