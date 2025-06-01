import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import "devextreme/dist/css/dx.light.css";
import axios from "axios";

import "./style.css";

axios.defaults.headers.common["Accept"] = "application/json";
axios.defaults.headers.post["Content-Type"] = "application/json";

axios.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("authToken");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

let isRefreshing = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
  failedQueue.forEach((prom) => {
    if (error) {
      prom.reject(error);
    } else {
      prom.resolve(token);
    }
  });
  failedQueue = [];
};

axios.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    if (
      error.response &&
      error.response.status === 401 &&
      !originalRequest._retry
    ) {
      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          failedQueue.push({ resolve, reject });
        })
          .then((token) => {
            originalRequest.headers["Authorization"] = `Bearer ${token}`;
            return axios(originalRequest);
          })
          .catch((err) => {
            return Promise.reject(err);
          });
      }

      originalRequest._retry = true;
      isRefreshing = true;

      try {
        const refreshResponse = await axios.post("/api/auth/refresh");
        const newToken = refreshResponse.data.access_token;

        localStorage.setItem("authToken", newToken);

        axios.defaults.headers.common["Authorization"] = `Bearer ${newToken}`;

        originalRequest.headers["Authorization"] = `Bearer ${newToken}`;

        processQueue(null, newToken);

        return axios(originalRequest);
      } catch (refreshError) {
        processQueue(refreshError, null);

        localStorage.removeItem("authToken");
        localStorage.removeItem("authUser");
        delete axios.defaults.headers.common["Authorization"];

        router.push("/login");

        console.error("Token refresh failed, logging out:", refreshError);
        return Promise.reject(refreshError);
      } finally {
        isRefreshing = false;
      }
    }

    return Promise.reject(error);
  }
);

function initializeApp() {
  const token = localStorage.getItem("authToken");
  if (token) {
    axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;
  }

  const app = createApp(App);
  app.use(router);
  app.mount("#app");
}

initializeApp();
