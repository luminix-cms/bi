# RawDimension

`RawDimension` é a válvula de escape das dimensões: quando nenhuma das dimensões prontas cobre o agrupamento necessário, você escreve a expressão SQL diretamente. Pense nela como um `GROUP BY` personalizado — você define a fórmula de agrupamento, e o pacote cuida do alias e de encaixá-la na query.

## Propósito

As dimensões `StringDimension`, `DateDimension` e `BelongsToDimension` cobrem os casos mais comuns, mas há situações em que o critério de agrupamento exige expressões SQL que essas dimensões não suportam: funções condicionais (`CASE WHEN`), concatenações, funções de data não padronizadas, ou qualquer outra expressão do banco de dados.

## O Método `->raw()`

O método `->raw('expressão SQL')` recebe uma string SQL que será inserida no `SELECT` com o alias `$key` e também no `GROUP BY`:

```php
use Luminix\Bi\Dimensions\RawDimension;

RawDimension::create('trimestre', 'Trimestre')
    ->raw("CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))")
```

SQL gerado:

```sql
SELECT CONCAT(YEAR(created_at), '-Q', QUARTER(created_at)) AS `trimestre`, COUNT(*) AS `total`
FROM `pedidos`
GROUP BY `trimestre`
```

A expressão é inserida no `SELECT` com alias, e o `GROUP BY` usa o alias (`$key`).

## Casos de Uso

- **Trimestre**: `CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))`
- **Semana do ano**: `CONCAT(YEAR(created_at), '-W', WEEK(created_at))`
- **Faixas condicionais**: `CASE WHEN valor < 100 THEN 'Baixo' WHEN valor < 500 THEN 'Médio' ELSE 'Alto' END`
- **Concatenação de colunas**: `CONCAT(cidade, ' - ', estado)`
- **Funções customizadas do banco**: qualquer função definida no banco de dados

## Exemplo: Agrupamento por Trimestre

```php
use Luminix\Bi\Dimensions\RawDimension;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Widgets\Table;

Table::create('receita-por-trimestre', 'Receita por Trimestre')
    ->dimension(
        RawDimension::create('trimestre', 'Trimestre')
            ->raw("CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))")
    )
    ->metric(
        SumMetric::create('receita', 'Receita Total')
            ->column('valor_pedido')
    )
```

SQL gerado:

```sql
SELECT
    CONCAT(YEAR(created_at), '-Q', QUARTER(created_at)) AS `trimestre`,
    SUM(`valor_pedido`) AS `receita`
FROM `pedidos`
GROUP BY `trimestre`
ORDER BY `trimestre` ASC
```

Resposta JSON:

```json
[
  { "trimestre": "2024-Q1", "receita": 125000.00 },
  { "trimestre": "2024-Q2", "receita": 148300.50 },
  { "trimestre": "2024-Q3", "receita": 172400.75 },
  { "trimestre": "2024-Q4", "receita": 198200.00 }
]
```

## Exemplo: Faixas de Valor com CASE WHEN

```php
use Luminix\Bi\Dimensions\RawDimension;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Widgets\Table;

Table::create('pedidos-por-faixa', 'Pedidos por Faixa de Valor')
    ->dimension(
        RawDimension::create('faixa', 'Faixa de Valor')
            ->raw("CASE
                WHEN valor_pedido < 100 THEN 'Até R$ 100'
                WHEN valor_pedido < 500 THEN 'R$ 100 a R$ 500'
                ELSE 'Acima de R$ 500'
            END")
    )
    ->metric(
        CountMetric::create('total', 'Total de Pedidos')
    )
```

## Aviso de Segurança

A expressão passada para `->raw()` é inserida diretamente no SQL sem escape ou parametrização. Nunca construa essa expressão a partir de dados enviados pelo usuário:

```php
// NUNCA faca isso — vulnerabilidade de SQL injection
RawDimension::create('grupo', 'Grupo')
    ->raw($request->input('coluna'))
```

A expressão deve ser sempre uma string literal definida no código-fonte. Se precisar de dinamismo, construa a string em PHP antes de passá-la para `->raw()`, garantindo que os valores variáveis sejam de origem controlada.

## Próximos Passos

← [BelongsToDimension](04-belongs-to.md) | → [Visão Geral dos Filtros](../07-filtros/01-visao-geral.md)
