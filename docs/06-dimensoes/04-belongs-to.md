# BelongsToDimension

`BelongsToDimension` agrupa registros por um modelo relacionado via `belongsTo`. Em vez de agrupar por uma coluna de texto do modelo principal, ela agrupa pela chave estrangeira e exibe o valor de um atributo do modelo relacionado — como o nome do vendedor, o título da categoria ou o e-mail do cliente.

## Como Funciona

`BelongsToDimension` realiza três operações:

1. Agrupa pela chave estrangeira (ex: `category_id`) com `GROUP BY`
2. Adiciona `with(relation)` para carregar o modelo relacionado sem N+1 queries
3. No JSON de resposta, exibe o valor de `$otherColumn` do modelo relacionado (ex: `name`) em vez do ID

## Uso

```php
BelongsToDimension::create($key, $name)
    ->relation($relationName)
    ->otherColumn($column)
```

- `->relation()`: nome do método de relacionamento no model principal
- `->otherColumn()`: atributo do modelo relacionado a ser exibido no JSON

Se o `$key` for igual ao nome do relacionamento, `->relation()` pode ser omitido — mas informá-lo explicitamente é recomendável para clareza.

## Exemplo: Pedidos por Categoria

O model `Order` tem um relacionamento `belongsTo` com `Category`:

```php
// App\Models\Order
public function category(): BelongsTo
{
    return $this->belongsTo(Category::class);
}
```

```php
use Luminix\Bi\Dimensions\BelongsToDimension;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Widgets\Table;

Table::create('revenue-by-category', 'Receita por Categoria')
    ->dimension(
        BelongsToDimension::create('category', 'Categoria')
            ->relation('category')
            ->otherColumn('name')
    )
    ->metric(
        SumMetric::create('revenue', 'Receita Total', 'total_amount')
    )
```

A query agrupa por `category_id`. O Eloquent carrega os registros de `categories` via eager loading. A resposta exibe o `name` da categoria, não o `category_id`:

```json
[
  { "category": "Electronics", "revenue": 125000.50 },
  { "category": "Clothing",    "revenue": 48300.00  },
  { "category": "Books",       "revenue": 9750.75   }
]
```

## Próximos Passos

← [Dimensões de Data](03-datas.md) | → [RawDimension](05-raw.md)
