<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Models\Role;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'roles';
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return 'Ρόλοι και Δικαιώματα';
    }

    public static function getModelLabel(): string
    {
        return 'Ρόλος';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Ρόλοι και Δικαιώματα';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Ρυθμίσεις';
    }

    public static function getNavigationSort(): ?int { return 2; }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Όνομα Ρόλου')
                    ->required()
                    ->maxLength(255),

                TextInput::make('guard_name')
                    ->default('web')
                    ->hidden()
                    ->dehydrated(),

                Section::make('Παρακολούθηση')
                    ->description('Δικαιώματα για πάγια και μετακινήσεις παγίων.')
                    ->schema([
                        \Filament\Forms\Components\CheckboxList::make('monitoring_permissions')
                            ->label('Επιλογή δικαιωμάτων')
                            ->options(static::monitoringPermissions())
                            ->columns(2)
                            ->bulkToggleable(),
                    ]),

                Section::make('Μητρώα')
                    ->description('Δικαιώματα για τα βασικά μητρώα της εφαρμογής.')
                    ->schema([
                        \Filament\Forms\Components\CheckboxList::make('registry_permissions')
                            ->label('Επιλογή δικαιωμάτων')
                            ->options(static::registryPermissions())
                            ->columns(2)
                            ->bulkToggleable(),
                    ]),

                Section::make('Ρυθμίσεις')
                    ->description('Δικαιώματα για χρήστες και ρόλους.')
                    ->schema([
                        \Filament\Forms\Components\CheckboxList::make('settings_permissions')
                            ->label('Επιλογή δικαιωμάτων')
                            ->options(static::settingsPermissions())
                            ->columns(2)
                            ->bulkToggleable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ρόλος')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('permissions.name')
                    ->label('Δικαιώματα')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Δημιουργία')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function monitoringPermissions(): array
    {
        return [
            'action-center.view' => 'Προβολή Κέντρου Ενεργειών και Εκκρεμοτήτων',
            'assets.view' => 'Προβολή παγίων',
            'assets.create' => 'Καταχώριση παγίων',
            'assets.update' => 'Επεξεργασία παγίων',
            'assets.delete' => 'Διαγραφή παγίων',
            'assets.export' => 'Εξαγωγή Excel / PDF παγίων',
            'assets.status_change' => 'Μεταβολή κατάστασης παγίων',
            'assets.import' => 'Μαζική εισαγωγή και ενημέρωση παγίων από Excel',
            'assets.qr_labels' => 'Μαζική δημιουργία και εκτύπωση ετικετών QR',
            'assets.dossier_export' => 'Εξαγωγή ηλεκτρονικού φακέλου παγίου σε PDF / ZIP',
            'asset-movements.view' => 'Προβολή μετακινήσεων',
            'asset-movements.create' => 'Καταχώριση μετακινήσεων',
            'asset-movements.update' => 'Επεξεργασία μετακινήσεων',
            'asset-movements.delete' => 'Διαγραφή μετακινήσεων',
            'asset-movements.submit' => 'Υποβολή μετακίνησης προς έγκριση',
            'asset-movements.approve' => 'Έγκριση μετακίνησης',
            'asset-movements.confirm_delivery' => 'Ηλεκτρονική επιβεβαίωση παράδοσης',
            'asset-movements.confirm_receipt' => 'Ηλεκτρονική επιβεβαίωση παραλαβής',
            'asset-movements.complete' => 'Ολοκλήρωση μετακίνησης και ενημέρωση μητρώου',
            'asset-movements.export' => 'Εξαγωγή Excel μετακινήσεων',
            'inventory.view' => 'Προβολή φυσικών απογραφών',
            'inventory.create' => 'Δημιουργία φυσικών απογραφών',
            'inventory.update' => 'Τροποποίηση φυσικών απογραφών',
            'inventory.delete' => 'Διαγραφή φυσικών απογραφών',
            'inventory.perform' => 'Σάρωση και οριστικοποίηση απογραφής',
            'asset-documents.view' => 'Προβολή δικαιολογητικών',
            'asset-documents.create' => 'Επισύναψη δικαιολογητικών',
            'asset-documents.update' => 'Επεξεργασία δικαιολογητικών',
            'asset-documents.delete' => 'Διαγραφή δικαιολογητικών',
            'asset-disposals.view' => 'Προβολή διαγραφών και μεταβιβάσεων',
            'asset-disposals.create' => 'Απόσυρση, πώληση, καταστροφή ή δωρεά',
            'depreciation-periods.view' => 'Προβολή αποσβέσεων',
            'depreciation-periods.create' => 'Δημιουργία περιόδου αποσβέσεων',
            'depreciation-periods.update' => 'Τροποποίηση πρόχειρης περιόδου',
            'depreciation-periods.delete' => 'Διαγραφή πρόχειρης περιόδου',
            'depreciation-periods.preview' => 'Προεπισκόπηση αποσβέσεων',
            'depreciation-periods.post' => 'Οριστικοποίηση αποσβέσεων',
            'depreciation-periods.lock' => 'Κλείδωμα περιόδου αποσβέσεων',
            'asset-maintenances.view' => 'Προβολή συντηρήσεων',
            'asset-maintenances.create' => 'Καταχώριση συντηρήσεων',
            'asset-maintenances.update' => 'Τροποποίηση συντηρήσεων',
            'asset-maintenances.delete' => 'Διαγραφή συντηρήσεων',
            'asset-insurances.view' => 'Προβολή ασφαλίσεων',
            'asset-insurances.create' => 'Καταχώριση ασφαλίσεων',
            'asset-insurances.update' => 'Τροποποίηση ασφαλίσεων',
            'asset-insurances.delete' => 'Διαγραφή ασφαλίσεων',
            'saved-asset-searches.view' => 'Προβολή αποθηκευμένων αναζητήσεων',
            'saved-asset-searches.create' => 'Δημιουργία αποθηκευμένων αναζητήσεων',
            'saved-asset-searches.update' => 'Τροποποίηση αποθηκευμένων αναζητήσεων',
            'saved-asset-searches.delete' => 'Διαγραφή αποθηκευμένων αναζητήσεων',
            'asset-receipts.view' => 'Προβολή παραλαβών παγίων',
            'asset-receipts.create' => 'Δημιουργία παραλαβών παγίων',
            'asset-receipts.update' => 'Τροποποίηση πρόχειρων παραλαβών',
            'asset-receipts.delete' => 'Διαγραφή πρόχειρων παραλαβών',
            'asset-receipts.capitalize' => 'Κεφαλαιοποίηση παραλαβών σε πάγια',
            'asset-receipts.export' => 'Εξαγωγή παραλαβών παγίων',
        ];
    }

    public static function registryPermissions(): array
    {
        return [
            'suppliers.view' => 'Προβολή προμηθευτών', 'suppliers.create' => 'Δημιουργία προμηθευτών', 'suppliers.update' => 'Τροποποίηση προμηθευτών', 'suppliers.delete' => 'Διαγραφή προμηθευτών',
            'asset-categories.view' => 'Προβολή κατηγοριών παγίων', 'asset-categories.create' => 'Δημιουργία κατηγοριών', 'asset-categories.update' => 'Τροποποίηση κατηγοριών', 'asset-categories.delete' => 'Διαγραφή κατηγοριών',
            'locations.view' => 'Προβολή τοποθεσιών', 'locations.create' => 'Δημιουργία τοποθεσιών', 'locations.update' => 'Τροποποίηση τοποθεσιών', 'locations.delete' => 'Διαγραφή τοποθεσιών',
            'departments.view' => 'Προβολή τμημάτων', 'departments.create' => 'Δημιουργία τμημάτων', 'departments.update' => 'Τροποποίηση τμημάτων', 'departments.delete' => 'Διαγραφή τμημάτων',
            'asset-custodians.view' => 'Προβολή υπευθύνων παγίων', 'asset-custodians.create' => 'Δημιουργία υπευθύνων', 'asset-custodians.update' => 'Τροποποίηση υπευθύνων', 'asset-custodians.delete' => 'Διαγραφή υπευθύνων',
            'procurement-contracts.view' => 'Προβολή συμβάσεων', 'procurement-contracts.create' => 'Δημιουργία συμβάσεων', 'procurement-contracts.update' => 'Τροποποίηση συμβάσεων', 'procurement-contracts.delete' => 'Διαγραφή συμβάσεων', 'procurement-contracts.export' => 'Εξαγωγή συμβάσεων',
            'funding-sources.view' => 'Προβολή πηγών χρηματοδότησης', 'funding-sources.create' => 'Δημιουργία πηγών χρηματοδότησης', 'funding-sources.update' => 'Τροποποίηση πηγών χρηματοδότησης', 'funding-sources.delete' => 'Διαγραφή πηγών χρηματοδότησης', 'funding-sources.export' => 'Εξαγωγή πηγών χρηματοδότησης',
            'cost-centers.view' => 'Προβολή κέντρων κόστους', 'cost-centers.create' => 'Δημιουργία κέντρων κόστους', 'cost-centers.update' => 'Τροποποίηση κέντρων κόστους', 'cost-centers.delete' => 'Διαγραφή κέντρων κόστους', 'cost-centers.export' => 'Εξαγωγή κέντρων κόστους',
        ];
    }

    public static function settingsPermissions(): array
    {
        return [
            'users.view' => 'Προβολή χρηστών', 'users.create' => 'Δημιουργία χρηστών', 'users.update' => 'Τροποποίηση χρηστών', 'users.delete' => 'Διαγραφή χρηστών',
            'users.manage_data_scope' => 'Ορισμός περιοχής πρόσβασης χρηστών',
            'roles.view' => 'Προβολή ρόλων', 'roles.create' => 'Δημιουργία ρόλων', 'roles.update' => 'Τροποποίηση ρόλων και δικαιωμάτων', 'roles.delete' => 'Διαγραφή ρόλων',
            'activity-logs.view' => 'Προβολή ιστορικού αλλαγών',
            'depreciation-books.view' => 'Προβολή βιβλίων αποσβέσεων',
            'depreciation-books.create' => 'Δημιουργία βιβλίων αποσβέσεων',
            'depreciation-books.update' => 'Τροποποίηση βιβλίων αποσβέσεων',
            'depreciation-books.delete' => 'Διαγραφή βιβλίων αποσβέσεων',
            'depreciation-periods.override_locked' => 'Ειδική παράκαμψη κλειδωμένης οικονομικής περιόδου',
            'application-settings.manage' => 'Διαχείριση ρυθμίσεων φορέα και εφαρμογής',
            'system-backups.view' => 'Προβολή αντιγράφων ασφαλείας',
            'system-backups.create' => 'Δημιουργία αντιγράφων ασφαλείας',
            'system-backups.download' => 'Λήψη αντιγράφων ασφαλείας',
            'system-backups.verify' => 'Έλεγχος ακεραιότητας αντιγράφων',
            'system-backups.restore' => 'Επαναφορά βάσης και συνημμένων',
            'system-backups.delete' => 'Διαγραφή αντιγράφων ασφαλείας',
        ];
    }
}
