# Visão Geral das Dimensões

Dimensões respondem à pergunta "por quê?": por status, por mês, por categoria, por vendedor. Em SQL, toda dimensão define uma cláusula `GROUP BY` — ela determina como os registros são agrupados e quantas linhas o resultado terá. Cada linha representa um grupo distinto.

No Luminix BI, cada dimensão é uma classe PHP que adiciona ao `SELECT` a coluna de agrupamento e ao `GROUP BY` o critério correspondente. A dimensão define as linhas; a métrica define os valores numéricos dentro de cada linha.

## Os Três Parâmetros Fundamentais

| Parâmetro | Papel |
|---|---|
| `$key` | Alias SQL e chave no JSON de resposta |
| `$name` | Rótulo exibido no front-end |
| `$column` | Coluna SQL usada no `GROUP BY` (padrão: igual ao `$key`) |

Quando o identificador desejado no JSON difere do nome real da coluna no banco, use `->column()`:

```php
use Luminix\Bi\Dimensions\StringDimension;

StringDimension::create('situation', 'Situação')
    ->column('order_status')
```

## Tipos de Dimensões

| Dimensão | Agrupamento | Quando usar |
|---|---|---|
| `StringDimension` | Valor de coluna texto | Status, categoria, região, tipo |
| `DayDimension` | `DATE_FORMAT(col, '%Y-%m-%d')` | Série temporal por dia |
| `MonthDimension` | `DATE_FORMAT(col, '%Y-%m')` | Série temporal por mês |
| `YearDimension` | `DATE_FORMAT(col, '%Y')` | Série temporal por ano |
| `BelongsToDimension` | Chave estrangeira com eager loading | Agrupamento por modelo relacionado |
| `RawDimension` | Expressão SQL livre | Agrupamentos que as dimensões acima não cobrem |

## Como Dimensão e Métrica Interagem

A dimensão e a métrica constroem a query em conjunto: a dimensão adiciona a coluna de agrupamento ao `SELECT` e define o `GROUP BY`; a métrica adiciona a função de agregação. O resultado é uma linha por grupo com o valor agregado correspondente:

```php
// Dimensão: StringDimension('category', 'Categoria')
// Métrica:  SumMetric('revenue', 'Receita', 'total_amount')

// Query resultante:
// SELECT `category`, SUM(`total_amount`) AS `revenue`
// FROM `orders`
// GROUP BY `category`
```

## Próximos Passos

← [SumManyMetric](../05-metricas/07-sum-many.md) | → [StringDimension](02-string.md)
