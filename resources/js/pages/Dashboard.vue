<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { Queue, Service } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const { services, todayStats } = usePage().props as unknown as {
    services: Service[];
    todayStats: { total: number; waiting: number; serving: number; completed: number };
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">

            <!-- Quick-action cards -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <!-- Queue Monitor -->
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border flex items-center justify-center">
                    <a href="/queue/monitor"
                       class="flex flex-col items-center justify-center w-full h-full p-4 rounded-xl bg-gradient-to-br from-green-500 to-green-700 text-white font-bold text-lg shadow-lg hover:from-green-600 hover:to-green-800 transition-all">
                        <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8M12 8v8" />
                        </svg>
                        Queue Monitor
                    </a>
                </div>

                <!-- Register for Queue -->
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border flex items-center justify-center">
                    <a href="/queue/register"
                       class="flex flex-col items-center justify-center w-full h-full p-4 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 text-white font-bold text-lg shadow-lg hover:from-blue-600 hover:to-blue-800 transition-all">
                        <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Register for Queue
                    </a>
                </div>

                <!-- Today's Stats (replaces PlaceholderPattern) -->
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-gradient-to-br from-purple-500 to-purple-700 text-white p-4 flex flex-col justify-between shadow-lg">
                    <p class="font-bold text-sm uppercase tracking-wide opacity-80">Today's Summary</p>
                    <div class="grid grid-cols-2 gap-2 text-center">
                        <div>
                            <div class="text-3xl font-extrabold">{{ todayStats.total }}</div>
                            <div class="text-xs opacity-80">Registered</div>
                        </div>
                        <div>
                            <div class="text-3xl font-extrabold">{{ todayStats.waiting }}</div>
                            <div class="text-xs opacity-80">Waiting</div>
                        </div>
                        <div>
                            <div class="text-3xl font-extrabold">{{ todayStats.serving }}</div>
                            <div class="text-xs opacity-80">Serving</div>
                        </div>
                        <div>
                            <div class="text-3xl font-extrabold">{{ todayStats.completed }}</div>
                            <div class="text-xs opacity-80">Completed</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Service overview -->
            <div class="relative flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border p-6 overflow-x-auto">
                <h2 class="text-xl font-bold mb-4">Service Overview</h2>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div v-for="service in services" :key="service.id"
                         class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-200 dark:border-gray-700">
                        <div class="font-bold text-lg text-blue-700 dark:text-blue-300 mb-1">{{ service.name }}</div>
                        <div v-if="service.description" class="mb-2 text-gray-500 text-sm">{{ service.description }}</div>

                        <div class="mb-2">
                            <span class="font-semibold text-sm">Assigned Windows:</span>
                            <div v-if="service.windows && service.windows.length" class="mt-1 space-y-1">
                                <div v-for="w in service.windows" :key="w.id">
                                    <span class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-200">
                                        {{ w.name }}
                                    </span>
                                    <span v-if="w.users && w.users.length" class="ml-2 text-xs text-gray-500 dark:text-gray-300">
                                        <span v-for="u in w.users" :key="u.id"
                                              class="inline-block bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 rounded px-2 py-0.5 mr-1">
                                            {{ u.name }}
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <span v-else class="text-gray-400 text-xs ml-1">No windows</span>
                        </div>

                        <div class="flex items-center gap-2 mt-3">
                            <span class="font-semibold text-sm">On Queue:</span>
                            <span class="text-lg font-bold text-green-700 dark:text-green-300">
                                {{ service.queues ? service.queues.length : 0 }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
