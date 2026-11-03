@extends('layouts.app')
@section('title', 'Sesi berakhir')
@section('content')
<section class="subpage-heading">
    <p class="eyebrow">SESI BERAKHIR</p>
    <h1>Muat ulang halaman.</h1>
    <p>Sesi formulir sudah berakhir. Muat ulang halaman sebelum mengirim kembali.</p>
    <p><a class="text-link" href="{{ route('websites.index') }}">← Kembali ke monitoring</a></p>
</section>
@endsection
