<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
type Service = {
  id: number;
  name: string;
  description?: string;
};
type Window = {
  id: number;
  name: string;
  description?: string;
  services?: Service[];
};
const { windows } = usePage().props as unknown as { windows: Window[] };
</script>
<template>
  <Head title="Windows" />
  <div class="flex items-center justify-center min-h-[60vh] bg-gray-50 dark:bg-gray-900 py-8">
    <div class="w-full max-w-4xl bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
      <div class="flex flex-col gap-4 mb-6">
        <div class="flex justify-between items-center">
          <div class="flex items-center">
            <svg class="w-8 h-8 text-primary-500 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            <h1 class="text-2xl font-bold">Windows</h1>
          </div>
          <Link href="/windows/create" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold transition-all duration-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Window
          </Link>
        </div>
        <div>
          <Link href="/dashboard" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 hover:from-indigo-600 hover:to-pink-600 text-white text-sm font-bold shadow-lg transition-all duration-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back to Dashboard
          </Link>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-100 dark:bg-gray-700">
            <tr>
              <th class="py-3 px-6 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Name</th>
              <th class="py-3 px-6 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Description</th>
              <th class="py-3 px-6 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Services</th>
              <th class="py-3 px-6 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="window in windows" :key="window.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
              <td class="py-3 px-6">{{ window.name }}</td>
              <td class="py-3 px-6">{{ window.description }}</td>
              <td class="py-3 px-6">
                <div v-if="window.services && window.services.length">
                  <span v-for="service in window.services" :key="service.id" class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold mr-2 mb-1 px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-200">
                    {{ service.name }}
                  </span>
                </div>
                <span v-else class="text-gray-400 text-xs">No services</span>
              </td>
              <td class="py-3 px-6">
                <Link v-if="window && typeof window.id !== 'undefined' && window.id !== null" :href="`/windows/${window.id}/edit`" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium shadow mr-2 transition">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 11l6 6M3 21h6v-6H3v6zm0 0l9-9 6 6"/></svg>
                  Edit
                </Link>
                <form v-if="window && typeof window.id !== 'undefined' && window.id !== null" :action="`/windows/${window.id}`" method="post" style="display:inline">
                  <input type="hidden" name="_method" value="delete" />
                  <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md bg-red-500 hover:bg-red-600 text-white text-xs font-medium shadow transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Delete
                  </button>
                </form>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
