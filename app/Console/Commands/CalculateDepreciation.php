<?php
namespace App\Console\Commands;
use App\Models\DepreciationBook;
use App\Models\DepreciationPeriod;
use App\Services\DepreciationService;
use Illuminate\Console\Command;

class CalculateDepreciation extends Command
{
    protected $signature = 'assets:calculate-depreciation {--year=} {--month=} {--book=} {--preview : Δημιουργεί μόνο προεπισκόπηση} {--lock : Κλειδώνει μετά την οριστικοποίηση}';
    protected $description = 'Υπολογίζει ή προεπισκοπεί τις μηνιαίες αποσβέσεις ανά βιβλίο.';

    public function handle(DepreciationService $service): int
    {
        $book = $this->option('book') ? DepreciationBook::where('code', $this->option('book'))->first() : DepreciationBook::where('is_default', true)->first();
        if (! $book) { $this->error('Δεν βρέθηκε βιβλίο αποσβέσεων.'); return self::FAILURE; }
        $period = DepreciationPeriod::firstOrCreate(['depreciation_book_id' => $book->id, 'year' => (int) ($this->option('year') ?: now()->year), 'month' => (int) ($this->option('month') ?: now()->month)]);
        $entries = $service->preview($period);
        if ($this->option('preview')) { $this->info("Προεπισκόπηση: {$entries->count()} εγγραφές."); return self::SUCCESS; }
        $count = $service->post($period);
        if ($this->option('lock')) $service->lock($period->fresh());
        $this->info("Οριστικοποιήθηκαν {$count} αποσβέσεις στο βιβλίο {$book->name}.");
        return self::SUCCESS;
    }
}
