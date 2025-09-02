<template>
  <div class="min-h-screen bg-gray-50 flex flex-col items-center p-6 space-y-6">
    <!-- Title -->
    <h1 class="text-2xl font-bold text-gray-800">Cek Coverage Area</h1>
    <p class="text-gray-500 text-sm text-center max-w-md">
      Masukkan alamat Anda untuk mengecek apakah area sudah tercover jaringan kami.
    </p>

    <!-- Search Box -->
    <div class="w-full max-w-xl">
      <input
        id="searchBox"
        type="text"
        placeholder="Masukkan alamat Anda..."
        class="w-full px-4 py-3 rounded-2xl border border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
      />
    </div>

    <!-- Maps -->
    <div id="map" class="w-full max-w-5xl rounded-2xl shadow-lg"></div>

    <!-- Popup Notification -->
    <transition name="fade">
      <div
        v-if="popupMessage"
        class="fixed bottom-6 right-6 bg-white rounded-2xl shadow-xl p-4 border flex items-center space-x-3"
      >
        <div
          :class="popupSuccess ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'"
          class="p-2 rounded-full"
        >
          <!-- Icon Success -->
          <svg
            v-if="popupSuccess"
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <!-- Icon Error -->
          <svg
            v-else
            xmlns="http://www.w3.org/2000/svg"
            class="h-6 w-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </div>
        <span class="font-medium text-gray-700">{{ popupMessage }}</span>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { Loader } from "@googlemaps/js-api-loader"

const popupMessage = ref("")
const popupSuccess = ref(false)
let map, odpData = []

onMounted(async () => {
  try {
    // Fetch ODP data
    const res = await fetch("/backend/api/odp.php")
    odpData = await res.json()

    // Init Google Maps loader
    const loader = new Loader({
      apiKey: "YOUR_API_KEY", // <-- ganti dengan API key kamu
      version: "weekly",
      libraries: ["places"],
    })

    loader.load().then((google) => {
      map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: -7.9797, lng: 112.6304 }, // Malang default
        zoom: 12,
      })

      const input = document.getElementById("searchBox")
      const autocomplete = new google.maps.places.Autocomplete(input)
      autocomplete.bindTo("bounds", map)

      autocomplete.addListener("place_changed", () => {
        const place = autocomplete.getPlace()
        if (!place.geometry) return

        map.setCenter(place.geometry.location)
        map.setZoom(15)

        // Tambah marker lokasi yang dicari
        new google.maps.Marker({
          position: place.geometry.location,
          map,
        })

        // Check coverage by nearest ODP
        checkCoverage(place.geometry.location)
      })
    })
  } catch (e) {
    console.error("Error load map:", e)
  }
})

function checkCoverage(location) {
  // Cari ODP terdekat dalam radius 1km
  let covered = false
  odpData.forEach((odp) => {
    const distance = haversine(
      location.lat(),
      location.lng(),
      parseFloat(odp.latitude),
      parseFloat(odp.longitude)
    )
    if (distance <= 1) {
      covered = true
    }
  })

  if (covered) {
    popupMessage.value = "Selamat! Alamat Anda sudah tercover."
    popupSuccess.value = true
  } else {
    popupMessage.value = "Mohon maaf, alamat Anda belum tercover."
    popupSuccess.value = false
  }

  // Hilang otomatis setelah 5 detik
  setTimeout(() => (popupMessage.value = ""), 5000)
}

// Rumus haversine
function haversine(lat1, lon1, lat2, lon2) {
  const R = 6371 // km
  const dLat = ((lat2 - lat1) * Math.PI) / 180
  const dLon = ((lon2 - lon1) * Math.PI) / 180
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos((lat1 * Math.PI) / 180) *
      Math.cos((lat2 * Math.PI) / 180) *
      Math.sin(dLon / 2) * Math.sin(dLon / 2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
  return R * c
}
</script>

<style>
#map {
  width: 100%;
  height: 500px; /* fix supaya selalu muncul */
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
