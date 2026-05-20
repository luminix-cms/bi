# CountManyMetric

`CountManyMetric` conta os registros de um relacionamento Eloquent, em vez de contar as linhas do modelo principal. É a resposta para "quantos itens cada X tem?": quantos itens cada pedido contém, quantos comentários cada post recebeu, quantos endereços cada cliente cadastrou.

Internamente, usa o método `withCount()` do Eloquent — sem necessidade de join manual.

## Convenção de Nomeação do `$key`

O `$key` deve seguir o padrão `{relation}_count`, onde `{relation}` é o nome do método de relacionamento no model. O pacote infere automaticamente o nome da relação removendo o sufixo `_count`:

```
'items_count'    → relação inferida: 'items'
'comments_count' → relação inferida: 'comments'
```

```php
// Relação inferida automaticamente como 'items'
CountManyMetric::create('items_count', 'Quantidade de Itens')
```

Se o `$key` não terminar em `_count`, informe a relação com `->relation()`:

```php
CountManyMetric::create('total_items', 'Quantidade de Itens')
    ->relation('items')
```

## Uso

```php
CountManyMetric::create($key, $name)
```

## Exemplo: Quantidade de Itens por Pedido

O model `Order` possui um relacionamento `items`:

```php
// App\Models\Order
public function items(): HasMany
{
    return $this->hasMany(Item::class);
}
```

```php
use Luminix\Bi\Metrics\CountManyMetric;
use Luminix\Bi\Dimensions\BelongsToDimension;
use Luminix\Bi\Widgets\Table;

Table::create('items-per-order', 'Itens por Pedido')
    ->dimension(
        BelongsToDimension::create('customer', 'Cliente')
            ->relation('customer')
            ->otherColumn('name')
    )
    ->metric(
        CountManyMetric::create('items_count', 'Qtd. de Itens')
    )
```

Como `$key` é `items_count`, a relação `items` é inferida automaticamente.

## Exemplo com `->scope()`

Use `->scope(Closure)` para contar apenas um subconjunto dos registros relacionados:

```php
use Luminix\Bi\Metrics\CountManyMetric;

CountManyMetric::create('active_items_count', 'Itens Ativos')
    ->scope(fn ($query) => $query->where('status', 'active'))
```

O closure é passado diretamente para o `withCount()` do Eloquent, filtrando quais registros relacionados entram na contagem.

## `CountManyMetric` vs `CountMetric`

| Pergunta | Métrica correta |
|---|---|
| Quantos pedidos existem por status? | `CountMetric` |
| Quantos itens cada pedido tem? | `CountManyMetric` |
| Quantos usuários se cadastraram por dia? | `CountMetric` com `DayDimension` |

## Próximos Passos

← [RawMetric](05-raw.md) | → [SumManyMetric](07-sum-many.md)
