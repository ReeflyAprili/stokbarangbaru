/* assets/js/main.js - Global Client Logic for PT Wifian Solution Inventory */

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Mobile Toggle
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebar-toggle');
    const overlay = document.getElementById('sidebar-overlay');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('-translate-x-full');
            if (overlay) overlay.classList.toggle('hidden');
        });
    }

    if (overlay && sidebar) {
        overlay.addEventListener('click', function() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
    }

    // Auto-dismiss alerts after 5 seconds
    const flashAlert = document.getElementById('flash-alert');
    if (flashAlert) {
        setTimeout(function() {
            flashAlert.style.opacity = '0';
            flashAlert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => flashAlert.remove(), 500);
        }, 5000);
    }
});

// Modal Helpers
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

// Live Table Search Helper
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    if (!table) return;
    const trs = table.querySelectorAll('tbody tr');

    trs.forEach(tr => {
        const text = tr.textContent.toLowerCase();
        if (text.includes(filter)) {
            tr.style.display = '';
        } else {
            tr.style.display = 'none';
        }
    });
}

// Print Handler
function triggerPrint() {
    window.print();
}
