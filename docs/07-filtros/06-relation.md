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
    WHERE `orders`.`category_id` = `categories`.`id`
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

Os wheres do `->scope()` ficam agrupados, tanto nas opções quanto no `whereHas`. Um `orWhere` dentro dele não alarga a seleção: a query é `(escopo) AND id IN (seleção)`, e uma seleção nunca devolve linhas fora dela.

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

### Quais registros aparecem

As opções são os registros do modelo relacionado **que as linhas do dashboard referenciam**. Elas partem da mesma base de dados das linhas dos widgets: a connection configurada em `luminix.bi.connection`, o `allowed()` do modelo do dashboard, se for um modelo Luminix, e o `scope()` do dashboard (veja [Escopo Global](../03-dashboards/03-escopo.md)). Dessas linhas, o filtro segue a relação até o modelo relacionado. Sobre os registros alcançados ainda valem o `allowed()` do modelo relacionado e o `->scope()` do filtro.

Num dashboard recortado por tenant, um `RelationFilter` de `customer` lista só os clientes que têm pedidos daquele tenant, mesmo que `Customer` seja um modelo Eloquent comum.

O `->scope()` de cada widget e os demais filtros não entram, então a lista cobre as linhas do dashboard, não as de um widget específico.

O recorte vale também para relações aninhadas (`'company.sector'`) e para `HasOne`, `HasMany`, `BelongsToMany` e `MorphOne`/`MorphMany`. Nos modelos que o caminho atravessa, só valem os escopos globais do próprio modelo, como `SoftDeletes`. O `allowed()` desses modelos fica de fora, e o `whereHas` do filtro também não o aplica, então a lista bate com o que o `apply()` encontra.

As restrições declaradas no método da relação (`hasMany(...)->where('active', true)`, `wherePivot()`) também ficam de fora do recorte, ao contrário do `whereHas`, que as aplica. Uma relação assim pode oferecer um registro que, ao ser selecionado, não devolve linhas.

> Um registro que nenhuma linha referencia **não aparece**: uma categoria sem pedidos some da lista, porque selecioná-la não devolveria nada.

### `->allOptions()` — listar todos os registros

Para um catálogo que deve aparecer inteiro, use `->allOptions()`. Ele desliga o recorte pelas linhas do dashboard e restaura a lista de todos os registros que o `allowed()` e o `->scope()` do filtro deixam passar:

```php
RelationFilter::create('category', 'Category')
    ->allOptions(),
```

Use-o também quando a query do recorte ficar cara. Em tabelas grandes, o recorte faz um `IN (subquery)` sobre as linhas do dashboard, e uma chave estrangeira sem índice pesa.

Relações `HasManyThrough`, `HasOneThrough`, `MorphTo` e `MorphToMany` não podem ser seguidas a partir das linhas do dashboard. Com elas, `extra()` lança uma `LogicException` que pede `->allOptions()`.

## Próximos Passos

← [DateIntervalFilter](05-date-interval.md) | → [Segurança: Rotas e Middleware](../08-seguranca/01-rotas.md)
