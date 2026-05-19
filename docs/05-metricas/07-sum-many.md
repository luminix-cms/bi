# SumManyMetric

`SumManyMetric` soma uma coluna numérica de um relacionamento Eloquent. Pense nela como a resposta para "qual o total de X em cada Y?": qual o valor total dos itens de cada pedido, qual o peso total dos produtos de cada categoria, qual o saldo total das transações de cada conta.

## Propósito

Enquanto `SumMetric` executa `SUM(coluna)` sobre o modelo principal, `SumManyMetric` usa o método `withSum()` do Eloquent para somar uma coluna em registros de um relacionamento. O resultado é adicionado ao modelo via eager loading, sem join manual.

Internamente, `SumManyMetric` usa `withSum()` e `groupBy($model->getKeyName())` para garantir que cada linha corresponda a um registro distinto do modelo principal.

## Inferência Automática de Relação e Coluna

Quando o `$key` contém o padrão `_sum_`, o pacote infere automaticamente tanto o nome da relação quanto o nome da coluna:

```
'itens_sum_valor' → relação: 'itens', coluna: 'valor'
'transacoes_sum_montante' → relação: 'transacoes', coluna: 'montante'
```

Exemplo usando inferência automática:

```php
// 'itens_sum_valor' → relação 'itens', coluna 'valor'
SumManyMetric::create('itens_sum_valor', 'Valor Total dos Itens')
```

Se o padrão `_sum_` não estiver presente no `$key`, use `->relation()` e `->column()` explicitamente:

```php
SumManyMetric::create('total_itens', 'Valor Total dos Itens')
    ->relation('itens')
    ->column('valor')
```

## O Método `->scope()`

Assim como em `CountManyMetric`, o método `->scope(Closure)` adiciona condições à query do relacionamento:

```php
SumManyMetric::create('itens_sum_valor', 'Valor de Itens Ativos')
    ->scope(fn ($query) => $query->where('status', 'ativo'))
```

Internamente, o pacote passa o closure para o `withSum()` do Eloquent:

```php
$builder->withSum([
    'itens as itens_sum_valor' => fn ($query) => $query->where('status', 'ativo')
], 'valor');
```

## Exemplo com Inferência Automática

O exemplo a seguir soma o valor dos itens de cada pedido, com relação e coluna inferidas automaticamente:

```php
use Luminix\Bi\Metrics\SumManyMetric;
use Luminix\Bi\Dimensions\BelongsToDimension;
use Luminix\Bi\Widgets\Table;

// O model Pedido tem um relacionamento 'itens' com coluna 'valor'
Table::create('valor-por-pedido', 'Valor por Pedido')
    ->dimension(
        BelongsToDimension::create('cliente', 'Cliente')
            ->relation('cliente')
            ->otherColumn('nome')
    )
    ->metric(
        SumManyMetric::create('itens_sum_valor', 'Valor Total')
            ->color('#FF5722')
    )
```

Como `$key` é `itens_sum_valor` e contém `_sum_`, a relação `itens` e a coluna `valor` são inferidas automaticamente. A query resultante é equivalente a:

```sql
SELECT `pedidos`.`id`, SUM(`itens`.`valor`) AS `itens_sum_valor`
FROM `pedidos`
LEFT JOIN `itens` ON `itens`.`pedido_id` = `pedidos`.`id`
GROUP BY `pedidos`.`id`
```

## Exemplo com Relação e Coluna Explícitas

Quando o `$key` não segue o padrão `_sum_`, informe relação e coluna manualmente:

```php
use Luminix\Bi\Metrics\SumManyMetric;

SumManyMetric::create('receita_bruta', 'Receita Bruta')
    ->relation('linhasDePedido')
    ->column('preco_unitario')
    ->color('#4CAF50')
```

SQL equivalente:

```sql
SELECT `pedidos`.`id`, SUM(`linhas_de_pedido`.`preco_unitario`) AS `receita_bruta`
FROM `pedidos`
LEFT JOIN `linhas_de_pedido` ON `linhas_de_pedido`.`pedido_id` = `pedidos`.`id`
GROUP BY `pedidos`.`id`
```

## Exemplo com Scope: Somar Apenas Itens Aprovados

```php
use Luminix\Bi\Metrics\SumManyMetric;

SumManyMetric::create('itens_sum_valor', 'Valor Aprovado')
    ->scope(fn ($query) => $query->where('status', 'aprovado'))
```

> Para que `SumManyMetric` funcione corretamente, o modelo base do dashboard precisa ter o relacionamento definido (ex: `public function itens(): HasMany`). A coluna informada em `->column()` deve existir no modelo relacionado — não no modelo principal.

## Próximos Passos

← [CountManyMetric](06-count-many.md) | → [Visão Geral das Dimensões](../06-dimensoes/01-visao-geral.md)
