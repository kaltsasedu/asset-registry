<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use App\Models\Asset;
use App\Models\AssetStatusTransaction;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssetDisposalsRelationManager extends RelationManager
{
    protected static string $relationship = 'statusTransactions';
    protected static ?string $title = 'Διαγραφές & Μεταβιβάσεις';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Στοιχεία πράξης')->columns(2)->schema([
                Select::make('transaction_type')->label('Ενέργεια')->options(AssetStatusTransaction::DISPOSAL_TYPES)->required(),
                TextInput::make('document_number')->label('Αριθμός πρωτοκόλλου ή εγγράφου')->required()->maxLength(150),
                Textarea::make('reason')->label('Αιτία / απόφαση')->required()->maxLength(2000)->columnSpanFull(),
                DatePicker::make('document_date')->label('Ημερομηνία πρωτοκόλλου')->required()->default(now()),
                FileUpload::make('document_path')->label('Σχετικό πρωτόκολλο / έγγραφο')->disk('local')->directory('asset-disposals')->preserveFilenames()->required()->columnSpanFull(),
            ]),
            Section::make('Οικονομικά στοιχεία')->columns(2)->schema([
                TextInput::make('purchase_value')->label('Αξία κτήσης')->numeric()->suffix('€')->disabled()->dehydrated(),
                TextInput::make('accumulated_depreciation')->label('Σωρευμένες αποσβέσεις')->numeric()->suffix('€')->disabled()->dehydrated(),
                TextInput::make('book_value')->label('Αναπόσβεστη αξία')->numeric()->suffix('€')->disabled()->dehydrated(),
                TextInput::make('disposal_proceeds')->label('Έσοδο από πώληση')->numeric()->suffix('€')->default(0)->required(),
                Textarea::make('notes')->label('Παρατηρήσεις')->maxLength(2000)->columnSpanFull(),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('transaction_type', array_keys(AssetStatusTransaction::DISPOSAL_TYPES)))
            ->columns([
                TextColumn::make('transaction_type')->label('Ενέργεια')->badge()->formatStateUsing(fn (string $state): string => AssetStatusTransaction::DISPOSAL_TYPES[$state] ?? $state),
                TextColumn::make('document_number')->label('Πρωτόκολλο')->searchable(),
                TextColumn::make('document_date')->label('Ημερομηνία')->date('d/m/Y')->sortable(),
                TextColumn::make('book_value')->label('Αναπόσβεστη αξία')->money('EUR'),
                TextColumn::make('disposal_proceeds')->label('Έσοδο')->money('EUR'),
                TextColumn::make('gain_loss')->label('Κέρδος / Ζημία')->money('EUR'),
            ])->headerActions([
                CreateAction::make()->label('Νέα πράξη')
                    ->mutateDataUsing(fn (array $data): array => $this->prepareTransactionData($data))
                    ->after(function (): void {
                        $transaction = $this->getOwnerRecord()->statusTransactions()->latest('id')->first();
                        $this->getOwnerRecord()->update(['status' => $transaction->to_status]);
                    }),
            ])->recordActions([
                EditAction::make()->label('Επεξεργασία')
                    ->mutateDataUsing(fn (array $data): array => $this->prepareTransactionData($data)),
            ])->defaultSort('id', 'desc');
    }

    private function prepareTransactionData(array $data): array
    {
        /** @var Asset $asset */
        $asset = $this->getOwnerRecord();
        $data['from_status'] = $asset->status;
        $data['to_status'] = $data['transaction_type'] === 'sale' ? 'sold' : 'retired';
        $data['purchase_value'] = $asset->purchase_value;
        $data['accumulated_depreciation'] = $asset->accumulated_depreciation;
        $data['book_value'] = $asset->remaining_value;
        $data['gain_loss'] = round((float) $data['disposal_proceeds'] - (float) $asset->remaining_value, 2);
        $data['created_by'] = auth()->id();

        return $data;
    }
}
