<div id="{{ $modalId }}" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="modal-card">
        <h2 id="{{ $modalId }}-title">Batalkan Verifikasi</h2>
        <dl class="detail-grid">
            <dt>Nama guru</dt><dd>{{ $guru }}</dd>
            <dt>Jenis tugas</dt><dd>{{ $jenis }}</dd>
            <dt>Tanggal tugas</dt><dd>{{ $tanggalLabel }}</dd>
            <dt>Detail</dt><dd>{{ $extra }}</dd>
            <dt>Status lama</dt><dd>{{ $statusLama }}</dd>
            <dt>Sumber status</dt><dd>{{ ucfirst(str_replace('_', ' ', $statusSource ?? 'manual')) }}</dd>
            <dt>Waktu verifikasi lama</dt><dd>{{ $waktuLama }}</dd>
            <dt>Guru pengganti aktif</dt><dd>{{ $penggantiAktif }}</dd>
            <dt>Pengganti lanjutan</dt><dd>{{ $penggantiLanjutan }}</dd>
        </dl>

        <div class="warning-box">
            Status verifikasi guru akan dikembalikan menjadi Belum Terverifikasi. Penugasan guru pengganti untuk tanggal ini akan dinonaktifkan dan guru utama harus melakukan verifikasi ulang.
            @if($isAutomatic ?? false)
                <br><strong>Status Hadir Otomatis akan dibatalkan dan guru wajib melakukan verifikasi ulang. Status tidak akan otomatis kembali sampai guru melakukan konfirmasi.</strong>
            @endif
            @if($hasStudentAttendance)
                <br><strong>Data absensi siswa yang telah tercatat tidak akan dihapus.</strong>
            @endif
            @if($isAfterCutoff)
                <br><strong>Reset dilakukan setelah cutoff dan akan dicatat sebagai override admin.</strong>
            @endif
        </div>

        <form method="POST" action="{{ $action }}">
            @csrf
            <label>
                <strong>Alasan pembatalan</strong>
                <textarea name="alasan" required maxlength="1000" placeholder="Tuliskan alasan pembatalan verifikasi...">{{ old('alasan') }}</textarea>
            </label>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" data-close-modal>Batal</button>
                <button type="submit" class="btn btn-primary">Konfirmasi Pembatalan</button>
            </div>
        </form>
    </div>
</div>
