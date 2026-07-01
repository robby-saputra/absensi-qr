{{-- File ini menampilkan halaman arsip untuk melihat data lama yang disimpan sebagai riwayat sistem. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arsip Data</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-arsip.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        <section class="arsip-page">
            <div class="arsip-hero">
                <div>
                    <span class="arsip-kicker">Admin Sekolah</span>
                    <h1>Arsip Data</h1>
                    <p>Data yang dihapus sementara akan tampil di sini dan bisa dipulihkan kembali.</p>
                </div>
                <div class="arsip-hero-actions">
                    <form method="POST" action="/dashboard/admin/arsip-empty-all">
                        @csrf
                        <button type="submit" class="btn btn-danger" data-confirm="Kosongkan semua Arsip Data dari seluruh kategori? Data akan dihapus permanen.">Kosongkan Semua</button>
                    </form>
                    <a href="/dashboard/admin" class="btn">Kembali</a>
                </div>
            </div>

            <div class="arsip-stats">
                <div>
                    <span>Kategori Aktif</span>
                    <strong>{{ $resources[$resource]['label'] ?? 'Arsip' }}</strong>
                </div>
                <div>
                    <span>Total Kategori Ini</span>
                    <strong>{{ $stats[$resource] ?? 0 }}</strong>
                </div>
                <div>
                    <span>Total Semua Arsip</span>
                    <strong>{{ $stats->sum() }}</strong>
                </div>
            </div>

            <form method="GET" class="arsip-filter">
                <label>
                    <span>Kategori</span>
                    <select name="resource">
                        @foreach($resources as $key => $item)
                            <option value="{{ $key }}" {{ $resource === $key ? 'selected' : '' }}>
                                {{ $item['label'] }} ({{ $stats[$key] ?? 0 }})
                            </option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Cari</span>
                    <input type="search" name="q" value="{{ $q }}" placeholder="Nama, hari, tanggal, status">
                </label>
                <button class="btn" type="submit">Cari</button>
                <a href="/dashboard/admin/arsip?resource={{ $resource }}" class="btn">Reset</a>
            </form>

            <div class="arsip-table-card">
                <div class="arsip-toolbar">
                    <div>
                        <strong>{{ $config['label'] }}</strong>
                        <span>{{ method_exists($items, 'total') ? $items->total() : $items->count() }} data arsip ditemukan</span>
                    </div>
                    <div class="arsip-toolbar-actions">
                        <form id="arsip-bulk-form" method="POST" action="/dashboard/admin/arsip/{{ $resource }}/bulk-delete">
                            @csrf
                            <button type="submit" class="btn btn-danger" id="arsip-bulk-delete" disabled data-confirm="Hapus permanen data arsip yang dipilih?">Hapus Terpilih</button>
                        </form>
                        <form method="POST" action="/dashboard/admin/arsip/{{ $resource }}/empty">
                            @csrf
                            <button type="submit" class="btn btn-danger" {{ ($stats[$resource] ?? 0) < 1 ? 'disabled' : '' }} data-confirm="Kosongkan semua arsip kategori {{ $config['label'] }}? Data akan dihapus permanen.">Kosongkan Kategori</button>
                        </form>
                    </div>
                </div>

                <div class="arsip-list-head">
                    <label>
                        <input type="checkbox" id="arsip-check-all">
                        <span>ID</span>
                    </label>
                    <span>Data</span>
                    <span>Diarsipkan</span>
                    <span>Aksi</span>
                </div>

                <div class="arsip-list">
                    @forelse($items as $item)
                        @php
                            $titleColumn = $config['title'] ?? 'id';
                            $title = $item->{$titleColumn} ?? ('Data #'.$item->id);
                            if (($config['table'] ?? '') === 'guru_pikets') {
                                $title = $item->nama_guru ?? ('Guru #' . ($item->guru_id ?? $item->id));
                            }
                            $chips = collect((array) $item);
                            if (($config['table'] ?? '') === 'guru_pikets') {
                                $chips = collect([
                                    'hari' => ucfirst($item->hari ?? '-'),
                                    'jam' => substr($item->jam_mulai ?? '00:00', 0, 5) . ' - ' . substr($item->jam_selesai ?? '00:00', 0, 5),
                                    'pengganti' => $item->nama_pengganti ?? '-',
                                    'status' => $item->status ?? '-',
                                    'tahun ajaran id' => $item->tahun_ajaran_id ?? '-',
                                ]);
                            } else {
                                $chips = $chips
                                    ->except(['created_at', 'updated_at', 'deleted_at'])
                                    ->take(5);
                            }
                        @endphp
                        <article class="arsip-row-card">
                            <div class="arsip-row-check">
                                <input type="checkbox" class="arsip-row-checkbox" value="{{ $item->id }}" form="arsip-bulk-form">
                                <span class="arsip-id">#{{ $item->id }}</span>
                            </div>
                            <div class="arsip-record">
                                <div class="arsip-record-head">
                                    <strong>{{ $title ?: 'Data arsip' }}</strong>
                                    <span>{{ $config['label'] }}</span>
                                </div>
                                <div class="arsip-chips">
                                    @foreach($chips as $key => $value)
                                        <span><b>{{ str_replace('_', ' ', $key) }}</b> {{ $value ?? '-' }}</span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="arsip-row-date">
                                <span class="arsip-date">{{ $item->deleted_at }}</span>
                            </div>
                            <div class="arsip-actions">
                                <form method="POST" action="/dashboard/admin/arsip/{{ $resource }}/{{ $item->id }}/restore">
                                    @csrf
                                    <button type="submit" class="btn">Pulihkan</button>
                                </form>
                                <form method="POST" action="/dashboard/admin/arsip/{{ $resource }}/{{ $item->id }}/delete">
                                    @csrf
                                    <button type="submit" class="btn btn-danger" data-confirm="Hapus permanen data arsip ini?">Hapus Permanen</button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <div class="arsip-empty">
                            <strong>Belum ada data arsip</strong>
                            <span>Data yang dihapus sementara akan muncul di daftar ini.</span>
                        </div>
                    @endforelse
                </div>
            </div>

            @if(method_exists($items, 'links') && $items->hasPages())
                <div class="arsip-pagination">
                    <div class="arsip-pagination-info">
                        Menampilkan {{ $items->firstItem() }}-{{ $items->lastItem() }} dari {{ $items->total() }}
                    </div>
                    <div class="arsip-pagination-links">
                        {{ $items->links() }}
                    </div>
                </div>
            @endif
        </section>
    </main>
    <script>
        const bulkForm = document.getElementById('arsip-bulk-form');
        const bulkButton = document.getElementById('arsip-bulk-delete');
        const checkAll = document.getElementById('arsip-check-all');
        const rowChecks = [...document.querySelectorAll('.arsip-row-checkbox')];

        function syncBulkState() {
            const checked = rowChecks.filter((item) => item.checked);
            bulkButton.disabled = checked.length === 0;
            if (checkAll) {
                checkAll.checked = checked.length > 0 && checked.length === rowChecks.length;
                checkAll.indeterminate = checked.length > 0 && checked.length < rowChecks.length;
            }
        }

        checkAll?.addEventListener('change', () => {
            rowChecks.forEach((item) => item.checked = checkAll.checked);
            syncBulkState();
        });

        rowChecks.forEach((item) => item.addEventListener('change', syncBulkState));

        bulkForm?.addEventListener('submit', () => {
            bulkForm.querySelectorAll('input[name="ids[]"]').forEach((item) => item.remove());
            rowChecks
                .filter((item) => item.checked)
                .forEach((item) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = item.value;
                    bulkForm.appendChild(input);
                });
        });

        syncBulkState();
    </script>
</body>

</html>
