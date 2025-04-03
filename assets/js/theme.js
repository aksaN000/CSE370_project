/**
 * Habit Tracker - Theme Switching
 */

document.addEventListener('DOMContentLoaded', function() {
    // Robust animation handling
    function applyAnimations(enable) {
        if (enable) {
            document.body.classList.add('enable-animations');
            localStorage.setItem('habit-tracker-animations', 'true');
        } else {
            document.body.classList.remove('enable-animations');
            localStorage.setItem('habit-tracker-animations', 'false');
        }
    }

    // Check initial animation state
    const storedAnimations = localStorage.getItem('habit-tracker-animations');
    const animationsToggle = document.getElementById('enableAnimations');

    // Default to enabled if not set
    if (storedAnimations === null) {
        applyAnimations(true);
        if (animationsToggle) animationsToggle.checked = true;
    } else {
        const isEnabled = storedAnimations !== 'false';
        applyAnimations(isEnabled);
        if (animationsToggle) animationsToggle.checked = isEnabled;
    }

    // Add event listener if toggle exists
    if (animationsToggle) {
        animationsToggle.addEventListener('change', function() {
            applyAnimations(this.checked);
        });
    }
});