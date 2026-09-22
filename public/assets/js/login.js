document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('togglePassword');
    const toggleIcon = document.getElementById('toggleIcon');
    const passwordInput = document.getElementById('password');

    if (toggleBtn && passwordInput && toggleIcon) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';

            passwordInput.type = isPassword ? 'text' : 'password';
            toggleIcon.textContent = isPassword ? 'visibility_off' : 'visibility';
            toggleBtn.classList.toggle('is-active', isPassword);
            toggleBtn.setAttribute('aria-pressed', String(isPassword));
            toggleBtn.setAttribute(
                'aria-label',
                isPassword ? 'Sembunyikan password' : 'Tampilkan password'
            );
        });
    }

    const loginForm = document.querySelector('.login-form');
    const usernameInput = document.getElementById('username');

    if (loginForm && usernameInput && passwordInput) {
        loginForm.addEventListener('submit', function (e) {
            const username = usernameInput.value.trim();
            const password = passwordInput.value.trim();

            if (!username || !password) {
                e.preventDefault();
                alert('Username dan password wajib diisi.');
                return;
            }
        });
    }
});