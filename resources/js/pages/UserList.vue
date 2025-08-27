<template>
  <Head title="User List" />
  <div class="flex flex-col items-center min-h-[60vh] bg-gray-50 dark:bg-gray-900 py-8">
    <div class="w-full max-w-5xl bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">User List</h1>
        <a href="/dashboard" class="inline-flex items-center gap-1 px-4 py-2 rounded-md bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium shadow transition">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
          Back to Dashboard
        </a>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-100 dark:bg-gray-700">
            <tr>
              <th class="py-3 px-6 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Name</th>
              <th class="py-3 px-6 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Email</th>
              <th class="py-3 px-6 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Services</th>
              <th class="py-3 px-6 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Windows</th>
              <th class="py-3 px-6 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
              <td class="py-3 px-6">{{ user.name }}</td>
              <td class="py-3 px-6">{{ user.email }}</td>
              <td class="py-3 px-6">
                <span v-if="user.services && user.services.length">
                  <span v-for="service in user.services" :key="service.id" class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold mr-2 mb-1 px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-200">
                    {{ service.name }}
                  </span>
                </span>
                <span v-else class="text-gray-400 text-xs">No services</span>
              </td>
              <td class="py-3 px-6">
                <span v-if="user.windows && user.windows.length">
                  <span v-for="window in user.windows" :key="window.id" class="inline-block bg-green-100 text-green-800 text-xs font-semibold mr-2 mb-1 px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-200">
                    {{ window.name }}
                  </span>
                </span>
                <span v-else class="text-gray-400 text-xs">No windows</span>
              </td>
              <td class="py-3 px-6">
                <a :href="`/users/${user.id}/edit`" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium shadow mr-2 transition">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 11l6 6M3 21h6v-6H3v6zm0 0l9-9 6 6"/></svg>
                  Edit
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
const { users = [] } = usePage().props as any;
</script>
