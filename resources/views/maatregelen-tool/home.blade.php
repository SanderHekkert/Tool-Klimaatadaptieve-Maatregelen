@extends('maatregelen-tool.layout')

@section('title', 'Klimaatregelen quickscan')
@section('hide_header', '1')
@section('full_bleed', '1')

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer" />
@endpush

@section('content')
    <section class="mt-home-fullscreen">
        <div class="mt-home-stage">
            <div class="mt-home-content-wrap">
                <div class="mt-home-orbit" aria-hidden="true">
                    <span class="mt-home-orbit__ring"></span>
                    <span class="mt-home-orbit__icon mt-home-orbit__icon--top"><i class="fa-solid fa-cloud-rain"></i></span>
                    <span class="mt-home-orbit__icon mt-home-orbit__icon--tr"><i class="fa-solid fa-seedling"></i></span>
                    <span class="mt-home-orbit__icon mt-home-orbit__icon--r"><i class="fa-solid fa-droplet"></i></span>
                    <span class="mt-home-orbit__icon mt-home-orbit__icon--br"><i class="fa-solid fa-shield-heart"></i></span>
                    <span class="mt-home-orbit__icon mt-home-orbit__icon--bl"><i class="fa-solid fa-people-group"></i></span>
                    <span class="mt-home-orbit__icon mt-home-orbit__icon--l"><i class="fa-solid fa-tree"></i></span>
                    <span class="mt-home-orbit__icon mt-home-orbit__icon--tl"><i class="fa-solid fa-temperature-three-quarters"></i></span>
                </div>

                <div class="mt-home-content">
                    <h1 class="mt-home-logo">
                        <span class="mt-home-logo__top">
                            <span class="mt-home-logo__climate">KLI</span><span class="mt-home-logo__maatregelen">MAATregelen</span>
                        </span>
                        <span class="mt-home-logo__bottom">QUICKSCAN</span>
                    </h1>

                    <div class="mt-home-copy">
                        <p class="mt-home-copy__intro">He klimaattopper!</p>
                        <p>
                            Deze tool ondersteund het selecteren van klimaatadaptieve maatregelen bij binnenstedelijke nieuwbouwprojecten en projectontwikkelingen.
                            Door projectspecifieke gegevens in te vullen, laat de tool zien welke maatregelen toepasbaar zijn en hoeveel hiervan nodig is om aan de
                            gestelde eisen te voldoen. De uitkomst hiervan vormt een praktische basis en richtlijn binnen het selectieproces.
                        </p>
                        <p>
                            Kleine keuzes maken een groot verschil, veel succes en plezier met het maken van impact!
                        </p>
                    </div>
                </div>
                <div class="mt-home-cta mt-home-cta--floating">
                    <a href="{{ route('maatregelen.index') }}" class="mt-btn mt-btn--primary mt-btn--start">Start met selecteren</a>
                </div>
            </div>
        </div>
    </section>
@endsection
