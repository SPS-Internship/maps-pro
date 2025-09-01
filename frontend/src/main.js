import { createApp, h } from 'vue'
import App from './App.vue'
import router from './router'
import DefaultLayout from './layouts/DefaultLayout.vue'
import './style.css'

const app = createApp({
  render: () => h(App)
})

// auto-register layout
app.component('default-layout', DefaultLayout)

app.use(router)
app.mount('#app')

// import { createApp } from 'vue'
// import './style.css'
// import App from './App.vue'

// createApp(App).mount('#app')
