import { createApp } from "vue";
import App from "./App.vue";
import "devextreme/dist/css/dx.light.css";
import axios from "axios";

import "./style.css";

axios.defaults.withCredentials = true;

async function initializeApp() {
  try {
    await axios.get("/sanctum/csrf-cookie");
    console.log("CSRF cookie fetched successfully");
  } catch (error) {
    console.error("Could not fetch CSRF cookie:", error);
    console.error("Error details:", {
      message: error.message,
      response: error.response?.data,
      status: error.response?.status,
    });
  }

  const app = createApp(App);
  app.mount("#app");
}

initializeApp();
