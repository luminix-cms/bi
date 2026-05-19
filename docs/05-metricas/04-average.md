# AverageMetric

`AverageMetric` calcula a média aritmética dos valores de uma coluna numérica para cada grupo de registros. Pense nela como a resposta para "quanto em média?": qual o ticket médio por categoria, quanto tempo em média demora um atendimento, qual a avaliação média dos produtos.

## SQL Gerado

`AverageMetric` adiciona ao `SELECT` a expressão `AVG(column)`, aliasada com o valor de `$key`:

```sql
SELECT AVG(`valor_pedido`) AS `ticket_medio`
```

Assim como em `SumMetric`, a coluna usada na função `AVG` é determinada por `$column`. Por padrão, `$column` é igual a `$key`. Use `->column()` quando eles diferirem.

## O Método `->column()`

`->column()` é necessário quando o nome da métrica no JSON difere do nome da coluna no banco:

```php
use Luminix\Bi\Metrics\AverageMetric;

AverageMetric::create('ticket_medio', 'Ticket Médio')
    ->column('valor_pedido')
```

SQL gerado:

```sql
SELECT AVG(`valor_pedido`) AS `ticket_medio`
```

## Casos de Uso

- Ticket médio por categoria de produto
- Tempo médio de atendimento por operador
- Avaliação média de produtos por fornecedor
- Idade média de clientes por região
- Prazo médio de entrega por transportadora

## Exemplo: Ticket Médio por Categoria de Produto

```php
use Luminix\Bi\Metrics\AverageMetric;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Widgets\Table;

Table::create('ticket-por-categoria', 'Ticket Médio por Categoria')
    ->dimension(
        StringDimension::create('categoria', 'Categoria')
    )
    ->metric(
        AverageMetric::create('ticket_medio', 'Ticket Médio')
            ->column('valor_pedido')
            ->color('#9C27B0')
    )
```

A query gerada é equivalente a:

```sql
SELECT `categoria` AS `categoria`, AVG(`valor_pedido`) AS `ticket_medio`
FROM `pedidos`
GROUP BY `categoria`
```

A resposta JSON conterá linhas como:

```json
[
  { "categoria": "Eletrônicos", "ticket_medio": 879.99 },
  { "categoria": "Roupas",      "ticket_medio": 145.50 },
  { "categoria": "Livros",      "ticket_medio": 52.30  }
]
```

## Atenção: Comportamento com Valores `NULL`

A função `AVG` do MySQL ignora registros com valor `NULL` na coluna sendo calculada. Isso significa que se parte dos registros tiver o campo nulo, eles não entrarão no cálculo — nem no numerador nem no denominador da média.

Esse comportamento pode produzir resultados inesperados dependendo do contexto:

```sql
-- Tabela com 3 registros: 100, NULL, 200
SELECT AVG(valor) FROM tabela;
-- Resultado: 150 (calculado como (100 + 200) / 2, não como (100 + 0 + 200) / 3)
```

> Se a coluna pode conter `NULL` e você precisa que esses registros sejam tratados como zero, use `RawMetric` com `AVG(COALESCE(coluna, 0))` em vez de `AverageMetric`.

## Próximos Passos

← [SumMetric](03-sum.md) | → [RawMetric](05-raw.md)
