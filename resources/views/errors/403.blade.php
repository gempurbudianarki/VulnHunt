@extends('errors.layout')

@section('title', '403 - Akses Ditolak')
@section('code', '403')
@section('icon-bg', 'bg-rose-950/80 text-rose-400 border border-rose-800')
@section('badge-class', 'bg-rose-950/60 text-rose-300 border-rose-800/80')
@section('icon')
    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
    </svg>
@endsection
@section('heading', 'Akses Dilarang')
@section('message', 'Anda tidak memiliki hak akses atau izin yang sah untuk membuka sumber daya ini. Pastikan akun dan verifikasi NDA Anda telah aktif.')
