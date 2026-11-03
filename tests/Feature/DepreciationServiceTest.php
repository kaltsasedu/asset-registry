<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDepreciationProfile;
use App\Models\DepreciationBook;
use App\Models\DepreciationPeriod;
use App\Services\DepreciationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DepreciationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_cannot_reapply_even_when_the_caller_holds_a_stale_draft(): void
    {
        [$profile, $period] = $this->fixtures();
        $service = app(DepreciationService::class);
        $this->assertSame(1, $service->post($period));
        try {
            $service->post($period);
            $this->fail('The same period was posted twice.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('period', $exception->errors());
        }

        $this->assertEquals(10, $profile->fresh()->accumulated_depreciation);
        $this->assertSame('10.00', $period->entries()->sole()->amount);
        $service->lock($period);
        $this->assertSame('locked', $period->fresh()->status);
    }

    public function test_future_start_and_next_month_timing_are_respected(): void
    {
        [$profile, $period] = $this->fixtures();
        $profile->update(['depreciation_start_date' => '2026-11-12']);
        $service = app(DepreciationService::class);
        $this->assertCount(0, $service->preview($period));
        $period->update(['month' => 11]);
        $this->assertCount(1, $service->preview($period));
        $profile->update(['depreciation_timing' => 'next_month']);
        $this->assertCount(0, $service->preview($period));
        $period->update(['month' => 12]);
        $this->assertCount(1, $service->preview($period));
    }

    private function fixtures(): array
    {
        $book = DepreciationBook::create(['name' => 'Βιβλίο δοκιμής', 'code' => 'TEST']);
        $category = AssetCategory::create(['name' => 'Δοκιμή']);
        $asset = Asset::create([
            'name' => 'Πάγιο', 'asset_code' => 'TEST-01', 'asset_category_id' => $category->id,
            'purchase_date' => '2026-10-01', 'depreciation_start_date' => '2026-10-01',
            'purchase_value' => 1200, 'depreciation_rate' => 10, 'useful_life_years' => 10,
            'residual_value' => 0, 'accumulated_depreciation' => 0, 'depreciation_method' => 'straight_line',
            'depreciation_book_id' => $book->id, 'status' => 'in_use',
        ]);
        $profile = AssetDepreciationProfile::create([
            'asset_id' => $asset->id, 'depreciation_book_id' => $book->id,
            'depreciation_start_date' => '2026-10-01', 'depreciation_rate' => 10,
            'depreciation_method' => 'straight_line', 'depreciation_timing' => 'full_month',
            'residual_value' => 0, 'useful_life_years' => 10, 'accumulated_depreciation' => 0, 'remaining_value' => 1200,
        ]);
        $period = DepreciationPeriod::create(['depreciation_book_id' => $book->id, 'year' => 2026, 'month' => 10, 'status' => 'draft']);

        return [$profile, $period];
    }
}
