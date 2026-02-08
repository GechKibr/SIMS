const logoutButton = document.getElementById('logoutBtn');

if (logoutButton) {
    logoutButton.addEventListener('click', async () => {
        try {
            await fetch('/api/logout.php', {
                method: 'POST',
                credentials: 'same-origin',
            });
        } finally {
            window.location.href = '/index.html';
        }
    });
}
