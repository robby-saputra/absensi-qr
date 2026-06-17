<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Arsip Data</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-arsip.css') }}?v=20260602-arsip-pagination">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content arsip-page">
        @include('layouts.alerts')

        <section class="arsip-hero">
            <div>
                <span class="arsip-kicker">Pusat Arsip</span>
                <h1>Arsip Data Terhapus</h1>
                <p>Kelola data yang masuk arsip, pulihkan jika salah hapus, atau hapus permanen saat sudah benar-benar
                    tidak dibutuhkan.</p>
            </div>
            <div class="arsip-hero-actions">
                <a class="btn back" href="/dashboard/admin">Kembali</a>
                <a class="btn" target="_blank" href="/dashboard/admin/pdf/arsip?table={{ $table }}">PDF
                    Resmi</a>
            </div>
        </section>

        <section class="arsip-stats">
            <div>
                <span>Tabel Aktif</span>
                <strong>{{ $tables[$table] ?? $table }}</strong>
            </div>
            <div>
                <span>Data Ditampilkan</span>
                <strong>{{ $data->count() }}</strong>
            </div>
            <div>
                <span>Total Arsip</span>
                <strong>{{ $data->total() }}</strong>
            </div>
        </section>

        <form method="GET" class="arsip-filter">
            <label>
                <span>Tabel</span>
                <select name="table">
                    @foreach ($tables as $key => $label)
                        <option value="{{ $key }}" {{ $table === $key ? 'selected' : '' }}>{{ $label }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Mulai</span>
                <input type="date" name="tanggal_mulai" value="{{ $filters['tanggalMulai'] ?? '' }}">
            </label>
            <label>
                <span>Selesai</span>
                <input type="date" name="tanggal_selesai" value="{{ $filters['tanggalSelesai'] ?? '' }}">
            </label>
            <label>
                <span>Penghapus</span>
                <input type="text" name="deleted_by" value="{{ $filters['deletedBy'] ?? '' }}"
                    placeholder="Cari nama penghapus">
            </label>
            <button class="btn" type="submit">Tampilkan</button>
            <a class="btn back" href="/dashboard/admin/arsip?table={{ $table }}">Reset</a>
        </form>

        <form id="arsip-bulk-form" method="POST" class="arsip-table-card">
            @csrf
            <input type="hidden" name="table" value="{{ $table }}">

            <div class="arsip-toolbar">
                <div>
                    <strong>Data Arsip</strong>
                    <span id="arsip-selected-count">0 data dipilih</span>
                </div>
                <div class="arsip-toolbar-actions">
                    <button class="btn" type="submit" data-action="/dashboard/admin/arsip/bulk-restore"
                        disabled>Restore Terpilih</button>
                    <button class="btn btn-danger" type="submit" data-action="/dashboard/admin/arsip/bulk-force-delete"
                        data-danger="1" disabled>Hapus Permanen Terpilih</button>
                </div>
            </div>

            <div class="arsip-list-head">
                <label>
                    <input type="checkbox" id="arsip-check-all" aria-label="Pilih semua arsip">
                    <span>Pilih semua</span>
                </label>
                <span>Ringkasan Data</span>
                <span>Diarsipkan</span>
                <span>Aksi</span>
            </div>

            <div class="arsip-list">
                @forelse($data as $row)
                    @php
                        $hiddenFields = [
                            'password',
                            'remember_token',
                            'deleted_at',
                            'created_at',
                            'updated_at',
                            'active_unique_key',
                        ];
                        $rowArray = (array) $row;
                        $titleCandidates = [
                            'nama',
                            'nama_siswa',
                            'judul',
                            'nama_kelas',
                            'nama_mapel',
                            'username',
                            'nis',
                            'kode_jurusan',
                            'nama_jurusan',
                        ];
                        $titleKey = collect($titleCandidates)->first(
                            fn($key) => array_key_exists($key, $rowArray) && filled($rowArray[$key]),
                        );
                        $titleValue = $titleKey ? $rowArray[$titleKey] : '#' . $row->id;
                        $summaryFields = collect([
                            'tanggal' => 'Tanggal',
                            'hari' => 'Hari',
                            'jam_masuk' => 'Masuk',
                            'status_masuk' => 'Status Masuk',
                            'jam_pulang' => 'Pulang',
                            'status_pulang' => 'Status Pulang',
                            'id_siswa' => 'ID Siswa',
                            'tahun_ajaran_id' => 'Tahun Ajaran',
                            'kelas_id' => 'ID Kelas',
                            'role' => 'Role',
                            'aktif' => 'Aktif',
                        ])->filter(fn($label, $key) => array_key_exists($key, $rowArray) && filled($rowArray[$key]));
                        $visibleSummary = $summaryFields->take(5);
                        $totalVisibleFields = collect($rowArray)
                            ->keys()
                            ->reject(fn($key) => in_array($key, $hiddenFields, true))
                            ->count();
                        $hiddenCount = max(0, $totalVisibleFields - (1 + $visibleSummary->count()));
                    @endphp
                    <article class="arsip-row-card">
                        <div class="arsip-row-check">
                            <input type="checkbox" class="arsip-check-row" name="ids[]" value="{{ $row->id }}"
                                aria-label="Pilih arsip #{{ $row->id }}">
                            <span class="arsip-id">#{{ $row->id }}</span>
                        </div>

                        <div class="arsip-row-main">
                            <div class="arsip-record">
                                <div class="arsip-record-head">
                                    <strong>{{ $titleValue }}</strong>
                                    @if ($titleKey)
                                        <span>{{ str_replace('_', ' ', $titleKey) }}</span>
                                    @endif
                                </div>
                                <div class="arsip-chips">
                                    @foreach ($visibleSummary as $key => $label)
                                        <span><b>{{ $label }}</b>{{ is_scalar($rowArray[$key]) ? $rowArray[$key] : json_encode($rowArray[$key]) }}</span>
                                    @endforeach
                                </div>
                                @if ($hiddenCount > 0)
                                    <a class="arsip-more-link"
                                        href="/dashboard/admin/arsip/preview?table={{ $table }}&id={{ $row->id }}">
                                        Lihat {{ $hiddenCount }} field lainnya
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="arsip-row-date">
                            <span class="arsip-date">{{ $row->deleted_at }}</span>
                        </div>

                        <div class="arsip-row-actions">
                            <div class="arsip-actions">
                                <a class="btn back"
                                    href="/dashboard/admin/arsip/preview?table={{ $table }}&id={{ $row->id }}">View
                                    Rincian</a>
                                <button class="btn" type="submit" formaction="/dashboard/admin/arsip/restore"
                                    name="id" value="{{ $row->id }}">Restore</button>
                                <button class="btn btn-danger" type="submit"
                                    formaction="/dashboard/admin/arsip/force-delete" name="id"
                                    value="{{ $row->id }}"
                                    data-confirm-permanent="Hapus permanen arsip #{{ $row->id }}?">Hapus</button>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="arsip-empty">
                        <strong>Belum ada arsip</strong>
                        <span>Data yang dihapus sementara dari tabel ini akan muncul di sini.</span>
                    </div>
                @endforelse
            </div>
        </form>

        @if ($data->hasPages())
            <nav class="arsip-pagination" aria-label="Navigasi halaman arsip">
                <div class="arsip-pagination-info">
                    Menampilkan {{ $data->firstItem() }} - {{ $data->lastItem() }} dari {{ $data->total() }} data
                </div>
                <div class="arsip-pagination-links">
                    @if ($data->onFirstPage())
                        <span class="page-btn disabled">Sebelumnya</span>
                    @else
                        <a class="page-btn" href="{{ $data->previousPageUrl() }}">Sebelumnya</a>
                    @endif

                    @php
                        $currentPage = $data->currentPage();
                        $lastPage = $data->lastPage();
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($lastPage, $currentPage + 2);
                    @endphp

                    @if ($startPage > 1)
                        <a class="page-btn" href="{{ $data->url(1) }}">1</a>
                        @if ($startPage > 2)
                            <span class="page-ellipsis">...</span>
                        @endif
                    @endif

                    @for ($page = $startPage; $page <= $endPage; $page++)
                        @if ($page === $currentPage)
                            <span class="page-btn active">{{ $page }}</span>
                        @else
                            <a class="page-btn" href="{{ $data->url($page) }}">{{ $page }}</a>
                        @endif
                    @endfor

                    @if ($endPage < $lastPage)
                        @if ($endPage < $lastPage - 1)
                            <span class="page-ellipsis">...</span>
                        @endif
                        <a class="page-btn" href="{{ $data->url($lastPage) }}">{{ $lastPage }}</a>
                    @endif

                    @if ($data->hasMorePages())
                        <a class="page-btn" href="{{ $data->nextPageUrl() }}">Berikutnya</a>
                    @else
                        <span class="page-btn disabled">Berikutnya</span>
                    @endif
                </div>
            </nav>
        @endif
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('arsip-bulk-form');
            const checkAll = document.getElementById('arsip-check-all');
            const checks = Array.from(document.querySelectorAll('.arsip-check-row'));
            const count = document.getElementById('arsip-selected-count');
            const bulkButtons = Array.from(document.querySelectorAll('.arsip-toolbar-actions button'));

            const refresh = () => {
                const total = checks.filter((check) => check.checked).length;
                count.textContent = `${total} data dipilih`;
                bulkButtons.forEach((button) => button.disabled = total === 0);
                if (checkAll) {
                    checkAll.checked = total > 0 && total === checks.length;
                    checkAll.indeterminate = total > 0 && total < checks.length;
                }
            };

            checkAll?.addEventListener('change', () => {
                checks.forEach((check) => check.checked = checkAll.checked);
                refresh();
            });

            checks.forEach((check) => check.addEventListener('change', refresh));

            form?.addEventListener('submit', (event) => {
                const submitter = event.submitter;
                if (!submitter) return;

                const bulkAction = submitter.dataset.action;
                const permanentText = submitter.dataset.confirmPermanent;
                if (bulkAction) {
                    event.preventDefault();
                    const selected = checks.filter((check) => check.checked).length;
                    if (!selected) return;
                    const message = submitter.dataset.danger ?
                        `${selected} data arsip akan dihapus permanen dan tidak bisa dipulihkan.` :
                        `${selected} data arsip akan dipulihkan.`;
                    if (!confirm(message)) return;
                    form.action = bulkAction;
                    form.submit();
                    return;
                }

                if (permanentText && !confirm(permanentText)) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                }
            }, true);
        });
    </script>
</body>

</html>
