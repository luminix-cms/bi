# Visão Geral das Métricas

Pense em uma métrica como a resposta para uma pergunta do tipo "quanto?". Quanto foi faturado? Quantos pedidos foram feitos? Qual a média de avaliação dos produtos? Em termos SQL, toda métrica é uma função de agregação — `COUNT`, `SUM`, `AVG` ou uma expressão equivalente — que produz um valor numérico por grupo de registros.

No Luminix BI, cada métrica é uma classe PHP responsável por adicionar uma cláusula `SELECT` com uma agregação à query do widget. A dimensão define as linhas (o `GROUP BY`); a métrica define o valor exibido em cada linha.

## A Interface `Metric` e a Classe `BaseMetric`

Toda métrica implementa a interface `Luminix\Bi\Metrics\Metric`, que exige a presença de um método `apply(Builder $builder, Widget $widget): Builder`. Esse método recebe o `QueryBuilder` já parcialmente construído pelo widget e deve retornar o builder com a cláusula de agregação adicionada.

A classe abstrata `BaseMetric` implementa `Metric` e estende `Attribute`, fornecendo as funcionalidades comuns a todas as métricas do pacote: as propriedades `$key`, `$name` e `$column`, os métodos `asPercentage()` e `color()`, e o valor padrão `getEmptyValue()`.

Todas as métricas concretas do pacote — `CountMetric`, `SumMetric`, `AverageMetric`, `RawMetric`, `CountManyMetric` e `SumManyMetric` — estendem `BaseMetric`.

## A Classe `Attribute`: base comum entre Métrica e Dimensão

`Attribute` é a classe base compartilhada entre métricas e dimensões. Ela define as três propriedades fundamentais presentes em todo elemento do BI:

| Propriedade | Tipo | Papel |
|---|---|---|
| `$key` | `string` | Identificador no JSON de resposta e alias SQL |
| `$name` | `string` | Nome legível enviado ao front-end |
| `$column` | `string` | Coluna SQL usada na agregação (padrão: igual ao `$key`) |

### Como cada propriedade é usada

O `$key` cumpre dois papéis simultâneos: é o alias SQL usado no `SELECT` (o `as nome`) e é a chave do objeto no JSON retornado pela API. Por isso, deve ser um identificador sem espaços, preferencialmente em snake_case.

O `$name` é exclusivamente informativo — é enviado ao front-end no schema do widget para que a interface exiba um rótulo legível ao usuário.

O `$column` é a coluna real do banco de dados. Por padrão, o pacote assume que o `$column` é igual ao `$key`. Quando eles diferem, use o método `->column()`.

## O Método `->column()`

Quando o nome que você quer usar no JSON (`$key`) difere do nome da coluna no banco de dados, use `->column()` para informar o nome real:

```php
use Luminix\Bi\Metrics\SumMetric;

// $key = 'receita', mas a coluna no banco é 'valor_pedido'
SumMetric::create('receita', 'Receita Total')
    ->column('valor_pedido')
```

O SQL gerado será:

```sql
SELECT SUM(`valor_pedido`) AS `receita`
```

Sem `->column()`, o pacote tentaria executar `SUM(`receita`)`, que provavelmente não existe no banco.

## O Método `->asPercentage()`

`->asPercentage()` transforma o valor numérico bruto em uma representação percentual em relação ao total de todos os registros do resultado. O cálculo é feito em PHP, não em SQL: o método `display()` divide o valor da linha pelo somatório de todos os valores da mesma métrica no resultado.

```php
use Luminix\Bi\Metrics\SumMetric;

SumMetric::create('receita', 'Participação na Receita')
    ->column('valor_pedido')
    ->asPercentage()
```

Se o resultado contiver três linhas com valores `1000`, `2000` e `500`, a saída formatada será respectivamente `28.57%`, `57.14%` e `14.29%`. O valor raw continua existindo — apenas a exibição é alterada.

> `->asPercentage()` opera sobre os dados já retornados pelo banco. Não altera a query SQL. Funciona corretamente quando o widget retorna todos os grupos em uma única consulta, que é o comportamento padrão.

## O Método `->color()`

O método `->color()` define um metadado de cor associado à métrica. Esse valor é enviado ao front-end como parte do schema do widget e pode ser usado por gráficos para colorir séries, barras ou fatias.

```php
use Luminix\Bi\Metrics\CountMetric;

CountMetric::create('total', 'Total de Pedidos')
    ->color('#4CAF50')
```

A cor não afeta a query SQL — é puramente informativa para a camada de apresentação.

## O Método `getEmptyValue()`

`getEmptyValue()` retorna `0` para todas as métricas do pacote. Esse valor é usado pelo widget `LineChart` para preencher datas ausentes no resultado: quando uma data esperada não tem registro no banco, o gráfico insere o valor vazio (`0`) para manter a continuidade da série temporal.

## Tabela Resumo das Métricas

| Métrica | SQL gerado | Uso típico |
|---|---|---|
| `CountMetric` | `COUNT(*) AS \`key\`` | Número de registros |
| `SumMetric` | `SUM(column) AS \`key\`` | Soma de um valor numérico |
| `AverageMetric` | `AVG(column) AS \`key\`` | Média de um valor numérico |
| `RawMetric` | `{raw} AS \`key\`` | Expressão SQL livre |
| `CountManyMetric` | `withCount(relação)` | Contagem de registros relacionados |
| `SumManyMetric` | `withSum(relação, coluna)` | Soma de coluna em registros relacionados |

## Próximos Passos

← [Exportação CSV](../04-widgets/06-csv.md) | → [CountMetric](02-count.md)
