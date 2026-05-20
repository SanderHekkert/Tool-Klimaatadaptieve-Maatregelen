{{-- Verwacht: $filterChips als list<array{label: string, value: string}> --}}
<div class="mt-filter" aria-label="Gekozen filters">
    <p class="mt-filter__label">Filters</p>
    <ul class="mt-filter__chips">
        @foreach ($filterChips as $chip)
            <li><span class="mt-filter__k">{{ $chip['label'] }}</span>{{ $chip['value'] }}</li>
        @endforeach
    </ul>
</div>
