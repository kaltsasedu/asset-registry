<?php
namespace App\Filament\Resources\SavedAssetSearches;
use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\Assets\AssetResource;
use App\Filament\Resources\SavedAssetSearches\Pages\CreateSavedAssetSearch;
use App\Filament\Resources\SavedAssetSearches\Pages\EditSavedAssetSearch;
use App\Filament\Resources\SavedAssetSearches\Pages\ListSavedAssetSearches;
use App\Models\AssetStatusTransaction;
use App\Models\SavedAssetSearch;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
class SavedAssetSearchResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'saved-asset-searches';
    protected static ?string $model=SavedAssetSearch::class; protected static string|BackedEnum|null $navigationIcon=Heroicon::OutlinedMagnifyingGlass;
    public static function getNavigationLabel(): string{return 'Αποθηκευμένες Αναζητήσεις';} public static function getModelLabel():string{return 'Αποθηκευμένη Αναζήτηση';} public static function getPluralModelLabel():string{return 'Αποθηκευμένες Αναζητήσεις';} public static function getNavigationGroup():?string{return 'Παρακολούθηση';} public static function getNavigationSort():?int{return 6;}
    public static function getEloquentQuery(): Builder{return parent::getEloquentQuery()->where('user_id',auth()->id());}
    public static function form(Schema $schema):Schema{return $schema->components([TextInput::make('name')->label('Ονομασία αναζήτησης')->required()->maxLength(100),Select::make('status')->label('Κατάσταση')->options(AssetStatusTransaction::STATUSES),Select::make('asset_category_id')->label('Κατηγορία')->relationship('category','name')->searchable()->preload(),Select::make('location_id')->label('Τοποθεσία')->relationship('location','name')->searchable()->preload(),Select::make('department_id')->label('Τμήμα')->relationship('department','name')->searchable()->preload(),Checkbox::make('without_custodian')->label('Χωρίς υπεύθυνο'),Checkbox::make('without_serial')->label('Χωρίς serial number'),Checkbox::make('without_invoice')->label('Χωρίς τιμολόγιο'),Checkbox::make('without_location')->label('Χωρίς τοποθεσία')]);}
    public static function table(Table $table):Table{return $table->columns([TextColumn::make('name')->label('Αναζήτηση')->searchable(),TextColumn::make('status')->label('Κατάσταση')->formatStateUsing(fn($s)=>AssetStatusTransaction::STATUSES[$s]??'Όλες'),TextColumn::make('category.name')->label('Κατηγορία'),TextColumn::make('location.name')->label('Τοποθεσία'),TextColumn::make('department.name')->label('Τμήμα')])->recordActions([Action::make('apply')->label('Εφαρμογή')->icon('heroicon-o-play')->url(fn(SavedAssetSearch $r)=>AssetResource::getUrl('index',['tableFilters'=>array_filter(['status'=>$r->status?['value'=>$r->status]:null,'asset_category_id'=>$r->asset_category_id?['value'=>$r->asset_category_id]:null,'location_id'=>$r->location_id?['value'=>$r->location_id]:null,'department_id'=>$r->department_id?['value'=>$r->department_id]:null,'without_custodian'=>$r->without_custodian?['isActive'=>true]:null,'without_serial'=>$r->without_serial?['isActive'=>true]:null,'without_invoice'=>$r->without_invoice?['isActive'=>true]:null,'without_location'=>$r->without_location?['isActive'=>true]:null])])),EditAction::make()]);}
    public static function getPages():array{return ['index'=>ListSavedAssetSearches::route('/'),'create'=>CreateSavedAssetSearch::route('/create'),'edit'=>EditSavedAssetSearch::route('/{record}/edit')];}
}
