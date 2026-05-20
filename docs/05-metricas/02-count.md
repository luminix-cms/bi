# CountMetric

`CountMetric` conta quantos registros existem em cada grupo definido pela dimensão. É a métrica mais simples e a resposta para "quantos?": quantos pedidos foram feitos, quantos usuários se cadastraram por mês, quantos eventos ocorreram por categoria.

## Uso

```php
CountMetric::create($key, $name)
```

`CountMetric` não opera sobre nenhuma coluna específica — `COUNT(*)` conta todas as linhas do grupo. O método `->column()` não tem efeito e pode ser omitido.

## Exemplo: Pedidos por Status

```php
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Widgets\Table;

Table::create('orders-by-status', 'Pedidos por Status')
    ->dimension(
        StringDimension::create('status', 'Status')
    )
    ->metric(
        CountMetric::create('total', 'Total')
            ->color('#2196F3')
    )
```

Resposta JSON:

```json
[
  { "status": "approved", "total": 142 },
  { "status": "pending",  "total": 58  },
  { "status": "canceled", "total": 21  }
]
```

## Exemplo com `->asPercentage()`

Para exibir a participação percentual de cada status no total:

```php
CountMetric::create('total', 'Participação')
    ->asPercentage()
```

Com os valores `142`, `58` e `21` (total: 221), a exibição será `64.25%`, `26.24%` e `9.50%`. O SQL não muda — apenas a exibição.

## `CountMetric` vs `CountManyMetric`

`CountMetric` conta linhas do modelo principal da query. Se o dashboard é baseado em `Order`, ele conta pedidos.

`CountManyMetric` conta registros de um relacionamento. Para saber quantos itens cada pedido possui, use `CountManyMetric` com a relação `items`.

| Pergunta | Métrica correta |
|---|---|
| Quantos pedidos existem por status? | `CountMetric` |
| Quantos itens cada pedido tem? | `CountManyMetric` |

## Próximos Passos

← [Visão Geral das Métricas](01-visao-geral.md) | → [SumMetric](03-sum.md)
