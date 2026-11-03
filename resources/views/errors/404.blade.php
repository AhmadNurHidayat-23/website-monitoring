@extends('layouts.app')
@section('title', 'Halaman tidak ditemukan')
@section('content')
    <section class="subpage-heading">
        <p class="eyebrow">404 / TIDAK DITEMUKAN</p>
        <h1>Halaman tidak ditemukan.</h1>
        <p>Website mungkin sudah dihapus atau alamat halaman tidak sesuai.</p>
        <p>
            <a class="text-link" href="{{ route('websites.index') }}">← Kembali ke monitoring</a>
        </p>
    </section>
@endsection
