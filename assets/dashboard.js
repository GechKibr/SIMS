// Toast notification system
function showToast(message, type = 'info') {
    const container = document.querySelector('.toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'polite');
    toast.innerHTML = `<div>${message}</div>`;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(400px)';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container';
    container.setAttribute('aria-label', 'Notifications');
    document.body.appendChild(container);
    return container;
}

// Session timeout management
let sessionTimeout = 1800; // 30 minutes in seconds
let warningShown = false;
let activityTimer;

function resetActivityTimer() {
    clearTimeout(activityTimer);
    warningShown = false;
    
    // Warn user 2 minutes before timeout
    const warningTime = (sessionTimeout - 120) * 1000;
    activityTimer = setTimeout(() => {
        if (!warningShown) {
            warningShown = true;
            showToast('Your session will expire in 2 minutes due to inactivity.', 'warning');
        }
    }, warningTime);
}

// Track user activity
['mousedown', 'keypress', 'scroll', 'touchstart'].forEach(event => {
    document.addEventListener(event, resetActivityTimer, { passive: true });
});

// Initialize activity timer
resetActivityTimer();

// Logout functionality
const logoutButton = document.getElementById('logoutBtn');

if (logoutButton) {
    logoutButton.addEventListener('click', async () => {
        logoutButton.disabled = true;
        logoutButton.textContent = 'Signing out...';
        
        try {
            await fetch('/api/logout.php', {
                method: 'POST',
                credentials: 'same-origin',
            });
            showToast('Successfully logged out', 'success');
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            // Redirect even if logout API fails
            setTimeout(() => {
                window.location.href = '/index.html';
            }, 500);
        }
    });
}

// Keyboard navigation support
document.addEventListener('keydown', (e) => {
    // Alt+L for logout
    if (e.altKey && e.key === 'l' && logoutButton) {
        e.preventDefault();
        logoutButton.click();
    }
});
