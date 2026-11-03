@extends('layouts.app')
@section('title', 'Terlalu banyak pemeriksaan')
@section('content')
    <section class="subpage-heading">
        <p class="eyebrow">BATAS PEMERIKSAAN</p>
        <h1>Tunggu sebentar.</h1>
        <p>Terlalu banyak pemeriksaan manual. Batasnya dua kali per website dan sepuluh kali per alamat IP setiap menit.</p>
        <p>
            <a class="text-link" href="{{ route('websites.index') }}">← Kembali ke monitoring</a>
        </p>
    </section>
@endsection
