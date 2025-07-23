document.addEventListener('DOMContentLoaded', function() {
    // Navigasi Tab
    const navLinks = document.querySelectorAll('.sidebar-menu a');
    const pages = document.querySelectorAll('.page');

    function showPage(hash) {
        // Sembunyikan semua halaman
        pages.forEach(p => p.classList.remove('active'));
        // Hapus kelas aktif dari semua link
        navLinks.forEach(l => l.classList.remove('active'));

        const targetPage = document.querySelector(hash);
        const targetLink = document.querySelector(`.sidebar-menu a[href="${hash}"]`);

        if (targetPage && targetLink) {
            targetPage.classList.add('active');
            targetLink.classList.add('active');
        }
    }

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const hash = this.getAttribute('href');
            // Hanya proses link internal (yang dimulai dengan #)
            if (hash.startsWith('#')) {
                e.preventDefault();
                window.location.hash = hash;
                showPage(hash);
            }
        });
    });

    // --- PERBAIKAN UTAMA ADA DI SINI ---
    // Logika untuk menampilkan halaman saat pertama kali dimuat
    function initializeDashboardView() {
        const currentHash = window.location.hash;
        
        // Jika ada hash di URL dan elemennya ada, tampilkan halaman tersebut
        if (currentHash && document.querySelector(currentHash)) {
            showPage(currentHash);
        } else {
            // Jika tidak, cari link pertama yang valid di sidebar dan tampilkan halamannya
            const firstValidLink = document.querySelector('.sidebar-menu a[href^="#"]');
            if (firstValidLink) {
                const defaultHash = firstValidLink.getAttribute('href');
                window.location.hash = defaultHash; // Set hash di URL
                showPage(defaultHash);
            }
        }
    }

    // Panggil fungsi inisialisasi untuk menampilkan halaman yang benar
    initializeDashboardView();

    // Event listener untuk perubahan hash (jika pengguna menggunakan tombol back/forward browser)
    window.addEventListener('hashchange', function() {
        const currentHash = window.location.hash;
        if(currentHash) {
            showPage(currentHash);
        }
    }, false);
});