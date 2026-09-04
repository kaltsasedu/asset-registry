<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetCustodian;
use App\Models\AssetDepreciationProfile;
use App\Models\AssetStatusTransaction;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\DepreciationBook;
use App\Models\FundingSource;
use App\Models\Location;
use App\Models\ProcurementContract;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class AssetSpreadsheetImportService
{
    private const MAX_ROWS = 5000;

    private const HEADER_ALIASES = [
        'κωδικός παγίου' => 'asset_code', 'asset code' => 'asset_code',
        'περιγραφή' => 'name', 'πάγιο' => 'name', 'name' => 'name',
        'αναλυτική περιγραφή' => 'description',
        'κατηγορία' => 'category', 'category' => 'category',
        'προμηθευτής' => 'supplier', 'supplier' => 'supplier',
        'τοποθεσία' => 'location', 'location' => 'location',
        'τμήμα' => 'department', 'department' => 'department',
        'υπεύθυνος' => 'custodian', 'υπεύθυνος παγίου' => 'custodian',
        'serial number' => 'serial_number', 'σειριακός αριθμός' => 'serial_number',
        'κατασκευαστής' => 'manufacturer', 'μοντέλο' => 'model',
        'ημερομηνία απόκτησης' => 'purchase_date', 'purchase date' => 'purchase_date',
        'έναρξη απόσβεσης' => 'depreciation_start_date',
        'αξία κτήσης' => 'purchase_value', 'purchase value' => 'purchase_value',
        'φπα' => 'vat_amount', 'υπολειμματική αξία' => 'residual_value',
        'συντελεστής απόσβεσης' => 'depreciation_rate',
        'μέθοδος απόσβεσης' => 'depreciation_method',
        'χρονισμός απόσβεσης' => 'depreciation_timing',
        'χρήσιμη ζωή' => 'useful_life_years',
        'σωρευμένες αποσβέσεις' => 'accumulated_depreciation',
        'αριθμός τιμολογίου' => 'invoice_number',
        'ημερομηνία τιμολογίου' => 'invoice_date',
        'κατάσταση' => 'status', 'status' => 'status',
        'βιβλίο αποσβέσεων' => 'depreciation_book',
        'σύμβαση' => 'procurement_contract',
        'πηγή χρηματοδότησης' => 'funding_source',
        'κέντρο κόστους' => 'cost_center',
    ];

    public function preview(string $absolutePath, string $mode): array
    {
        $parsed = $this->read($absolutePath);
        if ($parsed['global_errors']) return $parsed + ['valid_count' => 0, 'invalid_count' => 0];

        $references = $this->references();
        $seenCodes = [];
        $seenSerials = [];
        $rows = [];
        foreach ($parsed['rows'] as $row) {
            $rows[] = $this->validateAndMap($row['row_number'], $row['values'], $mode, $references, $seenCodes, $seenSerials);
        }

        return [
            'global_errors' => [],
            'rows' => $rows,
            'total_count' => count($rows),
            'valid_count' => count(array_filter($rows, fn (array $row): bool => $row['valid'])),
            'invalid_count' => count(array_filter($rows, fn (array $row): bool => ! $row['valid'])),
        ];
    }

    public function import(string $absolutePath, string $mode): array
    {
        $preview = $this->preview($absolutePath, $mode);
        $created = 0;
        $updated = 0;
        $failed = [];
        $successful = [];

        foreach ($preview['rows'] ?? [] as $row) {
            if (! $row['valid']) {
                $failed[] = ['row_number' => $row['row_number'], 'errors' => $row['errors']];
                continue;
            }

            try {
                DB::transaction(function () use ($row, &$created, &$updated, &$successful): void {
                    $data = $row['data'];
                    $existing = filled($data['asset_code'] ?? null) ? Asset::where('asset_code', $data['asset_code'])->first() : null;
                    $asset = $existing ?? new Asset();
                    $asset->fill($data);
                    $asset->save();

                    AssetDepreciationProfile::updateOrCreate([
                        'asset_id' => $asset->id,
                        'depreciation_book_id' => $asset->depreciation_book_id,
                    ], [
                        'depreciation_start_date' => $asset->depreciation_start_date,
                        'residual_value' => $asset->residual_value,
                        'depreciation_rate' => $asset->depreciation_rate,
                        'depreciation_method' => $asset->depreciation_method,
                        'depreciation_timing' => $asset->depreciation_timing,
                        'useful_life_years' => $asset->useful_life_years,
                        'accumulated_depreciation' => $asset->accumulated_depreciation,
                        'remaining_value' => $asset->remaining_value,
                        'is_active' => true,
                    ]);

                    if ($existing) {
                        $updated++;
                        $action = 'Ενημέρωση';
                    } else {
                        AssetStatusTransaction::create([
                            'asset_id' => $asset->id, 'transaction_type' => 'initial_registration', 'to_status' => $asset->status,
                            'reason' => 'Αρχική καταχώριση μέσω μαζικής εισαγωγής Excel.',
                            'purchase_value' => $asset->purchase_value, 'accumulated_depreciation' => $asset->accumulated_depreciation,
                            'book_value' => $asset->remaining_value, 'created_by' => auth()->id(),
                        ]);
                        $created++;
                        $action = 'Δημιουργία';
                    }

                    $successful[] = ['row_number' => $row['row_number'], 'action' => $action, 'asset_code' => $asset->asset_code, 'name' => $asset->name];
                });
            } catch (Throwable $exception) {
                $failed[] = ['row_number' => $row['row_number'], 'errors' => [$exception->getMessage()]];
            }
        }

        activity('Εισαγωγές Παγίων')->event('imported')->causedBy(auth()->user())->withProperties([
            'mode' => $mode, 'created' => $created, 'updated' => $updated, 'failed' => count($failed),
        ])->log('Ολοκληρώθηκε μαζική εισαγωγή παγίων από Excel.');

        return compact('created', 'updated', 'failed', 'successful') + ['total' => $preview['total_count'] ?? 0];
    }

    private function read(string $absolutePath): array
    {
        try {
            $sheet = IOFactory::load($absolutePath)->getActiveSheet();
            $rawRows = $sheet->toArray(null, true, true, false);
        } catch (Throwable $exception) {
            return ['global_errors' => ['Το αρχείο δεν μπορεί να διαβαστεί: ' . $exception->getMessage()], 'rows' => []];
        }

        while ($rawRows && ! array_filter($rawRows[0], fn ($value): bool => filled($value))) array_shift($rawRows);
        if (! $rawRows) return ['global_errors' => ['Το αρχείο δεν περιέχει δεδομένα.'], 'rows' => []];

        $headerRow = array_shift($rawRows);
        $columns = [];
        foreach ($headerRow as $index => $header) {
            $canonical = self::HEADER_ALIASES[$this->normalize((string) $header)] ?? null;
            if ($canonical) $columns[$index] = $canonical;
        }

        $missing = array_diff(['name', 'category', 'purchase_date', 'purchase_value'], array_values($columns));
        $labels = ['name' => 'Περιγραφή', 'category' => 'Κατηγορία', 'purchase_date' => 'Ημερομηνία απόκτησης', 'purchase_value' => 'Αξία κτήσης'];
        if ($missing) return ['global_errors' => ['Λείπουν υποχρεωτικές κεφαλίδες: ' . implode(', ', array_map(fn (string $key): string => $labels[$key], $missing)) . '. Κατεβάστε και χρησιμοποιήστε το πρότυπο Excel.'], 'rows' => []];

        $rows = [];
        foreach ($rawRows as $offset => $rawRow) {
            if (! array_filter($rawRow, fn ($value): bool => filled($value))) continue;
            if (count($rows) >= self::MAX_ROWS) return ['global_errors' => ['Το αρχείο υπερβαίνει το όριο των ' . self::MAX_ROWS . ' γραμμών.'], 'rows' => []];
            $values = [];
            foreach ($columns as $index => $key) $values[$key] = $rawRow[$index] ?? null;
            $rows[] = ['row_number' => $offset + 2, 'values' => $values];
        }

        return ['global_errors' => [], 'rows' => $rows];
    }

    private function validateAndMap(int $rowNumber, array $values, string $mode, array $references, array &$seenCodes, array &$seenSerials): array
    {
        $errors = [];
        $code = trim((string) ($values['asset_code'] ?? ''));
        $name = trim((string) ($values['name'] ?? ''));
        if ($name === '') $errors[] = 'Η περιγραφή είναι υποχρεωτική.';

        $existing = $code !== '' ? Asset::where('asset_code', $code)->first() : null;
        if ($mode === 'create_only' && $existing) $errors[] = "Ο κωδικός {$code} υπάρχει ήδη.";
        if ($mode === 'update_existing' && $code === '') $errors[] = 'Ο κωδικός παγίου είναι υποχρεωτικός για ενημέρωση.';
        if ($mode === 'update_existing' && $code !== '' && ! $existing) $errors[] = "Ο κωδικός {$code} δεν βρέθηκε.";
        if ($code !== '' && isset($seenCodes[$this->normalize($code)])) $errors[] = "Ο κωδικός {$code} επαναλαμβάνεται στο αρχείο.";
        if ($code !== '') $seenCodes[$this->normalize($code)] = true;

        $category = $this->reference($references['categories'], $values['category'] ?? null);
        if (! $category) $errors[] = 'Η κατηγορία δεν βρέθηκε.';
        $supplier = $this->optionalReference($references['suppliers'], $values['supplier'] ?? null, 'Ο προμηθευτής δεν βρέθηκε.', $errors);
        $location = $this->optionalReference($references['locations'], $values['location'] ?? null, 'Η τοποθεσία δεν βρέθηκε.', $errors);
        $department = $this->optionalReference($references['departments'], $values['department'] ?? null, 'Το τμήμα δεν βρέθηκε.', $errors);
        $custodian = $this->optionalReference($references['custodians'], $values['custodian'] ?? null, 'Ο υπεύθυνος δεν βρέθηκε.', $errors);
        $contract = $this->optionalReference($references['contracts'], $values['procurement_contract'] ?? null, 'Η σύμβαση δεν βρέθηκε.', $errors);
        $funding = $this->optionalReference($references['funding_sources'], $values['funding_source'] ?? null, 'Η πηγή χρηματοδότησης δεν βρέθηκε.', $errors);
        $costCenter = $this->optionalReference($references['cost_centers'], $values['cost_center'] ?? null, 'Το κέντρο κόστους δεν βρέθηκε.', $errors);

        if ($contract) {
            if ($supplier && (int) $supplier->id !== (int) $contract->supplier_id) $errors[] = 'Ο προμηθευτής δεν συμφωνεί με τη σύμβαση.';
            if ($funding && $contract->funding_source_id && (int) $funding->id !== (int) $contract->funding_source_id) $errors[] = 'Η πηγή χρηματοδότησης δεν συμφωνεί με τη σύμβαση.';
            if ($costCenter && $contract->cost_center_id && (int) $costCenter->id !== (int) $contract->cost_center_id) $errors[] = 'Το κέντρο κόστους δεν συμφωνεί με τη σύμβαση.';
            $supplier ??= $contract->supplier;
            $funding ??= $contract->fundingSource;
            $costCenter ??= $contract->costCenter;
        }

        $book = $this->reference($references['books'], $values['depreciation_book'] ?? null);
        if (filled($values['depreciation_book'] ?? null) && ! $book) $errors[] = 'Το βιβλίο αποσβέσεων δεν βρέθηκε ή είναι ανενεργό.';
        $book ??= $references['default_book'];
        if (! $book) $errors[] = 'Δεν υπάρχει ενεργό βιβλίο αποσβέσεων.';

        $purchaseDate = $this->date($values['purchase_date'] ?? null);
        if (! $purchaseDate) $errors[] = 'Η ημερομηνία απόκτησης δεν είναι έγκυρη.';
        $depreciationStart = $this->date($values['depreciation_start_date'] ?? null) ?? $purchaseDate;
        $invoiceDate = $this->date($values['invoice_date'] ?? null);
        if (filled($values['invoice_date'] ?? null) && ! $invoiceDate) $errors[] = 'Η ημερομηνία τιμολογίου δεν είναι έγκυρη.';

        $purchaseValue = $this->number($values['purchase_value'] ?? null);
        if ($purchaseValue === null || $purchaseValue < 0) $errors[] = 'Η αξία κτήσης δεν είναι έγκυρη.';
        $vat = $this->number($values['vat_amount'] ?? null) ?? 0;
        $residual = $this->number($values['residual_value'] ?? null) ?? 0;
        $rate = $this->number($values['depreciation_rate'] ?? null) ?? (float) ($category?->default_rate ?? 0);
        $accumulated = $this->number($values['accumulated_depreciation'] ?? null) ?? 0;
        if ($purchaseValue !== null && ($residual < 0 || $residual > $purchaseValue)) $errors[] = 'Η υπολειμματική αξία είναι εκτός ορίων.';
        if ($purchaseValue !== null && ($accumulated < 0 || $accumulated > $purchaseValue - $residual + 0.005)) $errors[] = 'Οι σωρευμένες αποσβέσεις είναι εκτός ορίων.';

        $methodMap = ['σταθερή' => 'straight_line', 'straight_line' => 'straight_line', 'φθίνουσα' => 'declining_balance', 'declining_balance' => 'declining_balance', 'μηδενική' => 'none', 'none' => 'none'];
        $timingMap = ['πλήρης μήνας' => 'full_month', 'full_month' => 'full_month', 'από τον επόμενο μήνα' => 'next_month', 'next_month' => 'next_month'];
        $statusMap = array_flip(AssetStatusTransaction::STATUSES) + array_combine(array_keys(AssetStatusTransaction::STATUSES), array_keys(AssetStatusTransaction::STATUSES));
        if (filled($values['depreciation_method'] ?? null) && ! $this->hasMappedValue($values['depreciation_method'], $methodMap)) $errors[] = 'Η μέθοδος απόσβεσης δεν αναγνωρίζεται.';
        if (filled($values['depreciation_timing'] ?? null) && ! $this->hasMappedValue($values['depreciation_timing'], $timingMap)) $errors[] = 'Ο χρονισμός απόσβεσης δεν αναγνωρίζεται.';
        if (filled($values['status'] ?? null) && ! $this->hasMappedValue($values['status'], $statusMap)) $errors[] = 'Η κατάσταση παγίου δεν αναγνωρίζεται.';
        $method = $this->mappedValue($values['depreciation_method'] ?? null, $methodMap, $category?->default_depreciation_method ?? 'straight_line');
        $timing = $this->mappedValue($values['depreciation_timing'] ?? null, $timingMap, $category?->default_depreciation_timing ?? 'full_month');
        $status = $this->mappedValue($values['status'] ?? null, $statusMap, 'in_use');
        $life = (int) ($this->number($values['useful_life_years'] ?? null) ?? ($rate > 0 ? round(100 / $rate) : 1));
        if ($life < 1) $errors[] = 'Η χρήσιμη ζωή πρέπει να είναι τουλάχιστον 1 έτος.';

        $serial = trim((string) ($values['serial_number'] ?? '')) ?: null;
        if ($serial) {
            $serialKey = $this->normalize($serial);
            if (isset($seenSerials[$serialKey])) $errors[] = "Το serial number {$serial} επαναλαμβάνεται στο αρχείο.";
            $duplicate = Asset::where('serial_number', $serial)->when($existing, fn ($query) => $query->whereKeyNot($existing->id))->exists();
            if ($duplicate) $errors[] = "Το serial number {$serial} χρησιμοποιείται ήδη.";
            $seenSerials[$serialKey] = true;
        }

        $data = [
            'asset_code' => $code ?: null, 'name' => $name, 'description' => $values['description'] ?? null,
            'asset_category_id' => $category?->id, 'supplier_id' => $supplier?->id, 'location_id' => $location?->id,
            'department_id' => $department?->id, 'asset_custodian_id' => $custodian?->id,
            'procurement_contract_id' => $contract?->id, 'funding_source_id' => $funding?->id, 'cost_center_id' => $costCenter?->id,
            'serial_number' => $serial, 'manufacturer' => $values['manufacturer'] ?? null, 'model' => $values['model'] ?? null,
            'purchase_date' => $purchaseDate, 'depreciation_start_date' => $depreciationStart,
            'purchase_value' => $purchaseValue, 'vat_amount' => $vat, 'residual_value' => $residual,
            'depreciation_rate' => $rate, 'depreciation_method' => $method, 'depreciation_timing' => $timing,
            'depreciation_book_id' => $book?->id, 'useful_life_years' => $life, 'accumulated_depreciation' => $accumulated,
            'book_value' => ($purchaseValue ?? 0) - $accumulated, 'invoice_number' => $values['invoice_number'] ?? null,
            'invoice_date' => $invoiceDate, 'status' => $status,
        ];

        return ['row_number' => $rowNumber, 'valid' => $errors === [], 'errors' => $errors, 'data' => $data, 'display' => ['asset_code' => $code ?: 'Αυτόματος', 'name' => $name, 'category' => trim((string) ($values['category'] ?? '')), 'serial_number' => $serial, 'purchase_value' => $purchaseValue]];
    }

    private function references(): array
    {
        return [
            'categories' => $this->index(AssetCategory::all(), ['name']),
            'suppliers' => $this->index(Supplier::all(), ['name', 'afm']),
            'locations' => $this->index(Location::all(), ['name']),
            'departments' => $this->index(Department::all(), ['name']),
            'custodians' => $this->index(AssetCustodian::all(), ['name', 'email']),
            'books' => $this->index(DepreciationBook::where('is_active', true)->get(), ['name', 'code']),
            'default_book' => DepreciationBook::where('is_default', true)->first(),
            'contracts' => $this->index(ProcurementContract::with(['supplier', 'fundingSource', 'costCenter'])->get(), ['contract_number', 'title']),
            'funding_sources' => $this->index(FundingSource::where('is_active', true)->get(), ['name', 'code', 'project_code', 'ops_code']),
            'cost_centers' => $this->index(CostCenter::where('is_active', true)->get(), ['name', 'code']),
        ];
    }

    private function index($records, array $fields): array
    {
        $index = [];
        foreach ($records as $record) foreach ($fields as $field) if (filled($record->{$field})) $index[$this->normalize((string) $record->{$field})] = $record;
        return $index;
    }

    private function reference(array $index, mixed $value): mixed
    {
        if (! filled($value)) return null;
        return $index[$this->normalize((string) $value)] ?? null;
    }

    private function optionalReference(array $index, mixed $value, string $message, array &$errors): mixed
    {
        if (! filled($value)) return null;
        $record = $this->reference($index, $value);
        if (! $record) $errors[] = $message;
        return $record;
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));
    }

    private function number(mixed $value): ?float
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return (float) $value;
        $normalized = str_replace(["\u{00A0}", ' ', '€', '%'], '', (string) $value);
        if (str_contains($normalized, ',') && str_contains($normalized, '.')) $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);
        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, trim((string) $value));
                if ($date !== false) return $date->format('Y-m-d');
            } catch (Throwable) {
                continue;
            }
        }
        return null;
    }

    private function mappedValue(mixed $value, array $map, string $default): string
    {
        if (! filled($value)) return $default;
        $normalizedMap = [];
        foreach ($map as $key => $mapped) $normalizedMap[$this->normalize((string) $key)] = $mapped;
        return $normalizedMap[$this->normalize((string) $value)] ?? $default;
    }

    private function hasMappedValue(mixed $value, array $map): bool
    {
        $needle = $this->normalize((string) $value);
        foreach (array_keys($map) as $key) if ($this->normalize((string) $key) === $needle) return true;
        return false;
    }
}
