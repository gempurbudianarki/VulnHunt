@extends('errors.layout')

@section('title', '404 - Halaman Tidak Ditemukan')
@section('code', '404')
@section('icon-bg', 'bg-indigo-950/80 text-indigo-400 border border-indigo-800')
@section('badge-class', 'bg-indigo-950/60 text-indigo-300 border-indigo-800/80')
@section('icon')
    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
@endsection
@section('heading', 'Halaman Tidak Ditemukan')
@section('message', 'Alamat tautan yang Anda cari tidak tersedia, telah dipindahkan, atau Anda mengetik URL yang salah.')
