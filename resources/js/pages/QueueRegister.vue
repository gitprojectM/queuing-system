<template>
  <Head title="Queue Registration" />
  <div class="flex items-center justify-center min-h-[60vh] bg-gray-50 dark:bg-gray-900 py-8">
    <Dialog v-model:open="modalOpen">
      <DialogTrigger as-child>
        <button class="btn btn-primary px-6 py-2 shadow-lg text-lg font-semibold flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zm0 10c-4.418 0-8-1.79-8-4V6a2 2 0 012-2h12a2 2 0 012 2v8c0 2.21-3.582 4-8 4z" /></svg>
          Register for Queue
        </button>
      </DialogTrigger>
      <DialogContent class="max-w-md w-full bg-gradient-to-b from-[#fff7f3] to-white dark:from-[#1a1a1a] dark:to-[#232323] p-0 overflow-hidden rounded-2xl shadow-2xl border-0">
        <div class="flex flex-col items-center px-8 pt-8 pb-2">
          <div class="rounded-full bg-[#ffe3db] dark:bg-[#2a1a1a] p-4 mb-4 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-[#f53003]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zm0 10c-4.418 0-8-1.79-8-4V6a2 2 0 012-2h12a2 2 0 012 2v8c0 2.21-3.582 4-8 4z" /></svg>
          </div>
          <DialogHeader class="w-full text-center">
            <DialogTitle class="text-2xl font-bold text-[#f53003] dark:text-[#FF4433] mb-1">Register for Queue</DialogTitle>
            <DialogDescription class="mb-4 text-gray-600 dark:text-gray-300 text-base">Enter your details to get your queue number. You'll receive a printable ticket after registration.</DialogDescription>
          </DialogHeader>
        </div>
        <div class="px-8 pb-8">
          <form v-if="!successMessage" @submit.prevent="submit" class="space-y-6" aria-label="Queue Registration Form">
            <div>
              <label class="block mb-1 font-semibold text-gray-700 dark:text-gray-200" for="name">Full Name</label>
              <input v-model="form.name" id="name" type="text"
                class="input input-bordered w-full rounded-xl border border-[#e3e3e0] dark:border-[#333] bg-white dark:bg-[#232323] px-4 py-3 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#f53003] focus:border-[#f53003] transition-all duration-150 placeholder-gray-400 dark:placeholder-gray-500 text-base"
                required placeholder="Enter your name" autocomplete="name" />
              <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
            </div>
            <div>
              <label class="block mb-1 font-semibold text-gray-700 dark:text-gray-200" for="service">Service</label>
              <select v-model="form.service_id" id="service"
                class="input input-bordered w-full rounded-xl border border-[#e3e3e0] dark:border-[#333] bg-white dark:bg-[#232323] px-4 py-3 shadow-sm focus:outline-none focus:ring-2 focus:ring-[#f53003] focus:border-[#f53003] transition-all duration-150 text-base"
                required>
                <option value="" disabled>Select a service</option>
                <option v-for="service in services" :key="service.id" :value="service.id">{{ service.name }}</option>
              </select>
              <div v-if="form.errors.service_id" class="text-red-500 text-sm mt-1">{{ form.errors.service_id }}</div>
            </div>
            <div class="flex justify-end mt-6">
              <button type="submit" class="btn btn-primary px-8 py-2 flex items-center gap-2 text-lg rounded-full shadow-md transition-all duration-150 hover:scale-105" :disabled="form.processing">
                <svg v-if="form.processing" class="animate-spin h-5 w-5 mr-2 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                <span v-if="!form.processing">Register</span>
                <span v-else>Registering...</span>
              </button>
            </div>
          </form>
          <div v-else class="flex flex-col items-center justify-center py-8">
            <svg class="h-16 w-16 text-green-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2l4-4m5 2a9 9 0 11-18 0a9 9 0 0118 0z" /></svg>
            <span class="text-xl font-semibold text-green-700 dark:text-green-400 mb-2">{{ successMessage }}</span>
            <a v-if="ticketUrl" :href="ticketUrl" target="_blank" class="btn btn-success px-6 py-2 mt-2 rounded-full shadow hover:scale-105 transition-all">View/Print Ticket</a>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="ts">

import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogDescription } from '@/components/ui/dialog';

const { services = [], windows = [] } = usePage().props as any;
const form = useForm({
  name: '',
  service_id: '',
  window_id: '',
});
const successMessage = ref('');
const ticketUrl = ref('');
const modalOpen = ref(true);

const availableWindows = computed(() => {
  if (!form.service_id) return [];
  // Only show windows that are unoccupied and offer the selected service
  return windows.filter((w: any) =>
    w.services &&
    w.services.some((s: any) => s.id === Number(form.service_id)) &&
    (!w.current_client || w.current_client === null)
  );
});

function submit() {
  form.post('/queue/register', {
    onSuccess: () => {
      successMessage.value = 'You have been registered in the queue!';
      // Get the ticket URL from the latest page props
      const queue = usePage().props.queue as { id: number } | undefined;
      if (queue && queue.id) {
        ticketUrl.value = `/queue/ticket/${queue.id}`;
      } else {
        ticketUrl.value = '';
      }
      form.reset();
    },
  });
}
</script>
