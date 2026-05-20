# RelationFilter

`RelationFilter` filtra registros pela presença de relacionamentos Eloquent. O usuário seleciona um ou mais registros de um modelo relacionado e o filtro usa `whereHas` para retornar apenas os registros que possuem esse relacionamento.

É o filtro indicado quando a informação usada como critério não é uma coluna no próprio modelo, mas sim uma chave estrangeira apontando para outra tabela — por exemplo, filtrar pedidos por categoria ou por vendedor.

## Exemplo

```php
use Luminix\Bi\Filters\RelationFilter;

public function filters(): array
{
    return [
        RelationFilter::create('category', 'Category')
            ->otherColumn('name'),
    ];
}
```

O primeiro argumento é o `$key` (e também o nome da relação Eloquent no modelo, por padrão). O segundo é o nome exibido na interface. `->otherColumn()` define qual coluna do modelo relacionado é exibida como rótulo no dropdown (padrão: `'name'`).

## Formato de Envio no Request

O frontend envia os IDs dos registros selecionados como array:

```
?filters[category][]=5&filters[category][]=8
```

Isso gera:

```sql
WHERE EXISTS (
    SELECT 1 FROM `categories`
    WHERE `categories`.`order_id` = `orders`.`id`
    AND `categories`.`id` IN (5, 8)
)
```

## Relações Aninhadas

Use notação de ponto para atravessar relações:

```php
RelationFilter::create('brand', 'Brand')
    ->relation('category.brand'),
```

O filtro percorre a cadeia `category → brand` para resolver o modelo e construir o `whereHas`.

## O Método `->scope()`

`->scope()` restringe tanto as opções exibidas no dropdown quanto a verificação de existência na query. Isso garante consistência: o usuário só vê e pode selecionar opções que de fato se aplicam.

```php
RelationFilter::create('category', 'Category')
    ->otherColumn('name')
    ->scope(function ($query) {
        return $query->where('active', true);
    }),
```

Com esse escopo, o dropdown exibirá apenas categorias ativas, e o `whereHas` também filtrará apenas entre categorias ativas.

## O Método `extra()` — Opções para o Dropdown

`RelationFilter` implementa `extra()`, chamado pelo endpoint `GET /bi-apis/{dashboard}/filters/{filter}`. Ele retorna a lista de registros do modelo relacionado para popular o dropdown:

```json
{
    "status": 200,
    "extra": {
        "options": [
            { "id": 5, "name": "Electronics" },
            { "id": 8, "name": "Clothing" }
        ],
        "otherColumn": "name",
        "primaryKey": "id"
    }
}
```

O frontend usa `primaryKey` para saber qual campo enviar como valor na request, e `otherColumn` para saber qual campo exibir como rótulo.

## Próximos Passos

← [DateIntervalFilter](05-date-interval.md) | → [Segurança: Rotas e Middleware](../08-seguranca/01-rotas.md)
