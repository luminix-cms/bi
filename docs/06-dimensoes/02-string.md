# StringDimension

`StringDimension` agrupa os registros pelo valor de uma coluna textual ou categórica. É a dimensão mais direta e a resposta para "por qual categoria?": por status do pedido, por tipo de produto, por região, por canal de vendas.

## Uso

```php
StringDimension::create($key, $name)
```

Por padrão, a coluna do banco usada no agrupamento tem o mesmo nome que o `$key`. Use `->column()` quando diferirem.

## Exemplo: Pedidos por Status

```php
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Widgets\Table;

Table::create('orders-by-status', 'Pedidos por Status')
    ->dimension(
        StringDimension::create('status', 'Status do Pedido')
    )
    ->metric(
        CountMetric::create('total', 'Total')
    )
```

Resposta JSON:

```json
[
  { "status": "approved",  "total": 142 },
  { "status": "pending",   "total": 58  },
  { "status": "canceled",  "total": 21  }
]
```

## Exemplo: Receita por Categoria com Coluna Diferente do Key

```php
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Widgets\Table;

Table::create('revenue-by-category', 'Receita por Categoria')
    ->dimension(
        StringDimension::create('category', 'Categoria')
            ->column('product_category') // coluna real no banco
    )
    ->metric(
        SumMetric::create('revenue', 'Receita Total', 'total_amount')
    )
```

A resposta retornará a chave `category` (não `product_category`), pois o `$key` é o alias que aparece no JSON.

## Cuidado com Alta Cardinalidade

`StringDimension` retorna uma linha por valor distinto da coluna. Colunas com muitos valores distintos — como campos de texto livre ou identificadores únicos — podem produzir resultados com milhares de linhas, impactando desempenho e legibilidade.

Para colunas de alta cardinalidade, considere adicionar filtros ou usar `RawDimension` com `CASE WHEN` para consolidar valores em grupos menores.

## Próximos Passos

← [Visão Geral das Dimensões](01-visao-geral.md) | → [Dimensões de Data](03-datas.md)
