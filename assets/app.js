// Toast notification system
function showToast(message, type = 'info') {
    const container = document.querySelector('.toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'polite');
    toast.innerHTML = `
        <div>${message}</div>
    `;
    
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

// Check for URL parameters (timeout, deactivated)
window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    
    if (params.get('timeout') === '1') {
        showToast('Your session has timed out. Please log in again.', 'warning');
        window.history.replaceState({}, '', window.location.pathname);
    }
    
    if (params.get('deactivated') === '1') {
        showToast('Your account has been deactivated. Please contact an administrator.', 'error');
        window.history.replaceState({}, '', window.location.pathname);
    }
});

const loginForm = document.getElementById('loginForm');
const loginMessage = document.getElementById('loginMessage');
const loginButton = document.getElementById('loginSubmitBtn');

if (loginForm) {
    // Add real-time validation
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    
    function validateUsername() {
        const value = usernameInput.value.trim();
        if (value.length === 0) {
            return 'Username is required';
        }
        if (value.length < 3) {
            return 'Username must be at least 3 characters';
        }
        return null;
    }
    
    function validatePassword() {
        const value = passwordInput.value;
        if (value.length === 0) {
            return 'Password is required';
        }
        if (value.length < 6) {
            return 'Password must be at least 6 characters';
        }
        return null;
    }
    
    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        loginMessage.textContent = '';
        
        // Validate inputs
        const usernameError = validateUsername();
        const passwordError = validatePassword();
        
        if (usernameError) {
            loginMessage.textContent = usernameError;
            usernameInput.focus();
            return;
        }
        
        if (passwordError) {
            loginMessage.textContent = passwordError;
            passwordInput.focus();
            return;
        }
        
        loginButton.disabled = true;
        loginButton.innerHTML = '<span class="spinner"></span> Signing in...';

        try {
            const formData = new FormData(loginForm);
            const response = await fetch('/api/login.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            });

            const data = await response.json();
            if (!response.ok || !data.ok) {
                loginMessage.textContent = data.message || 'Login failed.';
                return;
            }

            loginMessage.style.color = 'var(--success)';
            loginMessage.textContent = 'Login successful! Redirecting...';
            
            setTimeout(() => {
                window.location.href = data.redirect || '/dashboard.php';
            }, 500);
        } catch (error) {
            loginMessage.textContent = 'Unable to reach the server. Please try again.';
        } finally {
            loginButton.disabled = false;
            loginButton.textContent = 'Sign In';
        }
    });
    
    // Add enter key support
    loginForm.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !loginButton.disabled) {
            e.preventDefault();
            loginForm.dispatchEvent(new Event('submit'));
        }
    });
}
