<template>
    <div class="login-container">
        <div class="login-form-container">
            <div class="form-header">
                <h2>Welcome Back</h2>
                <p>Sign in to your account</p>
            </div>

            <form @submit.prevent="handleLogin" class="login-form">
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" v-model="formData.email" required
                        :class="{ 'error': fieldErrors?.email }" placeholder="Enter your email">
                    <span v-if="fieldErrors?.email" class="field-error">
                        {{ fieldErrors.email }}
                    </span>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="password-input-wrapper">
                        <input :type="showPassword ? 'text' : 'password'" id="password" v-model="formData.password"
                            required :class="{ 'error': fieldErrors?.password }" placeholder="Enter your password">
                        <button type="button" class="password-toggle" @click="togglePasswordVisibility"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'">
                            <svg v-if="showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="eye-icon">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 11-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="eye-icon">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.639 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.639 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                    <span v-if="fieldErrors?.password" class="field-error">
                        {{ fieldErrors.password }}
                    </span>
                </div>

                <button type="submit" :disabled="isLoading" class="submit-btn">
                    <span v-if="isLoading" class="spinner"></span>
                    {{ isLoading ? 'Signing In...' : 'Sign In' }}
                </button>
            </form>

            <div v-if="message && !hasFieldErrors" :class="['message', messageType]">
                {{ message }}
            </div>

            <div class="form-footer">
                <p>Don't have an account?
                    <router-link to="/register" class="register-link">Create Account</router-link>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '../utils/axios';

const router = useRouter();

const formData = reactive({
    email: '',
    password: ''
});

const isLoading = ref(false);
const message = ref('');
const messageType = ref('');
const fieldErrors = ref(null);
const showPassword = ref(false);

const hasFieldErrors = computed(() => {
    return fieldErrors.value && Object.keys(fieldErrors.value).length > 0;
});

function togglePasswordVisibility() {
    showPassword.value = !showPassword.value;
}

function clearErrors() {
    message.value = '';
    messageType.value = '';
    fieldErrors.value = null;
}

async function handleLogin() {
    isLoading.value = true;
    clearErrors();

    try {
        const response = await api.post('/auth/login', formData);

        const { data } = response.data;
        const { access_token, user } = data;

        localStorage.setItem('authToken', access_token);
        localStorage.setItem('authUser', JSON.stringify(user));

        message.value = 'Login successful! Redirecting...';
        messageType.value = 'success';

        formData.email = '';
        formData.password = '';
        showPassword.value = false; // Hide password when clearing form

        setTimeout(() => {
            router.push('/home');
        }, 1000);

    } catch (error) {
        // Detailed error logging for debugging
        console.error('Login error:', error);
        
        if (error.response) {
            const { status, data } = error.response;
            // Log the complete response data for debugging
            console.log('Error response data:', JSON.stringify(data, null, 2));
            
            if (status === 401) {
                // Authentication failed - show field-specific errors from backend
                if (data.field_errors) {
                    console.log('Field errors found:', data.field_errors);
                    fieldErrors.value = data.field_errors;
                } else {
                    // Fallback if no field_errors provided
                    message.value = data.message || 'Invalid credentials';
                    messageType.value = 'error';
                    
                    // Check if there's an error property with field-specific errors
                    if (data.error && typeof data.error === 'object') {
                        fieldErrors.value = data.error;
                    }
                }
            } else if (status === 422) {
                // Validation errors (empty email/password)
                if (data.field_errors) {
                    console.log('Validation field errors found:', data.field_errors);
                    fieldErrors.value = data.field_errors;
                } else if (data.errors && typeof data.errors === 'object') {
                    // Handle Laravel validation errors format
                    console.log('Laravel validation errors found:', data.errors);
                    // Convert Laravel validation errors to our field_errors format
                    const convertedErrors = {};
                    for (const [field, errorMsgs] of Object.entries(data.errors)) {
                        convertedErrors[field] = Array.isArray(errorMsgs) ? errorMsgs[0] : errorMsgs;
                    }
                    fieldErrors.value = convertedErrors;
                } else {
                    message.value = data.message || 'Please check your input';
                    messageType.value = 'error';
                }
            } else if (status === 429) {
                // Rate limiting
                message.value = data.message || 'Too many attempts. Please try again later.';
                messageType.value = 'error';
            } else if (status === 500) {
                // Server error
                message.value = 'Server error. Please try again later.';
                messageType.value = 'error';
            } else {
                // Other errors
                message.value = data.message || 'Login failed. Please try again.';
                messageType.value = 'error';
                
                // Still try to extract field_errors if available
                if (data.field_errors) {
                    fieldErrors.value = data.field_errors;
                }
            }
        } else if (error.request) {
            // Network error
            console.error('Network error - no response received:', error.request);
            message.value = 'No response from server. Please check your connection.';
            messageType.value = 'error';
        } else {
            // Unexpected error
            console.error('Unexpected error occurred:', error.message);
            message.value = 'An unexpected error occurred.';
            messageType.value = 'error';
        }
    } finally {
        isLoading.value = false;
    }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

* {
    box-sizing: border-box;
}

.login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #ffffff;
    font-family: 'Inter', sans-serif;
    padding: 20px;
}

.login-form-container {
    width: 100%;
    max-width: 400px;
    background-color: #ffffff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 40px 32px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
}

.form-header {
    text-align: center;
    margin-bottom: 32px;
}

.form-header h2 {
    font-size: 24px;
    font-weight: 600;
    color: #000000;
    margin: 0 0 8px 0;
}

.form-header p {
    font-size: 14px;
    color: #666666;
    margin: 0;
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.input-group {
    display: flex;
    flex-direction: column;
}

.input-group label {
    font-size: 14px;
    font-weight: 500;
    color: #000000;
    margin-bottom: 6px;
}

.input-group input {
    background-color: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 12px 16px;
    font-size: 14px;
    color: #000000;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.input-group input::placeholder {
    color: #9ca3af;
}

.input-group input:focus {
    border-color: #000000;
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05);
}

.input-group input.error {
    border-color: #ef4444;
}

/* Password input wrapper styling - exactly like RegisterForm */
.password-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.password-input-wrapper input {
    width: 100%;
    padding-right: 45px;
    /* Make space for the eye icon */
}

.password-toggle {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    transition: color 0.2s ease;
}

.password-toggle:hover {
    color: #374151;
}

.password-toggle:focus {
    outline: none;
    color: #000000;
}

.eye-icon {
    width: 20px;
    height: 20px;
}

.field-error {
    font-size: 12px;
    color: #ef4444;
    margin-top: 4px;
}

.submit-btn {
    background-color: #000000;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 8px;
}

.submit-btn:hover:not(:disabled) {
    background-color: #1f2937;
}

.submit-btn:disabled {
    background-color: #9ca3af;
    cursor: not-allowed;
}

.spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.message {
    padding: 12px 16px;
    margin-top: 16px;
    border-radius: 6px;
    font-size: 14px;
    text-align: center;
}

.message.success {
    background-color: #f0f9ff;
    color: #059669;
    border: 1px solid #10b981;
}

.message.error {
    background-color: #fef2f2;
    color: #dc2626;
    border: 1px solid #ef4444;
}

.form-footer {
    text-align: center;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #e5e7eb;
}

.form-footer p {
    font-size: 14px;
    color: #666666;
    margin: 0;
}

.register-link {
    color: #000000;
    font-weight: 500;
    text-decoration: none;
}

.register-link:hover {
    text-decoration: underline;
}

@media (max-width: 480px) {
    .login-form-container {
        padding: 32px 24px;
        border: none;
        border-radius: 0;
        box-shadow: none;
    }

    .form-header h2 {
        font-size: 22px;
    }
}
</style>