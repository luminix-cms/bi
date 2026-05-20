# SumManyMetric

`SumManyMetric` soma uma coluna numérica de um relacionamento Eloquent. É a resposta para "qual o total de X em cada Y?": qual o valor total dos itens de cada pedido, qual o peso total dos produtos de cada categoria, qual o saldo total das transações de cada conta.

Internamente, usa o método `withSum()` do Eloquent — sem necessidade de join manual.

## Convenção de Nomeação do `$key`

O `$key` deve seguir o padrão `{relation}_sum_{column}`, onde `{relation}` é o nome do método de relacionamento e `{column}` é a coluna a ser somada. O pacote infere automaticamente ambos a partir do `$key`:

```
'items_sum_price'        → relação: 'items',        coluna: 'price'
'transactions_sum_amount'→ relação: 'transactions', coluna: 'amount'
```

```php
// Relação 'items' e coluna 'price' inferidas automaticamente
SumManyMetric::create('items_sum_price', 'Valor Total dos Itens')
```

Se o `$key` não seguir o padrão `_sum_`, informe relação e coluna com `->relation()` e `->column()`:

```php
SumManyMetric::create('gross_revenue', 'Receita Bruta')
    ->relation('orderLines')
    ->column('unit_price')
```

## Uso

```php
SumManyMetric::create($key, $name)
```

## Exemplo: Valor Total dos Itens por Pedido

O model `Order` possui um relacionamento `items` com coluna `price`:

```php
// App\Models\Order
public function items(): HasMany
{
    return $this->hasMany(Item::class);
}
```

```php
use Luminix\Bi\Metrics\SumManyMetric;
use Luminix\Bi\Dimensions\BelongsToDimension;
use Luminix\Bi\Widgets\Table;

Table::create('value-per-order', 'Valor por Pedido')
    ->dimension(
        BelongsToDimension::create('customer', 'Cliente')
            ->relation('customer')
            ->otherColumn('name')
    )
    ->metric(
        SumManyMetric::create('items_sum_price', 'Valor Total')
            ->color('#FF5722')
    )
```

Como `$key` é `items_sum_price`, a relação `items` e a coluna `price` são inferidas automaticamente.

## Exemplo com `->scope()`

Use `->scope(Closure)` para somar apenas um subconjunto dos registros relacionados:

```php
use Luminix\Bi\Metrics\SumManyMetric;

SumManyMetric::create('items_sum_price', 'Valor de Itens Aprovados')
    ->scope(fn ($query) => $query->where('status', 'approved'))
```

O closure é passado diretamente para o `withSum()` do Eloquent, filtrando quais registros entram na soma.

> A coluna informada (via convenção ou `->column()`) deve existir no model relacionado, não no model principal do dashboard.

## Próximos Passos

← [CountManyMetric](06-count-many.md) | → [Visão Geral das Dimensões](../06-dimensoes/01-visao-geral.md)
