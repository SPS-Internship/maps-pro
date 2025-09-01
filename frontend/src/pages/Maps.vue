<template>
  <div class="px-4 space-y-4">
    <h1 class="text-2xl font-bold">Peta Area Coverage</h1>

    <!-- Bar Filter & Control -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-gray-50 p-3 rounded-lg shadow border">
      <!-- Filter Dropdown -->
      <div class="flex flex-wrap items-center gap-3">
        <!-- Kota -->
        <div>
          <label for="kota" class="block text-xs font-medium text-gray-600">Kota</label>
          <select
            id="kota"
            v-model="selectedCity"
            @change="onCityChange"
            class="px-3 py-2 border rounded-md text-sm bg-white"
          >
            <option value="">Semua</option>
            <option v-for="k in kotaList" :key="k" :value="k">{{ k }}</option>
          </select>
        </div>

        <!-- Kecamatan -->
        <div>
          <label for="kecamatan" class="block text-xs font-medium text-gray-600">Kecamatan</label>
          <select
            id="kecamatan"
            v-model="selectedDistrict"
            @change="applyFilter"
            class="px-3 py-2 border rounded-md text-sm bg-white"
            :disabled="!selectedCity"
          >
            <option value="">Semua</option>
            <option v-for="kec in kecamatanList" :key="kec" :value="kec">{{ kec }}</option>
          </select>
        </div>

        <!-- Status -->
        <!-- <div>
          <label for="status" class="block text-xs font-medium text-gray-600">Status ODP</label>
          <select
            id="status"
            v-model="selectedStatus"
            @change="applyFilter"
            class="px-3 py-2 border rounded-md text-sm bg-white"
          >
            <option value="">Semua</option>
            <option v-for="s in statusList" :key="s" :value="s">{{ s }}</option>
          </select>
        </div> -->

        <!-- Produk -->
        <!-- <div>
          <label for="produk" class="block text-xs font-medium text-gray-600">Produk</label>
          <select
            id="produk"
            v-model="selectedProduk"
            @change="applyFilter"
            class="px-3 py-2 border rounded-md text-sm bg-white"
          >
            <option value="">Semua</option>
            <option v-for="p in produkList" :key="p" :value="p">{{ p }}</option>
          </select>
        </div>-->
      </div> 

      <!-- Input Search & Toggle -->
      <div class="flex flex-wrap items-center gap-4">
        <input
          id="pac-input"
          type="text"
          placeholder="Cari lokasi..."
          class="px-4 py-2 border rounded-md text-sm max-w-[300px] w-[280px]"
        />

        <!-- Toggle ODP -->
        <label class="flex items-center gap-2 select-none">
          <span class="text-sm">ODP</span>
          <input
            type="checkbox"
            v-model="showMarkers"
            @change="handleToggleMarkers"
            class="w-10 h-5 rounded-full bg-gray-300 appearance-none checked:bg-blue-600 relative before:absolute before:top-[2px] before:left-[2px] before:bg-white before:w-4 before:h-4 before:rounded-full before:transition-transform checked:before:translate-x-5"
          />
        </label>

        <!-- Toggle Area -->
        <label class="flex items-center gap-2 select-none">
          <span class="text-sm">Area</span>
          <input
            type="checkbox"
            v-model="showCircles"
            @change="handleToggleCircles"
            class="w-10 h-5 rounded-full bg-gray-300 appearance-none checked:bg-red-600 relative before:absolute before:top-[2px] before:left-[2px] before:bg-white before:w-4 before:h-4 before:rounded-full before:transition-transform checked:before:translate-x-5"
          />
        </label>
      </div>
    </div>

    <!-- Map -->
    <div class="mt-4">
      <div id="map" class="w-full h-[550px] rounded-lg shadow border"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Loader } from '@googlemaps/js-api-loader'
import { MarkerClusterer } from '@googlemaps/markerclusterer'

const mapInstance = ref(null)
const odpList = ref([])

const markers = ref([])              // plain markers array
let markerCluster = null             // MarkerClusterer instance
let activeCircle = null              // only one circle active at a time
const infoWindow = ref(null)         // reuse single InfoWindow

// Filters
const kotaList = ref([])
const kecamatanList = ref([])
const statusList = ref([])
const produkList = ref([])

const selectedCity = ref('')
const selectedDistrict = ref('')
const selectedStatus = ref('')
const selectedProduk = ref('')

const showMarkers = ref(true)
const showCircles = ref(false)

/* ---------------------- Helpers ---------------------- */
const normalizeStatus = (o) => o.status_wo ?? o['status wo'] ?? 'Tidak Ada'

const buildLists = () => {
  kotaList.value = [...new Set(odpList.value.map(o => o.kabupaten).filter(Boolean))]
  statusList.value = [...new Set(odpList.value.map(o => normalizeStatus(o)).filter(Boolean))]
  produkList.value = [...new Set(odpList.value.map(o => o.produk || 'Tidak Ada'))]
}

const updateKecamatanList = () => {
  if (!selectedCity.value) {
    kecamatanList.value = []
    selectedDistrict.value = ''
    return
  }
  const kec = odpList.value
    .filter(o => o.kabupaten === selectedCity.value)
    .map(o => o.kecamatan)
    .filter(Boolean)
  kecamatanList.value = [...new Set(kec)]

  // reset kecamatan kalau tidak lagi valid
  if (selectedDistrict.value && !kecamatanList.value.includes(selectedDistrict.value)) {
    selectedDistrict.value = ''
  }
}

/* ---------------------- Data & Render ---------------------- */
const initODP = async () => {
  const res = await fetch('http://localhost/maps-pro/backend/api/odp.php')
  const data = await res.json()
  odpList.value = Array.isArray(data) ? data : (data.data || [])

  buildLists()
  updateKecamatanList()
  renderMarkers(odpList.value)
}

const renderMarkers = (data) => {
  // clear previous
  if (markerCluster) {
    markerCluster.clearMarkers()
    markerCluster.setMap(null)
    markerCluster = null
  }
  markers.value.forEach(m => m.setMap(null))
  markers.value = []
  if (activeCircle) { activeCircle.setMap(null); activeCircle = null }

  const geocoder = new google.maps.Geocoder()
  const sharedInfo = infoWindow.value || new google.maps.InfoWindow()
  infoWindow.value = sharedInfo

  // create markers
  const created = []
  for (const odp of data) {
    const lat = parseFloat(odp.latitude)
    const lng = parseFloat(odp.longitude)
    if (Number.isNaN(lat) || Number.isNaN(lng)) continue

    const position = { lat, lng }
    const marker = new google.maps.Marker({
      position,
      map: null, // attach via clusterer below
      title: odp.nama_odp,
      optimized: true,
      icon: {
        path: "M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z",
        fillColor: "#EF4444",
        fillOpacity: 0.85,
        strokeWeight: 1,
        scale: 0.7,
        anchor: new google.maps.Point(12, 24)
      }
    })

    // lazy geocode on click + cache address
    marker._addr = null
    marker.addListener('click', async () => {
      // circle logic (only if toggle Area ON)
      if (activeCircle) activeCircle.setMap(null)
      activeCircle = new google.maps.Circle({
        center: position,
        radius: 200,
        strokeColor: '#EF4444',
        strokeOpacity: 0.3,
        strokeWeight: 1,
        fillColor: '#FCA5A5',
        fillOpacity: 0.25,
        map: showCircles.value ? mapInstance.value : null
      })

      if (!marker._addr) {
        try {
          const r = await geocoder.geocode({ location: position })
          marker._addr = r.results?.[0]?.formatted_address ?? 'Alamat tidak ditemukan'
        } catch {
          marker._addr = 'Alamat tidak ditemukan'
        }
      }

      sharedInfo.setContent(`
        <div style="max-width:260px">
          <strong>${odp.nama_odp ?? '-'}</strong><br/>
          ${marker._addr}<br/>
          <small>${odp.kabupaten ?? '-'}${odp.kecamatan ? ' - ' + odp.kecamatan : ''}</small>
        </div>
      `)
      sharedInfo.open({ map: mapInstance.value, anchor: marker })
    })

    created.push(marker)
  }

  markers.value = created

  // clusterer (map attached depending on showMarkers)
  markerCluster = new MarkerClusterer({
    map: showMarkers.value ? mapInstance.value : null,
    markers: created
    // default algorithm is fine & smooth; no gridSize to avoid API mismatch
  })

  // fit bounds (only if there is data)
  if (data.length > 0) {
    const b = new google.maps.LatLngBounds()
    for (const o of data) {
      const lt = +o.latitude, lg = +o.longitude
      if (!Number.isNaN(lt) && !Number.isNaN(lg)) b.extend({ lat: lt, lng: lg })
    }
    if (!b.isEmpty()) mapInstance.value.fitBounds(b)
  }
}

/* ---------------------- Filters ---------------------- */
const applyFilter = () => {
  updateKecamatanList()

  let filtered = odpList.value
  if (selectedCity.value)      filtered = filtered.filter(o => o.kabupaten === selectedCity.value)
  if (selectedDistrict.value)  filtered = filtered.filter(o => o.kecamatan === selectedDistrict.value)
  if (selectedStatus.value)    filtered = filtered.filter(o => normalizeStatus(o) === selectedStatus.value)
  if (selectedProduk.value)    filtered = filtered.filter(o => (o.produk || 'Tidak Ada') === selectedProduk.value)

  renderMarkers(filtered)
}

const onCityChange = () => {
  selectedDistrict.value = ''
  applyFilter()
}

/* ---------------------- Toggles ---------------------- */
const handleToggleMarkers = () => {
  if (!markerCluster) return
  markerCluster.setMap(showMarkers.value ? mapInstance.value : null)
  if (!showMarkers.value) {
    // hide also the active circle & close info if no markers visible
    if (activeCircle) { activeCircle.setMap(null); activeCircle = null }
    if (infoWindow.value) infoWindow.value.close()
  }
}

const handleToggleCircles = () => {
  if (!activeCircle) return
  activeCircle.setMap(showCircles.value ? mapInstance.value : null)
}

/* ---------------------- Map Init ---------------------- */
onMounted(async () => {
  const loader = new Loader({
    apiKey: 'AIzaSyA99VXYKlRV5wCubuIyXWdTGhSwkfyqeSc',
    version: 'weekly',
    libraries: ['places']
  })

  await loader.load()
  mapInstance.value = new google.maps.Map(document.getElementById('map'), {
    center: { lat: -7.9467, lng: 112.6150 },
    zoom: 12
  })

  const input = document.getElementById('pac-input')
  const sb = new google.maps.places.SearchBox(input)
  mapInstance.value.addListener('bounds_changed', () => {
    sb.setBounds(mapInstance.value.getBounds())
  })
  sb.addListener('places_changed', () => {
    const p = sb.getPlaces()
    if (p?.[0]?.geometry?.location) {
      mapInstance.value.setCenter(p[0].geometry.location)
      mapInstance.value.setZoom(15)
    }
  })

  await initODP()
})
</script>

<!-- <template>
  <div class="px-4 space-y-4">
    <h1 class="text-2xl font-bold">Peta Area Coverage</h1> -->

    <!-- Bar Filter & Control -->
    <!-- <div
      class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-gray-50 p-3 rounded-lg shadow border"
    > -->
      <!-- Filter Dropdown -->
      <!-- <div class="flex flex-wrap items-center gap-3">
        Kota /
        <div>
          <label for="kota" class="block text-xs font-medium text-gray-600">Kota</label>
          <select
            id="kota"
            v-model="selectedCity"
            @change="applyFilter"
            class="px-3 py-2 border rounded-md text-sm bg-white"
          >
            <option value="">Semua</option>
            <option v-for="k in kotaList" :key="k" :value="k">{{ k }}</option>
          </select>
        </div>

        Kecamatan
        <div>
          <label for="kecamatan" class="block text-xs font-medium text-gray-600">Kecamatan</label>
          <select
            id="kecamatan"
            v-model="selectedDistrict"
            @change="applyFilter"
            class="px-3 py-2 border rounded-md text-sm bg-white"
            :disabled="!selectedCity"
          >
            <option value="">Semua</option>
            <option v-for="kec in kecamatanList" :key="kec" :value="kec">{{ kec }}</option>
          </select>
        </div>

        Status
        <div>
          <label for="status" class="block text-xs font-medium text-gray-600">Status ODP</label>
          <select
            id="status"
            v-model="selectedStatus"
            @change="applyFilter"
            class="px-3 py-2 border rounded-md text-sm bg-white"
          >
            <option value="">Semua</option>
            <option v-for="s in statusList" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>

        Produk
        <div>
          <label for="produk" class="block text-xs font-medium text-gray-600">Produk</label>
          <select
            id="produk"
            v-model="selectedProduk"
            @change="applyFilter"
            class="px-3 py-2 border rounded-md text-sm bg-white"
          >
            <option value="">Semua</option>
            <option v-for="p in produkList" :key="p" :value="p">{{ p }}</option>
          </select>
        </div>
      </div> -->

      <!-- Input Search & Toggle -->
      <!-- <div class="flex flex-wrap items-center gap-4">
        <input
          id="pac-input"
          type="text"
          placeholder="Cari lokasi..."
          class="px-4 py-2 border rounded-md text-sm max-w-[250px]"
        />

        Toggle ODP -->
        <!-- <label class="flex items-center gap-2 select-none">
          <span class="text-sm">ODP</span>
          <input
            type="checkbox"
            v-model="showMarkers"
            @change="handleToggleMarkers"
            class="w-10 h-5 rounded-full bg-gray-300 appearance-none checked:bg-blue-600 relative before:absolute before:top-[2px] before:left-[2px] before:bg-white before:w-4 before:h-4 before:rounded-full before:transition-transform checked:before:translate-x-5"
          />
        </label> --> 

        <!-- Toggle Area -->
        <!-- <label class="flex items-center gap-2 select-none">
          <span class="text-sm">Area</span>
          <input
            type="checkbox"
            v-model="showCircles"
            @change="handleToggleCircles"
            class="w-10 h-5 rounded-full bg-gray-300 appearance-none checked:bg-red-600 relative before:absolute before:top-[2px] before:left-[2px] before:bg-white before:w-4 before:h-4 before:rounded-full before:transition-transform checked:before:translate-x-5"
          />
        </label>
      </div>
    </div> -->

    <!-- Map -->
    <!-- <div class="mt-4">
      <div id="map" class="w-full h-[550px] rounded-lg shadow border"></div>
    </div>
  </div>
</template> -->


<!-- <script setup>
import { ref, computed, onMounted } from 'vue'
import { Loader } from '@googlemaps/js-api-loader'
import { MarkerClusterer } from "@googlemaps/markerclusterer"

const mapInstance = ref(null)
const odpList = ref([])
const markers = ref([])
let markerCluster = null
let activeCircle = null

// Filter data
const kotaList = ref([])
const kecamatanList = ref([])
const statusList = ref([])
const produkList = ref([])

const selectedCity = ref('')
const selectedDistrict = ref('')
const selectedStatus = ref('')
const selectedProduk = ref('')

const showMarkers = ref(true)
const showCircles = ref(false)

// Ambil data awal
const initODP = async () => {
  const res = await fetch('http://localhost/maps-pro/backend/api/odp.php')
  const data = await res.json()
  odpList.value = Array.isArray(data) ? data : (data.data || [])

  kotaList.value = [...new Set(odpList.value.map(o => o.kabupaten).filter(Boolean))]
  statusList.value = [...new Set(odpList.value.map(o => o.status_wo || "Tidak Ada"))]
  produkList.value = [...new Set(odpList.value.map(o => o.produk || "Tidak Ada"))]

  // Render marker default
  renderMarkers(odpList.value)
}

// Update list kecamatan saat kota berubah
const updateKecamatanList = () => {
  if (!selectedCity.value) {
    kecamatanList.value = []
    return
  }
  kecamatanList.value = [...new Set(
    odpList.value
      .filter(o => o.kabupaten === selectedCity.value)
      .map(o => o.kecamatan)
      .filter(Boolean)
  )]
}

// Terapkan filter multi-level
const applyFilter = () => {
  updateKecamatanList()

  let filtered = odpList.value
  if (selectedCity.value)
    filtered = filtered.filter(o => o.kabupaten === selectedCity.value)
  if (selectedDistrict.value)
    filtered = filtered.filter(o => o.kecamatan === selectedDistrict.value)
  if (selectedStatus.value)
    filtered = filtered.filter(o => o.status_wo === selectedStatus.value)
  if (selectedProduk.value)
    filtered = filtered.filter(o => o.produk === selectedProduk.value)

  renderMarkers(filtered)
}

// Render marker dengan cluster
const renderMarkers = (data) => {
  markers.value.forEach(m => m.setMap(null))
  markers.value = []
  if (markerCluster) markerCluster.clearMarkers()

  const geocoder = new google.maps.Geocoder()
  const newMarkers = []

  data.forEach(odp => {
    const lat = parseFloat(odp.latitude)
    const lng = parseFloat(odp.longitude)
    if (isNaN(lat) || isNaN(lng)) return

    const position = { lat, lng }
    const marker = new google.maps.Marker({
      position,
      title: odp.nama_odp,
      icon: {
        path: "M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z",
        fillColor: "#EF4444",
        fillOpacity: 0.8,
        strokeWeight: 1,
        scale: 0.7,
        anchor: { x: 12, y: 24 }
      }
    })

    // InfoWindow saat klik marker
    const info = new google.maps.InfoWindow()
    marker.addListener('click', async () => {
      if (activeCircle) activeCircle.setMap(null)
      activeCircle = new google.maps.Circle({
        center: position,
        radius: 200,
        strokeColor: '#EF4444',
        strokeOpacity: 0.3,
        strokeWeight: 1,
        fillColor: '#FCA5A5',
        fillOpacity: 0.25,
        map: showCircles.value ? mapInstance.value : null
      })

      if (!marker.address) {
        const r = await geocoder.geocode({ location: position })
        marker.address = r.results?.[0]?.formatted_address || 'Alamat tidak ditemukan'
      }

      info.setContent(`
        <strong>${odp.nama_odp}</strong><br>
        ${marker.address}<br>
        <small>${odp.kabupaten} - ${odp.kecamatan || '-'}</small>
      `)
      info.open(mapInstance.value, marker)
    })

    newMarkers.push(marker)
  })

  markers.value = newMarkers
  markerCluster = new MarkerClusterer({
    map: showMarkers.value ? mapInstance.value : null,
    markers: newMarkers,
    gridSize: 60
  })

  if (data.length > 0) {
    const bounds = new google.maps.LatLngBounds()
    data.forEach(odp => bounds.extend({ lat: +odp.latitude, lng: +odp.longitude }))
    mapInstance.value.fitBounds(bounds)
  }
}

const handleToggleMarkers = () => {
  markerCluster.setMap(showMarkers.value ? mapInstance.value : null)
  if (!showMarkers.value && activeCircle) activeCircle.setMap(null)
}

const handleToggleCircles = () => {
  if (!showCircles.value && activeCircle) activeCircle.setMap(null)
}

onMounted(async () => {
  const loader = new Loader({
    apiKey: 'YOUR_API_KEY',
    version: 'weekly',
    libraries: ['places']
  })

  await loader.load()
  mapInstance.value = new google.maps.Map(document.getElementById('map'), {
    center: { lat: -7.9467, lng: 112.6150 },
    zoom: 12
  })

  const input = document.getElementById('pac-input')
  const sb = new google.maps.places.SearchBox(input)
  mapInstance.value.addListener('bounds_changed', () => {
    sb.setBounds(mapInstance.value.getBounds())
  })
  sb.addListener('places_changed', () => {
    const p = sb.getPlaces()
    if (p[0]?.geometry?.location) {
      mapInstance.value.setCenter(p[0].geometry.location)
      mapInstance.value.setZoom(15)
    }
  })

  await initODP()
})
</script> -->
