<?php

declare(strict_types=1);

namespace Coddin\NovaMoneyField;

use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

final class FieldServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Nova::serving(static function (ServingNova $event) {
            Nova::script('nova-money-field', __DIR__ . '/../dist/js/field.js');
        });
    }
}
