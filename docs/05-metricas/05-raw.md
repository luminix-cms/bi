# RawMetric

`RawMetric` é a válvula de escape das métricas: quando nenhuma das métricas prontas atende ao caso de uso, você escreve a expressão SQL diretamente. Pense nela como uma calculadora livre — você define a fórmula, e o pacote cuida do alias e de encaixá-la na query.

## Propósito

As métricas `CountMetric`, `SumMetric` e `AverageMetric` cobrem os casos mais comuns, mas análises reais frequentemente exigem combinações: margem de lucro, taxa de conversão, proporções, valores condicionais. `RawMetric` permite expressar qualquer dessas situações como SQL puro.

## O Método `->raw()`

O método `->raw('expressão SQL')` recebe uma string SQL que será inserida diretamente no `SELECT`. O alias (`AS \`key\``) é adicionado automaticamente pelo pacote:

```php
use Luminix\Bi\Metrics\RawMetric;

RawMetric::create('margem', 'Margem de Lucro')
    ->raw('(SUM(receita) - SUM(custo)) / SUM(receita) * 100')
```

SQL gerado:

```sql
SELECT (SUM(receita) - SUM(custo)) / SUM(receita) * 100 AS `margem`
```

A expressão passada para `->raw()` pode conter qualquer função SQL suportada pelo banco de dados: funções matemáticas, de string, condicionais, subqueries escalares, etc.

## Casos de Uso

- **Margem de lucro**: `(SUM(receita) - SUM(custo)) / SUM(receita) * 100`
- **Taxa de aprovação**: `SUM(CASE WHEN status = 'aprovado' THEN 1 ELSE 0 END) / COUNT(*) * 100`
- **Valor com arredondamento**: `ROUND(AVG(valor_pedido), 2)`
- **Tratamento de nulos**: `AVG(COALESCE(avaliacao, 0))`
- **Percentual calculado no banco**: `SUM(desconto) / SUM(valor_bruto) * 100`

## Exemplo: Margem de Lucro por Categoria

```php
use Luminix\Bi\Metrics\RawMetric;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Widgets\Table;

Table::create('margem-por-categoria', 'Margem por Categoria')
    ->dimension(
        StringDimension::create('categoria', 'Categoria')
    )
    ->metric(
        RawMetric::create('margem', 'Margem (%)')
            ->raw('ROUND((SUM(receita) - SUM(custo)) / NULLIF(SUM(receita), 0) * 100, 2)')
            ->color('#E91E63')
    )
```

A query gerada é equivalente a:

```sql
SELECT
    `categoria` AS `categoria`,
    ROUND((SUM(receita) - SUM(custo)) / NULLIF(SUM(receita), 0) * 100, 2) AS `margem`
FROM `produtos`
GROUP BY `categoria`
```

> O uso de `NULLIF(SUM(receita), 0)` evita divisão por zero quando a receita de um grupo é zero. Sem essa proteção, o MySQL retornaria `NULL` nesse caso, e não um erro — mas é uma boa prática explicitá-la.

## Exemplo: Taxa de Aprovação por Vendedor

```php
use Luminix\Bi\Metrics\RawMetric;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Widgets\Table;

Table::create('aprovacao-por-vendedor', 'Taxa de Aprovação por Vendedor')
    ->dimension(
        StringDimension::create('vendedor', 'Vendedor')
    )
    ->metric(
        RawMetric::create('taxa_aprovacao', 'Taxa de Aprovação (%)')
            ->raw('ROUND(SUM(CASE WHEN status = \'aprovado\' THEN 1 ELSE 0 END) / COUNT(*) * 100, 1)')
    )
```

## Aviso de Segurança

A expressão passada para `->raw()` é inserida diretamente no SQL sem nenhum tipo de escape ou parametrização. Nunca construa essa expressão a partir de dados enviados pelo usuário:

```php
// NUNCA faca isso — vulnerabilidade de SQL injection
RawMetric::create('valor', 'Valor')
    ->raw($request->input('formula'))
```

A expressão deve ser sempre uma string literal definida no código-fonte, escrita pelo desenvolvedor. Se precisar de dinamismo, use construtores condicionais em PHP antes de passar a string final para `->raw()`.

## Próximos Passos

← [AverageMetric](04-average.md) | → [CountManyMetric](06-count-many.md)
