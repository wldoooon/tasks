<template>
    <div class="register-container">
        <div class="register-form-container">
            <div class="form-header">
                <h2>Create Account</h2>
                <p>Sign up to get started</p>
            </div>

            <form @submit.prevent="handleRegister" class="register-form">
                <div class="input-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" v-model="formData.name" required
                        :class="{ 'error': validationErrors?.name }" placeholder="Enter your full name">
                    <span v-if="validationErrors?.name" class="field-error">
                        {{ validationErrors.name[0] }}
                    </span>
                </div>

                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" v-model="formData.email" required
                        :class="{ 'error': validationErrors?.email }" placeholder="Enter your email">
                    <span v-if="validationErrors?.email" class="field-error">
                        {{ validationErrors.email[0] }}
                    </span>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="password-input-wrapper">
                        <input :type="showPassword ? 'text' : 'password'" id="password" v-model="formData.password"
                            required :class="{ 'error': validationErrors?.password }" placeholder="Enter your password">
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
                    <span v-if="validationErrors?.password" class="field-error">
                        {{ validationErrors.password[0] }}
                    </span>
                </div>

                <div class="input-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <div class="password-input-wrapper">
                        <input :type="showConfirmPassword ? 'text' : 'password'" id="password_confirmation"
                            v-model="formData.password_confirmation" required
                            :class="{ 'error': validationErrors?.password_confirmation }"
                            placeholder="Confirm your password">
                        <button type="button" class="password-toggle" @click="toggleConfirmPasswordVisibility"
                            :aria-label="showConfirmPassword ? 'Hide password' : 'Show password'">
                            <svg v-if="showConfirmPassword" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="eye-icon">
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
                    <span v-if="validationErrors?.password_confirmation" class="field-error">
                        {{ validationErrors.password_confirmation[0] }}
                    </span>
                </div>

                <button type="submit" :disabled="isLoading" class="submit-btn">
                    <span v-if="isLoading" class="spinner"></span>
                    {{ isLoading ? 'Creating Account...' : 'Create Account' }}
                </button>
            </form>

            <div v-if="message" :class="['message', messageType]">
                {{ message }}
            </div>

            <div class="form-footer">
                <p>Already have an account? <a href="#" class="login-link" @click.prevent="goToLogin">Sign in</a></p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import api from '../utils/axios';

const router = useRouter();

const formData = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
});

const isLoading = ref(false);
const message = ref('');
const messageType = ref('');
const validationErrors = ref(null);

const showPassword = ref(false);
const showConfirmPassword = ref(false);

function goToLogin() {
    router.push('/login');
}

function togglePasswordVisibility() {
    showPassword.value = !showPassword.value;
}

function toggleConfirmPasswordVisibility() {
    showConfirmPassword.value = !showConfirmPassword.value;
}

async function handleRegister() {
    isLoading.value = true;
    message.value = '';
    messageType.value = '';
    validationErrors.value = null;

    try {
        const response = await api.post('/auth/register', formData);

        if (response.data.success) {
            message.value = response.data.message;
            messageType.value = 'success';

            console.log('Registration successful:', response.data);

            formData.name = '';
            formData.email = '';
            formData.password = '';
            formData.password_confirmation = '';

            showPassword.value = false;
            showConfirmPassword.value = false;

            setTimeout(() => {
                router.push('/login');
            }, 2000);
        }

    } catch (error) {
        if (error.response) {
            console.error('Registration error response:', error.response.data);

            if (error.response.status === 422) {
                const errorData = error.response.data;

                validationErrors.value = errorData.errors;

                message.value = errorData.message || 'Please fix the errors below.';
                messageType.value = 'error';


            } else if (error.response.status === 500) {
                const errorData = error.response.data;
                message.value = errorData.message || 'Server error. Please try again later.';
                messageType.value = 'error';

            } else {
                const errorData = error.response.data;
                message.value = errorData.message || 'Registration failed. Please try again.';
                messageType.value = 'error';
            }

        } else if (error.request) {
            console.error('Registration error request:', error.request);
            message.value = 'No response from server. Please check your internet connection.';
            messageType.value = 'error';

        } else {
            console.error('Registration error message:', error.message);
            message.value = 'An unexpected error occurred. Please try again.';
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

.register-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #ffffff;
    font-family: 'Inter', sans-serif;
    padding: 20px;
}

.register-form-container {
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

.register-form {
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

/* Password input wrapper styling */
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

.login-link {
    color: #000000;
    font-weight: 500;
    text-decoration: none;
}

.login-link:hover {
    text-decoration: underline;
}

@media (max-width: 480px) {
    .register-form-container {
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
