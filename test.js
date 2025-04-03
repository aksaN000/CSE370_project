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
        if(theme === 'dark') {
            htmlElement.setAttribute('data-bs-theme', 'dark');
        } else if(theme === 'light') {
            htmlElement.removeAttribute('data-bs-theme');
        } else if(theme === 'system') {
            // Check system preference
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (prefersDark) {
                htmlElement.setAttribute('data-bs-theme', 'dark');
            } else {
                htmlElement.removeAttribute('data-bs-theme');
            }
        }
    }
    
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

// ... (existing functions) ...