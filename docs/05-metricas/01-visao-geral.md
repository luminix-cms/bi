# Visão Geral das Métricas

Métricas respondem à pergunta "quanto?": quantos pedidos foram feitos, quanto foi faturado, qual a média de valor por pedido. Em SQL, toda métrica é uma função de agregação — `COUNT`, `SUM`, `AVG` ou uma expressão equivalente — que produz um valor numérico por grupo de registros.

No Luminix BI, cada métrica é uma classe PHP que adiciona uma cláusula de agregação ao `SELECT` da query do widget. A dimensão define as linhas (o `GROUP BY`); a métrica define o valor exibido em cada linha.

## Os Três Parâmetros Fundamentais

Toda métrica recebe ao menos dois parâmetros no construtor: `$key` e `$name`. Métricas que operam sobre uma coluna específica recebem também `$column`.

| Parâmetro | Papel |
|---|---|
| `$key` | Alias SQL e chave no JSON de resposta |
| `$name` | Rótulo exibido no front-end |
| `$column` | Coluna SQL usada na agregação |

O `$key` deve ser um identificador sem espaços (preferencialmente kebab-case ou snake_case), pois aparece tanto no alias do `SELECT` quanto na chave do objeto JSON retornado pela API.

## Tipos de Métricas

| Métrica | Agregação | Quando usar |
|---|---|---|
| `CountMetric` | `COUNT(*)` | Contar registros do modelo principal |
| `SumMetric` | `SUM(column)` | Somar uma coluna numérica |
| `AverageMetric` | `AVG(column)` | Calcular a média de uma coluna numérica |
| `RawMetric` | expressão SQL livre | Lógica que as métricas acima não cobrem |
| `CountManyMetric` | `withCount(relation)` | Contar registros de um relacionamento Eloquent |
| `SumManyMetric` | `withSum(relation, column)` | Somar coluna em registros de um relacionamento |

## O Método `->asPercentage()`

Transforma o valor numérico em participação percentual em relação ao total do resultado. O cálculo é feito em PHP após a execução da query: o valor de cada linha é dividido pelo somatório de todos os valores da mesma métrica.

```php
use Luminix\Bi\Metrics\SumMetric;

SumMetric::create('revenue', 'Participação na Receita', 'total_amount')
    ->asPercentage()
```

Se o resultado retornar três linhas com `1000`, `2000` e `500`, a exibição será `28.57%`, `57.14%` e `14.29%`. O `->asPercentage()` não altera o SQL gerado.

## O Método `->color()`

Define um metadado de cor enviado ao front-end como parte do schema do widget. Usado por gráficos para colorir séries, barras ou fatias.

```php
use Luminix\Bi\Metrics\CountMetric;

CountMetric::create('total-orders', 'Total de Pedidos')
    ->color('#4CAF50')
```

## Próximos Passos

← [Uso Básico](../uso-basico.md) | → [CountMetric](02-count.md)
