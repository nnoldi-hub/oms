<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\Menu;
use Illuminate\Support\Facades\DB;

class MenuReviewService
{
    public function approve(Menu $menu): Menu
    {
        $menu->is_reviewed = true;
        $menu->save();

        return $menu;
    }

    /**
     * Uneste reteta propusa cu o reteta existenta: toate zilele care folosesc
     * reteta propusa sunt mutate pe reteta existenta, iar reteta propusa se sterge.
     */
    public function merge(Menu $source, Menu $target): Menu
    {
        if ($source->is($target)) {
            return $target;
        }

        DB::transaction(function () use ($source, $target): void {
            foreach (['menu_id', 'soup_menu_id', 'dessert_menu_id', 'actual_menu_id'] as $column) {
                DailyMeal::query()
                    ->where($column, $source->id)
                    ->update([$column => $target->id]);
            }

            $source->congregations()->detach();
            $source->delete();
        });

        return $target;
    }
}
