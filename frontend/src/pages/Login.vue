<template>
  <div class="min-h-screen flex items-center justify-center"
       style="background: linear-gradient(to bottom right,#0a0a2e,#071a52)">
    <div class="bg-white rounded-xl shadow-md p-8 w-full max-w-md">
      <h2 class="text-xl font-bold mb-6 text-center">Login to your account</h2>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Email</label>
          <input type="email" v-model="email" class="w-full border px-3 py-2 rounded-md" required/>
        </div>

        <div class="relative">
          <label class="block text-sm font-medium mb-1">Password</label>
          <input :type="showPassword?'text':'password'" v-model="password"
            class="w-full border px-3 py-2 rounded-md" required/>
          <span class="absolute right-3 top-8 cursor-pointer select-none"
            @click="showPassword=!showPassword">
            {{ showPassword ? '🙈' : '👁️' }}
          </span>
        </div>

        <p v-if="error" class="text-sm text-red-500">{{ error }}</p>

        <button type="submit" :disabled="loading"
          class="w-full py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">
          {{ loading ? 'Logging in...' : 'Login now' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const email = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const error = ref('')

const handleLogin = async () => {
  error.value = ''
  loading.value = true

  try {
    const res = await axios.post('http://localhost/maps-pro/backend/auth/login.php', {
      email: email.value,
      password: password.value
    }, { withCredentials: true })

    if (res.data.success) {
      localStorage.setItem('user', JSON.stringify(res.data))
      router.push('/dashboard')
    } else {
      error.value = res.data.error || 'Login failed'
    }
  } catch (err) {
    error.value =
      err.response && err.response.status === 401
        ? 'Email atau password salah'
        : 'Server error. Please try again later.'
  } finally {
    loading.value = false
  }
}
</script>


<!-- <template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white shadow-md rounded-xl p-8 w-full max-w-md">
      <h2 class="text-2xl font-semibold mb-6 text-center">Login</h2>

      <form @submit.prevent="onSubmit" class="space-y-4">
        <input v-model="email" type="email" placeholder="Email"
          class="w-full px-4 py-2 border rounded-md" />
        <input v-model="password" type="password" placeholder="Password"
          class="w-full px-4 py-2 border rounded-md" />
        <button
          class="w-full py-2 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700">
          Login
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const email = ref('')
const password = ref('')

const onSubmit = () => {
  // nanti panggil backend, untuk sekarang langsung redirect
  router.push('/dashboard')
}
</script> -->
