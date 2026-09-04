<x-filament-panels::page>
    <div wire:poll.60s class="space-y-6">
        <x-filament::section>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Ενιαία εικόνα εκκρεμοτήτων</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Εμφανίζονται μόνο οι ενότητες στις οποίες έχετε δικαίωμα πρόσβασης. Η οθόνη ανανεώνεται αυτόματα κάθε λεπτό.</p>
                </div>
                <x-filament::button wire:click="$refresh" color="gray" icon="heroicon-o-arrow-path">Ανανέωση</x-filament::button>
            </div>
        </x-filament::section>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Συνολικές εκκρεμότητες</p>
                <p class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">{{ $totalItems }}</p>
            </div>
            <div class="rounded-xl border border-danger-200 bg-danger-50 p-5 shadow-sm dark:border-danger-500/30 dark:bg-danger-950/30">
                <p class="text-sm font-medium text-danger-700 dark:text-danger-300">Κρίσιμες ή εκπρόθεσμες</p>
                <p class="mt-2 text-3xl font-bold text-danger-700 dark:text-danger-300">{{ $criticalItems }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Τελευταία ενημέρωση</p>
                <p class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">{{ $updatedAt->format('d/m/Y H:i:s') }}</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            @forelse($sections as $section)
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-3">
                            <span>{{ $section['title'] }}</span>
                            <x-filament::badge :color="$section['critical'] > 0 ? 'danger' : ($section['count'] > 0 ? 'warning' : 'success')">{{ $section['count'] }}</x-filament::badge>
                        </div>
                    </x-slot>
                    <x-slot name="description">{{ $section['description'] }}</x-slot>

                    <div class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse($section['items'] as $item)
                            <a href="{{ $item['url'] }}" class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0 hover:text-primary-600">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $item['title'] }}</span>
                                    <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $item['subtitle'] }}</span>
                                </span>
                                <x-filament::badge :color="$item['color']">{{ $item['badge'] }}</x-filament::badge>
                            </a>
                        @empty
                            <p class="py-3 text-sm font-medium text-success-600">Δεν υπάρχουν εκκρεμότητες σε αυτή την ενότητα.</p>
                        @endforelse
                    </div>

                    <div class="mt-4 border-t border-gray-200 pt-4 text-right dark:border-white/10">
                        <a href="{{ $section['url'] }}" class="text-sm font-semibold text-primary-600 hover:text-primary-500">Προβολή ενότητας →</a>
                    </div>
                </x-filament::section>
            @empty
                <x-filament::section>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Δεν υπάρχουν διαθέσιμες ενότητες για τον λογαριασμό σας.</p>
                </x-filament::section>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
