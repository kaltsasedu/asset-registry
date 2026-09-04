<?php
namespace App\Filament\Widgets;
use App\Models\Asset;
use Filament\Widgets\ChartWidget;
class AssetAgeChart extends ChartWidget
{
    protected ?string $heading = 'Ηλικία παγίων';
    protected function getData(): array { $now=now(); $groups=['0–3 έτη'=>[0,3],'4–7 έτη'=>[4,7],'8–12 έτη'=>[8,12],'13+ έτη'=>[13,100]]; $data=[]; foreach($groups as [$min,$max]) $data[]=Asset::whereDate('purchase_date','<=',$now->copy()->subYears($min)->endOfDay())->whereDate('purchase_date','>',$now->copy()->subYears($max+1)->endOfDay())->count(); return ['datasets'=>[['label'=>'Πάγια','data'=>$data,'backgroundColor'=>['#60a5fa','#34d399','#fbbf24','#f87171']]],'labels'=>array_keys($groups)]; }
    protected function getType(): string { return 'bar'; }
}
