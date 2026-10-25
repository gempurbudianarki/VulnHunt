@extends('errors.layout')

@section('title', '429 - Terlalu Banyak Permintaan')
@section('code', '429')
@section('icon-bg', 'bg-orange-950/80 text-orange-400 border border-orange-800')
@section('badge-class', 'bg-orange-950/60 text-orange-300 border-orange-800/80')
@section('icon')
    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
    </svg>
@endsection
@section('heading', 'Batas Kecepatan Terlampaui')
@section('message', 'Anda telah melakukan terlalu banyak permintaan dalam waktu singkat (Rate Limit). Mohon tunggu beberapa saat sebelum mencoba kembali.')
