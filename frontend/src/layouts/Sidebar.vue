<template>
  <aside :class="['bg-white border-r min-h-screen transition-all', collapsed ? 'w-16' : 'w-64']">
    <!-- Brand -->
    <div class="h-20 flex items-center pl-10">
      <span class="text-xl font-bold text-blue-600">ODPArea</span>
    </div>

    <!-- Menu -->
    <nav class="px-3 py-1 space-y-2">
      <RouterLink
        v-for="item in menu"
        :key="item.path"
        :to="item.path"
        class="flex items-center gap-3 px-4 py-3 rounded-md text-[15px] font-medium transition"
        :class="{
          'bg-blue-100 text-blue-700': $route.path === item.path,
          'text-gray-600 hover:bg-gray-100 hover:text-gray-800': $route.path !== item.path
        }"
      >
        <component :is="item.icon" class="w-5 h-5"/>
        <span v-if="!collapsed">{{ item.label }}</span>
      </RouterLink>
    </nav>
  </aside>
</template>

<script setup>
import { ref } from 'vue'
import { LineChart, Map, List } from 'lucide-vue-next'

const collapsed = ref(false)

const menu = [
  { label: 'Dashboard', path: '/dashboard', icon: LineChart },
  { label: 'Maps', path: '/maps', icon: Map },
  { label: 'List ODP', path: '/odp', icon: List }
]

defineExpose({ collapsed })
</script>

<!-- <template>
  <aside class="w-64 bg-white border-r">
    <div class="h-16 flex items-center justify-center">
      <span class="font-bold text-lg">Maps-Pro</span>
    </div>
    <nav class="px-4 py-2">
      <RouterLink
        v-for="item in menu"
        :key="item.path"
        :to="item.path"
        class="block px-4 py-2 rounded-lg transition"
        :class="{
          'bg-blue-600 text-white': $route.path === item.path,
          'text-gray-700 hover:bg-gray-100': $route.path !== item.path
        }"
      >
        {{ item.label }}
      </RouterLink>
    </nav>
  </aside>
</template>

<script setup>
const menu = [
  { label: 'Dashboard', path: '/dashboard' },
  { label: 'Maps', path: '/maps' },
  { label: 'List ODP', path: '/odp' }
]
</script> -->
