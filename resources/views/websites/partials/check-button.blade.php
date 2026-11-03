<form action="{{ route('websites.check', $website) }}" method="post" data-check>
    @csrf
    <button class="button button-small" type="submit" aria-label="Periksa {{ $website->name }} sekarang">Periksa Sekarang
        <span aria-hidden="true">↗</span>
    </button>
</form>
