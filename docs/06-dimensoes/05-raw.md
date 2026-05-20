# RawDimension

`RawDimension` é usada quando nenhuma das dimensões prontas cobre o agrupamento necessário. Você escreve a expressão SQL diretamente, e o pacote cuida de encaixá-la no `SELECT` e no `GROUP BY` com o alias correto.

## Quando Usar

Use `RawDimension` para critérios de agrupamento que `StringDimension`, as dimensões de data e `BelongsToDimension` não suportam:

- Trimestre: `CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))`
- Semana do ano: `CONCAT(YEAR(created_at), '-W', WEEK(created_at))`
- Faixas condicionais: `CASE WHEN total_amount < 100 THEN 'Low' WHEN total_amount < 500 THEN 'Medium' ELSE 'High' END`
- Concatenação de colunas: `CONCAT(city, ' - ', state)`

## Uso

```php
RawDimension::create($key, $name, $raw)
```

O terceiro parâmetro `$raw` é a expressão SQL inserida no `SELECT` com alias `$key` e repetida no `GROUP BY`.

## Exemplo: Receita por Trimestre

```php
use Luminix\Bi\Dimensions\RawDimension;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Widgets\Table;

Table::create('revenue-by-quarter', 'Receita por Trimestre')
    ->dimension(
        RawDimension::create(
            'quarter',
            'Trimestre',
            "CONCAT(YEAR(created_at), '-Q', QUARTER(created_at))"
        )
    )
    ->metric(
        SumMetric::create('revenue', 'Receita Total', 'total_amount')
    )
```

Resposta JSON:

```json
[
  { "quarter": "2024-Q1", "revenue": 125000.00 },
  { "quarter": "2024-Q2", "revenue": 148300.50 },
  { "quarter": "2024-Q3", "revenue": 172400.75 },
  { "quarter": "2024-Q4", "revenue": 198200.00 }
]
```

## Exemplo: Faixas de Valor com CASE WHEN

```php
use Luminix\Bi\Dimensions\RawDimension;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Widgets\Table;

Table::create('orders-by-value-range', 'Pedidos por Faixa de Valor')
    ->dimension(
        RawDimension::create(
            'value_range',
            'Faixa de Valor',
            "CASE
                WHEN total_amount < 100 THEN 'Up to $100'
                WHEN total_amount < 500 THEN '$100 to $500'
                ELSE 'Over $500'
            END"
        )
    )
    ->metric(
        CountMetric::create('total', 'Total de Pedidos')
    )
```

## Aviso de Segurança

A expressão passada como `$raw` é inserida diretamente no SQL sem escape ou parametrização. Nunca construa essa string a partir de dados enviados pelo usuário — isso cria uma vulnerabilidade de SQL injection. A expressão deve ser sempre uma string literal definida no código-fonte.

## Próximos Passos

← [BelongsToDimension](04-belongs-to.md) | → [Visão Geral dos Filtros](../07-filtros/01-visao-geral.md)
