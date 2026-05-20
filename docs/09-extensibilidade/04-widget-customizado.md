# Widgets Customizados

O pacote inclui `BigNumber`, `LineChart`, `PartitionPie` e `Table`. Quando a visualização desejada tem uma estrutura de dados diferente — um funil de conversão, um mapa de calor, um gauge — crie seu próprio widget estendendo `BaseWidget`.

## Contrato

```php
namespace Luminix\Bi\Widgets;

interface Widget
{
    public function data(Dashboard $dashboard, BiRequest $request);
}
```

`BaseWidget` implementa essa interface e fornece os seguintes recursos via trait `HasAttributes`:

| Método / Recurso | Descrição |
|---|---|
| `getBaseBuilder(Dashboard $dashboard)` | Cria o `Builder` base a partir do model do dashboard |
| `applyAttributes(Builder $builder)` | Aplica métricas e dimensões registradas ao builder |
| `applyFilters(Builder $builder, Dashboard $dashboard, BiRequest $request)` | Aplica os filtros enviados na requisição |
| `displayModel($model, $rawModels)` | Converte um model Eloquent em `stdClass` via `display()` de cada atributo |
| `dimension()` / `dimensions()` | Fluent setters para dimensões |
| `metric()` / `metrics()` | Fluent setters para métricas |
| `scope(Closure $scope)` | Restringe a query base |
| `width($width)` | Define a largura no grid |
| `create(string $key, string $name)` | Fábrica estática |

Dois membros são obrigatórios ao criar um widget customizado:

- **`$component`** — string serializada no JSON de configuração; o front-end usa esse valor para determinar qual componente renderizar
- **`data()`** — retorna a `Collection` ou `array` que será o campo `data` da resposta JSON

O método `extra()` é opcional: sobrescreva-o para incluir metadados no campo `extra` da serialização do widget (disponível no endpoint de listagem do dashboard).

## Exemplo Completo: `HeatMapWidget`

Um widget que calcula o volume de pedidos por dia da semana e hora do dia, retornando uma matriz para o front-end renderizar um mapa de calor:

```php
<?php

namespace App\Bi\Widgets;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Support\BiRequest;
use Luminix\Bi\Widgets\BaseWidget;

class HeatMapWidget extends BaseWidget
{
    protected $component = 'heat-map';

    private array $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    public function data(Dashboard $dashboard, BiRequest $request): array
    {
        $builder = $this->getBaseBuilder($dashboard);
        $builder = $this->applyFilters($builder, $dashboard, $request);

        $rows = $builder
            ->select([
                DB::raw('DAYOFWEEK(created_at) - 1 as day_of_week'),
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as total'),
            ])
            ->groupBy('day_of_week', 'hour')
            ->get();

        $matrix = [];

        foreach ($rows as $row) {
            $matrix[] = [
                'day'   => $this->days[$row->day_of_week],
                'hour'  => (int) $row->hour,
                'total' => (int) $row->total,
            ];
        }

        return $matrix;
    }

    protected function extra(): array
    {
        return [
            'days'  => $this->days,
            'hours' => range(0, 23),
        ];
    }
}
```

### Usando no Dashboard

```php
use App\Bi\Widgets\HeatMapWidget;
use Luminix\Bi\Filters\DateIntervalFilter;
use Carbon\Carbon;

public function widgets(): array
{
    return [
        HeatMapWidget::create('orders-heatmap', 'Orders by Day & Hour')
            ->width(12),
    ];
}
```

### Resposta JSON

```json
{
    "status": 200,
    "data": [
        { "day": "Mon", "hour": 9,  "total": 42 },
        { "day": "Mon", "hour": 10, "total": 67 },
        { "day": "Tue", "hour": 9,  "total": 38 }
    ]
}
```

Serialização do widget no endpoint de configuração:

```json
{
    "key":        "orders-heatmap",
    "name":       "Orders by Day & Hour",
    "component":  "heat-map",
    "width":      12,
    "metrics":    [],
    "dimensions": [],
    "extra": {
        "days":  ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
        "hours": [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23]
    }
}
```

## Usando Métricas e Dimensões em Widgets Customizados

Se o widget aceita métricas e dimensões configuradas pelo desenvolvedor, use `applyAttributes()` dentro de `data()`:

```php
public function data(Dashboard $dashboard, BiRequest $request): Collection
{
    $builder = $this->getBaseBuilder($dashboard);
    $builder = $this->applyAttributes($builder);
    $builder = $this->applyFilters($builder, $dashboard, $request);

    $rawModels = $builder->get();

    return $rawModels->map(function ($model) use ($rawModels) {
        return $this->displayModel($model, $rawModels->toArray());
    });
}
```

Widgets que executam suas próprias queries (como o `HeatMapWidget` acima) não precisam chamar `applyAttributes()`.

## Próximos Passos

[← Filtros Customizados](03-filtro-customizado.md) | [→ Endpoints da API](../10-api/01-endpoints.md)
