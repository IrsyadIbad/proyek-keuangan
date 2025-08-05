</main> <?php 
// Memasukkan file modal, pastikan file ini ada
include '_modal_konfirmasi.php'; 
?>

<div id="toast-container">
    <?php display_notification(); ?>
</div>


<script>
document.addEventListener('DOMContentLoaded', () => {

    // =======================================================
    // BAGIAN 1: LOGIKA UNTUK MODAL KONFIRMASI HAPUS
    // =======================================================
    const modalStore = Alpine.store('modal');
    const tombolKonfirmasiHapus = document.getElementById('tombol-konfirmasi-hapus');
    const tombolBatal = document.getElementById('tombol-batal');

    document.body.addEventListener('click', function(event) {
        const tombolHapus = event.target.closest('.tombol-hapus');
        if (tombolHapus) {
            event.preventDefault();
            const url = tombolHapus.dataset.url;
            if (url) {
                modalStore.deleteUrl = url;
                modalStore.isOpen = true;
            }
        }
    });

    if (tombolKonfirmasiHapus) {
        tombolKonfirmasiHapus.addEventListener('click', function(event) {
            event.preventDefault();
            if (modalStore.deleteUrl) {
                window.location.href = modalStore.deleteUrl;
            }
        });
    }

    if (tombolBatal) {
        tombolBatal.addEventListener('click', function() {
            modalStore.isOpen = false;
        });
    }

    // =======================================================
    // BAGIAN 2: LOGIKA UNTUK TOAST NOTIFICATION
    // =======================================================
    const toast = document.querySelector('#toast-container .toast');
    if (toast) {
        setTimeout(() => { toast.classList.add('show'); }, 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentElement) { toast.parentElement.remove(); }
            }, 500);
        }, 3000);
    }

    // =======================================================
    // BAGIAN 3: LOGIKA DROPDOWN KATEGORI (VANILLA JS)
    // =======================================================
    const categoryInput = document.getElementById('category-input');
    const categoryDropdown = document.getElementById('category-dropdown');

    if (categoryInput && categoryDropdown && typeof categoriesData !== 'undefined') {

        const renderDropdown = (filter = '') => {
            categoryDropdown.innerHTML = '';
            const filtered = categoriesData.filter(cat => cat.name.toLowerCase().includes(filter.toLowerCase()));

            if (filtered.length > 0) {
                filtered.forEach(cat => {
                    const optionDiv = document.createElement('div');
                    optionDiv.textContent = cat.name;
                    optionDiv.className = 'px-4 py-2 text-gray-700 hover:bg-gray-100 cursor-pointer';
                    optionDiv.addEventListener('click', () => {
                        categoryInput.value = cat.name;
                        categoryDropdown.classList.add('hidden');
                    });
                    categoryDropdown.appendChild(optionDiv);
                });
            } else {
                 categoryDropdown.innerHTML = '<div class="px-4 py-2 text-gray-500">Ketik untuk membuat kategori baru.</div>';
            }
        };

        categoryInput.addEventListener('focus', () => {
            renderDropdown(categoryInput.value);
            categoryDropdown.classList.remove('hidden');
        });

        categoryInput.addEventListener('input', () => {
            renderDropdown(categoryInput.value);
        });

        categoryInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                categoryDropdown.classList.add('hidden');
            }
        });

        document.addEventListener('click', (e) => {
            if (!categoryInput.contains(e.target) && !categoryDropdown.contains(e.target)) {
                categoryDropdown.classList.add('hidden');
            }
        });
    }

    // =======================================================
    // BAGIAN 4: LOGIKA ANIMASI KARTU DASHBOARD (YANG HILANG)
    // =======================================================
    const cards = document.querySelectorAll('.card-animate');
    if (cards.length > 0) {
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('visible');
            }, 100 * index);
        });
    }

});
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const expenseChartCanvas = document.getElementById('expenseChart');
if (expenseChartCanvas) {
    <?php
    if (!empty($chart_labels) && !empty($chart_data)) {
        echo "const chart_labels = " . json_encode($chart_labels) . ";\n";
        echo "const chart_data = " . json_encode($chart_data) . ";\n";
    } else {
        echo "const chart_labels = [];\n";
        echo "const chart_data = [];\n";
    }
    ?>

    if (chart_labels.length > 0) {
        const data = {
            labels: chart_labels,
            datasets: [{
                label: 'Pengeluaran',
                data: chart_data,
                backgroundColor: ['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#6366F1', '#F97316', '#0EA5E9', '#65A30D'],
                hoverOffset: 4
            }]
        };
        const config = {
            type: 'doughnut',
            data: data,
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
        };
        new Chart(expenseChartCanvas, config);
    }
}
</script>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 600,
        once: true
    });
</script>

</body>
</html>