<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <title>Peran & Akses</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-role-akses.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/admin-polish.css') }}?v=20260602-polish">
</head>

<body>
    @include('layouts.sidebar_admin')

    @php
        $formatPassword = function (?string $password) {
            $password = (string) $password;
            $isLaravelHash =
                str_starts_with($password, '$2y$') ||
                str_starts_with($password, '$2a$') ||
                str_starts_with($password, '$argon2');
            $isMd5 = strlen($password) === 32 && ctype_xdigit($password);

            if ($password === '') {
                return ['label' => 'Belum ada', 'plain' => false, 'hint' => 'Atur kata sandi dari menu Atur Ulang.'];
            }

            if ($isLaravelHash || $isMd5) {
                return [
                    'label' => 'Tidak dapat dilihat',
                    'plain' => false,
                    'hint' => 'Klik Atur Ulang untuk membuat kata sandi baru.',
                ];
            }

            return ['label' => $password, 'plain' => true, 'hint' => 'Kata sandi tersimpan sebagai teks biasa.'];
        };

        $roleCounts = $akunAkses->groupBy('role')->map->count();
    @endphp

    <main id="content" class="content role-page admin-polish">
        <section class="role-hero">
            <div>
                <span class="role-kicker">Kontrol akses</span>
                <h1>Manajemen Peran & Akses</h1>
                <p>Pantau relasi peran, status akun, serta kredensial masuk yang boleh dilihat superadmin.</p>
            </div>
            <a class="btn back" href="/dashboard/admin">Kembali</a>
        </section>

        <section class="role-stats">
            <div>
                <span>Siswa Aktif</span>
                <strong>{{ $siswaAktif }}</strong>
            </div>
            <div>
                <span>Siswa Nonaktif</span>
                <strong>{{ $siswaNonaktif }}</strong>
            </div>
            <div>
                <span>Guru Wali</span>
                <strong>{{ $guruWali->count() }}</strong>
            </div>
            <div>
                <span>Guru Piket</span>
                <strong>{{ $guruPiket->count() }}</strong>
            </div>
            <div>
                <span>Total Akun</span>
                <strong>{{ $akunAkses->count() }}</strong>
            </div>
        </section>

        <section class="role-grid">
            <article class="role-panel">
                <div class="panel-head">
                    <div>
                        <h2>Guru yang juga Wali Kelas</h2>
                        <p>Akun guru dengan tanggung jawab wali kelas.</p>
                    </div>
                    <span>{{ $guruWali->count() }} akun</span>
                </div>
                <div class="mini-list">
                    @forelse($guruWali as $g)
                        @php($passwordInfo = $formatPassword($g->password))
                        <div class="mini-row">
                            <div>
                                <strong>{{ $g->nama }}</strong>
                                <span>{{ $g->username }}</span>
                            </div>
                            <span
                                class="password-chip {{ $passwordInfo['plain'] ? 'plain' : '' }}">{{ $passwordInfo['label'] }}</span>
                        </div>
                    @empty
                        <div class="empty-row">Tidak ada guru wali kelas.</div>
                    @endforelse
                </div>
            </article>

            <article class="role-panel">
                <div class="panel-head">
                    <div>
                        <h2>Guru yang juga Guru Piket</h2>
                        <p>Akun guru yang punya jadwal piket aktif.</p>
                    </div>
                    <span>{{ $guruPiket->count() }} akun</span>
                </div>
                <div class="mini-list">
                    @forelse($guruPiket as $g)
                        @php($passwordInfo = $formatPassword($g->password))
                        <div class="mini-row">
                            <div>
                                <strong>{{ $g->nama }}</strong>
                                <span>{{ $g->username }}</span>
                            </div>
                            <span
                                class="password-chip {{ $passwordInfo['plain'] ? 'plain' : '' }}">{{ $passwordInfo['label'] }}</span>
                        </div>
                    @empty
                        <div class="empty-row">Tidak ada guru piket.</div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="role-panel">
            <div class="panel-head">
                <div>
                    <h2>Kredensial Akun</h2>
                    <p>Nama pengguna ditampilkan langsung. Kata sandi yang sudah diamankan tidak dapat dilihat, jadi
                        gunakan Atur Ulang untuk membuat kata sandi baru.</p>
                </div>
                <div class="role-tabs">
                    @foreach ($roleCounts as $role => $total)
                        <span>{{ ucfirst($role) }}: {{ $total }}</span>
                    @endforeach
                </div>
            </div>

            <div class="table-wrap">
                <table class="role-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Peran</th>
                            <th>Nama Pengguna</th>
                            <th>Kata Sandi</th>
                            <th>Kelas/Level</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($akunAkses as $akun)
                            @php($passwordInfo = $formatPassword($akun->password))
                            <tr>
                                <td>
                                    <strong>{{ $akun->nama }}</strong>
                                    <small>{{ $akun->nis ?: ($akun->nuptk ?: 'Kode ' . $akun->id) }}</small>
                                </td>
                                <td><span class="role-badge">{{ ucfirst($akun->role) }}</span></td>
                                <td><code>{{ $akun->username }}</code></td>
                                <td>
                                    @if ($passwordInfo['plain'])
                                        <div class="secret-field">
                                            <input type="password" value="{{ $passwordInfo['label'] }}" readonly>
                                            <button type="button" data-toggle-secret aria-label="Lihat kata sandi">
                                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    @else
                                        <span class="password-chip">{{ $passwordInfo['label'] }}</span>
                                        <small>{{ $passwordInfo['hint'] }}</small>
                                    @endif
                                </td>
                                <td>{{ $akun->kelasRelasi->nama_kelas ?? ($akun->admin_level ?? '-') }}</td>
                                <td><span
                                        class="status-dot {{ $akun->aktif ? 'active' : 'inactive' }}">{{ $akun->aktif ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                                <td>
                                    <a class="btn" href="/dashboard/admin/users/edit/{{ $akun->id }}">Ubah</a>
                                    <a class="btn back"
                                        href="/dashboard/admin/users/{{ $akun->id }}/reset-password">Atur Ulang</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-row">Data akun belum tersedia.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="role-panel">
            <div class="panel-head">
                <div>
                    <h2>Akun Tanpa Riwayat Masuk</h2>
                    <p>Akun yang belum pernah masuk ke sistem.</p>
                </div>
                <span>{{ $akunTanpaLogin->count() }} akun</span>
            </div>
            <div class="table-wrap">
                <table class="role-table compact">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Peran</th>
                            <th>Nama Pengguna</th>
                            <th>Kata Sandi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($akunTanpaLogin as $a)
                            @php($passwordInfo = $formatPassword($a->password))
                            <tr>
                                <td>{{ $a->nama }}</td>
                                <td><span class="role-badge">{{ ucfirst($a->role) }}</span></td>
                                <td><code>{{ $a->username }}</code></td>
                                <td><span
                                        class="password-chip {{ $passwordInfo['plain'] ? 'plain' : '' }}">{{ $passwordInfo['label'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-row">Semua akun sudah pernah login.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-toggle-secret]').forEach((button) => {
                button.addEventListener('click', () => {
                    const input = button.closest('.secret-field')?.querySelector('input');
                    if (!input) return;
                    const visible = input.type === 'text';
                    input.type = visible ? 'password' : 'text';
                    button.innerHTML =
                        `<i class="fa-solid ${visible ? 'fa-eye' : 'fa-eye-slash'}" aria-hidden="true"></i>`;
                });
            });
        });
    </script>
</body>

</html>
