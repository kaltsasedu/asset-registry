<?php
namespace App\Filament\Widgets;
use App\Models\Asset;
use Filament\Widgets\Widget;
class AssetFinancialByDimension extends Widget
{
    protected string $view = 'filament.widgets.asset-financial-by-dimension';
    protected int|string|array $columnSpan = 'full';
    protected function getViewData(): array
    {
        return ['categories' => $this->summary('asset_categories', 'assets.asset_category_id'), 'departments' => $this->summary('departments', 'assets.department_id'), 'locations' => $this->summary('locations', 'assets.location_id')];
    }
    private function summary(string $table, string $foreignKey): array
    {
        return Asset::query()->leftJoin($table, $foreignKey, '=', "$table.id")->selectRaw("COALESCE($table.name, 'Χωρίς καταχώριση') as label, SUM(assets.purchase_value) as purchase_value, SUM(assets.accumulated_depreciation) as accumulated, SUM(assets.remaining_value) as remaining")->groupBy('label')->orderByDesc('remaining')->limit(8)->get()->map(fn ($r) => ['label'=>$r->label,'purchase'=>$r->purchase_value,'accumulated'=>$r->accumulated,'remaining'=>$r->remaining])->all();
    }
}
