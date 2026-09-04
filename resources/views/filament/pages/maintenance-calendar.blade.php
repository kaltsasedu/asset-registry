<x-filament-panels::page>
    <style>
        .maintenance-calendar-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .maintenance-calendar-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: #172033;
        }

        .maintenance-calendar-button {
            display: inline-flex;
            align-items: center;
            min-height: 2.25rem;
            border: 1px solid #cbd5e1;
            border-radius: .5rem;
            padding: 0 .9rem;
            background: #fff;
            color: #172033;
            font-size: .875rem;
            font-weight: 600;
            text-decoration: none;
        }

        .maintenance-calendar-button:hover { background: #f8fafc; }
        .maintenance-calendar-scroll { overflow-x: auto; border: 1px solid #d8e0ea; border-radius: .75rem; background: #fff; }

        .maintenance-calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(130px, 1fr));
            min-width: 910px;
        }

        .maintenance-calendar-weekday {
            padding: .8rem .5rem;
            border-right: 1px solid #d8e0ea;
            border-bottom: 1px solid #d8e0ea;
            background: #edf3fa;
            color: #344054;
            text-align: center;
            font-size: .8rem;
            font-weight: 700;
        }

        .maintenance-calendar-weekday:nth-child(7n) { border-right: 0; }

        .maintenance-calendar-day {
            min-height: 145px;
            padding: .55rem;
            border-right: 1px solid #d8e0ea;
            border-bottom: 1px solid #d8e0ea;
            background: #fff;
        }

        .maintenance-calendar-day:nth-child(7n) { border-right: 0; }
        .maintenance-calendar-day--other { background: #f8fafc; }
        .maintenance-calendar-day--today { background: #fffaf0; }

        .maintenance-calendar-date {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 999px;
            color: #344054;
            font-size: .875rem;
            font-weight: 700;
        }

        .maintenance-calendar-day--today .maintenance-calendar-date { background: #d97706; color: #fff; }
        .maintenance-calendar-event { display: block; margin-top: .45rem; border-radius: .4rem; padding: .42rem .5rem; background: #e0f2fe; color: #075985; font-size: .76rem; line-height: 1.3; text-decoration: none; }
        .maintenance-calendar-event:hover { background: #bae6fd; }
        .maintenance-calendar-event--progress { background: #fef3c7; color: #92400e; }
        .maintenance-calendar-event-code { display: block; font-weight: 800; }
        .maintenance-calendar-help { margin-top: .8rem; color: #667085; font-size: .83rem; }

        @media (max-width: 640px) {
            .maintenance-calendar-toolbar { align-items: stretch; }
            .maintenance-calendar-title { text-align: center; font-size: 1rem; }
            .maintenance-calendar-button { padding: 0 .6rem; font-size: .78rem; }
        }
    </style>

    <div class="maintenance-calendar-toolbar">
        <a class="maintenance-calendar-button" href="{{ url('/admin/maintenance-calendar?month=' . $previousMonth) }}">‹ Προηγούμενος</a>
        <h2 class="maintenance-calendar-title">{{ $month->translatedFormat('F Y') }}</h2>
        <a class="maintenance-calendar-button" href="{{ url('/admin/maintenance-calendar?month=' . $nextMonth) }}">Επόμενος ›</a>
    </div>

    <div class="maintenance-calendar-scroll">
        <div class="maintenance-calendar-grid">
            <div class="maintenance-calendar-weekday">Δευτέρα</div>
            <div class="maintenance-calendar-weekday">Τρίτη</div>
            <div class="maintenance-calendar-weekday">Τετάρτη</div>
            <div class="maintenance-calendar-weekday">Πέμπτη</div>
            <div class="maintenance-calendar-weekday">Παρασκευή</div>
            <div class="maintenance-calendar-weekday">Σάββατο</div>
            <div class="maintenance-calendar-weekday">Κυριακή</div>

            @foreach ($weeks as $week)
                @foreach ($week as $day)
                    <div class="maintenance-calendar-day {{ $day['date']->month !== $month->month ? 'maintenance-calendar-day--other' : '' }} {{ $day['date']->isToday() ? 'maintenance-calendar-day--today' : '' }}">
                        <span class="maintenance-calendar-date">{{ $day['date']->day }}</span>

                        @foreach ($day['events'] as $event)
                            <a href="{{ $resource::getUrl('edit', ['record' => $event]) }}" class="maintenance-calendar-event {{ $event->status === 'in_progress' ? 'maintenance-calendar-event--progress' : '' }}">
                                <span class="maintenance-calendar-event-code">{{ $event->asset?->asset_code }}</span>
                                {{ $event->title }}
                            </a>
                        @endforeach
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>

    <p class="maintenance-calendar-help">Κάθε εργασία εμφανίζεται στο κελί της ημερομηνίας της. Επιλέξτε την εργασία για επεξεργασία ή εκτύπωση δελτίου εργασίας.</p>
</x-filament-panels::page>
