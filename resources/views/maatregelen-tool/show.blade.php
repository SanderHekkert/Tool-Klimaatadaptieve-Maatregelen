@extends('maatregelen-tool.layout')

@section('title', 'Resultaat maatregelen')

@section('content')
    <div class="mt-toolbar">
        <a href="{{ route('maatregelen.index') }}" class="mt-btn mt-btn--secondary">← Filters aanpassen</a>
    </div>

    <h1 class="mt-page-title">Resultaat</h1>
    <p class="mt-lead">Maatregelen die aan je criteria voldoen. Je gekozen filters staan hieronder in het kort.</p>

    @include('maatregelen-tool.partials.filter-samenvatting', ['filterChips' => $filterChips])

    @include('maatregelen-tool.partials.resultaat', ['result' => $result])
@endsection
