<template>
  <div class="min-h-screen w-full flex flex-col bg-gray-100">
    <!-- Header -->
    <div class="p-4 sm:p-6 text-center bg-white shadow z-10">
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Cek Jangkauan Area</h1>
      <p class="text-sm sm:text-base text-gray-600">
        Masukkan alamat Anda atau gunakan lokasi saat ini
      </p>
    </div>

    <!-- Map Section -->
    <div class="relative flex-1">
      <!-- Search + Location Control -->
      <div class="absolute top-4 left-1/2 -translate-x-1/2 w-[90%] md:w-[70%] flex gap-2 z-20">
        <input
          id="pac-input"
          type="text"
          placeholder="Masukkan alamat Anda..."
          class="flex-1 px-4 py-2 bg-white border border-gray-200 rounded-full shadow focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm sm:text-base"
          :disabled="isLoading"
        />
        <button
          @click="getMyLocation"
          class="flex items-center gap-2 px-4 sm:px-6 py-2 sm:py-3 bg-blue-600 text-white rounded-full font-medium shadow hover:bg-blue-700 transition"
          :disabled="isLoading"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm0-14a6 6 0 11-4.243 10.243A6 6 0 0110 4z" clip-rule="evenodd" />
          </svg>
          <span class="hidden sm:inline">Lokasi Saya</span>
        </button>
      </div>

      <!-- Loading Overlay -->
      <!-- <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-70 z-30">
        <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5 0 0 5 0 12h4zm2 5.3A8 8 0 014 12H0c0 3 1 6 3 8l3-2.7z"></path>
        </svg>
      </div> -->

      <!-- Map -->
      <div id="map" class="w-full h-full"></div>
    </div>

    <!-- Result Modal -->
    <transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="showResult"
        class="absolute inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40"
        @click="closeResult"
      >
        <div
          class="bg-white p-6 sm:p-8 rounded-lg shadow-2xl text-center max-w-xs sm:max-w-sm"
          @click.stop
        >
          <div v-if="isCovered">
            <div class="text-green-500 text-5xl mb-4">🎉</div>
            <h3 class="text-2xl font-bold text-gray-800 mb-2">Selamat!</h3>
            <p class="text-gray-600">Alamat Anda sudah tercover jaringan kami.</p>
          </div>
          <div v-else>
            <div class="text-red-500 text-5xl mb-4">😔</div>
            <h3 class="text-2xl font-bold text-gray-800 mb-2">Mohon Maaf</h3>
            <p class="text-gray-600">Alamat Anda belum tercover jaringan kami.</p>
          </div>
          <button
            @click="closeResult"
            class="mt-6 px-6 py-2 bg-gray-200 text-gray-800 rounded-full hover:bg-gray-300 transition-colors"
          >
            Tutup
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Loader } from '@googlemaps/js-api-loader';
import axios from 'axios';

const mapInstance = ref(null);
const userMarker = ref(null);
const showResult = ref(false);
const isCovered = ref(false);
const isLoading = ref(true);
const odpLocations = ref([]);

/* ---------------------- Data & Render ---------------------- */
const initOdpData = async () => {
  try {
    const res = await axios.get('http://localhost/maps-pro/backend/api/odp.php');
    const data = Array.isArray(res.data) ? res.data : (res.data.data || []);
    odpLocations.value = data.filter(o => !isNaN(parseFloat(o.latitude)) && !isNaN(parseFloat(o.longitude)));
    // renderCoverageCircles();
  } catch (error) {
    console.error("Gagal memuat data ODP:", error);
  } finally {
    isLoading.value = false;
  }
};

const renderCoverageCircles = () => {
  if (!mapInstance.value) return;
  odpLocations.value.forEach(odp => {
    new google.maps.Circle({
      strokeColor: '#3B82F6',
      strokeOpacity: 0.8,
      strokeWeight: 1,
      fillColor: '#60A5FA',
      fillOpacity: 0.2,
      map: mapInstance.value,
      center: { lat: parseFloat(odp.latitude), lng: parseFloat(odp.longitude) },
      radius: 200,
    });
  });
};

/* ---------------------- Coverage Logic ---------------------- */
const isLocationCovered = (userLat, userLng) => {
  const userPoint = new google.maps.LatLng(userLat, userLng);
  let covered = false;

  for (const odp of odpLocations.value) {
    const odpPoint = new google.maps.LatLng(parseFloat(odp.latitude), parseFloat(odp.longitude));
    const distanceInMeters = google.maps.geometry.spherical.computeDistanceBetween(userPoint, odpPoint);
    if (distanceInMeters <= 200) {
      covered = true;
      break;
    }
  }
  return covered;
};

const checkCoverageAndShowResult = (lat, lng) => {
  isCovered.value = isLocationCovered(lat, lng);
  showResult.value = true;
};

const closeResult = () => {
  showResult.value = false;
};

/* ---------------------- Map Controls ---------------------- */
const getMyLocation = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const pos = { lat: position.coords.latitude, lng: position.coords.longitude };
        mapInstance.value.setCenter(pos);
        mapInstance.value.setZoom(17);
        updateUserMarker(pos);
        checkCoverageAndShowResult(pos.lat, pos.lng);
      },
      () => {
        alert('Tidak dapat mengambil lokasi Anda. Pastikan GPS diaktifkan.');
      }
    );
  } else {
    alert('Browser Anda tidak mendukung Geolocation.');
  }
};

const updateUserMarker = (position) => {
  if (userMarker.value) userMarker.value.setMap(null);
  userMarker.value = new google.maps.Marker({
    position,
    map: mapInstance.value,
    icon: {
      path: google.maps.SymbolPath.CIRCLE,
      fillColor: '#F59E0B',
      fillOpacity: 1,
      strokeColor: '#fff',
      strokeWeight: 2,
      scale: 8,
    },
    animation: google.maps.Animation.DROP,
  });
};

/* ---------------------- Map Init ---------------------- */
onMounted(async () => {
  const loader = new Loader({
    apiKey: 'AIzaSyA99VXYKlRV5wCubuIyXWdTGhSwkfyqeSc',
    version: 'weekly',
    libraries: ['places', 'geometry'],
  });

  await loader.load();
  mapInstance.value = new google.maps.Map(document.getElementById('map'), {
    center: { lat: -7.9467, lng: 112.6150 },
    zoom: 12,
    mapTypeControl: false,
    streetViewControl: false,
    zoomControl: true,
    fullscreenControl: true,
  });

  const input = document.getElementById('pac-input');
  const searchBox = new google.maps.places.SearchBox(input);
  mapInstance.value.addListener('bounds_changed', () => {
    searchBox.setBounds(mapInstance.value.getBounds());
  });

  searchBox.addListener('places_changed', () => {
    const places = searchBox.getPlaces();
    if (places.length === 0) return;
    const place = places[0];
    const location = place.geometry.location;
    mapInstance.value.setCenter(location);
    mapInstance.value.setZoom(17);
    updateUserMarker({ lat: location.lat(), lng: location.lng() });
    checkCoverageAndShowResult(location.lat(), location.lng());
  });

  initOdpData();
});
</script>

<style>
#map {
  min-height: calc(100vh - 120px); /* Biar penuh sisa layar */
}
</style>
