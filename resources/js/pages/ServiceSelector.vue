<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';

const services = computed(() => usePage().props.services as any[]);
const selectedServices = ref<number[]>([]);

function toggleService(serviceId: number) {
  const index = selectedServices.value.indexOf(serviceId);
  if (index > -1) {
    selectedServices.value.splice(index, 1);
  } else {
    selectedServices.value.push(serviceId);
  }
}

function viewSelectedServices() {
  if (selectedServices.value.length === 0) {
    alert('Please select at least one service to view');
    return;
  }
  
  const serviceIds = selectedServices.value.join(',');
  router.get(`/now-serving?services=${serviceIds}`);
}

function viewAllServices() {
  router.get('/now-serving');
}

function selectAll() {
  selectedServices.value = services.value.map(service => service.id);
}

function clearAll() {
  selectedServices.value = [];
}
</script>

<template>
  <Head title="Select Services - Now Serving" />
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-blue-200 dark:from-gray-900 dark:to-blue-900 py-8">
    <div class="max-w-4xl mx-auto px-4">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-4xl font-extrabold text-blue-700 dark:text-blue-200 mb-4">
          Select Services to Display
        </h1>
        <p class="text-lg text-gray-600 dark:text-gray-300">
          Choose which services you want to monitor on the Now Serving page
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-center space-x-4 mb-8">
        <button @click="selectAll" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
          Select All
        </button>
        <button @click="clearAll" 
                class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
          Clear All
        </button>
        <Link href="/now-serving" 
              class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
          View All Services
        </Link>
      </div>

      <!-- Services Grid -->
      <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 mb-8">
        <div v-for="service in services" :key="service.id" 
             class="rounded-xl shadow-lg bg-white dark:bg-gray-800 border-2 transition-all duration-200 cursor-pointer"
             :class="{
               'border-blue-500 bg-blue-50 dark:bg-blue-900': selectedServices.includes(service.id),
               'border-gray-200 dark:border-gray-700 hover:border-blue-300': !selectedServices.includes(service.id)
             }"
             @click="toggleService(service.id)">
          
          <div class="p-6">
            <!-- Checkbox -->
            <div class="flex items-start justify-between mb-4">
              <div class="flex items-center">
                <input type="checkbox" 
                       :checked="selectedServices.includes(service.id)"
                       @change="toggleService(service.id)"
                       class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                <span class="ml-3 text-lg font-semibold text-gray-900 dark:text-gray-100">
                  {{ service.name }}
                </span>
              </div>
            </div>
            
            <!-- Service Description -->
            <p class="text-gray-600 dark:text-gray-300 mb-4">
              {{ service.description || 'No description available' }}
            </p>
            
            <!-- Windows Count -->
            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
              {{ service.windows?.length || 0 }} windows
            </div>
          </div>
        </div>
      </div>

      <!-- Selected Services Summary -->
      <div v-if="selectedServices.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
          Selected Services ({{ selectedServices.length }})
        </h3>
        <div class="flex flex-wrap gap-2 mb-4">
          <span v-for="serviceId in selectedServices" :key="serviceId"
                class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-1 rounded-full text-sm font-medium">
            {{ services.find(s => s.id === serviceId)?.name }}
          </span>
        </div>
        <button @click="viewSelectedServices" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-medium text-lg transition-colors">
          View Selected Services
        </button>
      </div>

      <!-- No Selection Message -->
      <div v-else class="text-center text-gray-500 dark:text-gray-400">
        <p class="text-lg mb-4">No services selected</p>
        <p class="text-sm">Click on the service cards above to select them</p>
      </div>

      <!-- Back to Dashboard -->
      <div class="text-center mt-8">
        <Link href="/dashboard" 
              class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 font-medium">
          ← Back to Dashboard
        </Link>
      </div>
    </div>
  </div>
</template>
