import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import './style.css'
import 'vue-select/dist/vue-select.css'

createApp(App).use(router).mount('#app')