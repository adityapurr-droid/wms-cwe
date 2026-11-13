<?php
/**
 * Layout Footer WMS CWE
 */
?>
        <!-- Institutional Footer -->
        <footer class="mt-auto py-3 px-3 px-md-4 bg-white border-top text-muted small w-100">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 text-center text-md-start">
                <div class="mb-1 mb-md-0">
                    &copy; <?= date('Y') ?> <strong><?= INSTITUTION_NAME ?></strong> (M.A.P). All rights reserved.
                </div>
                <div class="text-secondary text-center text-md-end">
                    <span class="badge bg-light text-dark border me-1"><?= APP_NAME ?> v<?= APP_VERSION ?></span>
                    <span class="d-inline-block">Sistem Manajemen Pergudangan Terpadu</span>
                </div>
            </div>
        </footer>
    </div> <!-- End content-wrapper -->
</div> <!-- End wrapper -->

<!-- Core Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables & Responsive -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.8/dist/sweetalert2.all.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<!-- Barcode & QR Code Libraries -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script data-pjax-global>
(function ($) {
    'use strict';

    /* ============================================================
       KONFIGURASI GLOBAL
       ============================================================ */
    const datatableOptions = {
        responsive: false,
        autoWidth: false,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Ketik untuk mencari...",
            lengthMenu: "Tampilkan _MENU_ baris",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
            infoFiltered: "(disaring dari _MAX_ entri keseluruhan)",
            zeroRecords: "Tidak ditemukan data yang sesuai",
            paginate: {
                first: "Awal",
                last: "Akhir",
                next: "Lanjut &rarr;",
                previous: "&larr; Kembali"
            }
        },
        pageLength: 6,
        lengthMenu: [[6, 10, 25, 50, -1], [6, 10, 25, 50, "Semua"]]
    };

    // Inisialisasi DataTables pada tabel yang belum diinisialisasi
    function initDataTables(scope) {
        if (!$.fn.DataTable) return;
        $(scope || document).find('.datatable').each(function () {
            if (!$.fn.dataTable.isDataTable(this)) {
                $(this).DataTable(datatableOptions);
            }
        });
    }

    window.WMS = window.WMS || {};
    window.WMS.initDataTables = initDataTables;

    /* ============================================================
       SIDEBAR (Desktop & Mobile)
       ============================================================ */
    function toggleSidebar() {
        const isMobile = window.innerWidth < 992;
        if (isMobile) {
            if ($('#sidebar').hasClass('show')) {
                closeMobileSidebar();
            } else {
                openMobileSidebar();
            }
        } else {
            $('#sidebar').toggleClass('collapsed');
        }
    }

    function openMobileSidebar() {
        $('#sidebar').addClass('show');
        $('#sidebarBackdrop').addClass('show');
        $('body').addClass('overflow-hidden');
    }

    function closeMobileSidebar() {
        $('#sidebar').removeClass('show');
        $('#sidebarBackdrop').removeClass('show');
        $('body').removeClass('overflow-hidden');
    }

    /* ============================================================
       NAVIGASI TANPA RELOAD (PJAX)
       ============================================================ */
    // Halaman yang tetap dimuat ulang secara normal (bukan konten HTML biasa)
    const PJAX_SKIP = /(?:logout|\/hapus\.php|export|cetak_)/i;
    let pjaxLoader = null;
    let navToken = 0;

    function ensureLoader() {
        if (pjaxLoader && document.body.contains(pjaxLoader)) return pjaxLoader;
        pjaxLoader = document.createElement('div');
        pjaxLoader.id = 'pjaxLoader';
        pjaxLoader.style.cssText = 'position:fixed;top:0;left:0;height:3px;width:0;background:linear-gradient(90deg,#198754,#20c997);z-index:2000;transition:width .25s ease,opacity .3s ease;pointer-events:none;opacity:0;';
        document.body.appendChild(pjaxLoader);
        return pjaxLoader;
    }

    function showLoader() {
        const el = ensureLoader();
        el.style.width = '10%';
        el.style.opacity = '1';
        setTimeout(function () {
            if (el.style.opacity === '1') el.style.width = '80%';
        }, 200);
    }

    function hideLoader() {
        if (!pjaxLoader) return;
        const el = pjaxLoader;
        el.style.width = '100%';
        setTimeout(function () {
            el.style.opacity = '0';
            el.style.width = '0';
        }, 250);
    }

    function toUrl(href, base) {
        try { return new URL(href, base || location.href); } catch (e) { return null; }
    }

    function isPjaxable(link) {
        if (!link) return false;
        if (link.hasAttribute('data-no-pjax')) return false;
        if (link.target && link.target !== '_self') return false;
        if (link.hasAttribute('download')) return false;
        if (link.classList.contains('btn-delete-confirm')) return false;

        const href = link.getAttribute('href');
        if (!href || href.charAt(0) === '#') return false;
        if (/^(mailto:|tel:|javascript:|blob:|data:)/i.test(href)) return false;

        const url = toUrl(link.href);
        if (!url || url.origin !== location.origin) return false;
        if (PJAX_SKIP.test(url.pathname)) return false;
        if (/\.(pdf|zip|rar|csv|xlsx?|docx?|jpe?g|png|gif|webp|svg|txt)$/i.test(url.pathname)) return false;
        if (url.pathname === location.pathname && url.search === location.search && url.hash) return false;

        return true;
    }

    // Ambil script halaman dari dokumen hasil fetch (tanpa script global layout)
    function collectScripts(doc) {
        return Array.prototype.slice.call(doc.body.querySelectorAll('script')).filter(function (script) {
            if (script.hasAttribute('data-pjax-global')) return false;
            const type = (script.type || '').toLowerCase();
            if (type && type !== 'text/javascript' && type !== 'application/javascript' && type !== 'module') return false;
            if (script.src) {
                return !Array.prototype.some.call(document.scripts, function (existing) {
                    return existing.src === script.src;
                });
            }
            return true;
        });
    }

    // Jalankan script halaman; event DOMContentLoaded dijalankan langsung
    // karena dokumen sudah dalam keadaan siap setelah navigasi PJAX.
    function runScripts(scripts) {
        const originalAdd = document.addEventListener;
        document.addEventListener = function (type, listener, options) {
            if (type === 'DOMContentLoaded' && typeof listener === 'function') {
                try {
                    listener.call(document, new Event('DOMContentLoaded'));
                } catch (err) {
                    console.error('[PJAX] Kesalahan pada DOMContentLoaded:', err);
                }
                return;
            }
            return originalAdd.call(document, type, listener, options);
        };

        try {
            scripts.forEach(function (oldScript) {
                const script = document.createElement('script');
                Array.prototype.forEach.call(oldScript.attributes, function (attr) {
                    if (attr.name === 'src') {
                        script.setAttribute('src', oldScript.src);
                    } else {
                        script.setAttribute(attr.name, attr.value);
                    }
                });
                script.textContent = oldScript.textContent;
                document.body.appendChild(script);
                script.remove();
            });
        } finally {
            document.addEventListener = originalAdd;
        }
    }

    function updateActiveMenu(url) {
        const target = toUrl(url);
        if (!target) return;

        const links = document.querySelectorAll('.sidebar-link');
        let best = null;

        links.forEach(function (link) { link.classList.remove('active'); });

        links.forEach(function (link) {
            const lurl = toUrl(link.href);
            if (lurl && lurl.pathname === target.pathname) best = link;
        });

        if (!best) {
            const match = target.pathname.match(/\/modules\/([^\/]+)\//);
            if (match) {
                links.forEach(function (link) {
                    const lurl = toUrl(link.href);
                    if (lurl && lurl.pathname.indexOf('/modules/' + match[1] + '/') !== -1) best = link;
                });
            }
        }

        if (best) best.classList.add('active');
    }

    async function navigate(url, push) {
        const currentWrapper = document.getElementById('content-wrapper');
        if (!currentWrapper) {
            window.location.href = url;
            return;
        }

        const token = ++navToken;
        showLoader();

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });

            if (token !== navToken) return;
            if (!response.ok) throw new Error('HTTP ' + response.status);

            // Sesi berakhir / dialihkan ke halaman login
            if (/login\.php/i.test(response.url)) {
                window.location.href = response.url;
                return;
            }

            const html = await response.text();
            if (token !== navToken) return;

            const doc = new DOMParser().parseFromString(html, 'text/html');
            const newWrapper = doc.getElementById('content-wrapper');
            if (!newWrapper) {
                window.location.href = url;
                return;
            }

            const scripts = collectScripts(doc);
            const clone = document.importNode(newWrapper, true);
            // Script dijalankan manual agar tidak dobel dan event init tetap berjalan
            Array.prototype.forEach.call(clone.querySelectorAll('script'), function (s) { s.remove(); });

            document.dispatchEvent(new CustomEvent('wms:before-replace', { detail: { url: url } }));

            currentWrapper.replaceWith(clone);
            runScripts(scripts);

            document.title = doc.title;
            updateActiveMenu(url);
            initDataTables(document);

            const wrapper = document.getElementById('content-wrapper');
            if (wrapper) wrapper.scrollTop = 0;

            if (push) {
                history.pushState({ pjax: true }, '', url);
            }

            document.dispatchEvent(new CustomEvent('wms:content-loaded', { detail: { url: url } }));
        } catch (err) {
            if (token !== navToken) return;
            console.error('[PJAX] Gagal memuat halaman:', err);
            window.location.href = url;
        } finally {
            if (token === navToken) hideLoader();
        }
    }

    // Bersihkan instance Chart.js lama sebelum konten diganti
    document.addEventListener('wms:before-replace', function () {
        if (window.Chart && Chart.instances) {
            Object.keys(Chart.instances).forEach(function (id) {
                try { Chart.instances[id].destroy(); } catch (e) {}
            });
        }
    });

    /* ============================================================
       EVENT BINDING
       ============================================================ */
    $(document).ready(function () {
        initDataTables(document);

        // Gunakan event delegation agar tetap berfungsi setelah navbar diganti PJAX
        $(document).on('click', '#sidebarToggle', function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });

        $(document).on('click', '#sidebarClose, #sidebarBackdrop', function (e) {
            e.preventDefault();
            closeMobileSidebar();
        });

        // Tutup sidebar otomatis saat link menu diklik di layar mobile
        $(document).on('click', '.sidebar-link', function () {
            if (window.innerWidth < 992) {
                closeMobileSidebar();
            }
        });

        // Tangani perubahan ukuran layar (Resize / Orientation Change)
        $(window).on('resize', function () {
            if (window.innerWidth >= 992) {
                closeMobileSidebar();
            }
        });

        // Konfirmasi Logout dengan SweetAlert2
        $(document).on('click', '.btn-logout-confirm', function (e) {
            e.preventDefault();
            const url = $(this).attr('href');

            Swal.fire({
                title: 'Konfirmasi Logout',
                text: 'Apakah Anda yakin ingin keluar dari sistem WMS?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0f5132',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-box-arrow-right me-1"></i> Ya, Keluar',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });

        // Konfirmasi Hapus Data dengan SweetAlert2
        $(document).on('click', '.btn-delete-confirm', function (e) {
            e.preventDefault();
            const url = $(this).attr('href');
            const itemName = $(this).data('name') || 'data ini';

            Swal.fire({
                title: 'Konfirmasi Penghapusan',
                text: `Apakah Anda yakin ingin menghapus ${itemName}? Tindakan ini tidak dapat dibatalkan.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bi bi-trash"></i> Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });

    // Intersepsi klik tautan internal agar tidak memuat ulang seluruh halaman
    document.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        const link = e.target.closest ? e.target.closest('a[href]') : null;
        if (!link || !isPjaxable(link)) return;

        e.preventDefault();
        navigate(link.href, true);
    });

    // Dukungan tombol Back / Forward browser
    window.addEventListener('popstate', function () {
        navigate(location.href, false);
    });

    if (window.history && history.replaceState) {
        history.replaceState({ pjax: true }, '', location.href);
    }
})(jQuery);
</script>
</body>
</html>

