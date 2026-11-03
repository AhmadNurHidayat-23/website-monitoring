@extends('layouts.app')
@section('title', $website->name)
@section('content')
    <div class="breadcrumb">
        <a href="{{ route('websites.index') }}">Monitoring</a>
        <span aria-hidden="true">/</span>
        <span>Detail website</span>
    </div>
    <section class="detail-heading">
        <div>
            <p class="eyebrow">DETAIL PEMANTAUAN</p>
            <h1>{{ $website->name }}</h1>
            <p class="detail-url">{{ $website->url }}</p>
        </div>
        <div class="detail-actions">
            @include('websites.partials.check-button')<a class="button button-small" href="{{ route('websites.edit', $website) }}">Edit
                Website</a>
        </div>
    </section>
    <dl class="metrics">
        <div>
            <dt>STATUS TERBARU</dt>
            <dd>
                @include('websites.partials.status')</dd>
            <span>Interval {{ $website->interval_minutes }} menit</span>
        </div>
        <div>
            <dt>HTTP STATUS CODE</dt>
            <dd class="numeric">{{ $website->http_status_code ?? '—' }}</dd>
            <span>{{ $website->checked_at ? 'Respons pemeriksaan terakhir' : 'Menunggu pemeriksaan' }}</span>
        </div>
        <div>
            <dt>WAKTU RESPONS</dt>
            <dd class="numeric">
                {{ $website->response_time_ms !== null ? number_format($website->response_time_ms, 0, ',', '.') : '—' }}
                @if ($website->response_time_ms !== null)
                    <small>ms</small>
                @endif
            </dd>
            <span>Durasi seluruh pemeriksaan</span>
        </div>
        <div>
            <dt>KEBERHASILAN</dt>
            <dd class="numeric">{{ $successRate !== null ? number_format($successRate, 1, ',', '.') : '—' }}@if ($successRate !== null)
                    <small>%</small>
                @endif
            </dd>
            <span>{{ $successful }} dari {{ $total }} pemeriksaan</span>
        </div>
    </dl>
    @if ($website->last_error)
        <p class="notice" role="status">{{ $website->last_error }}</p>
    @endif
    <section class="chart-section" aria-labelledby="chart-title">
        <div class="section-heading">
            <div class="section-title">
                <span class="section-marker" aria-hidden="true">
                </span>
                <h2 id="chart-title">Waktu respons</h2>
            </div>
            <span class="section-meta">{{ $chart->count() }} pemeriksaan terakhir · milidetik</span>
        </div>
        @if ($chart->isNotEmpty())
            <div class="chart-frame">
                <svg class="response-chart" role="img" aria-labelledby="chart-title chart-description"
                    data-points="{{ $chart->map(fn($check) => ['ms' => $check->response_time_ms, 'time' => $check->checked_at->format('d M H:i:s'), 'status' => $check->status])->toJson() }}"
                    viewBox="0 0 1000 240">
                    <desc id="chart-description">Grafik durasi pemeriksaan aktual. Hasil Up ditandai lingkaran dan Down
                        ditandai silang. Data lengkap tersedia pada tabel riwayat.</desc>
                </svg>
                <p class="chart-fallback">{{ $chart->count() }} hasil aktual tersedia di tabel riwayat di bawah.</p>
            </div>
            <p class="chart-caption">
                <span>○ Up &nbsp; × Down</span>
                <span>Durasi termasuk redirect dan percobaan yang gagal.</span>
            </p>
        @else
            <div class="chart-empty">
                <p>Grafik dimulai dari pemeriksaan pertama.</p>
                <span>Jalankan “Periksa Sekarang” untuk merekam waktu respons.</span>
            </div>
        @endif
    </section>
    <section class="history-section" aria-labelledby="history-title">
        <div class="section-heading">
            <div class="section-title">
                <span class="section-marker" aria-hidden="true">
                </span>
                <h2 id="history-title">Riwayat pemeriksaan</h2>
            </div>
            <span class="section-meta">URL saat ini · WIB</span>
        </div>
        @if ($checks->isNotEmpty())
            <div class="table-wrap">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th scope="col">WAKTU PEMERIKSAAN</th>
                            <th scope="col">STATUS</th>
                            <th scope="col">HTTP</th>
                            <th scope="col">DURASI</th>
                            <th scope="col">KETERANGAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($checks as $check)
                            <tr>
                                <td data-label="Waktu">
                                    <time
                                        datetime="{{ $check->checked_at->toIso8601String() }}">{{ $check->checked_at->format('d M Y, H:i:s') }}</time>
                                </td>
                                <td data-label="Status">
                                    <span class="status">
                                        <i class="status-mark {{ $check->status }}" aria-hidden="true">
                                        </i>{{ $check->status === 'up' ? 'Up' : 'Down' }}</span>
                                </td>
                                <td data-label="HTTP" class="numeric">{{ $check->http_status_code ?? '—' }}</td>
                                <td data-label="Durasi">
                                    <span class="numeric">{{ number_format($check->response_time_ms, 0, ',', '.') }}</span>
                                    ms
                                </td>
                                <td data-label="Keterangan">
                                    {{ $check->error ?? ($check->status === 'up' ? 'Respons berhasil diterima' : 'Respons HTTP gagal') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($checks->hasPages())
                <nav class="pagination" aria-label="Halaman riwayat">
                    @if ($checks->onFirstPage())
                        <span>Sebelumnya</span>
                    @else
                        <a href="{{ $checks->previousPageUrl() }}">← Sebelumnya</a>
                    @endif
                    <span>{{ $checks->currentPage() }} / {{ $checks->lastPage() }}</span>
                    @if ($checks->hasMorePages())
                        <a href="{{ $checks->nextPageUrl() }}">Berikutnya →</a>
                    @else
                        <span>Berikutnya</span>
                    @endif
                </nav>
            @endif
        @else
            <div class="history-empty">Belum ada riwayat pemeriksaan untuk URL ini.</div>
        @endif
    </section>
    <div class="detail-bottom">
        <a class="text-link" href="{{ route('websites.index') }}">← Kembali ke monitoring</a>
        <form method="post" action="{{ route('websites.destroy', $website) }}" data-confirm-delete>
            @csrf
            @method('delete')<button class="delete-button" type="submit">Hapus Website</button>
        </form>
    </div>
@endsection
