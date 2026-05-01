const sidebar  = document.getElementById('sidebar');
const topbar   = document.getElementById('topbar');
const content  = document.getElementById('main-content');
const overlay  = document.getElementById('sidebar-overlay');
const isMobile = () => window.innerWidth < 992;
let collapsed  = false;

function toggleSidebar() {
    if (isMobile()) {
        sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('show');
    } else {
        collapsed = !collapsed;
        sidebar.classList.toggle('collapsed', collapsed);
        topbar.classList.toggle('expanded', collapsed);
        content.classList.toggle('expanded', collapsed);
    }
}

window.addEventListener('resize', () => {
    if (!isMobile()) {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('show');
    }
});
