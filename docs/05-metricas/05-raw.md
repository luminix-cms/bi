# RawMetric

`RawMetric` é usada quando nenhuma das métricas prontas atende ao caso de uso. Você escreve a expressão SQL diretamente, e o pacote cuida de encaixá-la no `SELECT` com o alias correto.

## Quando Usar

Use `RawMetric` para lógicas que `CountMetric`, `SumMetric` e `AverageMetric` não cobrem:

- Margem de lucro: `(SUM(revenue) - SUM(cost)) / SUM(revenue) * 100`
- Taxa de conversão: `SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) / COUNT(*) * 100`
- Média com tratamento de nulos: `AVG(COALESCE(rating, 0))`
- Arredondamento: `ROUND(AVG(total_amount), 2)`

## Uso

```php
RawMetric::create($key, $name, $raw)
```

O terceiro parâmetro `$raw` é a expressão SQL que será inserida diretamente no `SELECT`. O alias `AS \`key\`` é adicionado automaticamente.

## Exemplo: Margem de Lucro por Categoria

```php
use Luminix\Bi\Metrics\RawMetric;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Widgets\Table;

Table::create('margin-by-category', 'Margem por Categoria')
    ->dimension(
        StringDimension::create('category', 'Categoria')
    )
    ->metric(
        RawMetric::create(
            'margin',
            'Margem (%)',
            'ROUND((SUM(revenue) - SUM(cost)) / NULLIF(SUM(revenue), 0) * 100, 2)'
        )->color('#E91E63')
    )
```

O `NULLIF(SUM(revenue), 0)` evita divisão por zero quando a receita de um grupo é zero.

## Exemplo: Taxa de Aprovação por Vendedor

```php
use Luminix\Bi\Metrics\RawMetric;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Widgets\Table;

Table::create('approval-rate-by-seller', 'Taxa de Aprovação por Vendedor')
    ->dimension(
        StringDimension::create('seller', 'Vendedor')
    )
    ->metric(
        RawMetric::create(
            'approval_rate',
            'Taxa de Aprovação (%)',
            "ROUND(SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) / COUNT(*) * 100, 1)"
        )
    )
```

## Aviso de Segurança

A expressão passada como `$raw` é inserida diretamente no SQL sem escape ou parametrização. Nunca construa essa string a partir de dados enviados pelo usuário — isso cria uma vulnerabilidade de SQL injection. A expressão deve ser sempre uma string literal definida no código-fonte.

## Próximos Passos

← [AverageMetric](04-average.md) | → [CountManyMetric](06-count-many.md)
