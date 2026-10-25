@extends('errors.layout')

@section('title', '419 - Sesi Kedaluwarsa')
@section('code', '419')
@section('icon-bg', 'bg-amber-950/80 text-amber-400 border border-amber-800')
@section('badge-class', 'bg-amber-950/60 text-amber-300 border-amber-800/80')
@section('icon')
    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
@endsection
@section('heading', 'Sesi Telah Kedaluwarsa')
@section('message', 'Token keamanan sesi halaman ini telah berakhir atau tidak aktif terlalu lama. Silakan muat ulang halaman atau masuk kembali.')
