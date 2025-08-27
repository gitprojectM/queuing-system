<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import { ref, onMounted } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
        {
                title: 'Dashboard',
                href: '/dashboard',
        },
];

const services = ref<any[]>([]);
onMounted(async () => {
    const res = await fetch('/api/dashboard/services');
    const data = await res.json();
    services.value = data.services;
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border flex items-center justify-center">
                    <a href="/queue/monitor" class="flex flex-col items-center justify-center w-full h-full p-4 rounded-xl bg-gradient-to-br from-green-500 to-green-700 text-white font-bold text-lg shadow-lg hover:from-green-600 hover:to-green-800 transition-all">
                        <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8M12 8v8"/></svg>
                        On Queue Monitor
                    </a>
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border flex items-center justify-center">
                    <a href="/queue/register" class="flex flex-col items-center justify-center w-full h-full p-4 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 text-white font-bold text-lg shadow-lg hover:from-blue-600 hover:to-blue-800 transition-all">
                        <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Register for Queue
                    </a>
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border flex items-center justify-center">
                    <PlaceholderPattern />
                </div>
            </div>
                        <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border p-6 overflow-x-auto">
                                <h2 class="text-xl font-bold mb-4">Service Overview</h2>
                                                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                                                    <div v-for="service in services" :key="service.id" class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-700">
                                                        <div class="flex items-center mb-2">
                                                            <span class="font-bold text-lg text-blue-700 dark:text-blue-300">{{ service.name }}</span>
                                                        </div>
                                                        <div class="mb-2 text-gray-500 text-sm">{{ service.description }}</div>
                                                        <div class="mb-2">
                                                            <span class="font-semibold">Assigned Windows:</span>
                                                            <span v-if="service.windows && service.windows.length">
                                                                <div v-for="w in service.windows" :key="w.id" class="mb-1">
                                                                    <span class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold mr-2 px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-200">
                                                                        {{ w.name }}
                                                                    </span>
                                                                    <span v-if="w.users && w.users.length" class="ml-2 text-xs text-gray-600 dark:text-gray-300">
                                                                        Users:
                                                                        <span v-for="u in w.users" :key="u.id" class="inline-block bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-0.5 mr-1">
                                                                            {{ u.name }}
                                                                        </span>
                                                                    </span>
                                                                    <span v-else class="ml-2 text-xs text-gray-400">No users</span>
                                                                </div>
                                                            </span>
                                                            <span v-else class="text-gray-400 text-xs">No windows</span>
                                                        </div>
                                                        <div class="mb-2">
                                                            <span class="font-semibold">Assigned Users:</span>
                                                            <span v-if="service.users && service.users.length">
                                                                <span v-for="u in service.users" :key="u.id" class="inline-block bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-0.5 mr-1">
                                                                    {{ u.name }}
                                                                </span>
                                                            </span>
                                                            <span v-else class="text-gray-400 text-xs">No users</span>
                                                        </div>
                                                        <div>
                                                            <span class="font-semibold">On Queue:</span>
                                                            <span class="ml-2 text-lg text-green-700 dark:text-green-300">{{ service.queues ? service.queues.length : 0 }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                        </div>
        </div>
    </AppLayout>
</template>
