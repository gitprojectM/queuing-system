<template>
  <Head :title="user ? 'Edit User & Assignment' : 'Add User & Assign'" />
  <div class="flex items-center justify-center min-h-[60vh] bg-gray-50 dark:bg-gray-900 py-8">
    <div class="w-full max-w-lg bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
      <h1 class="text-2xl font-bold mb-6">{{ user ? 'Edit User & Assignment' : 'Add User & Assign' }}</h1>
      <form @submit.prevent="submit" class="space-y-6">
        <div>
          <label class="block mb-1 font-semibold text-gray-700 dark:text-gray-200">Name</label>
          <input v-model="form.name" type="text" class="input input-bordered w-full" required placeholder="Enter user name" />
          <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
        </div>
        <div>
          <label class="block mb-1 font-semibold text-gray-700 dark:text-gray-200">Email</label>
          <input v-model="form.email" type="email" class="input input-bordered w-full" required placeholder="Enter user email" />
          <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
        </div>
        <div>
          <label class="block mb-1 font-semibold text-gray-700 dark:text-gray-200">Password</label>
          <input v-model="form.password" type="password" class="input input-bordered w-full" :required="!user" placeholder="Enter password" />
          <div v-if="form.errors.password" class="text-red-500 text-sm mt-1">{{ form.errors.password }}</div>
          <div v-if="user" class="text-xs text-gray-400 mt-1">Leave blank to keep current password</div>
        </div>
        <div>
          <label class="block mb-1 font-semibold text-gray-700 dark:text-gray-200">Assign Service</label>
          <select v-model="form.service_id" class="input input-bordered w-full" required>
            <option value="" disabled>Select a service</option>
            <option v-for="service in services" :key="service.id" :value="service.id">{{ service.name }}</option>
          </select>
          <div v-if="form.errors.service_id" class="text-red-500 text-sm mt-1">{{ form.errors.service_id }}</div>
        </div>
        <div>
          <label class="block mb-1 font-semibold text-gray-700 dark:text-gray-200">Assign Window</label>
          <select v-model="form.window_id" class="input input-bordered w-full" required>
            <option value="" disabled>Select a window</option>
            <option v-for="window in windows" :key="window.id" :value="window.id">{{ window.name }}</option>
          </select>
          <div v-if="form.errors.window_id" class="text-red-500 text-sm mt-1">{{ form.errors.window_id }}</div>
        </div>
        <div class="flex justify-end mt-6">
          <button type="submit" class="btn btn-primary px-6 py-2">{{ user ? 'Update User' : 'Add User' }}</button>
        </div>
      </form>
  <div v-if="successMessage" class="mt-4 text-green-600 font-semibold">{{ successMessage }}</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const pageProps = usePage().props as any;
const { services = [], windows = [], user } = pageProps;

interface UserForm {
  name: string;
  email: string;
  password: string;
  service_id: string | number;
  window_id: string | number;
}

const form = useForm<UserForm>({
  name: user?.name || '',
  email: user?.email || '',
  password: '',
  service_id: user?.services?.[0]?.id || '',
  window_id: user?.windows?.[0]?.id || '',
});
const successMessage = ref('');

function submit() {
  if (user && user.id) {
    form.post(`/users/${user.id}/edit`, {
      onSuccess: () => {
        successMessage.value = 'User updated successfully!';
        form.password = '';
      },
    });
  } else {
    form.post('/users/assign', {
      onSuccess: () => {
        successMessage.value = 'User added and assigned successfully!';
        form.reset();
      },
    });
  }
}
</script>
