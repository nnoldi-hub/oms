<?php

namespace Tests\Feature;

use App\Filament\Widgets\OmsStatsOverview;
use App\Models\DailyMeal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OmsStatsOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_shows_the_three_operational_statistics(): void
    {
        DailyMeal::factory()->create([
            'meal_date' => today(),
            'estimated_people' => 42,
        ]);
        $widget = new class extends OmsStatsOverview
        {
            public function stats(): array
            {
                return $this->getStats();
            }
        };

        $stats = $widget->stats();

        $this->assertCount(3, $stats);
        $this->assertSame('Persoane estimate azi', $stats[0]->getLabel());
        $this->assertSame('42', $stats[0]->getValue());
        $this->assertSame('Zile planificate saptamana aceasta', $stats[1]->getLabel());
        $this->assertSame('Meniuri active', $stats[2]->getLabel());
    }
}