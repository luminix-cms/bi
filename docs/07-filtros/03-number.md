# NumberFilter

`NumberFilter` é o filtro de comparação numérica. Pense nele como um campo de formulário onde o usuário informa um operador ("maior que", "entre", "igual a") e um ou dois valores numéricos — por exemplo, "mostrar apenas pedidos com valor acima de R$ 100,00". No SQL, isso se traduz em `WHERE coluna >= 100` ou `WHERE coluna BETWEEN 100 AND 500`.

## SQL Gerado

O SQL gerado pelo `NumberFilter` varia conforme o operador informado pelo front-end.

Para operadores simples de comparação:

```sql
WHERE `valor_total` >= 100
```

```sql
WHERE `valor_total` = 500
```

```sql
WHERE `valor_total` < 50
```

Para o operador `between`:

```sql
WHERE `valor_total` BETWEEN 100 AND 500
```

## Formato do `$filterData`

O `$filterData` que chega ao método `apply()` é um objeto JSON com dois campos obrigatórios:

```json
{ "operator": ">=", "values": [100] }
```

```json
{ "operator": "between", "values": [100, 500] }
```

O campo `operator` define a operação SQL a realizar. O campo `values` é sempre um array:

- Para operadores simples (`=`, `<`, `>`, `<=`, `>=`): array com **um** elemento — `values[0]` é o valor da comparação.
- Para `between`: array com **dois** elementos — `values[0]` é o limite inferior e `values[1]` é o limite superior.

## Operadores Suportados

| Operador | SQL gerado | Exemplo de `values` |
|---|---|---|
| `=` | `WHERE col = 100` | `[100]` |
| `<` | `WHERE col < 100` | `[100]` |
| `>` | `WHERE col > 100` | `[100]` |
| `<=` | `WHERE col <= 100` | `[100]` |
| `>=` | `WHERE col >= 100` | `[100]` |
| `between` | `WHERE col BETWEEN 100 AND 500` | `[100, 500]` |

## Filtro Ignorado Quando Operador Está Ausente

Se o campo `operator` vier vazio ou ausente no `$filterData`, o filtro é completamente ignorado — o builder é retornado sem nenhum `WHERE` adicional:

```php
public function apply(Builder $builder, array $filterData, BiRequest $request): Builder
{
    if (empty($filterData['operator'])) return $builder;
    // ...
}
```

Isso é útil para interfaces onde o usuário pode opcionalmente ativar o filtro numérico. Enquanto o usuário não escolher um operador, a query retorna todos os registros.

> Diferentemente do `StringFilter`, o `NumberFilter` não possui um `extra()` que retorne opções. O front-end apenas renderiza os campos de operador e valor, sem precisar carregar dados do back-end.

## O Método `->column()`

Quando o `$key` do filtro difere da coluna real no banco, use `->column()`:

```php
NumberFilter::create('valor', 'Valor do Pedido')
    ->column('valor_total')
```

Sem `->column()`, o filtro tentaria aplicar `WHERE valor >= ...`, que provavelmente não é a coluna correta.

## Exemplo: Filtro de Valor Mínimo

Um filtro simples para exibir apenas pedidos acima de um determinado valor:

```php
use Luminix\Bi\Filters\NumberFilter;

public function filters(): array
{
    return [
        NumberFilter::create('valor', 'Valor do Pedido')
            ->column('valor_total'),
    ];
}
```

Quando o front-end enviar:

```json
{ "filters": { "valor": { "operator": ">=", "values": [100] } } }
```

O builder receberá:

```sql
WHERE `valor_total` >= 100
```

## Exemplo: Filtro de Faixa com `between`

Para permitir que o usuário defina um intervalo de valores:

```php
use Luminix\Bi\Filters\NumberFilter;

public function filters(): array
{
    return [
        NumberFilter::create('faixa_valor', 'Faixa de Valor')
            ->column('valor_total'),
    ];
}
```

Quando o front-end enviar:

```json
{ "filters": { "faixa_valor": { "operator": "between", "values": [100, 500] } } }
```

O builder receberá:

```sql
WHERE `valor_total` BETWEEN 100 AND 500
```

O resultado incluirá apenas pedidos com `valor_total` maior ou igual a 100 e menor ou igual a 500. O operador `between` do Laravel é inclusivo em ambos os extremos.

## Próximos Passos

← [StringFilter](02-string.md) | → [DateFilter](04-date.md)
