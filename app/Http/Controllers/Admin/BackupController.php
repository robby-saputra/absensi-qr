<?php

namespace App\Http\Controllers\Admin;

use App\Support\AuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BackupController extends Controller
{
    public function index()
    {
        wajibSuperadmin();

        $user = session('user');
        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $backups = collect(array_merge(glob($backupDir.'/*.json') ?: [], glob($backupDir.'/*.sql') ?: []))
            ->map(function ($path) {
                return (object) [
                    'name' => basename($path),
                    'type' => strtoupper(pathinfo($path, PATHINFO_EXTENSION)),
                    'size' => filesize($path),
                    'created_at' => date('Y-m-d H:i:s', filemtime($path)),
                ];
            })
            ->sortByDesc('created_at')
            ->values();

        return view('dashboard.backup.index', compact('user', 'backups'));
    }

    public function create(Request $request)
    {
        wajibSuperadmin();

        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $tables = collect(DB::select('SHOW TABLES'))->map(function ($row) {
            return array_values((array) $row)[0];
        })->values();

        $dump = [
            'app' => 'absensi-qr',
            'created_at' => now()->toDateTimeString(),
            'created_by' => session('user')->nama ?? 'superadmin',
            'tables' => [],
        ];

        foreach ($tables as $table) {
            $dump['tables'][$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->values()->all();
        }

        $fileName = 'backup-'.now()->format('Ymd-His').'.json';
        file_put_contents($backupDir.DIRECTORY_SEPARATOR.$fileName, json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        AuditLogger::record('backup_create', 'database', null, 'Backup database dibuat', null, ['file' => $fileName, 'tables' => $tables->count()], $request);

        return back()->with('success', 'Backup database berhasil dibuat: '.$fileName);
    }

    public function createSql(Request $request)
    {
        wajibSuperadmin();

        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $fileName = 'backup-'.now()->format('Ymd-His').'.sql';
        $path = $backupDir.DIRECTORY_SEPARATOR.$fileName;
        file_put_contents($path, buatSqlDumpLaravel());
        AuditLogger::record('backup_sql_create', 'database', null, 'Backup SQL database dibuat', null, ['file' => $fileName], $request);

        return back()->with('success', 'Backup SQL berhasil dibuat: '.$fileName);
    }

    public function download($file)
    {
        wajibSuperadmin();

        $path = storage_path('app/backups/'.basename($file));
        abort_if(! is_file($path), 404);

        return response()->download($path);
    }

    public function restore(Request $request, $file)
    {
        wajibSuperadmin();

        $path = storage_path('app/backups/'.basename($file));
        abort_if(! is_file($path), 404);

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'sql') {
            try {
                DB::unprepared(file_get_contents($path));
            } catch (Throwable $e) {
                return back()->with('error', 'Restore SQL gagal: '.$e->getMessage());
            }

            AuditLogger::record('backup_sql_restore', 'database', null, 'Database direstore dari backup SQL', null, ['file' => basename($file)], $request);

            return back()->with('success', 'Restore SQL berhasil dari file '.basename($file).'.');
        }

        $dump = json_decode(file_get_contents($path), true);
        if (! is_array($dump) || empty($dump['tables'])) {
            return back()->with('error', 'File backup tidak valid.');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($dump['tables'] as $table => $rows) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->truncate();
            foreach (array_chunk($rows, 500) as $chunk) {
                if ($chunk) {
                    DB::table($table)->insert($chunk);
                }
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        AuditLogger::record('backup_restore', 'database', null, 'Database direstore dari backup', null, ['file' => basename($file)], $request);

        return back()->with('success', 'Restore database berhasil dari file '.basename($file).'.');
    }
}

