import axios from 'axios';
import router from '../router';

// Create axios instance
const api = axios.create({
  baseURL: '/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

let isRefreshing = false;

let failedQueue = [];

const processQueue = (error, token = null) => {
  failedQueue.forEach(promise => {
    if (error) {
      promise.reject(error);
    } else {
      promise.resolve(token);
    }
  });
  
  failedQueue = [];
};

api.interceptors.request.use(
  config => {
    const token = localStorage.getItem('authToken');
    if (token) {
      config.headers['Authorization'] = `Bearer ${token}`;
    }
    return config;
  },
  error => {
    return Promise.reject(error);
  }
);

api.interceptors.response.use(
  response => {
    return response;
  },
  async error => {
    const originalRequest = error.config;
    
    if (originalRequest.url === '/auth/logout') {
      localStorage.removeItem('authToken');
      localStorage.removeItem('authUser');
      
      router.push('/login');
      return Promise.reject(error);
    }
    
    if (!error.response || error.response.status !== 401 || originalRequest._retry) {
      return Promise.reject(error);
    }
    
    if (originalRequest.url === '/auth/refresh') {
      localStorage.removeItem('authToken');
      localStorage.removeItem('authUser');
      
      router.push('/login');
      return Promise.reject(error);
    }
    
    if (isRefreshing) {
      return new Promise((resolve, reject) => {
        failedQueue.push({ resolve, reject });
      })
        .then(token => {
          originalRequest.headers['Authorization'] = `Bearer ${token}`;
          return api(originalRequest);
        })
        .catch(err => {
          return Promise.reject(err);
        });
    }
    
    // Mark that we're now refreshing
    originalRequest._retry = true;
    isRefreshing = true;
    
    try {
      // Attempt to refresh the token using the api instance with baseURL already set
      const response = await api.post('/auth/refresh', {}, {
        // Explicitly include the current token in the request
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('authToken')}`
        }
      });
      
      // If successful, update the token and retry the original request
      const { access_token } = response.data;
      localStorage.setItem('authToken', access_token);
      
      // Update axios default header
      api.defaults.headers.common['Authorization'] = `Bearer ${access_token}`;
      
      // Process the queued requests
      processQueue(null, access_token);
      
      // Update the auth header of the failed request and retry it
      originalRequest.headers['Authorization'] = `Bearer ${access_token}`;
      
      // Reset the refreshing flag
      isRefreshing = false;
      
      // Return the retried request
      return api(originalRequest);
    } catch (refreshError) {
      console.error('Token refresh failed:', refreshError);
      
      // If refresh fails, process the queue with error
      processQueue(refreshError, null);
      
      // Reset the refreshing flag
      isRefreshing = false;
      
      // Clear auth data
      localStorage.removeItem('authToken');
      localStorage.removeItem('authUser');
      
      // Redirect to login page
      router.push('/login');
      
      // Reject the original request
      return Promise.reject(refreshError);
    }
  }
);

// Export the configured axios instance
export default api;

