# StringFilter

`StringFilter` é o filtro de seleção múltipla por valores textuais. Pense nele como uma caixa de marcação onde o usuário escolhe um ou mais valores de uma lista — por exemplo, "mostrar apenas pedidos com status Aprovado ou Pendente". No SQL, isso se traduz em um `WHERE ... IN (...)`.

## SQL Gerado

`StringFilter` adiciona ao builder a seguinte cláusula:

```sql
WHERE `status` IN ('aprovado', 'pendente')
```

O SQL equivalente no Eloquent é `->whereIn($this->column, $filterData)`, onde `$filterData` é o array de valores enviado pelo front-end.

## Formato do `$filterData`

O `$filterData` que chega ao método `apply()` é um array simples de strings:

```json
["aprovado", "pendente"]
```

Cada elemento do array vira um valor dentro do `IN (...)`. Se o front-end enviar apenas um valor, o array terá um único elemento — o comportamento é o mesmo.

> O front-end deve enviar os valores como um array, mesmo quando houver apenas um elemento selecionado. Um valor único fora de um array causará um erro de tipo no `whereIn`.

## O Método `extra()` e os Valores Distintos

`StringFilter` sobrescreve o método `extra()` para buscar automaticamente os valores distintos da coluna diretamente no banco:

```php
public function extra(Dashboard $dashboard, BiRequest $request): array
{
    return [
        'options' => $dashboard->model::query()
            ->select($this->column)
            ->distinct()
            ->pluck($this->column)
    ];
}
```

O SQL executado internamente é:

```sql
SELECT DISTINCT `status` FROM `pedidos`
```

Isso elimina a necessidade de manter uma lista estática de opções — o dropdown sempre refletirá os valores reais presentes no banco.

### O Endpoint `/filters/{filter}`

Quando o front-end precisa carregar as opções de um `StringFilter`, ele chama:

```
GET /bi-apis/{dashboard}/filters/{filter}
```

A resposta tem o seguinte formato:

```json
{
    "status": 200,
    "extra": {
        "options": ["aprovado", "pendente", "cancelado", "aguardando_pagamento"]
    }
}
```

O front-end usa esse array `options` para montar os itens do dropdown ou da lista de checkboxes.

## O Método `->column()`

Quando o `$key` do filtro difere da coluna real no banco, use `->column()` para apontar a coluna correta. Isso é importante porque o `extra()` também usa `$this->column` para buscar os valores distintos.

```php
// O filtro se chama 'categoria' no front-end,
// mas a coluna no banco é 'categoria_produto'
StringFilter::create('categoria', 'Categoria do Produto')
    ->column('categoria_produto')
```

O SQL de busca de opções será:

```sql
SELECT DISTINCT `categoria_produto` FROM `produtos`
```

E o WHERE aplicado na query do widget será:

```sql
WHERE `categoria_produto` IN ('eletronicos', 'vestuario')
```

## Exemplo: Filtro de Status

O caso de uso mais comum para `StringFilter` é filtrar registros por uma coluna de status com valores fixos.

No dashboard:

```php
use Luminix\Bi\Filters\StringFilter;

public function filters(): array
{
    return [
        StringFilter::create('status', 'Status do Pedido'),
    ];
}
```

Quando o usuário selecionar "aprovado" e "pendente" na interface, a request chegará com:

```
filters[status][]=aprovado&filters[status][]=pendente
```

O widget receberá um builder com:

```sql
WHERE `status` IN ('aprovado', 'pendente')
```

## Exemplo: Filtro com Coluna Diferente

Um filtro de categoria onde a chave no front-end difere da coluna no banco:

```php
use Luminix\Bi\Filters\StringFilter;

public function filters(): array
{
    return [
        StringFilter::create('categoria', 'Categoria')
            ->column('categoria_produto'),
    ];
}
```

O `extra()` buscará `SELECT DISTINCT categoria_produto FROM ...`, e o `WHERE` gerado na query do widget será `WHERE categoria_produto IN (...)`.

## Cuidado com Tabelas Grandes

O `extra()` do `StringFilter` executa `SELECT DISTINCT column FROM tabela` sem nenhum limite. Em tabelas com muitos registros distintos — como uma coluna de texto livre ou uma coluna com alta cardinalidade — essa query pode ser lenta ou retornar um número impraticável de opções para o dropdown.

Nesses casos, considere criar um filtro customizado com um `extra()` que aplique um `LIMIT`, que busque de uma tabela de referência separada, ou que use o `RelationFilter` para buscar de um modelo relacionado com paginação.

## Próximos Passos

← [Visão Geral dos Filtros](01-visao-geral.md) | → [NumberFilter](03-number.md)
