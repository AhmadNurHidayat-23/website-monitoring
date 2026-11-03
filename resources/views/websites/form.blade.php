@extends('layouts.app')
@section('title', $website->exists ? 'Edit Website' : 'Tambah Website')
@section('content')
    <div class="breadcrumb">
        <a href="{{ route('websites.index') }}">Monitoring</a>
        <span aria-hidden="true">/</span>
        <span>{{ $website->exists ? 'Edit website' : 'Tambah website' }}</span>
    </div>
    <section class="subpage-heading">
        <p class="eyebrow">{{ $website->exists ? 'PENGATURAN WEBSITE' : 'MULAI PEMANTAUAN' }}</p>
        <h1>{{ $website->exists ? 'Edit Website.' : 'Tambah Website.' }}</h1>
        <p>Berikan alamatnya. Kami catat ketersediaannya.</p>
    </section>
    <div class="form-grid">
        <form class="website-form"
            action="{{ $website->exists ? route('websites.update', $website) : route('websites.store') }}" method="post">
            @csrf
            @if ($website->exists)
                @method('put')
            @endif
            @if ($errors->any())
                <div class="notice" role="alert">Periksa kembali isian di bawah sebelum menyimpan.</div>
            @endif
            <div class="field">
                <label for="name">Nama website <span class="required-note">Wajib</span>
                </label>
                <input id="name" name="name" type="text" value="{{ old('name', $website->name) }}"
                    placeholder="Contoh: Website perusahaan" maxlength="100" required autocomplete="off"
                    @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                @error('name')
                    <p class="field-error" id="name-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="field">
                <label for="url">URL website <span class="required-note">Wajib</span>
                </label>
                <input id="url" name="url" type="url" value="{{ old('url', $website->url) }}"
                    placeholder="https://website-anda.com" maxlength="2048" required autocomplete="url"
                    aria-describedby="url-help{{ $errors->has('url') ? ' url-error' : '' }}"
                    @error('url') aria-invalid="true" @enderror>
                <p class="field-help" id="url-help">Gunakan alamat HTTP atau HTTPS yang dapat diakses publik.</p>
                @error('url')
                    <p class="field-error" id="url-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="field">
                <label for="interval_minutes">Interval pemeriksaan</label>
                <select name="interval_minutes" id="interval_minutes"
                    aria-describedby="interval-help{{ $errors->has('interval_minutes') ? ' interval-error' : '' }}"
                    @error('interval_minutes') aria-invalid="true" @enderror>
                    @foreach ([1, 5, 10, 15, 30, 60] as $interval)
                        <option value="{{ $interval }}" @selected((int) old('interval_minutes', $website->interval_minutes) === $interval)>Setiap {{ $interval }} menit
                        </option>
                    @endforeach
                </select>
                <p class="field-help" id="interval-help">Pemeriksaan berjalan selama scheduler lokal aktif.</p>
                @error('interval_minutes')
                    <p class="field-error" id="interval-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-actions">
                <button type="submit"
                    class="button button-primary">{{ $website->exists ? 'Simpan Perubahan' : 'Simpan Website' }} <span
                        aria-hidden="true">↗</span>
                </button>
                <a href="{{ $website->exists ? route('websites.show', $website) : route('websites.index') }}"
                    class="cancel-link">Batal</a>
            </div>
        </form>
        <aside class="form-aside">
            <span class="eyebrow">SEBELUM MEMULAI</span>
            <h2>Alamat publik.<br> Hasil yang terukur.</h2>
            <p>Alamat localhost, jaringan privat, dan port khusus tidak dapat dipantau.</p>
            <p>Setelah disimpan, jalankan pemeriksaan pertama melalui tombol “Periksa Sekarang”.</p>
            @if ($website->exists)
                <p>Jika URL berubah, ringkasan dan grafik mengikuti URL baru. Riwayat URL sebelumnya tetap tersimpan di
                    database.</p>
            @endif
            <span class="aside-line" aria-hidden="true">
            </span>
        </aside>
    </div>
@endsection
