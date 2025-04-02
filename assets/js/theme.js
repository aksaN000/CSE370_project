/**
 * Habit Tracker - Theme Switching
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get HTML element
    const htmlElement = document.documentElement;
    const bodyElement = document.body;
    
    // Get stored theme preferences
    const storedTheme = localStorage.getItem('habit-tracker-theme') || 'light';
    const storedColorScheme = localStorage.getItem('habit-tracker-color-scheme') || 'default';
    const storedAnimations = localStorage.getItem('habit-tracker-animations') !== 'false';
    const storedCompactMode = localStorage.getItem('habit-tracker-compact-mode') === 'true';
    
    // Apply stored preferences on page load
    applyTheme(storedTheme);
    applyColorScheme(storedColorScheme);
    applyAnimations(storedAnimations);
    applyCompactMode(storedCompactMode);
    
    // Theme switching function
    function applyTheme(theme) {
        if(theme === 'dark') {
            htmlElement.setAttribute('data-bs-theme', 'dark');
        } else if(theme === 'light') {
            htmlElement.setAttribute('data-bs-theme', 'light');
        } else if(theme === 'system') {
            // Check system preference
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            htmlElement.setAttribute('data-bs-theme', prefersDark ? 'dark' : 'light');
        }
        
        // Save preference
        localStorage.setItem('habit-tracker-theme', theme);
    }
    
    // Color scheme switching function
    function applyColorScheme(scheme) {
        // Remove any existing color scheme classes
        const colorClasses = ['color-default', 'color-teal', 'color-indigo', 'color-rose', 'color-amber', 'color-emerald'];
        colorClasses.forEach(cls => {
            bodyElement.classList.remove(cls);
        });
        
        // Add the new color scheme class
        bodyElement.classList.add('color-' + scheme);
        
        // Save preference
        localStorage.setItem('habit-tracker-color-scheme', scheme);
    }
    
    // Animation toggling function
    function applyAnimations(enable) {
        if(enable) {
            bodyElement.classList.add('enable-animations');
        } else {
            bodyElement.classList.remove('enable-animations');
        }
        
        // Save preference
        localStorage.setItem('habit-tracker-animations', enable);
    }
    
    // Compact mode toggling function
    function applyCompactMode(enable) {
        if(enable) {
            bodyElement.classList.add('compact-mode');
        } else {
            bodyElement.classList.remove('compact-mode');
        }
        
        // Save preference
        localStorage.setItem('habit-tracker-compact-mode', enable);
    }
    
    // Listen for theme changes from settings page
    const themeRadios = document.querySelectorAll('input[name="theme"]');
    if(themeRadios.length > 0) {
        themeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                applyTheme(this.value);
            });
        });
    }
    
    // Listen for color scheme changes
    const colorSchemeRadios = document.querySelectorAll('input[name="color_scheme"]');
    if(colorSchemeRadios.length > 0) {
        colorSchemeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                applyColorScheme(this.value);
            });
        });
    }
    
    // Listen for animations toggle
    const animationsToggle = document.getElementById('enableAnimations');
    if(animationsToggle) {
        animationsToggle.addEventListener('change', function() {
            applyAnimations(this.checked);
        });
    }
    
    // Listen for compact mode toggle
    const compactModeToggle = document.getElementById('compactMode');
    if(compactModeToggle) {
        compactModeToggle.addEventListener('change', function() {
            applyCompactMode(this.checked);
        });
    }
    
    // Listen for system theme changes if using system theme
    if(storedTheme === 'system') {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            applyTheme('system');
        });
    }
});