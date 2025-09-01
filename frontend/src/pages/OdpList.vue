<script setup>
import { ref, onMounted, computed, watch } from 'vue'

const odpData = ref([])
const loading = ref(true)
const error   = ref(null)

const searchQuery       = ref('')
const selectedKabupaten = ref('')
const selectedKecamatan = ref('')
const selectedProduk    = ref('') // ⬅️ Tambah ini


const currentPage  = ref(1)
const perPage      = ref(10)
const perPageOptions = [10, 25, 50, 100]

const selectedOdp = ref(null)
const showModal   = ref(false)

// Fetch ODP from backend
onMounted(async () => {
  try {
    const res = await fetch('http://localhost/maps-pro/backend/api/odp.php')
    const data = await res.json()
    odpData.value = Array.isArray(data) ? data : (data.data || [])
  } catch (err) {
    error.value = 'Gagal mengambil data ODP'
  } finally {
    loading.value = false
  }
})

// Kab options
const kabupatenOptions = computed(() =>
  [...new Set(odpData.value.map((d) => d.kabupaten).filter(Boolean))]
)

// ⬇ only show kecamatan for selected kabupaten
const kecamatanOptionsByKab = computed(() => {
  if (!selectedKabupaten.value) return []
  return [...new Set(
    odpData.value
      .filter((d) => d.kabupaten === selectedKabupaten.value)
      .map((d) => d.kecamatan)
      .filter(Boolean)
  )]
})

// reset kecamatan when kabupaten changed
watch(selectedKabupaten, () => {
  selectedKecamatan.value = ''
})

// ⬇️ Produk options
const produkOptions = computed(() =>
  [...new Set(odpData.value.map((d) => d.produk).filter(Boolean))]
)

// Filter & search
const filteredAndSearchedData = computed(() => {
  let data = odpData.value

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    data = data.filter(
      (odp) =>
        odp.nama_odp?.toLowerCase().includes(q) ||
        odp.id_odp?.toLowerCase().includes(q)
    )
  }

  if (selectedKabupaten.value) {
    data = data.filter((odp) => odp.kabupaten === selectedKabupaten.value)
  }

  if (selectedKecamatan.value) {
    data = data.filter((odp) => odp.kecamatan === selectedKecamatan.value)
  }

  if (selectedProduk.value) { 
    data = data.filter((odp) => odp.produk === selectedProduk.value)
  }


  return data
})

// Paginate
const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredAndSearchedData.value.slice(start, start + perPage.value)
})
const totalPages = computed(() =>
  Math.ceil(filteredAndSearchedData.value.length / perPage.value)
)

// Modal
const openModal = (odp) => {
  selectedOdp.value = odp
  showModal.value = true
}
const closeModal = () => { showModal.value = false }
</script>



<template>
  <div class="px-6 space-y-4">
    <h1 class="text-2xl font-bold">Daftar ODP</h1>

    <!-- Search + Filter -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <input
        v-model="searchQuery"
        type="text"
        class="flex-1 max-w-[420px] px-4 py-2 border rounded-md text-sm"
        placeholder="Cari nama atau ID ODP..."
      />

      <div class="flex gap-2">
        <!-- Kabupaten -->
        <select
          v-model="selectedKabupaten"
          class="px-4 py-2 border rounded-md text-sm w-40"
        >
          <option value="">Semua Kota</option>
          <option v-for="k in kabupatenOptions" :key="k" :value="k">{{ k }}</option>
        </select>

        <!-- Kecamatan -->
        <select
          v-model="selectedKecamatan"
          class="px-4 py-2 border rounded-md text-sm w-40"
        >
          <option value="">Kecamatan</option>
          <option v-for="k in kecamatanOptionsByKab" :key="k" :value="k">{{ k }}</option>
        </select>

        <!-- Produk -->
        <!-- <select
          v-model="selectedProduk"
          class="px-4 py-2 border rounded-md text-sm w-40"
        >
          <option value="">Produk</option>
          <option v-for="p in produkOptions" :key="p" :value="p">{{ p }}</option>
        </select> -->

      </div>
    </div>

    <!-- Per Page Setting -->
    <div class="flex items-center gap-2 text-sm">
      <span>Tampilkan</span>
      <select v-model="perPage" class="px-3 py-1.5 border rounded-md text-xs">
        <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
      </select>
      <span>data</span>
    </div>

    <!-- Table -->
    <div class="bg-white p-4 rounded-lg shadow">
      <div v-if="loading" class="text-gray-500">Memuat data...</div>
      <div v-else-if="error" class="text-red-500">{{ error }}</div>

      <table v-else class="min-w-full text-sm divide-y divide-gray-200">
        <thead class="bg-gray-50 text-gray-700 font-semibold">
          <tr>
            <th class="px-4 py-2">No</th>
            <th class="px-4 py-2">Nama ODP</th>
            <th class="px-4 py-2">Alamat</th>
            <th class="px-4 py-2">ID ODP</th>
            <!-- <th class="px-4 py-2">Status</th> -->
            <th class="px-4 py-2">Tanggal Instalasi</th>
            <th class="px-4 py-2">Produk</th>
            <th class="px-4 py-2 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="(odp, idx) in paginatedData" :key="odp.id_odp">
            <td class="px-4 py-2">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
            <td class="px-4 py-2">{{ odp.nama_odp }}</td>
            <td class="px-4 py-2">{{ odp.alamat_odp }}</td>
            <td class="px-4 py-2">{{ odp.id_odp }}</td>
            <!-- <td class="px-4 py-2">
              <span
                :class="{
                  'text-green-600 font-semibold': odp['status wo'] === 'Sudah Terpasang',
                  'text-yellow-600 font-semibold': odp['status wo'] === 'Proses',
                  'text-red-600 font-semibold': odp['status wo'] === 'Belum Terpasang'
                }"
              >
                {{ odp['status wo'] || '-' }}
              </span>
            </td> -->
            <td class="px-4 py-2">{{ odp.tanggal_instalasi }}</td>
            <td class="px-4 py-2">{{ odp.produk }}</td>
            <td class="px-4 py-2 text-center">
              <button @click="openModal(odp)" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-eye"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="flex justify-between items-center mt-4 text-sm">
        <div>
          Menampilkan {{ (currentPage - 1) * perPage + 1 }} -
          {{ Math.min(currentPage * perPage, filteredAndSearchedData.length) }}
          dari {{ filteredAndSearchedData.length }} data
        </div>
        <div class="space-x-1">
          <button
            @click="currentPage--"
            :disabled="currentPage === 1"
            class="px-3 py-1 rounded border bg-gray-50 hover:bg-gray-100 disabled:opacity-50">‹</button>
          <span>Hal. {{ currentPage }} / {{ totalPages }}</span>
          <button
            @click="currentPage++"
            :disabled="currentPage === totalPages"
            class="px-3 py-1 rounded border bg-gray-50 hover:bg-gray-100 disabled:opacity-50">›</button>
        </div>
      </div>
    </div>

    <!-- Modal Detail -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-auto relative">
        <h3 class="text-lg font-semibold mb-4">
          Detail ODP – {{ selectedOdp?.nama_odp }}
        </h3>

        <table class="w-full text-sm">
          <tbody>
            <tr v-for="(value, key) in selectedOdp" :key="key">
              <td class="font-semibold capitalize pr-4 py-1">{{ key }}</td>
              <td class="py-1 break-all">{{ value || '-' }}</td>
            </tr>
          </tbody>
        </table>

        <div class="mt-4">
          <a v-if="selectedOdp?.latitude && selectedOdp?.longitude"
             :href="`https://www.google.com/maps?q=${selectedOdp.latitude},${selectedOdp.longitude}`"
             target="_blank"
             class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Lihat di Peta
          </a>
        </div>

        <button @click="closeModal"
          class="absolute top-2 right-3 text-gray-600 hover:text-red-600">✕</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css';
</style>
