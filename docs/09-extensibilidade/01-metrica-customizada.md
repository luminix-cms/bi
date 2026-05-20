# Métricas Customizadas

O pacote inclui `CountMetric`, `SumMetric`, `AverageMetric`, `CountManyMetric`, `SumManyMetric` e `RawMetric`. Quando nenhuma delas cobre a lógica necessária — por exemplo, uma taxa calculada sobre múltiplas colunas ou um valor formatado de maneira especial — crie sua própria métrica estendendo `BaseMetric`.

## Contrato

```php
namespace Luminix\Bi\Metrics;

interface Metric
{
    public function apply(Builder $builder, Widget $widget): Builder;
    public function display(Model $value, array $models);
    public function getEmptyValue();
}
```

| Método | Responsabilidade |
|---|---|
| `apply()` | Adiciona o `SELECT` ao builder via `addSelect()` |
| `display()` | Formata o valor para o JSON de resposta |
| `getEmptyValue()` | Valor padrão quando não há dados (tipicamente `0`) |

> Use sempre `addSelect()`, nunca `select()`. Usar `select()` substitui os selects das outras métricas e dimensões já registradas no builder.

`BaseMetric` implementa a interface e fornece `column()`, `color()`, `asPercentage()`, `getEmptyValue()` e `create()`. Estendendo-a, você implementa apenas `apply()`.

## Exemplo Completo: `ConversionRateMetric`

Uma métrica que calcula a proporção de pedidos com status `confirmed` em relação ao total:

```php
<?php

namespace App\Bi\Metrics;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Luminix\Bi\Metrics\BaseMetric;
use Luminix\Bi\Widgets\Widget;

class ConversionRateMetric extends BaseMetric
{
    private string $confirmedStatus;

    public function __construct(string $key, string $name, string $confirmedStatus = 'confirmed')
    {
        parent::__construct($key, $name);
        $this->confirmedStatus = $confirmedStatus;
    }

    public function apply(Builder $builder, Widget $widget): Builder
    {
        $status = $this->confirmedStatus;

        return $builder->addSelect(
            DB::raw(
                "ROUND(
                    SUM(CASE WHEN {$this->column} = '{$status}' THEN 1 ELSE 0 END)
                    / NULLIF(COUNT(*), 0) * 100,
                2) as `{$this->key}`"
            )
        );
    }

    public function display(Model $value, array $models)
    {
        $raw = $value->{$this->key};

        return $raw !== null
            ? number_format((float) $raw, 2) . '%'
            : '0.00%';
    }

    public function getEmptyValue()
    {
        return '0.00%';
    }
}
```

### Usando no Dashboard

```php
<?php

namespace App\Bi\Dashboards;

use App\Bi\Metrics\ConversionRateMetric;
use App\Models\Order;
use Carbon\Carbon;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Filters\DateIntervalFilter;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Widgets\BigNumber;

class OrdersDashboard extends Dashboard
{
    public $uriKey = 'orders';
    public $name   = 'Orders';
    public $model  = Order::class;

    public function widgets(): array
    {
        return [
            BigNumber::create('total_orders', 'Total Orders')
                ->metric(CountMetric::create('total', 'Total')),

            BigNumber::create('conversion', 'Conversion Rate')
                ->metric(
                    ConversionRateMetric::create('conversion_rate', 'Conversion Rate')
                        ->column('status')
                ),
        ];
    }

    public function filters(): array
    {
        return [
            DateIntervalFilter::create('created_at', 'Period')
                ->defaultDates(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()),
        ];
    }
}
```

## Próximos Passos

[← Uso Básico](../uso-basico.md) | [→ Dimensões Customizadas](02-dimensao-customizada.md)
