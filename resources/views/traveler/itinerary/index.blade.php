@extends('layouts.app')
@section('title', 'Itinerary')

@push('styles')
<style>
    #itinerary-calendar .fc-day-today { background: #FEF3C7 !important; }
    #itinerary-calendar .fc-daygrid-day:hover { background: #F5EDE7; cursor: pointer; }
    #itinerary-calendar .fc-button-primary {
        background-color: var(--primary) !important;
        border-color: var(--primary) !important;
        font-size: 13px !important;
    }
    #itinerary-calendar .fc-button-primary:hover {
        background-color: #732f0d !important;
        border-color: #732f0d !important;
    }
    #itinerary-calendar .fc-toolbar-title { font-size: 16px !important; font-weight: 700 !important; }
    #itinerary-calendar .fc-daygrid-day-number { font-size: 13px !important; }
    #itinerary-calendar .fc-col-header-cell-cushion { font-size: 12px !important; font-weight: 600 !important; color: #6B7280; }
    #itinerary-calendar .fc-event { border-radius: 4px !important; font-size: 11px !important; font-weight: 600 !important; }
</style>
@endpush

@section('content')
@livewire('traveler.itinerary-manager')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
(function () {
    let calendar = null;

    function initCalendar() {
        const wrapper = document.getElementById('calendar-wrapper');
        const calEl   = document.getElementById('itinerary-calendar');
        if (!wrapper || !calEl) return;

        if (calendar) { calendar.destroy(); calendar = null; }

        const events  = JSON.parse(wrapper.dataset.events  || '[]');
        const start   = wrapper.dataset.start;
        const end     = wrapper.dataset.end;
        const initial = wrapper.dataset.initial;

        // Find the Livewire component id
        const lwEl = document.querySelector('[wire\\:id]');
        const lwId = lwEl ? lwEl.getAttribute('wire:id') : null;

        calendar = new FullCalendar.Calendar(calEl, {
            initialView: 'dayGridMonth',
            initialDate: initial || undefined,
            validRange: start && end ? { start, end } : undefined,
            events: events,
            height: 'auto',
            contentHeight: 620,
            headerToolbar: {
                left:   'prev,next today',
                center: 'title',
                right:  ''
            },
            dateClick: function (info) {
                if (!start || !end) return;
                if (info.dateStr < start || info.dateStr >= end) return;
                if (lwId) {
                    Livewire.find(lwId).call('selectDay', info.dateStr);
                }
            },
        });
        calendar.render();
    }

    // Init on first load
    document.addEventListener('DOMContentLoaded', initCalendar);

    // Re-init when Livewire swaps the DOM (navigation)
    document.addEventListener('livewire:navigated', initCalendar);

    // Re-init after Livewire renders (trip change, etc.)
    document.addEventListener('livewire:morph', function () {
        // Small delay to let wire:ignore divs settle
        setTimeout(initCalendar, 50);
    });

    // Listen for explicit trip-changed dispatch (events updated after generate)
    window.addEventListener('trip-changed', function (e) {
        const wrapper = document.getElementById('calendar-wrapper');
        if (!wrapper || !calendar) { setTimeout(initCalendar, 50); return; }
        const detail = e.detail || {};
        if (detail.start) wrapper.dataset.start   = detail.start;
        if (detail.end)   wrapper.dataset.end     = detail.end;
        if (detail.start) wrapper.dataset.initial = detail.start;
        if (detail.events !== undefined) wrapper.dataset.events = JSON.stringify(detail.events);
        setTimeout(initCalendar, 50);
    });

    // After trip is selected Livewire inserts the calendar-wrapper; wait for it then init
    window.addEventListener('trip-selected', function (e) {
        const detail = e.detail || {};
        function tryInit(attempts) {
            const wrapper = document.getElementById('calendar-wrapper');
            const calEl   = document.getElementById('itinerary-calendar');
            if (wrapper && calEl) {
                if (detail.start) wrapper.dataset.start   = detail.start;
                if (detail.end)   wrapper.dataset.end     = detail.end;
                if (detail.start) wrapper.dataset.initial = detail.start;
                if (detail.events !== undefined) wrapper.dataset.events = JSON.stringify(detail.events);
                initCalendar();
            } else if (attempts > 0) {
                setTimeout(() => tryInit(attempts - 1), 80);
            }
        }
        tryInit(15);
    });
})();
</script>
@endpush
