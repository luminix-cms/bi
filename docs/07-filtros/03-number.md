# NumberFilter

`NumberFilter` filtra registros por comparação numérica. O usuário informa um operador e um ou dois valores — por exemplo, "valor acima de 100" ou "valor entre 100 e 500".

## Exemplo

```php
use Luminix\Bi\Filters\NumberFilter;

public function filters(): array
{
    return [
        NumberFilter::create('amount', 'Order Amount')
            ->column('total_amount'),
    ];
}
```

## Operadores Disponíveis

| Operador | SQL gerado | Valores esperados |
|---|---|---|
| `=` | `WHERE col = 100` | `[100]` |
| `<` | `WHERE col < 100` | `[100]` |
| `>` | `WHERE col > 100` | `[100]` |
| `<=` | `WHERE col <= 100` | `[100]` |
| `>=` | `WHERE col >= 100` | `[100]` |
| `between` | `WHERE col BETWEEN 100 AND 500` | `[100, 500]` |

Para operadores simples, `values` contém um único elemento. Para `between`, contém dois: o limite inferior e o superior (ambos inclusivos).

Se o campo `operator` vier ausente ou vazio na request, o filtro é ignorado e a query não recebe nenhum `WHERE` adicional.

## Formato de Envio no Request

Operador simples:

```
?filters[amount][operator]=>=&filters[amount][values][]=100
```

Intervalo com `between`:

```
?filters[amount][operator]=between&filters[amount][values][]=100&filters[amount][values][]=500
```

Isso gera, respectivamente:

```sql
WHERE `total_amount` >= 100
WHERE `total_amount` BETWEEN 100 AND 500
```

## Exemplos Práticos

Filtro de valor mínimo:

```php
NumberFilter::create('min_amount', 'Minimum Amount')
    ->column('total_amount'),
```

Request: `?filters[min_amount][operator]=>=&filters[min_amount][values][]=500`

Faixa de quantidade:

```php
NumberFilter::create('qty_range', 'Quantity Range')
    ->column('quantity'),
```

Request: `?filters[qty_range][operator]=between&filters[qty_range][values][]=10&filters[qty_range][values][]=50`

## Próximos Passos

← [StringFilter](02-string.md) | → [DateFilter](04-date.md)
