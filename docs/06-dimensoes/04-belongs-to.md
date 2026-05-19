# BelongsToDimension

`BelongsToDimension` agrupa registros por um modelo relacionado via `belongsTo`. Pense nela como a resposta para "por qual entidade relacionada?": por vendedor, por fornecedor, por categoria (quando categoria é um modelo separado), por cliente. Em vez de agrupar por uma coluna de texto diretamente no modelo principal, ela agrupa pela chave estrangeira e exibe o nome (ou outro atributo) do modelo relacionado.

## Como Funciona

`BelongsToDimension` executa três operações na query:

1. **Seleciona a foreign key**: adiciona ao `SELECT` a coluna de chave estrangeira (ex: `vendedor_id`), inferida automaticamente a partir do relacionamento definido no model
2. **Agrupa pela foreign key**: adiciona `GROUP BY vendedor_id`
3. **Eager loading**: usa `with(relacao)` para carregar o modelo relacionado sem N+1 queries

Na formatação da resposta, o método `display()` acessa o modelo relacionado carregado pelo eager loading, retorna o valor de `$otherColumn` desse modelo, e em seguida descarrega a relação da memória com `unsetRelation()`.

## Configuração Obrigatória

Dois métodos devem ser encadeados na instanciação:

- **`->relation('nomeRelacao')`**: nome do método de relacionamento no model principal (ex: `'vendedor'` para `public function vendedor(): BelongsTo`)
- **`->otherColumn('coluna')`**: nome da propriedade do modelo relacionado a ser exibida no JSON (ex: `'nome'`)

```php
use Luminix\Bi\Dimensions\BelongsToDimension;

BelongsToDimension::create('vendedor', 'Vendedor')
    ->relation('vendedor')
    ->otherColumn('nome')
```

> Se o `$key` for igual ao nome do relacionamento, o `->relation()` pode ser omitido — o pacote usa o `$key` como nome padrão da relação. Na prática, é recomendável sempre informá-lo explicitamente para clareza.

## Exemplo: Agrupar Pedidos por Vendedor

O model `Pedido` tem um relacionamento `belongsTo` com `Vendedor`:

```php
// App\Models\Pedido
public function vendedor(): BelongsTo
{
    return $this->belongsTo(Vendedor::class);
}
```

A dimensão é configurada assim:

```php
use Luminix\Bi\Dimensions\BelongsToDimension;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Widgets\Table;

Table::create('vendas-por-vendedor', 'Vendas por Vendedor')
    ->dimension(
        BelongsToDimension::create('vendedor', 'Vendedor')
            ->relation('vendedor')
            ->otherColumn('nome')
    )
    ->metric(
        SumMetric::create('receita', 'Receita Total')
            ->column('valor_pedido')
    )
```

A query gerada é equivalente a:

```sql
SELECT `vendedor_id`, SUM(`valor_pedido`) AS `receita`
FROM `pedidos`
GROUP BY `vendedor_id`
```

Além da query principal, o Eloquent executa uma segunda query para o eager loading:

```sql
SELECT * FROM `vendedores` WHERE `id` IN (1, 2, 3, ...)
```

A resposta JSON final exibe o nome do vendedor (não o `vendedor_id`):

```json
[
  { "vendedor": "Ana Souza",    "receita": 85400.00 },
  { "vendedor": "Carlos Lima",  "receita": 62100.50 },
  { "vendedor": "Maria Oliveira","receita": 48300.75 }
]
```

## O que é Retornado no JSON

O valor no JSON é o resultado de `$model->vendedor->nome` — uma string simples. O objeto do modelo relacionado não é serializado para o JSON. Após a leitura, a relação é descarregada com `unsetRelation()` para liberar memória quando o resultado contém muitas linhas.

## Ordenação com `applySort()`

`BelongsToDimension` sobrescreve o comportamento padrão de `applySort()`: em vez de ordenar pelo `$key` (que é o alias do resultado), ordena pela foreign key diretamente (`vendedor_id`). Isso preserva a consistência da ordenação mesmo que `$key` seja um alias diferente do nome da coluna.

## Próximos Passos

← [Dimensões de Data](03-datas.md) | → [RawDimension](05-raw.md)
