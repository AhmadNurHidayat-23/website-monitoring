@extends('layouts.app')
@section('title', 'Terjadi gangguan')
@section('content')
    <section class="subpage-heading">
        <p class="eyebrow">GANGGUAN APLIKASI</p>
        <h1>Terjadi gangguan.</h1>
        <p>Permintaan belum dapat diproses. Silakan coba kembali sebentar lagi.</p>
        <p>
            <a class="text-link" href="{{ route('websites.index') }}">← Kembali ke monitoring</a>
        </p>
    </section>
@endsection
