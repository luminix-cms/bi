# SumMetric

`SumMetric` soma os valores de uma coluna numérica para cada grupo de registros. Pense nela como a resposta para "quanto no total?": quanto foi faturado por categoria, qual o peso total dos itens por pedido, quantos pontos cada cliente acumulou.

## SQL Gerado

`SumMetric` adiciona ao `SELECT` a expressão `SUM(column)`, aliasada com o valor de `$key`:

```sql
SELECT SUM(`valor_pedido`) AS `receita`
```

A coluna usada na função `SUM` é determinada pela propriedade `$column`. Por padrão, `$column` assume o mesmo valor que `$key`. Quando eles diferem, use `->column()`.

## O Método `->column()` em `SumMetric`

`->column()` é especialmente importante em `SumMetric` porque, na prática, o nome que você quer exibir no JSON raramente coincide com o nome da coluna no banco. Por exemplo, você pode querer exibir a chave `receita` no JSON, mas a coluna no banco se chama `valor_pedido`:

```php
use Luminix\Bi\Metrics\SumMetric;

SumMetric::create('receita', 'Receita Total')
    ->column('valor_pedido')
```

SQL gerado:

```sql
SELECT SUM(`valor_pedido`) AS `receita`
```

Sem `->column('valor_pedido')`, o pacote executaria `SUM(`receita`)`, que não existe no banco e causaria um erro de query.

## Casos de Uso

- Receita total por categoria de produto
- Peso total de itens por pedido
- Pontos acumulados por cliente
- Horas trabalhadas por projeto
- Desconto total concedido por vendedor

## Exemplo com Coluna Diferente do Key

O exemplo a seguir exibe a receita total agrupada por categoria de produto:

```php
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Widgets\Table;

Table::create('receita-por-categoria', 'Receita por Categoria')
    ->dimension(
        StringDimension::create('categoria', 'Categoria')
    )
    ->metric(
        SumMetric::create('receita', 'Receita Total')
            ->column('valor_pedido')
            ->color('#FF9800')
    )
```

A query gerada é equivalente a:

```sql
SELECT `categoria` AS `categoria`, SUM(`valor_pedido`) AS `receita`
FROM `pedidos`
GROUP BY `categoria`
```

A resposta JSON conterá linhas como:

```json
[
  { "categoria": "Eletrônicos", "receita": 125000.50 },
  { "categoria": "Roupas",      "receita": 48300.00  },
  { "categoria": "Livros",      "receita": 9750.75   }
]
```

## Exemplo com `->asPercentage()`

Para exibir a participação percentual de cada categoria na receita total:

```php
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Widgets\Table;

Table::create('receita-por-categoria', 'Receita por Categoria')
    ->dimension(
        StringDimension::create('categoria', 'Categoria')
    )
    ->metric(
        SumMetric::create('receita', 'Participação na Receita')
            ->column('valor_pedido')
            ->asPercentage()
    )
```

O SQL executado é idêntico ao exemplo anterior. A diferença ocorre na camada PHP: os valores `125000.50`, `48300.00` e `9750.75` são convertidos em `67.71%`, `26.14%` e `5.27%` antes de compor o JSON de resposta.

> `->asPercentage()` divide o valor de cada linha pelo somatório de todos os valores retornados para a mesma métrica. O cálculo é feito em PHP após a execução da query, sobre o conjunto completo de resultados.

## Próximos Passos

← [CountMetric](02-count.md) | → [AverageMetric](04-average.md)
