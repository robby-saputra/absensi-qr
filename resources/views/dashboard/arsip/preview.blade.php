<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Preview Arsip</title><link rel="stylesheet" href="{{ asset('css/pages/dashboard-rekap-admin.css') }}"></head><body>
@include('layouts.sidebar_admin')
<main id="content" class="content">
<div class="rekap-head"><div><h1>Preview Arsip</h1><p>{{ $table }} #{{ $row->id }}</p></div><a class="btn back" href="/dashboard/admin/arsip?table={{ $table }}">Kembali</a></div>
<table>
@foreach((array)$row as $key=>$value)
@continue(in_array($key,['password','remember_token']))
<tr><th>{{ $key }}</th><td>{{ is_scalar($value) ? $value : json_encode($value) }}</td></tr>
@endforeach
</table>
<h2 style="margin-top:22px">Audit Penghapusan</h2>
<table>
<tr><th>User</th><td>{{ $audit->user_name ?? '-' }}</td></tr>
<tr><th>Waktu</th><td>{{ $audit->created_at ?? '-' }}</td></tr>
<tr><th>IP</th><td>{{ $audit->ip_address ?? '-' }}</td></tr>
</table>
<form method="POST" action="/dashboard/admin/arsip/restore" style="margin-top:16px">@csrf<input type="hidden" name="table" value="{{ $table }}"><input type="hidden" name="id" value="{{ $row->id }}"><button class="btn" type="submit">Restore Data Ini</button></form>
</main></body></html>
