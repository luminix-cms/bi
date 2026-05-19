# StringDimension

`StringDimension` é a dimensão mais direta do pacote: agrupa os registros pelo valor de uma coluna textual ou categórica. Pense nela como a resposta para "por qual categoria?": por status do pedido, por tipo de produto, por região, por canal de vendas.

## SQL Gerado

`StringDimension` adiciona ao `SELECT` a coluna com seu alias e ao `GROUP BY` o alias correspondente:

```sql
SELECT `status` AS `status`, COUNT(*) AS `total`
FROM `pedidos`
GROUP BY `status`
```

A forma geral é:

```sql
SELECT {column} AS {key} ... GROUP BY {key}
```

O `GROUP BY` usa o alias (`$key`) e não o nome original da coluna. Isso garante consistência mesmo quando `$key` e `$column` diferem.

## Uso Típico

- Agrupar pedidos por status (`pendente`, `aprovado`, `cancelado`)
- Agrupar produtos por categoria
- Agrupar clientes por estado ou região
- Agrupar transações por tipo de pagamento
- Agrupar atendimentos por operador

## Exemplo: Agrupamento por Status de Pedido

```php
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Widgets\Table;

Table::create('pedidos-por-status', 'Pedidos por Status')
    ->dimension(
        StringDimension::create('status', 'Status do Pedido')
    )
    ->metric(
        CountMetric::create('total', 'Total')
    )
```

SQL gerado:

```sql
SELECT `status` AS `status`, COUNT(*) AS `total`
FROM `pedidos`
GROUP BY `status`
```

Resposta JSON:

```json
[
  { "status": "aprovado",  "total": 142 },
  { "status": "pendente",  "total": 58  },
  { "status": "cancelado", "total": 21  }
]
```

## Exemplo: Agrupamento por Categoria com Coluna Diferente do Key

Quando o nome desejado no JSON difere do nome real da coluna no banco, use `->column()`:

```php
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Widgets\Table;

Table::create('receita-por-categoria', 'Receita por Categoria')
    ->dimension(
        StringDimension::create('categoria', 'Categoria')
            ->column('categoria_produto') // coluna real no banco
    )
    ->metric(
        SumMetric::create('receita', 'Receita Total')
            ->column('valor_pedido')
    )
```

SQL gerado:

```sql
SELECT `categoria_produto` AS `categoria`, SUM(`valor_pedido`) AS `receita`
FROM `pedidos`
GROUP BY `categoria`
```

A resposta retornará a chave `categoria` (não `categoria_produto`), pois o alias `$key` é quem aparece no JSON.

## Cuidado com Alta Cardinalidade

`StringDimension` retorna uma linha por valor distinto da coluna. Se a coluna tiver muitos valores distintos — como um campo de texto livre, um identificador único ou uma coluna de e-mail —, o resultado pode ter milhares de linhas, impactando desempenho e legibilidade.

Para colunas de alta cardinalidade, considere:

- Adicionar um filtro que restrinja os valores antes da consulta
- Usar `CASE WHEN` via `RawDimension` para consolidar valores em grupos menores
- Limitar os resultados com uma ordenação e paginação no widget

> `StringDimension` não impõe nenhum limite ao número de grupos retornados. O controle de volume de dados é responsabilidade do desenvolvedor, via filtros, escopos ou configuração do widget.

## Próximos Passos

← [Visão Geral das Dimensões](01-visao-geral.md) | → [Dimensões de Data](03-datas.md)
