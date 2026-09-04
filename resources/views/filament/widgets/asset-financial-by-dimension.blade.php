<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Οικονομική εικόνα μητρώου</x-slot>

        <x-slot name="description">
            Αξίες κτήσης, σωρευμένες αποσβέσεις και αναπόσβεστη αξία ανά διάσταση.
        </x-slot>

        @php
            $dimensions = [
                ['title' => 'Ανά κατηγορία', 'rows' => $categories],
                ['title' => 'Ανά τμήμα', 'rows' => $departments],
                ['title' => 'Ανά τοποθεσία', 'rows' => $locations],
            ];
        @endphp

        <div class="asset-financial-grid">
            @foreach ($dimensions as $dimension)
                @php
                    $totalPurchase = collect($dimension['rows'])->sum(fn (array $row) => (float) $row['purchase']);
                    $totalAccumulated = collect($dimension['rows'])->sum(fn (array $row) => (float) $row['accumulated']);
                    $totalRemaining = collect($dimension['rows'])->sum(fn (array $row) => (float) $row['remaining']);
                @endphp

                <section class="asset-financial-card" aria-label="{{ $dimension['title'] }}">
                    <header class="asset-financial-card__header">
                        <div>
                            <h3 class="asset-financial-card__title">{{ $dimension['title'] }}</h3>
                            <p class="asset-financial-card__count">
                                {{ count($dimension['rows']) }} {{ count($dimension['rows']) === 1 ? 'εγγραφή' : 'εγγραφές' }}
                            </p>
                        </div>

                        <div class="asset-financial-card__summary">
                            <span>Σύνολο αναπόσβεστης αξίας</span>
                            <strong>{{ number_format($totalRemaining, 2, ',', '.') }} €</strong>
                        </div>
                    </header>

                    <div class="asset-financial-table-wrap">
                        <table class="asset-financial-table">
                            <thead>
                                <tr>
                                    <th scope="col">Στοιχείο</th>
                                    <th scope="col">Αξία κτήσης</th>
                                    <th scope="col">Σωρευμένες αποσβέσεις</th>
                                    <th scope="col">Αναπόσβεστη αξία</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($dimension['rows'] as $row)
                                    <tr>
                                        <th scope="row">{{ $row['label'] }}</th>
                                        <td>{{ number_format((float) $row['purchase'], 2, ',', '.') }} €</td>
                                        <td>{{ number_format((float) $row['accumulated'], 2, ',', '.') }} €</td>
                                        <td class="asset-financial-table__remaining">
                                            {{ number_format((float) $row['remaining'], 2, ',', '.') }} €
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="asset-financial-table__empty">
                                            Δεν υπάρχουν διαθέσιμα δεδομένα.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if (count($dimension['rows']) > 1)
                                <tfoot>
                                    <tr>
                                        <th scope="row">Σύνολο</th>
                                        <td>{{ number_format($totalPurchase, 2, ',', '.') }} €</td>
                                        <td>{{ number_format($totalAccumulated, 2, ',', '.') }} €</td>
                                        <td>{{ number_format($totalRemaining, 2, ',', '.') }} €</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </section>
            @endforeach
        </div>

        <style>
            .asset-financial-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(min(100%, 46rem), 1fr));
                gap: 1rem;
            }

            .asset-financial-card {
                min-width: 0;
                overflow: hidden;
                border: 1px solid rgb(229 231 235);
                border-radius: .75rem;
                background: rgb(255 255 255);
                box-shadow: 0 1px 2px rgb(0 0 0 / .04);
            }

            .asset-financial-card__header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding: .9rem 1rem;
                border-bottom: 1px solid rgb(229 231 235);
                background: rgb(249 250 251);
            }

            .asset-financial-card__title {
                display: flex;
                align-items: center;
                gap: .45rem;
                margin: 0;
                color: rgb(17 24 39);
                font-size: .95rem;
                font-weight: 700;
            }

            .asset-financial-card__count {
                margin: .2rem 0 0;
                color: rgb(107 114 128);
                font-size: .75rem;
            }

            .asset-financial-card__summary {
                flex: 0 0 auto;
                text-align: right;
            }

            .asset-financial-card__summary span {
                display: block;
                color: rgb(107 114 128);
                font-size: .7rem;
            }

            .asset-financial-card__summary strong {
                display: block;
                margin-top: .1rem;
                color: rgb(22 101 52);
                font-size: .9rem;
                font-variant-numeric: tabular-nums;
                white-space: nowrap;
            }

            .asset-financial-table-wrap {
                width: 100%;
                overflow-x: auto;
            }

            .asset-financial-table {
                width: 100%;
                min-width: 42rem;
                border-collapse: collapse;
                table-layout: fixed;
                color: rgb(55 65 81);
                font-size: .8rem;
            }

            .asset-financial-table th,
            .asset-financial-table td {
                padding: .7rem .8rem;
                border-bottom: 1px solid rgb(243 244 246);
                vertical-align: middle;
            }

            .asset-financial-table thead th {
                background: rgb(255 255 255);
                color: rgb(75 85 99);
                font-size: .7rem;
                font-weight: 700;
                line-height: 1.25;
                text-transform: uppercase;
                letter-spacing: .025em;
            }

            .asset-financial-table th:first-child {
                width: 31%;
                text-align: left;
            }

            .asset-financial-table thead th:not(:first-child),
            .asset-financial-table tbody td,
            .asset-financial-table tfoot td {
                text-align: right;
            }

            .asset-financial-table tbody th {
                overflow-wrap: anywhere;
                color: rgb(17 24 39);
                font-weight: 600;
            }

            .asset-financial-table tbody td,
            .asset-financial-table tfoot td {
                font-variant-numeric: tabular-nums;
                white-space: nowrap;
            }

            .asset-financial-table tbody tr:hover {
                background: rgb(249 250 251);
            }

            .asset-financial-table__remaining {
                color: rgb(22 101 52);
                font-weight: 700;
            }

            .asset-financial-table tfoot th,
            .asset-financial-table tfoot td {
                border-top: 2px solid rgb(229 231 235);
                border-bottom: 0;
                background: rgb(249 250 251);
                color: rgb(17 24 39);
                font-weight: 700;
            }

            .asset-financial-table__empty {
                padding: 1.5rem !important;
                color: rgb(107 114 128);
                text-align: center !important;
            }

            .dark .asset-financial-card {
                border-color: rgb(255 255 255 / .1);
                background: rgb(24 24 27);
            }

            .dark .asset-financial-card__header,
            .dark .asset-financial-table tfoot th,
            .dark .asset-financial-table tfoot td {
                border-color: rgb(255 255 255 / .1);
                background: rgb(255 255 255 / .04);
            }

            .dark .asset-financial-card__title,
            .dark .asset-financial-table tbody th,
            .dark .asset-financial-table tfoot th,
            .dark .asset-financial-table tfoot td {
                color: rgb(244 244 245);
            }

            .dark .asset-financial-table {
                color: rgb(212 212 216);
            }

            .dark .asset-financial-table thead th {
                background: rgb(24 24 27);
                color: rgb(161 161 170);
            }

            .dark .asset-financial-table th,
            .dark .asset-financial-table td {
                border-color: rgb(255 255 255 / .07);
            }

            .dark .asset-financial-table tbody tr:hover {
                background: rgb(255 255 255 / .035);
            }

            .dark .asset-financial-card__summary strong,
            .dark .asset-financial-table__remaining {
                color: rgb(74 222 128);
            }

            @media (max-width: 640px) {
                .asset-financial-card__header {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .asset-financial-card__summary {
                    text-align: left;
                }
            }
        </style>
    </x-filament::section>
</x-filament-widgets::widget>
