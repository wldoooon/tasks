import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import "devextreme/dist/css/dx.light.css";
import api from "./utils/axios"; // Import the centralized API client

import "./style.css";

// Use our custom API instance as a global $api property
function initializeApp() {
  // Set up the application
  const app = createApp(App);
  
  // Use Vue router
  app.use(router);
  
  // Provide the api instance globally
  app.config.globalProperties.$api = api;
  
  // Mount the app
  app.mount("#app");
}

// Initialize the application
initializeApp();
