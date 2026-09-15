import './bootstrap';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const topbar = document.getElementById('topbar');
    const toggleBtn = document.getElementById('toggleBtn');
    const mobileBtn = document.getElementById('mobileBtn');
    const overlay = document.getElementById('overlay');

    toggleBtn?.addEventListener('click', () => {
        sidebar?.classList.toggle('collapsed');
        content?.classList.toggle('full');
        topbar?.classList.toggle('full');
    });

    mobileBtn?.addEventListener('click', () => {
        sidebar?.classList.add('mobile-show');
        overlay?.classList.add('show');
    });

    overlay?.addEventListener('click', () => {
        sidebar?.classList.remove('mobile-show');
        overlay?.classList.remove('show');
    });
});
