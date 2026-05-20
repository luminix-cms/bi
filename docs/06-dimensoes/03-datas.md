# Dimensões de Data

`DayDimension`, `MonthDimension` e `YearDimension` agrupam registros por período temporal. Escolha o nível de granularidade adequado à análise — o pacote cuida da formatação SQL e da integração com o `LineChart`.

## Quando Usar Cada Uma

| Dimensão | Formato de saída | Quando usar |
|---|---|---|
| `DayDimension` | `'2024-03-15'` | Análises de curto prazo (dias, semanas) |
| `MonthDimension` | `'2024-03'` | Análises mensais, tendências de médio prazo |
| `YearDimension` | `'2024'` | Visão anual, comparativos entre anos |

## Uso

```php
DayDimension::create($key, $name)
MonthDimension::create($key, $name)
YearDimension::create($key, $name)
```

Por padrão, as três dimensões usam a coluna `created_at`. Para usar outra coluna de data, aplique `->column()`:

```php
MonthDimension::create('updated_month', 'Mês de Atualização')
    ->column('updated_at')
```

## Exemplo: Receita Mensal em um LineChart

```php
use Luminix\Bi\Dimensions\MonthDimension;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Widgets\LineChart;

LineChart::create('monthly-revenue', 'Receita Mensal')
    ->dimension(
        MonthDimension::create('month', 'Mês')
    )
    ->metric(
        SumMetric::create('revenue', 'Receita Total', 'total_amount')
            ->color('#2196F3')
    )
```

Resposta JSON (trecho):

```json
[
  { "month": "2024-01", "revenue": 98500.00 },
  { "month": "2024-02", "revenue": 0         },
  { "month": "2024-03", "revenue": 125000.50 }
]
```

O `LineChart` inseriu `{ "month": "2024-02", "revenue": 0 }` porque não havia pedidos naquele mês — mantendo a continuidade da série temporal.

## Interpolação de Períodos Ausentes no LineChart

O `LineChart` usa os metadados das dimensões de data para preencher automaticamente os períodos sem registros. Cada dimensão define internamente o formato Carbon correspondente e a unidade de incremento (`day`, `month` ou `year`) — o `LineChart` itera sobre o intervalo completo e insere o valor vazio (`0`) onde não há dados.

Esse comportamento é automático: basta usar a dimensão de data adequada. `StringDimension` e `BelongsToDimension` não produzem interpolação no `LineChart`.

Para mais detalhes sobre o `LineChart`, consulte [LineChart](../04-widgets/04-line-chart.md).

## Próximos Passos

← [StringDimension](02-string.md) | → [BelongsToDimension](04-belongs-to.md)
