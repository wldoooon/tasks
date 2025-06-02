<template>
  <div class="home-page-container">
    <header class="page-header">
      <h1>Welcome, {{ authUser ? authUser.name : 'Guest' }}!</h1>
      <p>Here are your tasks for today.</p>
    </header>

    <div class="task-controls">
      <button @click="openTaskForm()" class="btn btn-primary">
        + Add New Task
      </button>
    </div>

    <div v-if="showTaskForm" class="task-form-overlay">
      <div class="task-form-content">
        <h2>{{ isEditMode ? 'Edit Task' : 'Create New Task' }}</h2>
        <form @submit.prevent="handleFormSubmit">
          <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" v-model="formData.title" required>
            <span v-if="formErrors.title" class="error-text">{{ formErrors.title[0] }}</span>
          </div>
          <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" v-model="formData.description"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="status">Status</label>
              <select id="status" v-model="formData.status">
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
              </select>
            </div>
            <div class="form-group">
              <label for="priority">Priority</label>
              <select id="priority" v-model="formData.priority">
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="due_date">Due Date</label>
            <input type="date" id="due_date" v-model="formData.due_date">
          </div>
          <div class="form-actions">
            <button type="button" @click="closeTaskForm" class="btn btn-secondary">Cancel</button>
            <button type="submit" :disabled="isSubmitting" class="btn btn-primary">
              {{ isSubmitting ? 'Saving...' : (isEditMode ? 'Update Task' : 'Create Task') }}
            </button>
          </div>
          <p v-if="generalFormError" class="error-text general-error">{{ generalFormError }}</p>
        </form>
      </div>
    </div>

    <div v-if="isLoading" class="loading-indicator">Loading tasks...</div>
    <div v-if="apiError" class="error-message">{{ apiError }}</div>

    <div v-if="!isLoading && tasks.length === 0 && !apiError" class="no-tasks-message">
      No tasks yet. Add one to get started!
    </div>

    <ul v-if="tasks.length > 0" class="task-list">
      <li v-for="task in tasks" :key="task.id" class="task-item" :class="[task.status, task.priority + '-priority']">
        <div class="task-item-header">
          <input type="checkbox" :checked="task.status === 'completed'" @change="toggleTaskStatus(task)"
            class="task-checkbox" />
          <h3 :class="{ 'completed-title': task.status === 'completed' }">{{ task.title }}</h3>
        </div>
        <p v-if="task.description" class="task-description">{{ task.description }}</p>
        <div class="task-meta">
          <span>Status: {{ task.status.replace('_', ' ') }}</span>
          <span>Priority: {{ task.priority }}</span>
          <span v-if="task.due_date">Due: {{ formatDate(task.due_date) }}</span>
        </div>
        <div class="task-item-actions">
          <button @click="openTaskForm(task)" class="btn btn-sm btn-edit">Edit</button>
          <button @click="confirmDelete(task.id)" class="btn btn-sm btn-delete">Delete</button>
        </div>
      </li>
    </ul>

    <div v-if="showDeleteConfirm" class="task-form-overlay">
      <div class="task-form-content delete-confirm-dialog">
        <h3>Confirm Delete</h3>
        <p>Are you sure you want to delete this task?</p>
        <div class="form-actions">
          <button @click="cancelDelete" class="btn btn-secondary">Cancel</button>
          <button @click="executeDelete" class="btn btn-delete-confirm">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { getTasks, createTask, updateTask, deleteTask } from '../utils/taskApi'; // Ensure this path is correct

const authUser = ref(null);

const tasks = ref([]);
const isLoading = ref(false);
const apiError = ref('');

const showTaskForm = ref(false);
const isEditMode = ref(false);
const isSubmitting = ref(false);
const formErrors = ref({});
const generalFormError = ref('');

const initialFormData = () => ({
  id: null,
  title: '',
  description: '',
  status: 'pending',
  priority: 'medium',
  due_date: ''
});
const formData = reactive(initialFormData());

const showDeleteConfirm = ref(false);
const taskToDeleteId = ref(null);

const fetchLoggedInUser = () => {
  const user = localStorage.getItem('authUser');
  if (user) {
    authUser.value = JSON.parse(user);
  }
};

const fetchTasks = async () => {
  isLoading.value = true;
  apiError.value = '';
  try {
    const response = await getTasks();
    tasks.value = response.data.data.data || response.data.data; 
  } catch (err) {
    console.error("Failed to fetch tasks:", err);
    apiError.value = err.response?.data?.message || 'Failed to load tasks.';
    tasks.value = [];
  } finally {
    isLoading.value = false;
  }
};

const openTaskForm = (task = null) => {
  formErrors.value = {};
  generalFormError.value = '';
  if (task) {
    isEditMode.value = true;
    Object.assign(formData, task);
    if (task.due_date) { 
      formData.due_date = task.due_date.split('T')[0];
    }
  } else {
    isEditMode.value = false;
    Object.assign(formData, initialFormData());
  }
  showTaskForm.value = true;
};

const closeTaskForm = () => {
  showTaskForm.value = false;
  isEditMode.value = false;
  Object.assign(formData, initialFormData()); 
};

const handleFormSubmit = async () => {
  isSubmitting.value = true;
  formErrors.value = {};
  generalFormError.value = '';

  const payload = { ...formData };
  if (!payload.due_date) payload.due_date = null; 

  try {
    if (isEditMode.value) {
      await updateTask(formData.id, payload);
    } else {
      delete payload.id; 
      await createTask(payload);
    }
    fetchTasks(); 
    closeTaskForm();
  } catch (error) {
    console.error("Error saving task:", error);
    if (error.response && error.response.status === 422 && error.response.data.errors) {
      formErrors.value = error.response.data.errors;
    } else {
      generalFormError.value = error.response?.data?.message || 'An error occurred.';
    }
  } finally {
    isSubmitting.value = false;
  }
};

const toggleTaskStatus = async (task) => {
  const newStatus = task.status === 'completed' ? 'pending' : 'completed';
  try {
    await updateTask(task.id, { ...task, status: newStatus }); 
    fetchTasks(); 
  } catch (error) {
    console.error("Error updating task status:", error);
    apiError.value = "Failed to update task status.";
  }
};

const confirmDelete = (id) => {
  taskToDeleteId.value = id;
  showDeleteConfirm.value = true;
};

const cancelDelete = () => {
  showDeleteConfirm.value = false;
  taskToDeleteId.value = null;
};

const executeDelete = async () => {
  if (!taskToDeleteId.value) return;
  try {
    await deleteTask(taskToDeleteId.value);
    fetchTasks(); 
  } catch (error) {
    console.error("Error deleting task:", error);
    apiError.value = "Failed to delete task.";
  } finally {
    cancelDelete();
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

onMounted(() => {
  fetchLoggedInUser();
  fetchTasks();
});
</script>

<style scoped>
.home-page-container {
  max-width: 800px;
  margin: 20px auto;
  padding: 20px;
  font-family: 'Inter', sans-serif;
  background-color: #f9fafb;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.page-header {
  text-align: center;
  margin-bottom: 25px;
  padding-bottom: 15px;
  border-bottom: 1px solid #e5e7eb;
}

.page-header h1 {
  font-size: 2rem;
  color: #1f2937;
  margin: 0 0 5px 0;
}

.page-header p {
  font-size: 1rem;
  color: #4b5563;
}

.task-controls {
  margin-bottom: 20px;
  text-align: right;
}

.btn {
  padding: 10px 18px;
  border: none;
  border-radius: 6px;
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s ease, box-shadow 0.2s ease;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background-color: #2563eb;
}

.btn-secondary {
  background-color: #6b7280;
  color: white;
}

.btn-secondary:hover {
  background-color: #4b5563;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 0.85rem;
}

.btn-edit {
  background-color: #f59e0b;
  color: white;
}

.btn-edit:hover {
  background-color: #d97706;
}

.btn-delete {
  background-color: #ef4444;
  color: white;
}

.btn-delete:hover {
  background-color: #dc2626;
}

.btn-delete-confirm {
  background-color: #dc2626;
  color: white;
}

.btn-delete-confirm:hover {
  background-color: #b91c1c;
}


.task-form-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.task-form-content {
  background-color: white;
  padding: 25px 30px;
  border-radius: 8px;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  max-height: 90vh;
  overflow-y: auto;
}

.task-form-content h2 {
  margin-top: 0;
  margin-bottom: 20px;
  color: #1f2937;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #374151;
}

.form-group input[type="text"],
.form-group input[type="date"],
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 1rem;
  box-sizing: border-box;
}

.form-group textarea {
  min-height: 70px;
  resize: vertical;
}

.form-row {
  display: flex;
  gap: 15px;
}

.form-row .form-group {
  flex: 1;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.error-text {
  color: #ef4444;
  font-size: 0.8rem;
  margin-top: 4px;
}

.general-error {
  text-align: center;
  margin-top: 10px;
}

/* Task List */
.loading-indicator,
.error-message,
.no-tasks-message {
  text-align: center;
  padding: 20px;
  margin-top: 20px;
  border-radius: 6px;
}

.loading-indicator {
  color: #4b5563;
}

.error-message {
  background-color: #fee2e2;
  color: #b91c1c;
  border: 1px solid #fecaca;
}

.no-tasks-message {
  background-color: #eff6ff;
  color: #3b82f6;
  border: 1px solid #bfdbfe;
}

.task-list {
  list-style: none;
  padding: 0;
  margin-top: 20px;
}

.task-item {
  background-color: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
  transition: box-shadow 0.2s ease;
}

.task-item:hover {
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.06);
}

.task-item-header {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
}

.task-checkbox {
  margin-right: 10px;
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.task-item h3 {
  margin: 0;
  font-size: 1.15rem;
  font-weight: 600;
  color: #111827;
  flex-grow: 1;
}

.completed-title {
  text-decoration: line-through;
  color: #6b7280;
}

.task-description {
  font-size: 0.9rem;
  color: #4b5563;
  margin: 5px 0 10px 28px;
  white-space: pre-wrap;
}

.task-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  font-size: 0.8rem;
  color: #6b7280;
  margin-left: 28px;
  margin-bottom: 10px;
}

.task-meta span {
  background-color: #f3f4f6;
  padding: 3px 7px;
  border-radius: 4px;
}

.task-item-actions {
  text-align: right;
  margin-top: 10px;
}

.task-item-actions .btn {
  margin-left: 8px;
}

.task-item.pending {
  border-left: 4px solid #f59e0b;
}

.task-item.in_progress {
  border-left: 4px solid #3b82f6;
}

.task-item.completed {
  border-left: 4px solid #10b981;
}

.task-item.completed .task-item-header h3 {
  color: #6b7280;
}

.task-item.low-priority .task-meta span:nth-child(2) {
  background-color: #d1fae5;
  color: #065f46;
}

.task-item.medium-priority .task-meta span:nth-child(2) {
  background-color: #fef3c7;
  color: #92400e;
}

.task-item.high-priority .task-meta span:nth-child(2) {
  background-color: #fee2e2;
  color: #991b1b;
  font-weight: 500;
}

.delete-confirm-dialog {
  text-align: center;
}

.delete-confirm-dialog h3 {
  margin-top: 0;
}
</style>
