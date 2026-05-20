# Dimensões Customizadas

O pacote inclui `StringDimension`, `RawDimension`, `BelongsToDimension`, `MonthDimension`, `YearDimension` e `DayDimension`. Quando nenhuma delas cobre o agrupamento desejado — como agrupar por semana, trimestre ou faixa de valor — crie sua própria dimensão estendendo `BaseDimension`.

## Contrato

```php
namespace Luminix\Bi\Dimensions;

interface Dimension
{
    public function apply(Builder $builder, Widget $widget): Builder;
    public function display(Model $value, array $models);
}
```

| Método | Responsabilidade |
|---|---|
| `apply()` | Adiciona o `SELECT` e o `GROUP BY` ao builder |
| `display()` | Formata o valor do agrupamento para o JSON de resposta |

> Use sempre `addSelect()`, nunca `select()`. Substituir o select removeria as colunas de métricas já registradas no builder.

`BaseDimension` implementa a interface e fornece `column()`, `color()`, `create()` e um `display()` padrão que retorna `$value->{$this->key}`. Estendendo-a, você implementa apenas `apply()` — e sobrescreve `display()` quando precisar formatar o valor.

O `display()` recebe `$value` (o model Eloquent da linha atual) e `$models` (array com todos os resultados da query), permitindo cálculos relativos quando necessário.

## Exemplo Completo: `WeekDimension`

Uma dimensão que agrupa registros por semana do ano, retornando o formato `YYYY-WW` (ex: `2024-03`):

```php
<?php

namespace App\Bi\Dimensions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Luminix\Bi\Dimensions\BaseDimension;
use Luminix\Bi\Widgets\Widget;

class WeekDimension extends BaseDimension
{
    public function apply(Builder $builder, Widget $widget): Builder
    {
        return $builder
            ->addSelect(
                DB::raw("DATE_FORMAT({$this->column}, '%Y-%u') as `{$this->key}`")
            )
            ->groupBy(
                DB::raw("DATE_FORMAT({$this->column}, '%Y-%u')")
            );
    }

    public function display(Model $value, array $models)
    {
        $raw = $value->getRawOriginal($this->key);

        if (!$raw) {
            return null;
        }

        [$year, $week] = explode('-', $raw);

        return "Week {$week}, {$year}";
    }
}
```

O SQL gerado será equivalente a:

```sql
SELECT DATE_FORMAT(created_at, '%Y-%u') as `week`
FROM orders
GROUP BY DATE_FORMAT(created_at, '%Y-%u')
```

E cada linha do JSON de resposta ficará como:

```json
{ "week": "Week 03, 2024", "total_amount": 148500.00 }
```

### Usando no Dashboard

```php
use App\Bi\Dimensions\WeekDimension;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Widgets\Table;

Table::create('weekly-revenue', 'Weekly Revenue')
    ->dimensions([
        WeekDimension::create('week', 'Week')
            ->column('created_at'),
    ])
    ->metrics([
        SumMetric::create('total_amount', 'Total')
            ->column('total_amount'),
    ]);
```

## Integração com `LineChart`

O `LineChart` preenche automaticamente os períodos sem dados com o valor vazio da métrica. Esse comportamento só ocorre quando a dimensão é uma instância de `DateDimension`.

Para criar uma dimensão temporal compatível com `LineChart`, estenda `DateDimension` e configure o formato de data no construtor:

```php
<?php

namespace App\Bi\Dimensions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Luminix\Bi\Dimensions\DateDimension;
use Luminix\Bi\Widgets\Widget;

class WeekDimension extends DateDimension
{
    public function __construct(string $key, string $name)
    {
        parent::__construct($key, $name);

        $this->sqlFormat('%Y-%u');
        $this->carbonFormat('Y-W', 'week');
        $this->carbonFunctions('startOfWeek', 'endOfWeek');
    }

    public function apply(Builder $builder, Widget $widget): Builder
    {
        return $builder
            ->addSelect(
                DB::raw("DATE_FORMAT({$this->column}, '%Y-%u') as `{$this->key}`")
            )
            ->groupBy(
                DB::raw("DATE_FORMAT({$this->column}, '%Y-%u')")
            );
    }
}
```

> A expressão SQL dentro de `apply()` varia conforme o banco. `DATE_FORMAT` é do MySQL; use `TO_CHAR` no PostgreSQL ou `strftime` no SQLite.

## Próximos Passos

[← Métricas Customizadas](01-metrica-customizada.md) | [→ Filtros Customizados](03-filtro-customizado.md)
