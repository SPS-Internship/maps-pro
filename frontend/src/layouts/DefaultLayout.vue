<template>
  <div class="flex min-h-screen bg-gray-100">
    <!-- Sidebar -->
    <Sidebar />

    <!-- Main content -->
    <div class="flex-1 flex flex-col">
      <!-- Topbar -->
        <header class="h-16 px-6 flex items-center justify-between border-b bg-white">
            <h1 class="text-lg font-semibold capitalize">{{ pageTitle }}</h1>

            <div class="relative">
                <button @click="toggleDropdown" class="text-sm font-medium flex items-center gap-3">
                <span>{{ userName }}</span>
                <ChevronDown class="w-4 h-4" />
                </button>

                <div v-if="dropdown"
                class="absolute right-0 mt-2 w-40 bg-white shadow rounded-md py-2 z-50 text-sm">
                <a href="#" class="block px-4 py-2 hover:bg-gray-100">Profile</a>
                <a @click="logout" class="block px-4 py-2 hover:bg-gray-100 text-sm cursor-pointer">Log Out</a>
                <!-- <a href="#" class="block px-4 py-2 hover:bg-gray-100">Log Out</a> -->
                </div>
            </div>
        </header>


      <!-- Page content -->
      <main class="p-6 flex-1 overflow-y-auto">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Sidebar from './Sidebar.vue'
import { ChevronDown } from 'lucide-vue-next'

const router = useRouter()      // navigate / .push() ✅
const route = useRoute()        //  baca info url saat ini ✅
const userName = 'Salma'        // TODO: nanti ambil dari backend
const dropdown = ref(false)

const pageTitle = computed(() => {
  // ambil last path segment sebagai judul
  const path = route.path.split('/').pop()
  return path.replace('-', ' ')        // contoh 'list-odp' => 'list odp'
})

const toggleDropdown = () => {
  dropdown.value = !dropdown.value
}

const logout = () => {
  localStorage.removeItem('user')
  dropdown.value = false
  router.push('/login')
}

</script>
