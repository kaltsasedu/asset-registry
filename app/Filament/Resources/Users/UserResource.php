<?php

namespace App\Filament\Resources\Users;

use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'users';
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return 'Χρήστες';
    }

    public static function getModelLabel(): string
    {
        return 'Χρήστης';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Χρήστες';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Ρυθμίσεις';
    }

    public static function getNavigationSort(): ?int { return 1; }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Ονοματεπώνυμο')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Ηλεκτρονική Διεύθυνση')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('password')
                    ->label('Κωδικός Πρόσβασης')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),

                CheckboxList::make('roles')
                    ->label('Ρόλοι')
                    ->relationship('roles', 'name')
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Περιορισμός πρόσβασης στα δεδομένα')
                    ->description('Τα δικαιώματα του ρόλου καθορίζουν τις ενέργειες. Η περιοχή δεδομένων καθορίζει σε ποια πάγια εφαρμόζονται.')
                    ->schema([
                        Select::make('data_scope')
                            ->label('Περιοχή δεδομένων')
                            ->options([
                                'all' => 'Πλήρης πρόσβαση σε όλα τα δεδομένα',
                                'restricted' => 'Περιορισμένη πρόσβαση',
                            ])
                            ->default('all')
                            ->required()
                            ->live()
                            ->disabled(fn (): bool => ! static::canManageDataScopes()),

                        Select::make('permitted_departments')
                            ->label('Επιτρεπόμενα τμήματα')
                            ->relationship(name: 'permittedDepartments', titleAttribute: 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('data_scope') === 'restricted')
                            ->disabled(fn (): bool => ! static::canManageDataScopes()),

                        Select::make('permitted_locations')
                            ->label('Επιτρεπόμενες τοποθεσίες')
                            ->relationship(name: 'permittedLocations', titleAttribute: 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('data_scope') === 'restricted')
                            ->disabled(fn (): bool => ! static::canManageDataScopes()),

                        Select::make('permitted_assets')
                            ->label('Συγκεκριμένα επιτρεπόμενα πάγια')
                            ->relationship(name: 'permittedAssets', titleAttribute: 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->asset_code} — {$record->name}")
                            ->multiple()
                            ->searchable(['asset_code', 'name'])
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('data_scope') === 'restricted')
                            ->disabled(fn (): bool => ! static::canManageDataScopes()),

                        Toggle::make('include_responsible_assets')
                            ->label('Να περιλαμβάνονται τα πάγια ευθύνης του χρήστη')
                            ->helperText('Ισχύει όταν ο υπεύθυνος παγίου είναι συνδεδεμένος με αυτόν τον χρήστη.')
                            ->visible(fn (Get $get): bool => $get('data_scope') === 'restricted')
                            ->disabled(fn (): bool => ! static::canManageDataScopes()),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Ονοματεπώνυμο')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Ηλεκτρονική Διεύθυνση')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label('Ρόλοι')
                    ->badge(),

                TextColumn::make('data_scope')
                    ->label('Πρόσβαση δεδομένων')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === 'restricted' ? 'Περιορισμένη' : 'Πλήρης'),

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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function canManageDataScopes(): bool
    {
        return auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('users.manage_data_scope');
    }
}
