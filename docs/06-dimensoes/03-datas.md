# DayDimension, MonthDimension e YearDimension

As dimensões de data agrupam registros por período temporal — dia, mês ou ano. Pense nelas como a "escala" de um gráfico de linha no eixo do tempo: você escolhe o nível de granularidade que faz sentido para a análise, e o pacote cuida da formatação SQL e da integração com o `LineChart`.

## A Classe Base `DateDimension`

`DayDimension`, `MonthDimension` e `YearDimension` são subclasses de `DateDimension`, que por sua vez estende `BaseDimension`. A classe `DateDimension` define o comportamento central:

1. Usa `DATE_FORMAT(column, 'formato')` no `SELECT` para converter a coluna de data em uma string de agrupamento
2. Usa o mesmo `DATE_FORMAT` no `GROUP BY`
3. Sobrescreve `display()` para usar `getRawOriginal()` — impedindo que o Eloquent converta a string para `Carbon`

Cada subclasse configura o formato SQL específico e os metadados Carbon usados pelo `LineChart`:

```php
// Internamente, DayDimension configura:
$this->sqlFormat   = '%Y-%m-%d';
$this->carbonFormat = 'Y-m-d';
// e os métodos Carbon: startOfDay / endOfDay
```

## SQL Gerado por Cada Dimensão

### DayDimension

```sql
SELECT DATE_FORMAT(`created_at`, '%Y-%m-%d') AS `dia`, SUM(`valor`) AS `total`
FROM `pedidos`
GROUP BY DATE_FORMAT(`created_at`, '%Y-%m-%d')
```

Valor produzido: `'2024-03-15'`

### MonthDimension

```sql
SELECT DATE_FORMAT(`created_at`, '%Y-%m') AS `mes`, SUM(`valor`) AS `total`
FROM `pedidos`
GROUP BY DATE_FORMAT(`created_at`, '%Y-%m')
```

Valor produzido: `'2024-03'`

### YearDimension

```sql
SELECT DATE_FORMAT(`created_at`, '%Y') AS `ano`, SUM(`valor`) AS `total`
FROM `pedidos`
GROUP BY DATE_FORMAT(`created_at`, '%Y')
```

Valor produzido: `'2024'`

## Por que `display()` Usa `getRawOriginal()`

O Eloquent converte automaticamente colunas declaradas em `$casts` com tipo `datetime` para instâncias de `Carbon`. Quando a query retorna uma string como `'2024-03'` na coluna `mes`, o Eloquent poderia tentar interpolar essa string para um objeto `Carbon`, alterando ou falhando na conversão.

`DateDimension` sobrescreve `display()` para usar `getRawOriginal($key)`, que retorna o valor exatamente como chegou do banco de dados — sem passar pelo sistema de casts do Eloquent. Isso garante que a string `'2024-03'` seja retornada intacta no JSON.

## Tabela Comparativa

| Dimensão | Formato SQL | Exemplo de saída | Carbon format | Carbon interval | Funções Carbon |
|---|---|---|---|---|---|
| `DayDimension` | `%Y-%m-%d` | `'2024-03-15'` | `'Y-m-d'` | `day` | `startOfDay` / `endOfDay` |
| `MonthDimension` | `%Y-%m` | `'2024-03'` | `'Y-m'` | `month` | `startOfMonth` / `endOfMonth` |
| `YearDimension` | `%Y` | `'2024'` | `'Y'` | `year` | `startOfYear` / `endOfYear` |

## Integração com `LineChart`

O widget `LineChart` usa os metadados da dimensão de data para interpolar períodos ausentes. Quando não há registros em um determinado dia, mês ou ano dentro do intervalo analisado, o `LineChart` insere o valor vazio (`0`, retornado por `getEmptyValue()`) para manter a continuidade da série temporal.

Para isso, o `LineChart` acessa as propriedades expostas pela dimensão:

- `carbonFormat`: formato para parsear/formatar datas com Carbon
- `carbonInterval`: unidade de incremento (`day`, `month`, `year`)
- `carbonStartFunction` / `carbonEndFunction`: funções Carbon para calcular início e fim de cada período

Esses metadados são consumidos internamente pelo `LineChart` — você não precisa configurá-los manualmente. Basta usar a dimensão de data adequada ao nível de granularidade desejado.

## O Método `->column()`

Por padrão, as dimensões de data usam a coluna `created_at`. Para usar outra coluna de data, aplique `->column()`:

```php
use Luminix\Bi\Dimensions\MonthDimension;

// Agrupar por data de atualização, e não por data de criação
MonthDimension::create('mes_atualizacao', 'Mês de Atualização')
    ->column('updated_at')
```

SQL gerado:

```sql
SELECT DATE_FORMAT(`updated_at`, '%Y-%m') AS `mes_atualizacao`
...
GROUP BY DATE_FORMAT(`updated_at`, '%Y-%m')
```

## Exemplo: Receita Mensal em um LineChart

```php
use Luminix\Bi\Dimensions\MonthDimension;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Widgets\LineChart;

LineChart::create('receita-mensal', 'Receita Mensal')
    ->dimension(
        MonthDimension::create('mes', 'Mês')
    )
    ->metric(
        SumMetric::create('receita', 'Receita Total')
            ->column('valor_pedido')
            ->color('#2196F3')
    )
```

A query gerada é equivalente a:

```sql
SELECT DATE_FORMAT(`created_at`, '%Y-%m') AS `mes`, SUM(`valor_pedido`) AS `receita`
FROM `pedidos`
GROUP BY DATE_FORMAT(`created_at`, '%Y-%m')
ORDER BY `mes` ASC
```

O `LineChart` recebe o resultado e, se o intervalo analisado inclui meses sem pedidos (por exemplo, `2024-02` sem registros), insere automaticamente `{ "mes": "2024-02", "receita": 0 }` na série para manter a continuidade do gráfico.

> `LineChart` requer exatamente uma dimensão de data. Usar `StringDimension` ou `BelongsToDimension` com `LineChart` não produzirá interpolação de períodos ausentes.

## Próximos Passos

← [StringDimension](02-string.md) | → [BelongsToDimension](04-belongs-to.md)
