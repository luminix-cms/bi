# Visão Geral das Dimensões

Pense em uma dimensão como a resposta para "por quê?": por status, por mês, por categoria, por vendedor. Em termos SQL, toda dimensão define uma cláusula `GROUP BY` — ela determina como os registros são agrupados e, portanto, quantas linhas o resultado terá. Cada linha representa um grupo distinto definido pela dimensão.

No Luminix BI, cada dimensão é uma classe PHP responsável por adicionar ao `SELECT` a coluna de agrupamento e ao `GROUP BY` o critério correspondente. A dimensão define as linhas; a métrica define os valores numéricos dentro de cada linha.

## A Interface `Dimension` e a Classe `BaseDimension`

Toda dimensão implementa a interface `Luminix\Bi\Dimensions\Dimension`, que exige a presença do método `apply(Builder $builder, Widget $widget): Builder`. Esse método recebe o `QueryBuilder` parcialmente construído e deve retornar o builder com o `SELECT` e o `GROUP BY` da dimensão adicionados.

A classe abstrata `BaseDimension` implementa `Dimension` e estende `Attribute`, herdando as propriedades `$key`, `$name` e `$column`, bem como os métodos `column()`, `color()`, `display()` e `applySort()`.

Todas as dimensões concretas do pacote — `StringDimension`, `DayDimension`, `MonthDimension`, `YearDimension`, `BelongsToDimension` e `RawDimension` — estendem `BaseDimension`.

## A Classe `Attribute`: Base Comum com as Métricas

Dimensões e métricas compartilham a mesma classe base `Attribute`. As três propriedades fundamentais são idênticas:

| Propriedade | Tipo | Papel na Dimensão |
|---|---|---|
| `$key` | `string` | Identificador no JSON de resposta e alias SQL no `SELECT` |
| `$name` | `string` | Rótulo legível enviado ao front-end |
| `$column` | `string` | Coluna SQL usada no `GROUP BY` (padrão: igual ao `$key`) |

### O Método `->column()`

Quando o identificador que você quer usar no JSON difere do nome real da coluna no banco, use `->column()`:

```php
use Luminix\Bi\Dimensions\StringDimension;

// $key = 'situacao', mas a coluna no banco é 'status_pedido'
StringDimension::create('situacao', 'Situação')
    ->column('status_pedido')
```

SQL gerado:

```sql
SELECT `status_pedido` AS `situacao` ... GROUP BY `situacao`
```

## Como Dimensões e Métricas Interagem na Query

A dimensão e a métrica constroem a query em conjunto: a dimensão adiciona a coluna de agrupamento ao `SELECT` e define o `GROUP BY`, enquanto a métrica adiciona a função de agregação ao `SELECT`. O resultado é uma query que retorna um valor agregado por grupo:

```sql
-- Dimensão: StringDimension('categoria', 'Categoria')
-- Métrica:  SumMetric('receita', 'Receita').column('valor_pedido')

SELECT `categoria` AS `categoria`, SUM(`valor_pedido`) AS `receita`
FROM `pedidos`
GROUP BY `categoria`
```

Cada linha do resultado representa um valor distinto da dimensão (`categoria`), com o valor agregado da métrica (`receita`) para esse grupo.

## O Método `display()`

O método `display(Model $value, array $models)` formata o valor da dimensão antes de compor o JSON de resposta. O comportamento varia por tipo de dimensão:

- **StringDimension**: retorna o valor bruto da propriedade correspondente ao `$key` no modelo
- **DateDimension** (e suas subclasses): usa `getRawOriginal($key)` para retornar o valor sem cast do Eloquent — evita que uma string como `'2024-03'` seja convertida para um objeto `Carbon`
- **BelongsToDimension**: acessa o modelo relacionado carregado via eager loading e retorna o valor de `$otherColumn`

## O Método `applySort()`

`applySort(Builder $builder, string $dir): Builder` adiciona uma cláusula `ORDER BY` ao builder para ordenação por esta dimensão. O comportamento padrão (herdado de `Attribute`) é ordenar pela coluna definida em `$key`. `BelongsToDimension` sobrescreve esse comportamento para ordenar pela foreign key em vez do `$key`.

## Tabela Resumo das Dimensões

| Dimensão | SQL gerado (GROUP BY) | Uso típico |
|---|---|---|
| `StringDimension` | `GROUP BY {key}` | Status, categoria, região, tipo |
| `DayDimension` | `GROUP BY DATE_FORMAT(col, '%Y-%m-%d')` | Série temporal por dia |
| `MonthDimension` | `GROUP BY DATE_FORMAT(col, '%Y-%m')` | Série temporal por mês |
| `YearDimension` | `GROUP BY DATE_FORMAT(col, '%Y')` | Série temporal por ano |
| `BelongsToDimension` | `GROUP BY {foreign_key}` | Agrupamento por modelo relacionado |
| `RawDimension` | `GROUP BY {key}` (expressão SQL livre) | Agrupamentos customizados |

## Próximos Passos

← [SumManyMetric](../05-metricas/07-sum-many.md) | → [StringDimension](02-string.md)
