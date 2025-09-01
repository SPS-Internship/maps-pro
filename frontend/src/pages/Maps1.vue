<template>
  <div class="px-4 space-y-4">
    <h1 class="text-2xl font-bold">Peta Area Coverage</h1>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <input
        id="pac-input"
        type="text"
        placeholder="Cari lokasi..."
        class="flex-1 max-w-[600px] px-4 py-2 border rounded-md text-sm"
      />

      <div class="flex items-center gap-6">
        <!-- toggle ODP -->
        <label class="flex items-center gap-2 select-none">
          <span class="text-sm">ODP</span>
          <input
            type="checkbox"
            v-model="showMarkers"
            @change="handleToggleMarkers"
            class="w-10 h-5 rounded-full bg-gray-300 appearance-none checked:bg-blue-600 relative before:absolute before:top-[2px] before:left-[2px] before:bg-white before:w-4 before:h-4 before:rounded-full before:transition-transform checked:before:translate-x-5"
          />
        </label>

        <!-- toggle Area -->
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

    <div class="mt-4">
      <div id="map" class="w-full h-[550px] rounded-lg shadow border"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Loader } from '@googlemaps/js-api-loader'

const mapInstance = ref(null)
const odpList     = ref([])
const markers     = ref([])
const circles     = ref([])

const showMarkers = ref(true)
const showCircles = ref(true)

// Fetch data & create objects once
const initODP = async () => {
  const res    = await fetch('http://localhost/maps-pro/backend/api/odp.php')
  const data   = await res.json()
  odpList.value = Array.isArray(data) ? data : (data.data || [])

  // Create marker & circle but do not show them yet (setMap(null))
  const geocoder = new google.maps.Geocoder()

  odpList.value.forEach((odp) => {
    const lat = parseFloat(odp.latitude)
    const lng = parseFloat(odp.longitude)
    if (isNaN(lat) || isNaN(lng)) return

    const position = { lat, lng }

    // Marker
    const marker = new google.maps.Marker({
      position,
      map: null,          // hide by default
      optimized: false,
      title: odp.nama_odp,
      icon: {
        path: "M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z",
        fillColor: "#EF4444",
        fillOpacity: 0.5,
        strokeColor: "rgba(239, 68, 68, 0.25)",
        strokeWeight: 2,
        scale: 0.8,
        anchor: { x: 12, y: 24 }
      }
    })

    // InfoWindow
    const info = new google.maps.InfoWindow()
    marker.addListener('click', () => {
      geocoder.geocode({ location: position }, (r,s) => {
        let addr = 'Tidak ditemukan alamat'
        if (s === 'OK' && r[0]) addr = r[0].formatted_address
        info.setContent(`<strong>${odp.nama_odp}</strong><br>${addr}`)
        info.open(mapInstance.value, marker)
      })
    })

    markers.value.push(marker)

    // Circle
    const circle = new google.maps.Circle({
      center: position,
      radius: 200,
      strokeColor: '#EF4444',
      strokeOpacity: 0.1,
      strokeWeight: 2,
      fillColor: '#FCA5A5',
      fillOpacity: 0.35,
      map: null      // hide by default
    })
    circles.value.push(circle)
  })
}

// Toggle shows/hides only (no create/destroy)
const handleToggleMarkers = () => {
  markers.value.forEach(m =>
    m.setMap(showMarkers.value ? mapInstance.value : null)
  )

  // if marker is off, also hide circle
  if (!showMarkers.value) {
    showCircles.value = false
    circles.value.forEach(c => c.setMap(null))
  }
}

const handleToggleCircles = () => {
  circles.value.forEach(c =>
    c.setMap(showCircles.value ? mapInstance.value : null)
  )
}

onMounted(async () => {
  const loader = new Loader({
    apiKey : 'AIzaSyA99VXYKlRV5wCubuIyXWdTGhSwkfyqeSc',
    version: 'weekly',
    libraries: ['places']
  })

  await loader.load()
  mapInstance.value = new google.maps.Map(document.getElementById('map'), {
    center : { lat: -7.9467, lng: 112.6150 },
    zoom   : 13
  })

  // init searchbox
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

  // Fetch once and build markers
  await initODP()

  // show default
  handleToggleMarkers()
  handleToggleCircles()
})
</script>


<!-- <script setup>
import { ref, onMounted } from 'vue'
import { Loader } from '@googlemaps/js-api-loader'

const mapInstance = ref(null)
const odpList = ref([])

const markers = ref([])
const circles = ref([])

const showMarkers = ref(true)
const showCircles = ref(true)

/* ---------- FETCH DATA ------------ */
const loadODP = async () => {
  const res    = await fetch('http://localhost/maps-pro/backend/api/odp.php')
  const result = await res.json()
  odpList.value = Array.isArray(result) ? result : (result.data || [])

  // render default layers
  destroyMarkers()
  destroyCircles()
  createMarkers()
  createCircles()
}

/* ---------- CREATE / DESTROY MARKERS ------------ */
const createMarkers = () => {
  if (!mapInstance.value || !showMarkers.value) return
  odpList.value.forEach(odp => {
    const lat = parseFloat(odp.latitude)
    const lng = parseFloat(odp.longitude)
    if (isNaN(lat) || isNaN(lng)) return

    const marker = new google.maps.Marker({
      map: mapInstance.value,
      position: { lat, lng },
      title: odp.nama_odp,
      icon: {
         path: "M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z",
              fillColor: "#EF4444",
              fillOpacity: 1,
              strokeColor: "rgba(244, 114, 182, 0.25)",
              strokeWeight: 2,
              scale: 0.8,
              anchor: { x: 12, y: 24 }
      },
       optimized: false 
    })
    markers.value.push(marker)
  })
}

const destroyMarkers = () => {
  markers.value.forEach(m => m.setMap(null))
  markers.value = []
}

/* ---------- CREATE / DESTROY CIRCLES ------------ */
const createCircles = () => {
  if (!mapInstance.value || !showCircles.value) return
  odpList.value.forEach(odp => {
    const lat = parseFloat(odp.latitude)
    const lng = parseFloat(odp.longitude)
    if (isNaN(lat) || isNaN(lng)) return

    const circle = new google.maps.Circle({
      map: mapInstance.value,
      center: { lat, lng },
      radius: 200,
      strokeColor: '#EF4444',
      strokeOpacity: 0.1,
      strokeWeight: 2,
      fillColor: '#FCA5A5',
      fillOpacity: 0.35
    })
    circles.value.push(circle)
  })
}

const destroyCircles = () => {
  circles.value.forEach(c => c.setMap(null))
  circles.value = []
}

/* ---------- TOGGLE HANDLERS ------------ */
const handleToggleMarkers = () => {
  destroyMarkers()
  if (showMarkers.value) {
    createMarkers()
  }
}

const handleToggleCircles = () => {
  destroyCircles()
  if (showCircles.value) {
    createCircles()
  }
}

/* ---------- INIT / MAP ------------ */
onMounted(async () => {
  const loader = new Loader({
    apiKey: 'AIzaSyA99VXYKlRV5wCubuIyXWdTGhSwkfyqeSc',
    version: 'weekly',
    libraries: ['places']
  })

  await loader.load()
  const map = new google.maps.Map(document.getElementById('map'), {
    center: { lat: -7.9467, lng: 112.6150 },
    zoom: 13,
  })
  mapInstance.value = map

  const searchInput = document.getElementById('pac-input')
  const searchBox   = new google.maps.places.SearchBox(searchInput)
  map.addListener('bounds_changed', () => {
    searchBox.setBounds(map.getBounds())
  })
  searchBox.addListener('places_changed', () => {
    const p = searchBox.getPlaces()
    if (p[0]?.geometry?.location) {
      map.setCenter(p[0].geometry.location)
      map.setZoom(15)
    }
  })

  // 🛡 protect from Google's viewport-refresh behaviour
  // -> do NOT re-create, only destroy when OFF
  map.addListener('zoom_changed', () => {
    if (!showMarkers.value) destroyMarkers()
    if (!showCircles.value) destroyCircles()
  })

  await loadODP()
})
</script> -->

<!-- <script setup>
import { ref, onMounted } from 'vue'
import { Loader } from '@googlemaps/js-api-loader'

const mapInstance = ref(null)
const odpList     = ref([])

// simpan instance object
const markers = ref([])
const circles = ref([])

const showMarkers = ref(true)
const showCircles = ref(true)

/* -------------------------------------------- */
/* FETCH ODP DATA                              */
/* -------------------------------------------- */
const loadODP = async () => {
  try {
    const res    = await fetch('http://localhost/maps-pro/backend/api/odp.php')
    const result = await res.json()
    odpList.value = Array.isArray(result) ? result : (result.data || [])

    // by default, create everything on first render
    createMarkers()
    createCircles()
  } catch (err) {
    console.error('Gagal fetch ODP:', err)
  }
}

/* -------------------------------------------- */
/* CREATE / DESTROY Markers                     */
/* -------------------------------------------- */
const createMarkers = () => {
  if (!mapInstance.value) return
  odpList.value.forEach(odp => {
    const lat = parseFloat(odp.latitude)
    const lng = parseFloat(odp.longitude)
    if (isNaN(lat) || isNaN(lng)) return

    markers.value.push(
      new google.maps.Marker({
        position: { lat, lng },
        map: mapInstance.value,
        title: odp.nama_odp,
        icon: {
          url: 'https://cdn-icons-png.flaticon.com/512/2659/2659360.png', // icon nicer
          scaledSize: new google.maps.Size(30, 30)
        }
      })
    )
  })
}

const destroyMarkers = () => {
  markers.value.forEach(m => m.setMap(null))
  markers.value = []
}

/* -------------------------------------------- */
/* CREATE / DESTROY Circles                     */
/* -------------------------------------------- */
const createCircles = () => {
  if (!mapInstance.value) return
  odpList.value.forEach(odp => {
    const lat = parseFloat(odp.latitude)
    const lng = parseFloat(odp.longitude)
    if (isNaN(lat) || isNaN(lng)) return

    circles.value.push(
      new google.maps.Circle({
        map: mapInstance.value,
        center: { lat, lng },
        radius: 200,
        strokeColor: '#EF4444',
        strokeOpacity: 0.8,
        strokeWeight: 2,
        fillColor: '#FCA5A5',
        fillOpacity: 0.35
      })
    )
  })
}

const destroyCircles = () => {
  circles.value.forEach(c => c.setMap(null))
  circles.value = []
}

/* -------------------------------------------- */
/* HANDLERS                                     */
/* -------------------------------------------- */
const handleToggleMarkers = () => {
  if (showMarkers.value) {
    createMarkers()
  } else {
    destroyMarkers()
  }
}

const handleToggleCircles = () => {
  if (showCircles.value) {
    createCircles()
  } else {
    destroyCircles()
  }
}

/* -------------------------------------------- */
/* INIT MAP                                     */
/* -------------------------------------------- */
onMounted(async () => {
  const loader = new Loader({
    apiKey: 'AIzaSyA99VXYKlRV5wCubuIyXWdTGhSwkfyqeSc',
    version: 'weekly',
    libraries: ['places']
  })

  await loader.load()
  const map = new google.maps.Map(document.getElementById('map'), {
    center: { lat: -7.9467, lng: 112.6150 },
    zoom: 13
  })
  mapInstance.value = map

  // search box
  const searchInput = document.getElementById('pac-input')
  const searchBox   = new google.maps.places.SearchBox(searchInput)

  map.addListener('bounds_changed', () => {
    searchBox.setBounds(map.getBounds())
  })

  searchBox.addListener('places_changed', () => {
    const places = searchBox.getPlaces()
    if (!places.length) return
    const place = places[0]
    if (place.geometry && place.geometry.location) {
      map.setCenter(place.geometry.location)
      map.setZoom(15)
    }
  })

  // prevent zoom from “bringing back” objects
  map.addListener('zoom_changed', () => {
    // re-destroy if toggles in OFF state
    if (!showMarkers.value) destroyMarkers()
    if (!showCircles.value) destroyCircles()
  })

  // load ODP after map is ready
  await loadODP()
})
</script> -->

<!-- <template>
  <div class="p-6 space-y-4">
    <h1 class="text-2xl font-bold">Peta Area Coverage</h1>

    <!-- Search + Controls -->
    <!-- <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <input
        id="pac-input"
        type="text"
        placeholder="Cari lokasi..."
        class="flex-1 px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm w-full sm:w-64"
      />

      <div class="flex gap-2 justify-end">
        <button
          @click="toggleMarkers"
          class="px-4 py-2 text-sm rounded-md text-white bg-blue-600 hover:bg-blue-700">
          {{ showMarkers ? 'Sembunyikan ODP' : 'Tampilkan ODP' }}
        </button>
        <button
          @click="toggleCircles"
          class="px-4 py-2 text-sm rounded-md text-white bg-red-600 hover:bg-red-700">
          {{ showCircles ? 'Sembunyikan Area' : 'Tampilkan Area' }}
        </button>
      </div>
    </div> -->

    <!-- Search + Toggles -->
    <!-- <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"> -->
    <!-- Search -->
    <!-- <input
        id="pac-input"
        type="text"
        placeholder="Cari lokasi..."
        class="flex-1 max-w-[600px] px-4 py-2 border rounded-md text-sm"
    /> -->

    <!-- Toggle Switches -->
    <!-- <div class="flex items-center gap-6"> -->
        <!-- ODP switch -->
        <!-- <label class="flex items-center gap-2 select-none">
        <span class="text-sm">ODP</span>
        <input
            type="checkbox"
            v-model="showMarkers"
            @change="toggleMarkers"
            class="w-10 h-5 rounded-full bg-gray-300 appearance-none checked:bg-blue-600 relative before:absolute before:top-[2px] before:left-[2px] before:bg-white before:w-4 before:h-4 before:rounded-full before:transition-transform checked:before:translate-x-5"
        />
        </label> -->

        <!-- Coverage switch -->
        <!-- <label class="flex items-center gap-2 select-none">
        <span class="text-sm">Area</span>
        <input
            type="checkbox"
            v-model="showCircles"
            @change="toggleCircles"
            class="w-10 h-5 rounded-full bg-gray-300 appearance-none checked:bg-red-600 relative before:absolute before:top-[2px] before:left-[2px] before:bg-white before:w-4 before:h-4 before:rounded-full before:transition-transform checked:before:translate-x-5"
        />
        </label>
    </div>
    </div> -->


    <!-- Map container -->
    <!-- <div class="mt-4">
      <div id="map" class="w-full h-[550px] rounded-lg shadow border"></div>
    </div>
  </div>
</template> -->

<!-- <script setup>
import { ref, onMounted } from 'vue'
import { Loader } from '@googlemaps/js-api-loader'

const mapInstance   = ref(null)
const odpList       = ref([])
const markers       = ref([])
const circles       = ref([])

// ✅ default ON
const showMarkers = ref(true)
const showCircles = ref(true)

// Ambil data dari backend native
const loadODP = async () => {
  try {
    const res = await fetch('http://localhost/maps-pro/backend/api/odp.php')
    const result = await res.json()

    // kalau API return array langsung -> assign
    odpList.value = Array.isArray(result) ? result : (result.data || [])

    renderMarkers()            // ✅ langsung render semua marker dan circle
  } catch (err) {
    console.error('Gagal fetch ODP:', err)
  }
}

// Render semua marker & circle
const renderMarkers = () => {
  clearAll()

  const geocoder = new google.maps.Geocoder()
  odpList.value.forEach((odp) => {
    const lat = parseFloat(odp.latitude)
    const lng = parseFloat(odp.longitude)
    if (!lat || !lng || isNaN(lat) || isNaN(lng)) return

    const position = { lat, lng }

    // marker
    const marker = new google.maps.Marker({
      position,
      map: showMarkers.value ? mapInstance.value : null,
      title: odp.nama_odp,
      icon: { url: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png' }
    })

    // circle
    const circle = new google.maps.Circle({
      map: showCircles.value ? mapInstance.value : null,
      center: position,
      radius: 200,
      strokeColor: '#EF4444',
      strokeOpacity: 0.8,
      strokeWeight: 2,
      fillColor: '#FCA5A5',
      fillOpacity: 0.35,
    })

    // info window
    const infoWindow = new google.maps.InfoWindow({ content:'Memuat informasi...' })
    marker.addListener('click', () => {
      geocoder.geocode({ location: position }, (results,status) => {
        let address = 'Tidak ditemukan alamat'
        if (status === 'OK' && results[0]) address = results[0].formatted_address

        infoWindow.setContent(`
          <div style="max-width:240px;">
            <strong>${odp.nama_odp}</strong><br/>
            ${address}<br/>
            <a href="https://www.google.com/maps/search/?api=1&query=${lat},${lng}" target="_blank" class="text-blue-600 underline">View on Google Maps</a>
          </div>
        `)
        infoWindow.open(mapInstance.value, marker)
      })
    })

    markers.value.push(marker)
    circles.value.push(circle)
  })
}

const clearAll = () => {
  markers.value.forEach(m => m.setMap(null))
  circles.value.forEach(c => c.setMap(null))
  markers.value = []
  circles.value = []
}

// ✅ toggle hanya menyembunyikan / menampilkan (tidak render ulang)
const toggleMarkers = () => {
  markers.value.forEach(m =>
    m.setMap(showMarkers.value ? mapInstance.value : null)
  )
}

const toggleCircles = () => {
  circles.value.forEach(c =>
    c.setMap(showCircles.value ? mapInstance.value : null)
  )
} 

// const toggleMarkers = () => {
//   showMarkers.value = !showMarkers.value
//   markers.value.forEach(m =>
//     m.setMap(showMarkers.value ? mapInstance.value : null)
//   )
// }

// const toggleCircles = () => {
//   showCircles.value = !showCircles.value
//   circles.value.forEach(c =>
//     c.setMap(showCircles.value ? mapInstance.value : null)
//   )
// }

onMounted(async () => {
  const loader = new Loader({
    apiKey: 'AIzaSyA99VXYKlRV5wCubuIyXWdTGhSwkfyqeSc',
    version: 'weekly',
    libraries: ['places'],
  })

  await loader.load()

  // init map
  const map = new google.maps.Map(document.getElementById('map'), {
    center: { lat: -7.9467, lng: 112.6150 },
    zoom: 13,
  })
  mapInstance.value = map

  // search box
  const searchInput = document.getElementById('pac-input')
  const searchBox   = new google.maps.places.SearchBox(searchInput)

  map.addListener('bounds_changed', () => {
    searchBox.setBounds(map.getBounds())
  })

  searchBox.addListener('places_changed', () => {
    const places = searchBox.getPlaces()
    if (places.length === 0) return
    const place = places[0]
    if (!place.geometry || !place.geometry.location) return

    map.setCenter(place.geometry.location)
    map.setZoom(15)
  })

  // ✅ load & render ODP setelah map siap
  await loadODP()
})
</script>


<!-- <script setup>
import { ref, onMounted } from 'vue'
import { Loader } from '@googlemaps/js-api-loader'

const mapInstance = ref(null)
const odpList    = ref([])
const markers    = ref([])
const circles    = ref([])

const showMarkers = ref(true)
const showCircles = ref(true)

// Ambil ODP dari backend native
const loadODP = async () => {
  try {
    const res = await fetch('http://localhost/maps-pro/backend/api/odp.php')
    const result = await res.json()
    odpList.value = result.data || []
    renderMarkers()
  } catch (err) {
    console.error('Gagal fetch ODP:', err)
  }
}

const renderMarkers = () => {
  clearAll()

  const geocoder = new google.maps.Geocoder()

  odpList.value.forEach((odp) => {
    const lat = parseFloat(odp.latitude)
    const lng = parseFloat(odp.longitude)

    if (!lat || !lng || isNaN(lat) || isNaN(lng)) return

    const position = { lat, lng }

    // marker
    const marker = new google.maps.Marker({
      position,
      map: showMarkers.value ? mapInstance.value : null,
      title: odp.nama_odp,
      icon: { url: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png' }
    })

    // circle
    const circle = new google.maps.Circle({
      map: showCircles.value ? mapInstance.value : null,
      center: position,
      radius: 200,
      strokeColor: '#EF4444',
      strokeOpacity: 0.8,
      strokeWeight: 2,
      fillColor: '#FCA5A5',
      fillOpacity: 0.35,
    })

    // info window
    const infoWindow = new google.maps.InfoWindow({ content:'Memuat informasi...' })

    marker.addListener('click', () => {
      geocoder.geocode({ location: position }, (results,status) => {
        let address = 'Tidak ditemukan alamat'
        if (status === 'OK' && results[0]) address = results[0].formatted_address

        infoWindow.setContent(`
          <div style="max-width:240px;">
            <strong>${odp.nama_odp}</strong><br/>
            ${address}<br/>
            <a href="https://www.google.com/maps/search/?api=1&query=${lat},${lng}" target="_blank" class="text-blue-600 underline">View on Google Maps</a>
          </div>
        `)
        infoWindow.open(mapInstance.value, marker)
      })
    })

    markers.value.push(marker)
    circles.value.push(circle)
  })
}

const clearAll = () => {
  markers.value.forEach(m => m.setMap(null))
  circles.value.forEach(c => c.setMap(null))
  markers.value = []
  circles.value = []
}

// const toggleMarkers = () => {
//   showMarkers.value = !showMarkers.value
//   markers.value.forEach(m =>
//     m.setMap(showMarkers.value ? mapInstance.value : null)
//   )
// }

// const toggleCircles = () => {
//   showCircles.value = !showCircles.value
//   circles.value.forEach(c =>
//     c.setMap(showCircles.value ? mapInstance.value : null)
//   )
// }

const toggleMarkers = () => {
  showMarkers.value = !showMarkers.value
  markers.value.forEach(m => m.setMap(showMarkers.value ? mapInstance.value : null))
}

const toggleCircles = () => {
  showCircles.value = !showCircles.value
  circles.value.forEach(c => c.setMap(showCircles.value ? mapInstance.value : null))
}


// const toggleMarkers = () => {
//   showMarkers.value = !showMarkers.value
//   if (markers.value.length === 0) {
//     renderMarkers()
//   } else {
//     markers.value.forEach(m => m.setMap(showMarkers.value ? mapInstance.value : null))
//   }
// }

// const toggleCircles = () => {
//   showCircles.value = !showCircles.value
//   if (circles.value.length === 0) {
//     renderMarkers()
//   } else {
//     circles.value.forEach(c => c.setMap(showCircles.value ? mapInstance.value : null))
//   }
// }



onMounted(async () => {
  const loader = new Loader({
    apiKey: 'AIzaSyA99VXYKlRV5wCubuIyXWdTGhSwkfyqeSc',
    version: 'weekly',
    libraries: ['places'],
  })

  await loader.load()

  // init map
  const map = new google.maps.Map(document.getElementById('map'), {
    center: { lat: -7.9467, lng: 112.6150 },
    zoom: 13,
  })
  mapInstance.value = map

  // search box
  const searchInput = document.getElementById('pac-input')
  const searchBox   = new google.maps.places.SearchBox(searchInput)
//   map.controls[google.maps.ControlPosition.TOP_LEFT].push(searchInput)

  map.addListener('bounds_changed', () => {
    searchBox.setBounds(map.getBounds())
  })

  let searchMarker = null
  searchBox.addListener('places_changed', () => {
    const places = searchBox.getPlaces()
    if (places.length === 0) return
    if (searchMarker) searchMarker.setMap(null)

    const place = places[0]
    if (!place.geometry || !place.geometry.location) return

    map.setCenter(place.geometry.location)
    map.setZoom(15)
    searchMarker = new google.maps.Marker({
      map,
      position: place.geometry.location,
      title: place.name
    })
  })

  // load ODP after map ready
  await loadODP()
})
</script> -->
