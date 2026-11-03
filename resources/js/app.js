import '../css/app.css';
import './chart.js';

document.querySelectorAll('form[data-check]').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('button');
        button.disabled = true;
        button.textContent = 'Memeriksa…';
        form.setAttribute('aria-busy', 'true');
    });
});

document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm('Hapus website dan seluruh riwayat pemeriksaannya?')) event.preventDefault();
    });
});
