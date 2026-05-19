# RelationFilter

`RelationFilter` é o filtro por relacionamento Eloquent. Pense nele como um dropdown onde o usuário escolhe um ou mais registros de um modelo relacionado — por exemplo, "mostrar apenas os pedidos feitos pelos vendedores João e Maria". Internamente, o filtro verifica a existência do relacionamento com `whereHas`, o que garante que somente os pedidos vinculados aos vendedores selecionados sejam retornados.

## Propósito

Enquanto o `StringFilter` filtra por valores de uma coluna do próprio modelo principal, o `RelationFilter` filtra pela presença de registros em um modelo relacionado com IDs específicos. Isso é útil quando a "categoria" de um registro não é uma string diretamente na tabela, mas sim uma chave estrangeira apontando para outra tabela.

## SQL Gerado

`RelationFilter` traduz a seleção de IDs em uma subconsulta de existência:

```sql
WHERE EXISTS (
    SELECT 1
    FROM `vendedores`
    WHERE `vendedores`.`pedido_id` = `pedidos`.`id`
    AND `vendedores`.`id` IN (1, 3, 7)
)
```

O Eloquent cuida do `JOIN` implícito através do método `whereHas`. O filtro apenas informa quais IDs do modelo relacionado devem estar presentes.

## Formato do `$filterData`

O `$filterData` que chega ao método `apply()` é um array de IDs inteiros do modelo relacionado:

```json
[1, 3, 7]
```

Cada número é uma chave primária do modelo relacionado. O `RelationFilter` usa `$this->getRelatedModel($builder)->getKeyName()` para descobrir o nome correto da coluna de chave primária do modelo relacionado, tornando o filtro agnóstico ao nome da PK.

## Herança de `BaseRelationFilter`

`RelationFilter` estende `BaseRelationFilter`, que por sua vez estende `BaseFilter`. `BaseRelationFilter` adiciona dois métodos de configuração:

### `->relation()`

Define o nome da relação Eloquent no modelo principal. Por padrão, o pacote assume que o nome da relação é igual ao `$key` do filtro:

```php
RelationFilter::create('vendedor', 'Vendedor')
// A relação assumida é 'vendedor' — igual ao $key
```

Quando o nome da relação difere do `$key`:

```php
RelationFilter::create('responsavel', 'Responsável')
    ->relation('vendedor') // a relação no modelo se chama 'vendedor'
```

### `->otherColumn()`

Define a coluna do modelo relacionado que será exibida como rótulo no dropdown do front-end. O padrão é `'name'`:

```php
RelationFilter::create('vendedor', 'Vendedor')
    ->otherColumn('nome_completo') // exibir 'nome_completo' no dropdown
```

### Relações Aninhadas

`BaseRelationFilter` suporta relações aninhadas usando ponto como separador:

```php
RelationFilter::create('empresa', 'Empresa')
    ->relation('vendedor.empresa') // atravessa vendedor → empresa
```

O `getRelatedModel()` percorre a cadeia de relações para resolver o modelo final.

## O Método `extra()` e as Opções do Dropdown

`RelationFilter` sobrescreve `extra()` para retornar as opções que o front-end exibirá no dropdown:

```php
public function extra(Dashboard $dashboard, BiRequest $request): array
{
    return [
        'options'     => $query->select($related->getKeyName(), $this->otherColumn ?? 'name')->get(),
        'otherColumn' => $this->otherColumn,
        'primaryKey'  => $related->getKeyName(),
    ];
}
```

A resposta do endpoint `/bi-apis/{dashboard}/filters/{filter}` tem o seguinte formato:

```json
{
    "status": 200,
    "extra": {
        "options": [
            { "id": 1, "nome_completo": "João Silva" },
            { "id": 3, "nome_completo": "Maria Santos" },
            { "id": 7, "nome_completo": "Pedro Oliveira" }
        ],
        "otherColumn": "nome_completo",
        "primaryKey": "id"
    }
}
```

O front-end usa `primaryKey` para saber qual campo enviar como valor na request, e `otherColumn` para saber qual campo exibir como rótulo.

### Segurança nas Opções com `QueryService`

O `extra()` do `RelationFilter` usa `QueryService.create()` para construir a query de busca das opções. Isso garante que:

- Se o modelo relacionado for um modelo Luminix com gates habilitados, o escopo `->allowed('read')` é aplicado automaticamente, restringindo as opções apenas aos registros que o usuário autenticado tem permissão de leitura.
- Se a conexão `BI_DB_CONNECTION` estiver configurada, a query usará essa conexão.

Assim, o dropdown nunca exibirá opções que o usuário não teria direito de ver.

## O Método `->scope()`

`->scope()` aceita uma `Closure` que é aplicada **tanto nas opções do dropdown** (no `extra()`) **quanto na verificação de existência** (no `apply()`). Isso garante que o filtro seja consistente: o usuário só vê e pode selecionar as opções que de fato se aplicam.

```php
RelationFilter::create('vendedor', 'Vendedor')
    ->scope(function ($query) {
        return $query->where('ativo', true);
    })
```

Com esse `scope`, o dropdown mostrará apenas vendedores com `ativo = true`, e a cláusula `whereHas` também verificará apenas entre os vendedores ativos.

## Exemplo: Filtrar Pedidos por Vendedor

```php
use Luminix\Bi\Filters\RelationFilter;

public function filters(): array
{
    return [
        RelationFilter::create('vendedor', 'Vendedor')
            ->otherColumn('nome'),
    ];
}
```

Quando o usuário selecionar os vendedores com IDs 1 e 3, a request chegará com:

```json
{ "filters": { "vendedor": [1, 3] } }
```

O builder receberá:

```sql
WHERE EXISTS (
    SELECT 1
    FROM `vendedores`
    WHERE `vendedores`.`pedido_id` = `pedidos`.`id`
    AND `vendedores`.`id` IN (1, 3)
)
```

## Exemplo com Scope: Apenas Vendedores Ativos

```php
use Luminix\Bi\Filters\RelationFilter;

public function filters(): array
{
    return [
        RelationFilter::create('vendedor', 'Vendedor')
            ->otherColumn('nome')
            ->scope(function ($query) {
                return $query->where('ativo', true);
            }),
    ];
}
```

O dropdown exibirá apenas vendedores ativos. A query de verificação de existência também restringirá ao subconjunto ativo:

```sql
WHERE EXISTS (
    SELECT 1
    FROM `vendedores`
    WHERE `vendedores`.`pedido_id` = `pedidos`.`id`
    AND `vendedores`.`ativo` = 1
    AND `vendedores`.`id` IN (1, 3)
)
```

Isso evita a situação em que um vendedor inativo poderia aparecer nos filtros se seus IDs fossem enviados diretamente na request.

## Próximos Passos

← [DateIntervalFilter](05-date-interval.md) | → [Autenticação e Autorização das Rotas](../08-seguranca/01-rotas.md)
