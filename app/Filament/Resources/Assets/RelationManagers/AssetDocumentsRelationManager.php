<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use App\Models\AssetDocument;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';
    protected static ?string $title = 'Δικαιολογητικά Παγίων';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('document_type')->label('Είδος δικαιολογητικού')->options(AssetDocument::TYPES)->required(),
            TextInput::make('title')->label('Τίτλος / περιγραφή')->required()->maxLength(255),
            TextInput::make('document_number')->label('Αριθμός εγγράφου ή πρωτοκόλλου')->maxLength(150),
            DatePicker::make('document_date')->label('Ημερομηνία εγγράφου'),
            FileUpload::make('file_path')->label('Αρχείο')->disk('local')->directory('asset-documents')->preserveFilenames()->required()->columnSpanFull(),
            Textarea::make('notes')->label('Παρατηρήσεις')->maxLength(2000)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('document_type')->label('Είδος')->badge()->formatStateUsing(fn (string $state): string => AssetDocument::TYPES[$state] ?? $state),
            TextColumn::make('title')->label('Τίτλος')->searchable(),
            TextColumn::make('document_number')->label('Πρωτόκολλο')->toggleable(),
            TextColumn::make('document_date')->label('Ημερομηνία')->date('d/m/Y')->sortable(),
            TextColumn::make('file_path')->label('Αρχείο')->formatStateUsing(fn (): string => 'Άνοιγμα')
                ->url(fn (AssetDocument $record): string => route('asset-documents.download', $record))->openUrlInNewTab(),
        ])->headerActions([
            CreateAction::make()->label('Νέο δικαιολογητικό')->mutateDataUsing(function (array $data): array { $data['created_by'] = auth()->id(); return $data; }),
        ])->recordActions([
            EditAction::make()->label('Επεξεργασία'),
            DeleteAction::make(),
        ])->defaultSort('id', 'desc');
    }
}
