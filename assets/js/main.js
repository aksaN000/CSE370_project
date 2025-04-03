/**
 * Habit Tracker - Main JavaScript File
 * Contains client-side functionality for the application
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Habit frequency type change handler
    const habitFrequencySelect = document.getElementById('habitFrequency');
    if (habitFrequencySelect) {
        habitFrequencySelect.addEventListener('change', function() {
            handleHabitFrequencyChange(this.value);
        });
        
        // Initialize on page load
        handleHabitFrequencyChange(habitFrequencySelect.value);
    }
    
    // Handle habit completion
    const habitCompletionForms = document.querySelectorAll('.habit-completion-form');
    habitCompletionForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const habitId = this.querySelector('input[name="habit_id"]').value;
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Show loading state
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
            submitButton.disabled = true;
            
            // Submit the form via AJAX
            fetch(this.action, {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    const habitCard = document.getElementById('habit-' + habitId);
                    habitCard.classList.add('border-success');
                    habitCard.querySelector('.card-header').classList.add('bg-success');
                    habitCard.querySelector('.card-header').classList.remove('bg-primary');
                    
                    // Replace form with completion message
                    const formContainer = this.closest('.card-body');
                    formContainer.innerHTML = '<div class="alert alert-success mb-0"><i class="bi bi-check-circle-fill"></i> Completed today!</div>';
                    
                    // Show success message
                    showToast('Success', data.message, 'success');
                    
                    // If level up occurred, show special notification
                    if (data.level_up) {
                        showLevelUpModal(data.new_level);
                    }
                } else {
                    // Show error message
                    showToast('Error', data.message, 'danger');
                    
                    // Reset button
                    submitButton.innerHTML = '<i class="bi bi-check"></i> Mark as Complete';
                    submitButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error', 'An error occurred while processing your request.', 'danger');
                
                // Reset button
                submitButton.innerHTML = '<i class="bi bi-check"></i> Mark as Complete';
                submitButton.disabled = false;
            });
        });
    });
    
    // Goal progress range input handlers
    document.querySelectorAll('input[type="range"][name="progress_range"]').forEach(range => {
        const progressId = range.id.replace('Range', '');
        const numberInput = document.getElementById(progressId);
        
        range.addEventListener('input', function() {
            numberInput.value = this.value;
            updateProgressBar(progressId);
        });
        
        numberInput.addEventListener('input', function() {
            range.value = this.value;
            updateProgressBar(progressId);
        });
    });
    
    // Task list sortable (for challenge creation)
    const taskContainer = document.getElementById('taskContainer');
    if (taskContainer) {
        enableTaskReordering();
    }
    
    // Theme switching logic
    const themeRadios = document.querySelectorAll('input[name="theme"]');
    const htmlElement = document.documentElement;
    
    function applyTheme(theme) {
        const htmlElement = document.documentElement;
        const body = document.body;
    
        if(theme === 'dark') {
            htmlElement.setAttribute('data-bs-theme', 'dark');
            body.classList.add('dark-theme');
            localStorage.setItem('habit-tracker-theme', 'dark');
        } else if(theme === 'light') {
            htmlElement.removeAttribute('data-bs-theme');
            body.classList.remove('dark-theme');
            localStorage.setItem('habit-tracker-theme', 'light');
        } else if(theme === 'system') {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (prefersDark) {
                htmlElement.setAttribute('data-bs-theme', 'dark');
                body.classList.add('dark-theme');
            } else {
                htmlElement.removeAttribute('data-bs-theme');
                body.classList.remove('dark-theme');
            }
            
            localStorage.setItem('habit-tracker-theme', 'system');
        }
    
        // Persist theme choice
        localStorage.setItem('habit-tracker-theme', theme);
    }
    
    // On page load, apply the stored or system theme
    document.addEventListener('DOMContentLoaded', function() {
        const storedTheme = localStorage.getItem('habit-tracker-theme') || 'light';
        const themeRadios = document.querySelectorAll('input[name="theme"]');
        
        // Set the correct radio button
        themeRadios.forEach(radio => {
            if (radio.value === storedTheme) {
                radio.checked = true;
            }
        });
    
        applyTheme(storedTheme);
    
        // Listen for system theme changes if using system theme
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (storedTheme === 'system') {
                applyTheme('system');
            }
        });
    });
    // Apply theme on page load
    const currentTheme = '<?php echo $current_theme; ?>';
    applyTheme(currentTheme);
    
    // Listen for theme changes
    themeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            applyTheme(this.value);
        });
    });
    
    // Listen for system theme changes if using system theme
    if(currentTheme === 'system') {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            applyTheme('system');
        });
    }
});

/**
 * Handle habit frequency change
 * @param {string} frequencyType - The frequency type value
 */
function handleHabitFrequencyChange(frequencyType) {
    // Get all frequency option containers
    const frequencyOptions = document.querySelectorAll('.frequency-options');
    
    // Hide all options first
    frequencyOptions.forEach(option => {
        option.style.display = 'none';
    });
    
    // Show the selected option
    if (frequencyType === 'daily') {
        // No special options for daily
    } else if (frequencyType === 'weekly') {
        document.getElementById('weeklyOptions').style.display = 'block';
    } else if (frequencyType === 'monthly') {
        document.getElementById('monthlyOptions').style.display = 'block';
    } else if (frequencyType === 'custom') {
        document.getElementById('customOptions').style.display = 'block';
    }
}

/**
 * Update progress bar for goal
 * @param {string} progressId - The ID of the progress input
 */
function updateProgressBar(progressId) {
    const progressInput = document.getElementById(progressId);
    const progressBar = document.getElementById('progressBar' + progressId.replace('progress', ''));
    
    if (progressInput && progressBar) {
        const value = parseInt(progressInput.value);
        const max = parseInt(progressInput.max);
        const percentage = (value / max) * 100;
        
        progressBar.style.width = percentage + '%';
        progressBar.setAttribute('aria-valuenow', percentage);
        progressBar.innerHTML = percentage.toFixed(1) + '%';
    }
}

/**
 * Enable reordering of tasks in challenge creation
 */
function enableTaskReordering() {
    const addTaskButton = document.getElementById('addTaskButton');
    const taskContainer = document.getElementById('taskContainer');
    let taskCount = document.querySelectorAll('.task-item').length;
    
    addTaskButton.addEventListener('click', function() {
        const taskItem = document.createElement('div');
        taskItem.className = 'task-item mb-3 p-3 border rounded';
        taskItem.innerHTML = `
            <div class="d-flex justify-content-between mb-2">
                <h6>Task ${taskCount + 1}</h6>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary me-1 move-task-up" title="Move Up">
                        <i class="bi bi-arrow-up"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary me-1 move-task-down" title="Move Down">
                        <i class="bi bi-arrow-down"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-task" title="Remove Task">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label">Task Title</label>
                <input type="text" class="form-control" name="tasks[${taskCount}][title]" placeholder="Task title" required>
            </div>
            <div>
                <label class="form-label">Description (Optional)</label>
                <textarea class="form-control" name="tasks[${taskCount}][description]" rows="2" placeholder="Task description"></textarea>
            </div>
        `;
        
        taskContainer.appendChild(taskItem);
        taskCount++;
        
        // Add event listeners
        addTaskEventListeners(taskItem);
    });
    
    // Add event listeners to existing tasks
    document.querySelectorAll('.task-item').forEach(taskItem => {
        addTaskEventListeners(taskItem);
    });
    
    function addTaskEventListeners(taskItem) {
        // Remove button
        const removeButton = taskItem.querySelector('.remove-task');
        if (removeButton) {
            removeButton.addEventListener('click', function() {
                taskItem.remove();
                updateTaskNumbers();
            });
        }
        
        // Move up button
        const moveUpButton = taskItem.querySelector('.move-task-up');
        if (moveUpButton) {
            moveUpButton.addEventListener('click', function() {
                const prevSibling = taskItem.previousElementSibling;
                if (prevSibling && prevSibling.classList.contains('task-item')) {
                    taskContainer.insertBefore(taskItem, prevSibling);
                    updateTaskNumbers();
                }
            });
        }
        
        // Move down button
        const moveDownButton = taskItem.querySelector('.move-task-down');
        if (moveDownButton) {
            moveDownButton.addEventListener('click', function() {
                const nextSibling = taskItem.nextElementSibling;
                if (nextSibling && nextSibling.classList.contains('task-item')) {
                    taskContainer.insertBefore(nextSibling, taskItem);
                    updateTaskNumbers();
                }
            });
        }
    }
    
    function updateTaskNumbers() {
        const taskItems = document.querySelectorAll('.task-item');
        taskItems.forEach((item, index) => {
            const heading = item.querySelector('h6');
            if (heading) {
                heading.textContent = `Task ${index + 1}`;
            }
            
            const titleInput = item.querySelector('input[name^="tasks"]');
            if (titleInput) {
                titleInput.name = `tasks[${index}][title]`;
            }
            
            const descTextarea = item.querySelector('textarea[name^="tasks"]');
            if (descTextarea) {
                descTextarea.name = `tasks[${index}][description]`;
            }
        });
        
        taskCount = taskItems.length;
    }
}

/**
 * Show toast notification
 * @param {string} title - The toast title
 * @param {string} message - The toast message
 * @param {string} type - The toast type (success, danger, warning, info)
 */
function showToast(title, message, type = 'info') {
    // Check if the toast container exists, if not create it
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toastId = 'toast-' + new Date().getTime();
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('id', toastId);
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    // Toast content
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <strong>${title}</strong>: ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    // Add to container
    toastContainer.appendChild(toast);
    
    // Initialize and show the toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 5000
    });
    bsToast.show();
    
    // Remove from DOM after hiding
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

/**
 * Show level up modal
 * @param {number} level - The new level
 */
function showLevelUpModal(level) {
    // Create modal element
    const modalId = 'levelUpModal';
    let modal = document.getElementById(modalId);
    
    // Remove existing modal if it exists
    if (modal) {
        modal.remove();
    }
    
    // Create new modal
    modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.setAttribute('id', modalId);
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-labelledby', `${modalId}Label`);
    modal.setAttribute('aria-hidden', 'true');
    
    // Modal content
    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="${modalId}Label">Level Up!</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="py-3">
                        <i class="bi bi-trophy-fill text-warning display-1 mb-3"></i>
                        <h2 class="mb-3">Congratulations!</h2>
                        <p class="lead">You've reached <strong>Level ${level}</strong>!</p>
                        <div class="pulse-animation">
                            <div class="level-badge">
                                ${level}
                            </div>
                        </div>
                        <p>Keep up the great work and continue building your habits!</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Awesome!</button>
                </div>
            </div>
        </div>
    `;
    
    // Add to body
    document.body.appendChild(modal);
    
    // Initialize and show the modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}