import './assets/main.css'
import 'bootstrap-icons/font/bootstrap-icons.css';

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store' // importa lo store Vuex

createApp(App).use(router).use(store) .mount('#app')