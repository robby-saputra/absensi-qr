document.addEventListener('DOMContentLoaded', () => {
    const sendHeartbeat = () => {
        if (window.location.pathname === '/login') {
            return;
        }

        fetch('/heartbeat', {
            headers: { 'Accept': 'application/json' },
            cache: 'no-store',
        }).catch(() => {});
    };

    sendHeartbeat();
    window.setInterval(sendHeartbeat, 30000);

    const addIcon = (element, icon) => {
        if (element.querySelector('i')) {
            return;
        }

        const item = document.createElement('i');
        item.className = `fa-solid ${icon}`;
        item.setAttribute('aria-hidden', 'true');
        element.prepend(item);
    };

    document.querySelectorAll('.btn, .btn-edit, .btn-hapus, .btn-kembali, .btn-tambah, .btn-reset, .back, .edit, .hapus, button, a.danger').forEach((element) => {
        const href = element.getAttribute('href') || '';
        const text = element.textContent.trim().toLowerCase();

        if (element.classList.contains('toggle-btn') || element.classList.contains('link')) {
            return;
        }

        const isDangerAction = element.classList.contains('danger') && ['A', 'BUTTON'].includes(element.tagName);

        if (href.includes('/delete') || element.classList.contains('hapus') || isDangerAction || text.includes('hapus')) {
            addIcon(element, 'fa-trash');
        } else if (href.includes('/edit') || element.classList.contains('edit') || text.includes('edit')) {
            addIcon(element, 'fa-pen-to-square');
        } else if (href.includes('/create') || text.includes('tambah')) {
            addIcon(element, 'fa-plus');
        } else if (href.includes('reset-password') || text.includes('reset')) {
            addIcon(element, 'fa-rotate-left');
        } else if (href.includes('import') || text.includes('import')) {
            addIcon(element, 'fa-file-import');
        } else if (href.includes('template') || href.includes('export') || text.includes('export')) {
            addIcon(element, 'fa-file-arrow-down');
        } else if (text.includes('kembali') || href === '/dashboard/admin') {
            addIcon(element, 'fa-arrow-left');
        } else if (text.includes('tampilkan') || text.includes('cari')) {
            addIcon(element, 'fa-magnifying-glass');
        } else if (text.includes('nonaktif')) {
            addIcon(element, 'fa-user-slash');
        } else if (text.includes('aktif')) {
            addIcon(element, 'fa-user-check');
        } else if (element.tagName === 'BUTTON') {
            addIcon(element, 'fa-check');
        }
    });

    const isAdminPage = window.location.pathname.startsWith('/dashboard/admin');
    const hasSweetAlert = typeof window.Swal !== 'undefined';
    const adminSweet = {
        customClass: {
            popup: 'admin-swal',
            confirmButton: 'admin-swal-confirm',
            cancelButton: 'admin-swal-cancel',
        },
        buttonsStyling: false,
    };

    const showAdminLoading = (message = 'Mohon tunggu sebentar.') => {
        if (! isAdminPage || ! hasSweetAlert) {
            return;
        }

        window.Swal.fire({
            ...adminSweet,
            title: 'Memproses data',
            text: message,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => window.Swal.showLoading(),
        });
    };

    if (isAdminPage && hasSweetAlert) {
        document.querySelectorAll('a[href*="/delete/"], a[href*="reset-password"]').forEach((link) => {
            if (link.dataset.confirm) {
                return;
            }

            const isDelete = link.href.includes('/delete/');
            link.dataset.confirm = isDelete
                ? 'Data yang dihapus tidak bisa dikembalikan dari halaman ini.'
                : 'Lanjutkan ke proses reset password akun ini?';
        });
    }

    document.querySelectorAll('[data-confirm]').forEach((element) => {
        element.addEventListener('click', (event) => {
            if (! isAdminPage || ! hasSweetAlert) {
                if (!confirm(element.dataset.confirm)) {
                    event.preventDefault();
                }
                return;
            }

            event.preventDefault();

            window.Swal.fire({
                ...adminSweet,
                icon: 'question',
                title: 'Konfirmasi Aksi',
                text: element.dataset.confirm || 'Lanjutkan aksi ini?',
                showCancelButton: true,
                confirmButtonText: 'Ya, lanjutkan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                showClass: {
                    popup: 'animate__animated animate__fadeInDown',
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp',
                },
            }).then((result) => {
                if (! result.isConfirmed) {
                    return;
                }

                showAdminLoading('Perubahan sedang disimpan.');

                if (element.tagName === 'A') {
                    window.location.href = element.href;
                    return;
                }

                element.closest('form')?.submit();
            });
        });
    });

    const sidebarToggle = document.querySelector('[data-toggle-sidebar]');
    const sidebar = document.getElementById('sidebar');

    if (sidebar && window.matchMedia('(max-width: 900px)').matches) {
        sidebar.classList.add('close');
    }

    const links = Array.from(sidebar?.querySelectorAll('a[href]') || [])
        .filter((link) => link.getAttribute('href') !== '/logout');

    const activeLink = links
        .filter((link) => {
            const href = link.getAttribute('href');

            return window.location.pathname === href
                || (href !== '/dashboard/admin' && window.location.pathname.startsWith(href + '/'));
        })
        .sort((a, b) => b.getAttribute('href').length - a.getAttribute('href').length)[0]
        || links.find((link) => link.getAttribute('href') === window.location.pathname);

    activeLink?.classList.add('active');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar?.classList.toggle('close');
            document.getElementById('content')?.classList.toggle('full');
        });
    }

    if (isAdminPage && hasSweetAlert) {
        const alerts = Array.from(document.querySelectorAll('.app-alert'));

        if (alerts.length) {
            const firstAlert = alerts[0];
            const isSuccess = firstAlert.classList.contains('success');
            const message = firstAlert.textContent.trim();
            const isHolidayWarning = message.toLowerCase().includes('libur');

            document.querySelector('.app-alerts')?.remove();

            window.Swal.fire({
                ...adminSweet,
                icon: isSuccess ? 'success' : 'error',
                title: isSuccess ? 'Berhasil' : (isHolidayWarning ? 'Hari Libur' : 'Perlu Dicek'),
                text: message,
                timer: isSuccess ? 2400 : undefined,
                timerProgressBar: isSuccess,
                confirmButtonText: 'Mengerti',
                showConfirmButton: ! isSuccess,
            });
        }
    }

    document.querySelectorAll('[data-auto-dismiss]').forEach((alert) => {
        window.setTimeout(() => {
            alert.classList.add('is-hiding');
            window.setTimeout(() => alert.remove(), 240);
        }, 4800);
    });

    if (isAdminPage && hasSweetAlert) {
        document.querySelectorAll('form[method="POST"], form[method="post"]').forEach((form) => {
            form.addEventListener('submit', () => {
                showAdminLoading('Data sedang diproses dan disimpan.');
            });
        });
    }

    window.printReport = (title = document.title) => {
        document.body.dataset.printTitle = title;
        window.print();
    };

    window.exportTableToExcel = (filename = 'rekap-data', title = document.querySelector('main')?.dataset.printTitle || document.title) => {
        const table = document.querySelector('main table');

        if (! table) {
            alert('Tabel rekap tidak ditemukan.');
            return;
        }

        const clonedTable = table.cloneNode(true);
        clonedTable.querySelectorAll('a, button').forEach((element) => element.remove());
        clonedTable.querySelectorAll('th').forEach((cell) => {
            cell.setAttribute('style', 'background:#273c75;color:#ffffff;font-weight:bold;border:1px solid #8ea0c9;padding:8px;text-align:center;');
        });
        clonedTable.querySelectorAll('td').forEach((cell) => {
            cell.setAttribute('style', 'border:1px solid #cbd5e1;padding:7px;vertical-align:top;');
        });
        clonedTable.setAttribute('style', 'border-collapse:collapse;width:100%;font-family:Arial,sans-serif;font-size:12px;');

        const printedAt = new Date().toLocaleString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });

        const html = `
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; color:#111827; }
                    .report-title { font-size:20px; font-weight:700; text-align:center; margin-bottom:4px; }
                    .report-meta { font-size:12px; color:#475569; text-align:center; margin-bottom:14px; }
                    .report-note { font-size:11px; color:#64748b; margin-top:12px; }
                </style>
            </head>
            <body>
                <div class="report-title">${title}</div>
                <div class="report-meta">Dicetak: ${printedAt}</div>
                ${clonedTable.outerHTML}
                <div class="report-note">Diekspor dari sistem Absensi QR.</div>
            </body>
            </html>
        `;
        const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${filename}.xls`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    };
});
