const loginForm = document.getElementById('loginForm');
const loginMessage = document.getElementById('loginMessage');
const loginButton = document.getElementById('loginSubmitBtn');

if (loginForm) {
    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        loginMessage.textContent = '';
        loginButton.disabled = true;

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

            window.location.href = data.redirect || '/dashboard.php';
        } catch (error) {
            loginMessage.textContent = 'Unable to reach the server.';
        } finally {
            loginButton.disabled = false;
        }
    });
}
