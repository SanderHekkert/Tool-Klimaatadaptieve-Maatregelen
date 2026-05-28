@extends('maatregelen-tool.layout')

@section('title', 'Start — maatregelen kiezen')

@section('content')
    <div class="mt-container mt-entry-page" style="max-width:820px;">
        <h1 class="mt-page-title">Wil je alle maatregelen overwegen?</h1>
        <p class="mt-lead">Zo nee, selecteer welke maatregelen je wel wilt meenemen. Daarna ga je door naar de tool.</p>

        <form method="post" action="{{ route('maatregelen.start') }}" class="mt-card mt-form mt-entry-card">
            @csrf

            <section class="mt-section">
                <div class="mt-entry__choice">
                    <label class="mt-check mt-check--radio">
                        <input type="radio" name="overweeg_alle_maatregelen" value="1" {{ old('overweeg_alle_maatregelen', '1') === '1' ? 'checked' : '' }}>
                        Ja, neem alle maatregelen mee
                    </label>
                    <label class="mt-check mt-check--radio">
                        <input type="radio" name="overweeg_alle_maatregelen" value="0" {{ old('overweeg_alle_maatregelen') === '0' ? 'checked' : '' }}>
                        Nee, ik maak zelf een selectie
                    </label>
                </div>
            </section>

            <section id="mt-start-selectie" class="mt-section mt-entry__selectie" {{ old('overweeg_alle_maatregelen') === '0' ? '' : 'hidden' }}>
                <p class="mt-hint">Selecteer minimaal 1 maatregel:</p>
                @error('maatregel_ids')
                    <div class="mt-alert mt-alert--danger">{{ $message }}</div>
                @enderror
                <div class="mt-chip-grid mt-chip-grid--measures">
                    @foreach ($maatregelOpties as $maatregel)
                        <label class="mt-check">
                            <input
                                type="checkbox"
                                name="maatregel_ids[]"
                                value="{{ $maatregel['id'] }}"
                                {{ in_array($maatregel['id'], old('maatregel_ids', []), true) ? 'checked' : '' }}
                            >
                            {{ $maatregel['naam'] }}
                        </label>
                    @endforeach
                </div>
            </section>

            <div class="mt-form-actions mt-form-actions--entry">
                <button type="submit" class="mt-btn mt-btn--primary mt-btn--start">Verder naar de tool</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const radios = document.querySelectorAll('input[name="overweeg_alle_maatregelen"]');
            const wrap = document.getElementById('mt-start-selectie');
            if (!radios.length || !wrap) {
                return;
            }
            function update() {
                const checked = document.querySelector('input[name="overweeg_alle_maatregelen"]:checked');
                wrap.hidden = !checked || checked.value !== '0';
            }
            radios.forEach(function (r) {
                r.addEventListener('change', update);
            });
            update();
        });
    </script>
@endpush
