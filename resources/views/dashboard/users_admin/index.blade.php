{{-- File ini menampilkan daftar akun admin sebagai pusat pengelolaan pengguna dengan hak akses administrator. --}}
<!DOCTYPE html>
<html lang="id">

<head>
    @include('layouts.favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Users</title>
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/dashboard-users_admin-index.css') }}">
</head>

<body>
    @include('layouts.sidebar_admin')

    <main id="content" class="content">
        @include('layouts.alerts')

        <div class="rekap-head">
            <div>
                <h1>Kelola Users</h1>
                <p>CRUD user khusus superadmin. Admin utama saat ini: Devi.</p>
            </div>
            <div>
                <a class="btn back" href="/dashboard/admin">Kembali</a>
                <a class="btn" href="/dashboard/admin/users/create">Tambah User</a>
            </div>
        </div>

        <form method="GET" action="/dashboard/admin/users" class="rekap-filter user-filter">
            <label>
                Cari User
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                    placeholder="Nama, username, role, kelas, NIS, NUPTK">
            </label>
            <label>
                Role
                <select name="role">
                    <option value="">Semua Role</option>
                    <option value="admin" @selected(($filters['role'] ?? '') === 'admin')>Admin</option>
                    <option value="guru" @selected(($filters['role'] ?? '') === 'guru')>Guru</option>
                    <option value="piket" @selected(($filters['role'] ?? '') === 'piket')>Guru Piket</option>
                    <option value="siswa" @selected(($filters['role'] ?? '') === 'siswa')>Siswa</option>
                </select>
            </label>
            <label>
                Status
                <select name="status">
                    <option value="">Semua Status</option>
                    <option value="aktif" @selected(($filters['status'] ?? '') === 'aktif')>Aktif</option>
                    <option value="nonaktif" @selected(($filters['status'] ?? '') === 'nonaktif')>Nonaktif</option>
                </select>
            </label>
            <label>
                Kelas
                <select name="kelas_id">
                    <option value="">Semua Kelas</option>
                    @foreach ($kelas as $k)
                        <option value="{{ $k->id }}" @selected((string) ($filters['kelas_id'] ?? '') === (string) $k->id)>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Level Admin
                <select name="admin_level">
                    <option value="">Semua Level</option>
                    <option value="superadmin" @selected(($filters['admin_level'] ?? '') === 'superadmin')>Superadmin</option>
                </select>
            </label>
            <div class="filter-actions">
                <button type="submit" class="btn">Terapkan</button>
                <a class="btn back" href="/dashboard/admin/users">Reset</a>
            </div>
        </form>

        <div class="cards user-summary">
            <div class="card">
                <h3>Hasil Filter</h3>
                <p>{{ $ringkasan['total'] ?? $users->count() }}</p>
            </div>
            <div class="card">
                <h3>Akun Aktif</h3>
                <p>{{ $ringkasan['aktif'] ?? $users->where('aktif', 1)->count() }}</p>
            </div>
            <div class="card">
                <h3>Akun Nonaktif</h3>
                <p>{{ $ringkasan['nonaktif'] ?? $users->where('aktif', 0)->count() }}</p>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Level</th>
                    <th>Kelas</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
                @forelse($users as $u)
                    <tr>
                        <td>{{ $u->nama }}</td>
                        <td>{{ $u->username }}</td>
                        <td><span class="status-pill">{{ $u->role }}</span></td>
                        <td>{{ $u->admin_level ?? '-' }}</td>
                        <td>{{ $u->kelasRelasi->nama_kelas ?? '-' }}</td>
                        <td><span
                                class="status-pill {{ $u->aktif ? 'success' : 'danger' }}">{{ $u->aktif ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                        <td class="user-actions">
                            <a class="btn" href="/dashboard/admin/users/edit/{{ $u->id }}">Edit</a>
                            <a class="btn back" href="/dashboard/admin/users/{{ $u->id }}/reset-password">Reset
                                Password</a>
                            @if (!($u->role === 'admin' && $u->admin_level === 'superadmin'))
                                <a class="btn btn-danger confirm-delete"
                                    href="/dashboard/admin/users/delete/{{ $u->id }}">Hapus</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-row">Data user tidak ditemukan sesuai filter.</td>
                    </tr>
                @endforelse
            </table>
        </div>
    </main>
</body>

</html>
