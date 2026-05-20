# Configurando Widgets e Filtros

Um dashboard declara seus widgets e filtros por meio de dois métodos: `widgets()` e `filters()`. Os filtros são aplicados automaticamente a todos os widgets — não é necessário configurar o mesmo filtro em cada widget individualmente.

## O método `widgets()`

Retorna um array de instâncias de widget. Cada widget executa sua própria query e tem sua própria combinação de métricas e dimensões.

```php
public function widgets(): array
{
    return [
        BigNumber::create('total-orders', 'Total Orders')
            ->metric(CountMetric::create('total', 'Total'))
            ->width('1/4'),

        LineChart::create('orders-per-day', 'Orders per Day')
            ->dimension(DayDimension::create('day', 'Day')->column('created_at'))
            ->metric(CountMetric::create('total', 'Total')->color('#2196F3'))
            ->width('3/4'),
    ];
}
```

O método estático `create($key, $name)` recebe:

- `$key` — identificador único do widget dentro do dashboard, usado na URL (`/bi-apis/{dashboard}/widgets/{key}`)
- `$name` — nome legível exibido no frontend e no nome do arquivo CSV no download

A ordem dos widgets no array é preservada na resposta JSON, permitindo controlar o layout visual. Para mais detalhes sobre cada tipo de widget, consulte a [visão geral dos widgets](../04-widgets/01-visao-geral.md).

## O método `filters()`

Retorna um array de filtros. Quando o usuário envia um filtro na requisição, ele é aplicado a todos os widgets do dashboard. Filtros não enviados são simplesmente ignorados.

```php
public function filters(): array
{
    return [
        DateIntervalFilter::create('created_at', 'Period'),
        StringFilter::create('status', 'Status'),
    ];
}
```

Para mais detalhes sobre cada tipo de filtro, consulte a [visão geral dos filtros](../07-filtros/01-visao-geral.md).

## Exemplo com múltiplos widgets e filtros

```php
<?php

namespace App\Bi\Dashboards;

use Luminix\Bi\Dashboard;
use Luminix\Bi\Dimensions\DayDimension;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Filters\DateIntervalFilter;
use Luminix\Bi\Filters\StringFilter;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Widgets\BigNumber;
use Luminix\Bi\Widgets\LineChart;
use Luminix\Bi\Widgets\PartitionPie;

class SalesDashboard extends Dashboard
{
    public $model  = \App\Models\Sale::class;
    public $uriKey = 'sales';
    public $name   = 'Sales Dashboard';

    public function filters(): array
    {
        return [
            DateIntervalFilter::create('created_at', 'Period'),
            StringFilter::create('status', 'Status'),
        ];
    }

    public function widgets(): array
    {
        return [
            BigNumber::create('total-revenue', 'Total Revenue')
                ->metric(
                    SumMetric::create('revenue', 'Revenue')
                        ->column('total_amount')
                        ->color('#4CAF50')
                )
                ->width('1/3'),

            LineChart::create('sales-per-day', 'Sales per Day')
                ->dimension(
                    DayDimension::create('day', 'Day')->column('created_at')
                )
                ->metric(
                    CountMetric::create('qty', 'Quantity')->color('#2196F3')
                )
                ->width('2/3'),

            PartitionPie::create('by-status', 'Sales by Status')
                ->dimension(
                    StringDimension::create('status', 'Status')
                )
                ->metric(
                    CountMetric::create('total', 'Total')
                )
                ->width('1/2'),
        ];
    }
}
```

## Próximos Passos

← [Criando um Dashboard](01-criando.md) | → [Escopo Global](03-escopo.md)
