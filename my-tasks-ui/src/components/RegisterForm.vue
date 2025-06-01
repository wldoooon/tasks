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
                    <input type="password" id="password" v-model="formData.password" required
                        :class="{ 'error': validationErrors?.password }" placeholder="Enter your password">
                    <span v-if="validationErrors?.password" class="field-error">
                        {{ validationErrors.password[0] }}
                    </span>
                </div>

                <div class="input-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" id="password_confirmation" v-model="formData.password_confirmation" required
                        :class="{ 'error': validationErrors?.password_confirmation }"
                        placeholder="Confirm your password">
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
import axios from 'axios';

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

function goToLogin() {
    router.push('/login');
}

async function handleRegister() {
    isLoading.value = true;
    message.value = '';
    messageType.value = '';
    validationErrors.value = null;

    try {
        const response = await axios.post('/api/auth/register', formData);

        if (response.data.success) {
            message.value = response.data.message;
            messageType.value = 'success';
            
            console.log('Registration successful:', response.data);

            // Clear form
            formData.name = '';
            formData.email = '';
            formData.password = '';
            formData.password_confirmation = '';

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
                
                if (errorData.error_count > 1) {
                    message.value += ` (${errorData.error_count} errors found)`;
                }
                
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
