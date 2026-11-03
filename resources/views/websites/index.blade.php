@extends('layouts.app')
@section('title', 'Monitoring')
@section('content')
    <section class="hero" aria-labelledby="page-title">
        <div class="eyebrow">
            <span>01 / PEMANTAUAN WEBSITE</span>
            <span class="edition">HTTP · HTTPS</span>
        </div>
        <h1 id="page-title">Pantau Website dalam Satu Tempat.</h1>
        <div class="hero-bottom">
            <p class="intro">Periksa ketersediaan, waktu respons, dan riwayat gangguan<br class="desktop-break"> website
                secara otomatis.</p>
            <a class="button button-primary" href="{{ route('websites.create') }}">
                <span class="plus" aria-hidden="true">+</span> Tambah Website</a>
        </div>
    </section>
    <section class="monitor-section" aria-labelledby="website-list-title">
        <div class="section-heading">
            <div class="section-title">
                <span class="section-marker" aria-hidden="true">
                </span>
                <h2 id="website-list-title">Website yang dipantau</h2>
                <span class="count">{{ $websites->count() }}</span>
            </div>
            <p class="section-meta">Pemeriksaan mengikuti interval setiap website</p>
        </div>
        @if ($websites->isNotEmpty())
            <div class="status-summary" aria-label="Ringkasan status aktual">
                <span>
                    <i class="status-mark up" aria-hidden="true">
                    </i>{{ $websites->where('status', 'up')->count() }} Up</span>
                <span>
                    <i class="status-mark down" aria-hidden="true">
                    </i>{{ $websites->where('status', 'down')->count() }} Down</span>
                <span>
                    <i class="status-mark pending" aria-hidden="true">
                    </i>{{ $websites->where('status', 'pending')->count() }} Belum Diperiksa</span>
            </div>
            <div class="table-wrap">
                <table class="website-table">
                    <thead>
                        <tr>
                            <th scope="col">WEBSITE</th>
                            <th scope="col">STATUS</th>
                            <th scope="col">WAKTU RESPONS</th>
                            <th scope="col">PEMERIKSAAN TERAKHIR</th>
                            <th scope="col">
                                <span class="sr-only">Tindakan</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($websites as $website)
                            <tr>
                                <td data-label="Website">
                                    <a class="website-name"
                                        href="{{ route('websites.show', $website) }}">{{ $website->name }} <span
                                            class="link-arrow" aria-hidden="true">↗</span>
                                    </a>
                                    <span class="website-url">{{ $website->url }}</span>
                                </td>
                                <td data-label="Status">
                                    @include('websites.partials.status')</td>
                                <td data-label="Waktu respons">
                                    <span
                                        class="numeric">{{ $website->response_time_ms !== null ? number_format($website->response_time_ms, 0, ',', '.') : '—' }}</span>
                                    @if ($website->response_time_ms !== null)
                                        <span class="unit"> ms</span>
                                    @endif
                                </td>
                                <td data-label="Pemeriksaan terakhir">
                                    @if ($website->checked_at)
                                        <time
                                            datetime="{{ $website->checked_at->toIso8601String() }}">{{ $website->checked_at->format('d M Y, H:i:s') }}</time>
                                    @else
                                        <span>Belum ada pemeriksaan</span>
                                    @endif
                                    <span class="interval">Setiap {{ $website->interval_minutes }} menit</span>
                                </td>
                                <td class="row-actions">
                                    @include('websites.partials.check-button')</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <span class="empty-index" aria-hidden="true">—</span>
                <div>
                    <h3>Mulai dari website pertama Anda.</h3>
                    <p>Tambahkan URL publik untuk mulai mencatat ketersediaan<br class="desktop-break"> dan waktu
                        responsnya. Semua hasil berasal dari pemeriksaan nyata.</p>
                    <a class="text-link" href="{{ route('websites.create') }}">Tambah Website <span
                            aria-hidden="true">↗</span>
                    </a>
                </div>
                <span class="empty-number" aria-hidden="true">00</span>
            </div>
        @endif
        <div class="section-footnote">
            <span>Hasil terbaru ditampilkan setelah pemeriksaan selesai.</span>
            <span>Interval 1–60 menit</span>
        </div>
    </section>
    <aside class="method-note">
        <span class="eyebrow">CATATAN PEMANTAUAN</span>
        <p>Status <strong>Up</strong> berarti respons HTTP 2xx diterima. Status <strong>Down</strong> berarti respons gagal,
            koneksi terputus, atau batas pemeriksaan terlampaui.</p>
        <p>Pemeriksaan otomatis memerlukan scheduler lokal yang aktif.</p>
    </aside>
@endsection
