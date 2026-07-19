@extends('layouts.app')
@section('title', 'Itinerary')

@push('styles')
<style>
    /* Clean minimal calendar matching reference design */
    #itinerary-calendar { font-family: inherit; }
    #itinerary-calendar .fc-toolbar { align-items: center; margin-bottom: 16px !important; }
    #itinerary-calendar .fc-toolbar-title {
        font-size: 20px !important;
        font-weight: 700 !important;
        color: #1A0A00 !important;
        letter-spacing: -0.01em;
    }
    #itinerary-calendar .fc-button {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        color: #6B7280 !important;
        padding: 4px 8px !important;
        font-size: 16px !important;
        line-height: 1 !important;
    }
    #itinerary-calendar .fc-button:hover { color: #1A0A00 !important; }
    #itinerary-calendar .fc-button:focus { outline: none !important; box-shadow: none !important; }
    #itinerary-calendar .fc-col-header-cell {
        border: none !important;
        padding: 6px 0 10px !important;
    }
    #itinerary-calendar .fc-col-header-cell-cushion {
        font-size: 11px !important;
        font-weight: 600 !important;
        color: #9CA3AF !important;
        text-transform: uppercase;
        letter-spacing: .06em;
        text-decoration: none !important;
    }
    #itinerary-calendar .fc-daygrid-day {
        border-color: #F3F4F6 !important;
    }
    #itinerary-calendar .fc-scrollgrid { border: none !important; }
    #itinerary-calendar .fc-scrollgrid td, #itinerary-calendar .fc-scrollgrid th { border-color: #F3F4F6 !important; }
    #itinerary-calendar .fc-daygrid-day-number {
        font-size: 13px !important;
        color: #9CA3AF !important;
        font-weight: 500 !important;
        text-decoration: none !important;
        padding: 6px 8px 2px !important;
        line-height: 1 !important;
    }
    #itinerary-calendar .fc-day-other .fc-daygrid-day-number { color: #D1D5DB !important; }
    #itinerary-calendar .fc-day-today { background: transparent !important; }
    #itinerary-calendar .fc-day-today .fc-daygrid-day-number {
        color: var(--primary) !important;
        font-weight: 700 !important;
    }
    #itinerary-calendar .fc-daygrid-day-frame {
        min-height: 80px !important;
        cursor: pointer;
    }
    #itinerary-calendar .fc-daygrid-day-events { display: none !important; }
    #itinerary-calendar .fc-daygrid-day-bg     { display: none !important; }
    #itinerary-calendar .fc-daygrid-day:hover .fc-daygrid-day-frame { background: #FAFAF9; }
    .cal-icon-row {
        display: flex;
        flex-wrap: wrap;
        gap: 3px;
        padding: 2px 6px 4px;
        justify-content: flex-start;
    }
    .cal-icon-pip {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        font-size: 10px;
        font-weight: 600;
        line-height: 1;
    }
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

    const iconMap = {
        plane:  { fa: 'fa-plane',  color: '#1D4ED8' },
        bed:    { fa: 'fa-bed',    color: '#16A34A' },
        camera: { fa: 'fa-camera', color: '#8B3A10' },
        car:    { fa: 'fa-car',    color: '#D97706' },
    };

    function buildDayIcons(events) {
        const dayIcons = {};
        events.forEach(function(ev) {
            const date = ev.start.substring(0, 10);
            const icon = (ev.extendedProps && ev.extendedProps.icon) || 'camera';
            if (!dayIcons[date]) dayIcons[date] = [];
            if (!dayIcons[date].includes(icon)) dayIcons[date].push(icon);
        });
        return dayIcons;
    }

    function initCalendar() {
        const wrapper = document.getElementById('calendar-wrapper');
        const calEl   = document.getElementById('itinerary-calendar');
        if (!wrapper || !calEl) return;

        if (calendar) { calendar.destroy(); calendar = null; }

        const events  = JSON.parse(wrapper.dataset.events  || '[]');
        const start   = wrapper.dataset.start;
        const end     = wrapper.dataset.end;
        const initial = wrapper.dataset.initial;

        const lwEl = document.querySelector('[wire\\:id]');
        const lwId = lwEl ? lwEl.getAttribute('wire:id') : null;

        const dayIcons = buildDayIcons(events);

        calendar = new FullCalendar.Calendar(calEl, {
            initialView:   'dayGridMonth',
            initialDate:   initial || undefined,
            validRange:    start && end ? { start, end } : undefined,
            events:        [],
            height:        'auto',
            headerToolbar: {
                left:   'title',
                center: '',
                right:  'prev,next'
            },
            buttonText: { prev: '‹', next: '›' },
            dayCellDidMount: function(info) {
                // Use info.dateStr (local date) — never toISOString() which shifts by timezone
                const dateStr = info.dateStr;

                // Only render icons within the trip's valid range
                if (start && dateStr < start) return;
                if (end   && dateStr >= end)  return;

                const icons = dayIcons[dateStr];
                if (!icons || !icons.length) return;

                const row = document.createElement('div');
                row.className = 'cal-icon-row';

                icons.forEach(function(icon) {
                    const meta = iconMap[icon] || iconMap.camera;
                    const pip  = document.createElement('span');
                    pip.className = 'cal-icon-pip';
                    pip.innerHTML = '<i class="fa-solid ' + meta.fa + '" style="color:' + meta.color + ';font-size:10px;"></i>';
                    row.appendChild(pip);
                });

                const frame = info.el.querySelector('.fc-daygrid-day-frame');
                if (frame) frame.appendChild(row);
            },
            dateClick: function(info) {
                if (!start || !end) return;
                if (info.dateStr < start || info.dateStr >= end) return;
                if (lwId) Livewire.find(lwId).call('selectDay', info.dateStr);
            },
        });
        calendar.render();
    }

    document.addEventListener('DOMContentLoaded', initCalendar);
    document.addEventListener('livewire:navigated', initCalendar);
    document.addEventListener('livewire:morph', function() { setTimeout(initCalendar, 50); });

    window.addEventListener('trip-changed', function(e) {
        const wrapper = document.getElementById('calendar-wrapper');
        if (!wrapper || !calendar) { setTimeout(initCalendar, 50); return; }
        const detail = e.detail || {};
        if (detail.start)              wrapper.dataset.start   = detail.start;
        if (detail.end)                wrapper.dataset.end     = detail.end;
        if (detail.start)              wrapper.dataset.initial = detail.start;
        if (detail.events !== undefined) wrapper.dataset.events = JSON.stringify(detail.events);
        setTimeout(initCalendar, 50);
    });

    window.addEventListener('trip-cleared', function() {
        if (calendar) { calendar.destroy(); calendar = null; }
    });

    window.addEventListener('trip-selected', function(e) {
        const detail = e.detail || {};
        function tryInit(attempts) {
            const wrapper = document.getElementById('calendar-wrapper');
            const calEl   = document.getElementById('itinerary-calendar');
            if (wrapper && calEl) {
                if (detail.start)              wrapper.dataset.start   = detail.start;
                if (detail.end)                wrapper.dataset.end     = detail.end;
                if (detail.start)              wrapper.dataset.initial = detail.start;
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
