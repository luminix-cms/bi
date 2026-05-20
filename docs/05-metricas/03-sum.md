# SumMetric

`SumMetric` soma os valores de uma coluna numérica para cada grupo de registros. É a resposta para "quanto no total?": quanto foi faturado por categoria, qual o desconto total concedido por vendedor, quantos pontos cada cliente acumulou.

## Uso

```php
SumMetric::create($key, $name, $column)
```

O terceiro parâmetro `$column` é a coluna do banco a ser somada. Se omitido, o pacote assume que a coluna tem o mesmo nome que o `$key` — o que raramente é o caso. Prefira sempre informar `$column` explicitamente.

## Exemplo: Receita por Categoria

```php
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Widgets\Table;

Table::create('revenue-by-category', 'Receita por Categoria')
    ->dimension(
        StringDimension::create('category', 'Categoria')
    )
    ->metric(
        SumMetric::create('revenue', 'Receita Total', 'total_amount')
            ->color('#FF9800')
    )
```

Resposta JSON:

```json
[
  { "category": "Electronics", "revenue": 125000.50 },
  { "category": "Clothing",    "revenue": 48300.00  },
  { "category": "Books",       "revenue": 9750.75   }
]
```

## Exemplo com `->asPercentage()`

Para exibir a participação de cada categoria na receita total:

```php
SumMetric::create('revenue', 'Participação na Receita', 'total_amount')
    ->asPercentage()
```

Com os valores `125000.50`, `48300.00` e `9750.75`, a exibição será `67.71%`, `26.14%` e `5.27%`. O cálculo é feito em PHP após a execução da query — o SQL não é alterado.

## Próximos Passos

← [CountMetric](02-count.md) | → [AverageMetric](04-average.md)
