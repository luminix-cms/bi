# Visão Geral dos Filtros

Filtros permitem que o usuário recorte os dados exibidos nos widgets de um dashboard. Cada filtro declarado no método `filters()` do dashboard é aplicado automaticamente a todos os widgets — sem nenhuma configuração adicional por widget.

Quando a request chega com valores de filtro, o pacote injeta as cláusulas `WHERE` correspondentes em cada query antes de executá-la. Se um filtro não estiver presente na request, nenhum `WHERE` é adicionado para ele e a query retorna todos os registros normalmente. Filtros são, portanto, **aditivos e opcionais por design**.

## Tipos de Filtro

| Filtro | Quando usar | WHERE gerado |
|---|---|---|
| `StringFilter` | Coluna de texto com valores discretos (status, tipo, categoria) | `WHERE col IN ('a', 'b')` |
| `NumberFilter` | Coluna numérica com operadores de comparação | `WHERE col >= 100` ou `WHERE col BETWEEN 100 AND 500` |
| `DateFilter` | Seleção de um único dia | `WHERE col BETWEEN '2024-03-15 00:00:00' AND '2024-03-15 23:59:59'` |
| `DateIntervalFilter` | Seleção de um período (início e fim) | `WHERE col BETWEEN '2024-01-01' AND '2024-12-31'` |
| `RelationFilter` | Filtro por registros de um modelo relacionado | `WHERE EXISTS (SELECT 1 FROM related WHERE ... AND id IN (1, 3))` |

## Declarando Filtros no Dashboard

```php
use Luminix\Bi\Dashboard;
use Luminix\Bi\Filters\StringFilter;
use Luminix\Bi\Filters\DateIntervalFilter;
use Carbon\Carbon;

class SalesDashboard extends Dashboard
{
    public string $uriKey = 'sales';
    public string $name = 'Sales';
    public string $model = \App\Models\Order::class;

    public function filters(): array
    {
        return [
            StringFilter::create('status', 'Status'),
            DateIntervalFilter::create('period', 'Period')
                ->column('created_at')
                ->defaultDates(
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ),
        ];
    }
}
```

Com esses dois filtros declarados, uma request com `filters[status][]=active&filters[period][start]=2024-01-01&filters[period][end]=2024-01-31` aplicará automaticamente os `WHERE` correspondentes em todos os widgets do dashboard.

## Como os Filtros São Enviados no Request

Cada tipo de filtro usa um formato diferente na query string:

| Filtro | Formato no request |
|---|---|
| `StringFilter` | `?filters[status][]=active&filters[status][]=pending` |
| `NumberFilter` | `?filters[amount][operator]=between&filters[amount][values][]=100&filters[amount][values][]=500` |
| `DateFilter` | `?filters[date][]=2024-03-15` |
| `DateIntervalFilter` | `?filters[period][start]=2024-01-01&filters[period][end]=2024-03-31` |
| `RelationFilter` | `?filters[category][]=5&filters[category][]=8` |

## O Método `->column()`

Por padrão, o pacote assume que a coluna no banco tem o mesmo nome que o `$key` do filtro. Use `->column()` quando eles diferirem:

```php
// O filtro se chama 'period' na interface,
// mas a coluna no banco é 'created_at'
DateIntervalFilter::create('period', 'Period')
    ->column('created_at')
```

## O Método `extra()`

`StringFilter` e `RelationFilter` implementam o método `extra()`, que é chamado pelo endpoint `GET /bi-apis/{dashboard}/filters/{filter}` quando o frontend precisa carregar as opções de um controle (select, dropdown).

- `StringFilter::extra()` retorna os valores distintos da coluna para popular um select.
- `RelationFilter::extra()` retorna a lista de registros do modelo relacionado com `id` e nome para montar o dropdown.

Os demais filtros (`NumberFilter`, `DateFilter`, `DateIntervalFilter`) não possuem `extra()` — o frontend renderiza os campos sem precisar carregar opções do backend.

## Próximos Passos

← [Uso Básico](../uso-basico.md) | → [StringFilter](02-string.md)
