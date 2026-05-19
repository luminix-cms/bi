# CountManyMetric

`CountManyMetric` conta os registros de um relacionamento Eloquent, em vez de contar as linhas do modelo principal. Pense nela como a resposta para "quantos itens cada X tem?": quantos itens cada pedido contém, quantos comentários cada post recebeu, quantos endereços cada cliente cadastrou.

## Propósito

Enquanto `CountMetric` executa `COUNT(*)` sobre o modelo principal da query, `CountManyMetric` usa o método `withCount()` do Eloquent para contar registros em um relacionamento. O resultado é uma coluna adicionada ao modelo via eager loading, sem necessidade de join manual.

Internamente, `CountManyMetric` usa `withCount()` e `groupBy($model->getKeyName())` para garantir que cada linha do resultado corresponda a um registro distinto do modelo principal.

## Inferência Automática de Relação

Quando o `$key` termina com o sufixo `_count`, o pacote infere automaticamente o nome da relação a partir da parte anterior:

```php
// 'itens_count' → relação inferida: 'itens'
CountManyMetric::create('itens_count', 'Quantidade de Itens')
```

Se o nome da relação difere do padrão inferido, use `->relation()`:

```php
// key 'produtos' não termina em '_count', portanto a relação deve ser explícita
CountManyMetric::create('produtos', 'Itens do Pedido')
    ->relation('itensDoPedido')
```

## O Método `->scope()`

O método `->scope(Closure)` permite adicionar condições à query de contagem do relacionamento. Isso é útil quando você quer contar apenas um subconjunto dos registros relacionados:

```php
CountManyMetric::create('itens_ativos_count', 'Itens Ativos')
    ->scope(fn ($query) => $query->where('ativo', true))
```

Internamente, o pacote passa esse closure para o `withCount()` do Eloquent:

```php
$builder->withCount([
    'itens as itens_ativos_count' => fn ($query) => $query->where('ativo', true)
]);
```

## Exemplo Simples

O exemplo a seguir conta quantos itens cada pedido possui:

```php
use Luminix\Bi\Metrics\CountManyMetric;
use Luminix\Bi\Dimensions\BelongsToDimension;
use Luminix\Bi\Widgets\Table;

// O Pedido tem um relacionamento 'itens' definido no model
Table::create('itens-por-pedido', 'Itens por Pedido')
    ->dimension(
        BelongsToDimension::create('cliente', 'Cliente')
            ->relation('cliente')
            ->otherColumn('nome')
    )
    ->metric(
        CountManyMetric::create('itens_count', 'Quantidade de Itens')
    )
```

Como `$key` é `itens_count` e termina em `_count`, a relação `itens` é inferida automaticamente. A query resultante é equivalente a:

```sql
SELECT `pedidos`.`id`, COUNT(`itens`.`id`) AS `itens_count`
FROM `pedidos`
LEFT JOIN `itens` ON `itens`.`pedido_id` = `pedidos`.`id`
GROUP BY `pedidos`.`id`
```

## Exemplo com Relação Explícita e Scope

Para contar apenas os itens com `status = 'ativo'` de cada pedido, com relação definida explicitamente:

```php
use Luminix\Bi\Metrics\CountManyMetric;
use Luminix\Bi\Widgets\BigNumber;

// Pedido tem relacionamento 'linhasAtivas' com escopo de status
CountManyMetric::create('itens_ativos_count', 'Itens Ativos')
    ->relation('itens')
    ->scope(fn ($query) => $query->where('status', 'ativo'))
```

## Diferença em Relação a `CountMetric`

| Situação | Métrica correta |
|---|---|
| Quantos pedidos existem? | `CountMetric` |
| Quantos itens cada pedido tem? | `CountManyMetric` |
| Quantos comentários cada post recebeu? | `CountManyMetric` |
| Quantos usuários se cadastraram por dia? | `CountMetric` com `DayDimension` |

`CountMetric` conta linhas do modelo base da query. `CountManyMetric` conta linhas de uma relação desse modelo.

> Para que `CountManyMetric` funcione corretamente, o modelo base do dashboard precisa ter o relacionamento definido com o método correspondente (ex: `public function itens(): HasMany`). O Eloquent resolve a query do relacionamento automaticamente.

## Próximos Passos

← [RawMetric](05-raw.md) | → [SumManyMetric](07-sum-many.md)
