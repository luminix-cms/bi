# AverageMetric

`AverageMetric` calcula a média aritmética dos valores de uma coluna numérica para cada grupo de registros. É a resposta para "quanto em média?": qual o ticket médio por categoria, qual a avaliação média dos produtos por fornecedor, quanto tempo em média demora um atendimento.

## Uso

```php
AverageMetric::create($key, $name, $column)
```

O terceiro parâmetro `$column` é a coluna do banco sobre a qual a média é calculada. Assim como em `SumMetric`, prefira sempre informá-lo explicitamente.

## Exemplo: Ticket Médio por Categoria

```php
use Luminix\Bi\Metrics\AverageMetric;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Widgets\Table;

Table::create('avg-ticket-by-category', 'Ticket Médio por Categoria')
    ->dimension(
        StringDimension::create('category', 'Categoria')
    )
    ->metric(
        AverageMetric::create('avg_ticket', 'Ticket Médio', 'total_amount')
            ->color('#9C27B0')
    )
```

Resposta JSON:

```json
[
  { "category": "Electronics", "avg_ticket": 879.99 },
  { "category": "Clothing",    "avg_ticket": 145.50 },
  { "category": "Books",       "avg_ticket": 52.30  }
]
```

## Atenção: Valores `NULL`

A função `AVG` do MySQL ignora registros com valor `NULL` na coluna calculada — eles não entram nem no numerador nem no denominador. Se a coluna pode conter `NULL` e você precisa que esses registros sejam tratados como zero, use `RawMetric` com `AVG(COALESCE(column, 0))`.

## Próximos Passos

← [SumMetric](03-sum.md) | → [RawMetric](05-raw.md)
