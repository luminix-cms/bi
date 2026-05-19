# CountMetric

`CountMetric` é a métrica mais simples do pacote: conta quantos registros existem em cada grupo definido pela dimensão. Pense nela como a resposta para "quantos?": quantos pedidos foram feitos, quantos usuários se cadastraram, quantos eventos ocorreram.

## SQL Gerado

`CountMetric` adiciona ao `SELECT` a expressão `COUNT(*)`, aliasada com o valor de `$key`:

```sql
SELECT COUNT(*) AS `total`
```

A expressão `COUNT(*)` conta todas as linhas do grupo, independentemente de valores nulos em colunas específicas. Por isso, `CountMetric` não depende do método `->column()` — não há coluna a ser especificada.

## Não Requer `->column()`

Ao contrário de `SumMetric` e `AverageMetric`, `CountMetric` não opera sobre nenhuma coluna específica. A chamada a `->column()` não tem efeito e pode ser omitida com segurança.

## Casos de Uso

- Número de pedidos por status
- Número de usuários cadastrados por mês
- Número de eventos por categoria
- Número de transações por tipo de pagamento

## Exemplo Simples

O exemplo a seguir conta o total de pedidos agrupados por status:

```php
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Widgets\Table;

Table::create('pedidos-por-status', 'Pedidos por Status')
    ->dimension(
        StringDimension::create('status', 'Status')
    )
    ->metric(
        CountMetric::create('total', 'Total')
            ->color('#2196F3')
    )
```

A query gerada é equivalente a:

```sql
SELECT `status` AS `status`, COUNT(*) AS `total`
FROM `pedidos`
GROUP BY `status`
```

A resposta JSON conterá linhas como:

```json
[
  { "status": "aprovado", "total": 142 },
  { "status": "pendente", "total": 58 },
  { "status": "cancelado", "total": 21 }
]
```

## Exemplo com `->asPercentage()`

Para exibir a participação percentual de cada status no total de pedidos:

```php
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Widgets\Table;

Table::create('pedidos-por-status', 'Pedidos por Status')
    ->dimension(
        StringDimension::create('status', 'Status')
    )
    ->metric(
        CountMetric::create('total', 'Participação')
            ->asPercentage()
    )
```

O SQL executado é o mesmo — apenas a exibição do valor muda. Se o resultado retornar `142`, `58` e `21` registros (total: 221), a saída formatada será `64.25%`, `26.24%` e `9.50%` respectivamente.

## Diferença em Relação a `CountManyMetric`

`CountMetric` conta as linhas do **modelo principal** da query. Se o dashboard é baseado no model `Pedido`, `CountMetric` conta pedidos.

`CountManyMetric`, por outro lado, conta os registros de um **relacionamento** do modelo principal. Por exemplo, para contar quantos itens cada pedido tem, seria necessário usar `CountManyMetric` com a relação `itens` — não `CountMetric`.

| Situação | Métrica correta |
|---|---|
| Quantos pedidos existem por status? | `CountMetric` |
| Quantos itens cada pedido tem? | `CountManyMetric` |

## Próximos Passos

← [Visão Geral das Métricas](01-visao-geral.md) | → [SumMetric](03-sum.md)
