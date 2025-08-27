<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
type Window = {
  id?: number;
  name?: string;
  description?: string;
};
const pageProps = usePage().props as unknown as { window?: Window, services: any[], selectedServices: number[] };
const { window, services = [], selectedServices = [] } = pageProps;
const form = useForm({
  name: window?.name || '',
  description: window?.description || '',
  services: [...selectedServices],
});
function submit() {
  if (window && typeof window.id !== 'undefined' && window.id !== null) {
  form.put(`/windows/${window.id}`);
  } else {
  form.post('/windows');
  }
}
</script>
<template>
  <Head :title="window ? 'Edit Window' : 'Add Window'" />
  <div class="flex items-center justify-center min-h-[60vh] bg-gray-50 dark:bg-gray-900 py-8">
    <div class="w-full max-w-lg bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
  <template v-if="window !== null">
        <div class="flex items-center mb-6">
          <svg class="w-8 h-8 text-primary-500 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
          <h1 class="text-2xl font-bold">{{ window ? 'Edit Window' : 'Add Window' }}</h1>
        </div>
        <form @submit.prevent="submit" class="space-y-6">
          <div>
            <label class="block mb-1 font-semibold text-gray-700 dark:text-gray-200">Name</label>
            <input v-model="form.name" type="text" class="input input-bordered w-full focus:ring-2 focus:ring-primary-500" required placeholder="Enter window name" />
            <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
          </div>
          <div>
            <label class="block mb-1 font-semibold text-gray-700 dark:text-gray-200">Description</label>
            <textarea v-model="form.description" class="input input-bordered w-full focus:ring-2 focus:ring-primary-500" rows="3" placeholder="Describe the window (optional)"></textarea>
            <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</div>
          </div>
          <div>
            <label class="block mb-2 font-semibold text-gray-700 dark:text-gray-200 text-lg">Assign Services</label>
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-4 mb-2">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <label v-for="service in services" :key="service.id" class="flex items-center gap-2 cursor-pointer p-2 rounded hover:bg-blue-50 dark:hover:bg-blue-900 transition">
                  <input
                    type="checkbox"
                    :value="service.id"
                    v-model="form.services"
                    class="accent-blue-600 w-4 h-4"
                  />
                  <span class="font-medium text-gray-800 dark:text-gray-100">{{ service.name }}</span>
                  <span v-if="service.description" class="text-xs text-gray-500 ml-2">{{ service.description }}</span>
                </label>
              </div>
            </div>
            <div v-if="form.errors.services" class="text-red-500 text-sm mt-1">{{ form.errors.services }}</div>
          </div>
          <div class="flex justify-between items-center mt-6">
            <Link href="/windows" class="btn btn-secondary">Cancel</Link>
            <button type="submit" class="btn btn-primary px-6 py-2">{{ window ? 'Update' : 'Create' }}</button>
          </div>
        </form>
      </template>
      <div v-else class="text-center text-red-500 font-semibold py-8">
        Service not found.
      </div>
    </div>
  </div>
</template>
