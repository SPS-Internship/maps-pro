<template> 
  <div class="p-6 space-y-6">
    <!-- Summary Card -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <!-- Total ODP -->
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

      <!-- Radius Malang -->
      <div class="bg-white rounded-2xl shadow p-6 flex items-center justify-between">
        <div>
          <h2 class="text-gray-500 text-sm font-medium">Radius Malang</h2>
          <p class="text-2xl font-bold text-gray-800">
            <span v-if="loading">...</span>
            <span v-else>{{ radiusMalang.toFixed(2) }} km</span>
          </p>
        </div>
      </div>

      <!-- Radius Pasuruan -->
      <div class="bg-white rounded-2xl shadow p-6 flex items-center justify-between">
        <div>
          <h2 class="text-gray-500 text-sm font-medium">Radius Pasuruan</h2>
          <p class="text-2xl font-bold text-gray-800">
            <span v-if="loading">...</span>
            <span v-else>{{ radiusPasuruan.toFixed(2) }} km</span>
          </p>
        </div>
      </div>

      <!-- Radius Batu -->
      <div class="bg-white rounded-2xl shadow p-6 flex items-center justify-between">
        <div>
          <h2 class="text-gray-500 text-sm font-medium">Radius Batu</h2>
          <p class="text-2xl font-bold text-gray-800">
            <span v-if="loading">...</span>
            <span v-else>{{ radiusBatu.toFixed(2) }} km</span>
          </p>
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

const radiusMalang = ref(0)
const radiusPasuruan = ref(0)
const radiusBatu = ref(0)

// Titik nol kota
const titikNol = {
  malang: { lat: -7.9666, lng: 112.6326 },
  pasuruan: { lat: -7.6453, lng: 112.9075 },
  batu: { lat: -7.8714, lng: 112.5241 }
}

// Fungsi hitung jarak haversine
function haversine(lat1, lon1, lat2, lon2) {
  const R = 6371 // radius bumi km
  const dLat = (lat2 - lat1) * Math.PI / 180
  const dLon = (lon2 - lon1) * Math.PI / 180
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos(lat1 * Math.PI / 180) *
    Math.cos(lat2 * Math.PI / 180) *
    Math.sin(dLon / 2) ** 2
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
  return R * c
}

onMounted(async () => {
  try {
    const res = await fetch("http://localhost/maps-pro/backend/api/odp.php")
    const data = await res.json()

    totalOdp.value = data.length

    // Hitung radius per kota
    let totalMalang = 0, totalPasuruan = 0, totalBatu = 0

    data.forEach(item => {
      const lat = parseFloat(item.latitude)
      const lng = parseFloat(item.longitude)

      if (!lat || !lng) return

      if (item.kabupaten?.toLowerCase().includes("malang")) {
        totalMalang += haversine(titikNol.malang.lat, titikNol.malang.lng, lat, lng)
      } else if (item.kabupaten?.toLowerCase().includes("pasuruan")) {
        totalPasuruan += haversine(titikNol.pasuruan.lat, titikNol.pasuruan.lng, lat, lng)
      } else if (item.kabupaten?.toLowerCase().includes("batu")) {
        totalBatu += haversine(titikNol.batu.lat, titikNol.batu.lng, lat, lng)
      }
    })

    radiusMalang.value = totalMalang
    radiusPasuruan.value = totalPasuruan
    radiusBatu.value = totalBatu6

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
