<template> 
  <div class="p-6 space-y-6">
    <!-- Summary Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl shadow p-6 flex items-center justify-between">
        <div>
          <h2 class="text-gray-500 text-sm font-medium">Total ODP</h2>
          <p class="text-3xl font-bold text-gray-800">
            <span v-if="loading">...</span>
            <span v-else>{{ totalOdp }}</span>
          </p>
        </div>
        <div class="bg-indigo-100 text-indigo-600 p-3 rounded-full">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 20h5v-2a3 3 0 00-3-3h-4m-4 0H7a3 3 0 00-3 3v2h5m4-10a4 4 0 100-8 4 4 0 000 8z" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow p-6">
      <h2 class="text-lg font-semibold text-gray-700 mb-4">List ODP by Kota</h2>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-6 text-gray-500">
        Memuat data...
      </div>

      <!-- Table -->
      <table v-else class="w-full border-collapse">
        <thead>
          <tr class="bg-gray-100 text-left text-sm font-medium text-gray-600">
            <th class="p-3">Kota / Kabupaten</th>
            <th class="p-3">Jumlah Kecamatan</th>
            <th class="p-3">Jumlah ODP</th>
            <!-- <th class="p-3">ODP Aktif</th>
            <th class="p-3">ODP Nonaktif</th> -->
            <th class="p-3">Update Terakhir</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in summaryData" :key="row.kabupaten" class="border-t hover:bg-gray-50">
            <td class="p-3 font-medium text-gray-800">{{ row.kabupaten }}</td>
            <td class="p-3">{{ row.jumlahKecamatan }}</td>
            <td class="p-3">{{ row.jumlahOdp }}</td>
            <!-- <td class="p-3 text-green-600">{{ row.odpAktif }}</td>
            <td class="p-3 text-red-600">{{ row.odpNonaktif }}</td> -->
            <td class="p-3 text-gray-500">{{ row.lastUpdate }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"

const totalOdp = ref(0)
const summaryData = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const res = await fetch("http://localhost/maps-pro/backend/api/odp.php")
    const data = await res.json()

    totalOdp.value = data.length

    // Group data by kabupaten
    const grouped = {}
    data.forEach(item => {
      if (!grouped[item.kabupaten]) {
        grouped[item.kabupaten] = {
          kabupaten: item.kabupaten,
          kecamatanSet: new Set(),
          jumlahOdp: 0,
          odpAktif: 0,
          odpNonaktif: 0,
          lastUpdate: item.tanggal_instalasi
        }
      }

      grouped[item.kabupaten].jumlahOdp++
      grouped[item.kabupaten].kecamatanSet.add(item.kecamatan)

      if (item.status_wo === "aktif") grouped[item.kabupaten].odpAktif++
      else grouped[item.kabupaten].odpNonaktif++

      // Update last update kalau lebih baru
      if (new Date(item.tanggal_instalasi) > new Date(grouped[item.kabupaten].lastUpdate)) {
        grouped[item.kabupaten].lastUpdate = item.tanggal_instalasi
      }
    })

    // Convert ke array
    summaryData.value = Object.values(grouped).map(kota => ({
      kabupaten: kota.kabupaten,
      jumlahKecamatan: kota.kecamatanSet.size,
      jumlahOdp: kota.jumlahOdp,
      odpAktif: kota.odpAktif,
      odpNonaktif: kota.odpNonaktif,
      lastUpdate: kota.lastUpdate
    }))
  } catch (err) {
    console.error("Gagal fetch ODP:", err)
  } finally {
    loading.value = false
  }
})
</script>
