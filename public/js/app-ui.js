document.addEventListener('DOMContentLoaded', () => {
    const addIcon = (element, icon) => {
        if (element.querySelector('i')) {
            return;
        }

        const item = document.createElement('i');
        item.className = `fa-solid ${icon}`;
        item.setAttribute('aria-hidden', 'true');
        element.prepend(item);
    };

    document.querySelectorAll('.btn, .btn-edit, .btn-hapus, .btn-kembali, .btn-tambah, .btn-reset, .back, .danger, .edit, .hapus, button').forEach((element) => {
        const href = element.getAttribute('href') || '';
        const text = element.textContent.trim().toLowerCase();

        if (element.classList.contains('toggle-btn') || element.classList.contains('link')) {
            return;
        }

        if (href.includes('/delete') || element.classList.contains('hapus') || element.classList.contains('danger') || text.includes('hapus')) {
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

    document.querySelectorAll('[data-confirm]').forEach((element) => {
        element.addEventListener('click', (event) => {
            if (!confirm(element.dataset.confirm)) {
                event.preventDefault();
            }
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
});
