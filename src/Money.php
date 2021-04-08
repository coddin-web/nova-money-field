<?php

declare(strict_types=1);

namespace Coddin\NovaMoneyField;

use Laravel\Nova\Fields\Number;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metable;
use Money\Currencies\AggregateCurrencies;
use Money\Currencies\BitcoinCurrencies;
use Money\Currencies\ISOCurrencies;
use Money\Currency;

final class Money extends Number
{
    public $component = 'nova-money-field';

    public bool $inMinorUnits;

    public function __construct(
        string $name,
        string $currency = 'USD',
        ?string $attribute = null,
        ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->withMeta([
            'currency' => $currency,
            'subUnits' => $this->subunits($currency),
        ]);

        $this->step(1 / $this->minorUnit($currency));

        $this
            ->resolveUsing(function ($value) use ($currency, $resolveCallback) {
                if ($resolveCallback !== null) {
                    $value = call_user_func_array($resolveCallback, func_get_args());
                }

                return $this->inMinorUnits ? $value / $this->minorUnit($currency) : (float) $value;
            })
            ->fillUsing(function (NovaRequest $request, $model, $attribute, $requestAttribute) use ($currency) {
                $value = $request[$requestAttribute];

                if ($this->inMinorUnits) {
                    $value *= $this->minorUnit($currency);
                }

                $model->{$attribute} = $value;
            });
    }

    /**
     * The value in database is store in minor units (cents for dollars).
     */
    public function storedInMinorUnits(): self
    {
        $this->inMinorUnits = true;

        return $this;
    }

    public function locale(string $locale): Metable
    {
        return $this->withMeta(['locale' => $locale]);
    }

    public function subUnits(string $currency): int
    {
        return (new AggregateCurrencies([
            new ISOCurrencies(),
            new BitcoinCurrencies(),
        ]))->subunitFor(new Currency($currency));
    }

    public function minorUnit(string $currency): int
    {
        return 10 ** $this->subUnits($currency);
    }
}
