<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsiveUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_web_app_manifest_and_icons_are_published(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('OMS', $manifest['short_name']);
        $this->assertSame('/admin', $manifest['start_url']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('#047857', $manifest['theme_color']);
        $this->assertNotEmpty($manifest['icons']);

        foreach (['icons/icon-192.png', 'icons/icon-512.png'] as $icon) {
            $this->assertFileExists(public_path($icon));
        }
    }

    public function test_the_admin_panel_head_exposes_the_pwa_metadata(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);

        $this->actingAs($administrator)
            ->get('/admin')
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee(asset('manifest.json'), false)
            ->assertSee('<meta name="theme-color" content="#047857">', false)
            ->assertSee(asset('icons/icon-192.png'), false);
    }

    public function test_reports_keep_print_styles_and_scrollable_table_wrappers(): void
    {
        foreach ([
            'weekly-report.blade.php',
            'daily-meal-preparation-sheet.blade.php',
            'congregation-weekly-report.blade.php',
            'public-congregation-week-sheet.blade.php',
        ] as $view) {
            $contents = (string) file_get_contents(resource_path('views/'.$view));

            $this->assertStringContainsString('table-wrap', $contents, $view);
            $this->assertStringContainsString('@media print', $contents, $view);
            $this->assertStringContainsString('.table-wrap { overflow: visible; }', $contents, $view);
        }
    }

    public function test_the_weekly_calendar_collapses_to_one_column_and_keeps_touch_targets(): void
    {
        $contents = (string) file_get_contents(resource_path('views/filament/pages/calendar-saptamanal.blade.php'));

        $this->assertStringContainsString('@media (max-width: 639.98px)', $contents);
        $this->assertStringContainsString('grid-template-columns: 1fr;', $contents);
        $this->assertStringContainsString('min-height: 44px', $contents);
        $this->assertStringContainsString('inputmode="numeric"', $contents);
        $this->assertStringContainsString('inputmode="decimal"', $contents);
    }

    public function test_the_preparation_sheet_switches_ingredient_tables_to_lists_on_phones(): void
    {
        $contents = (string) file_get_contents(resource_path('views/daily-meal-preparation-sheet.blade.php'));

        $this->assertStringContainsString('@media screen and (max-width: 640px)', $contents);
        $this->assertStringContainsString('.ingredient-table thead { display: none; }', $contents);
        $this->assertStringContainsString('data-label="Ingredient"', $contents);
    }
}
