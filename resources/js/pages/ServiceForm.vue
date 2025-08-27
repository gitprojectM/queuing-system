<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
type Service = {
  id?: number;
  name?: string;
  description?: string;
};
const { service } = usePage().props as { service?: Service };
const form = useForm({
  name: service?.name || '',
  description: service?.description || '',
});
function submit() {
  if (service && typeof service.id !== 'undefined' && service.id !== null) {
    form.put(`/services/${service.id}`);
  } else {
    form.post('/services');
  }
}
</script>
<template>
  <Head :title="service ? 'Edit Service' : 'Add Service'"/>
  <div class="flex items-center justify-center min-h-[60vh] bg-gray-50 dark:bg-gray-900 py-8">
    <div class="w-full max-w-lg bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
  <div v-if="service !== undefined || !service" class="flex items-center mb-6">
        <svg class="w-8 h-8 text-primary-500 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
        <h1 class="text-2xl font-bold">{{ service ? 'Edit Service' : 'Add Service' }}</h1>
      </div>
      <div v-else class="text-center text-red-500 font-semibold py-8">
        Service not found.
      </div>
  <form v-if="service !== undefined || !service" @submit.prevent="submit" class="space-y-6">
        <div>
          <label class="block mb-1 font-semibold text-gray-700 dark:text-gray-200">Name</label>
          <input v-model="form.name" type="text" class="input input-bordered w-full focus:ring-2 focus:ring-primary-500" required placeholder="Enter service name" />
          <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
        </div>
        <div>
          <label class="block mb-1 font-semibold text-gray-700 dark:text-gray-200">Description</label>
          <textarea v-model="form.description" class="input input-bordered w-full focus:ring-2 focus:ring-primary-500" rows="3" placeholder="Describe the service (optional)"></textarea>
          <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</div>
        </div>
        <div class="flex justify-between items-center mt-6">
          <Link href="/services" class="btn btn-secondary">Cancel</Link>
          <button type="submit" class="btn btn-primary px-6 py-2">{{ service ? 'Update' : 'Create' }}</button>
        </div>
      </form>
    </div>
  </div>
</template>
