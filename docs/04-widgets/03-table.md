# Table

A tabela é o formato mais versátil de visualização analítica. Ela combina múltiplas dimensões e múltiplas métricas em uma grade de linhas e colunas — ideal para quando você precisa ver não apenas o total, mas a distribuição dos dados por critério, com a possibilidade de ordenar por qualquer coluna.

---

## Propósito

O widget `Table` gera uma tabela com linhas agrupadas pelas dimensões configuradas e colunas numéricas definidas pelas métricas. Ele é o substituto direto de uma query `SELECT ... GROUP BY` exposta como API.

O campo `component` enviado ao front-end é `'table'`.

---

## Configuração: Múltiplas Dimensões e Métricas

Ao contrário do `BigNumber`, o `Table` é projetado para receber múltiplas dimensões e métricas. Cada combinação de dimensões forma uma linha da tabela; cada métrica forma uma coluna numérica.

```php
Table::create('pedidos-por-categoria', 'Pedidos por Categoria')
    ->dimensions([
        new StringDimension('categoria', 'Categoria'),
        new MonthDimension('created_at', 'Mês'),
    ])
    ->metrics([
        new CountMetric('pedidos', 'Pedidos'),
        new SumMetric('receita', 'Receita')->column('total'),
    ]);
```

O SQL gerado internamente:

```sql
SELECT
    `categoria` as `categoria`,
    DATE_FORMAT(`created_at`, '%Y-%m') as `mes`,
    COUNT(*) as `pedidos`,
    SUM(`total`) as `receita`
FROM `pedidos`
GROUP BY `categoria`, DATE_FORMAT(`created_at`, '%Y-%m')
```

---

## Método `orderBy($coluna, $dir)`

O método `orderBy()` define a **ordenação padrão** da tabela — a que é aplicada quando nenhum parâmetro de sort é enviado na requisição. Essa ordenação também é incluída no campo `extra.orderBy` da serialização JSON, para que o front-end saiba qual coluna está ativa por padrão.

```php
Table::create('top-categorias', 'Top Categorias')
    ->dimension(new StringDimension('categoria', 'Categoria'))
    ->metric(new SumMetric('receita', 'Receita')->column('total'))
    ->orderBy('receita', 'desc');   // maior receita primeiro
```

O campo `extra` serializado para este widget:

```json
{
    "extra": {
        "orderBy": {
            "col": "receita",
            "dir": "desc"
        }
    }
}
```

> O `orderBy()` define a intenção de ordenação padrão. A aplicação real do `ORDER BY` na query fica a cargo do atributo (dimensão ou métrica) que possui o método `applySort()`. Se `orderBy()` não for chamado, `col` e `dir` serão `null` no `extra`.

---

## Ordenação Dinâmica via Request

O front-end pode solicitar ordenação por qualquer coluna enviando o parâmetro `sort` na requisição:

```
GET /bi-apis/financeiro/widgets/pedidos-por-categoria?sort[col]=pedidos&sort[dir]=desc
```

O `Table` processa esse parâmetro no método `data()`:

1. Verifica se `sort` está presente na requisição
2. Busca a coluna `sort[col]` primeiro na coleção de **dimensões** do widget
3. Se não encontrar, busca na coleção de **métricas**
4. Aplica `applySort($builder, $dir)` no atributo encontrado

Esse comportamento permite que o front-end implemente cabeçalhos de coluna clicáveis sem nenhuma configuração adicional no back-end.

```php
// Exemplo com URL completa incluindo filtro e sort
// GET /bi-apis/vendas/widgets/resumo-mensal
//     ?filters[created_at][start]=2024-01-01
//     &filters[created_at][end]=2024-12-31
//     &sort[col]=receita
//     &sort[dir]=asc
```

---

## Exemplo Completo: Tabela de Pedidos por Categoria e Mês

```php
// app/Bi/Dashboards/VendasDashboard.php

namespace App\Bi\Dashboards;

use App\Models\Pedido;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Widgets\Table;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Dimensions\MonthDimension;
use Luminix\Bi\Filters\DateIntervalFilter;

class VendasDashboard extends Dashboard
{
    public $uriKey = 'vendas';
    public $name   = 'Vendas';
    public $model  = Pedido::class;

    public function widgets(): array
    {
        return [
            Table::create('pedidos-por-categoria', 'Pedidos por Categoria e Mês')
                ->dimensions([
                    new StringDimension('categoria', 'Categoria'),
                    new MonthDimension('created_at', 'Mês'),
                ])
                ->metrics([
                    new CountMetric('pedidos', 'Pedidos'),
                    new SumMetric('receita', 'Receita')->column('total'),
                ])
                ->orderBy('pedidos', 'desc'),
        ];
    }

    public function filters(): array
    {
        return [
            DateIntervalFilter::create('created_at', 'Período'),
        ];
    }
}
```

Resposta da API sem parâmetros de sort:

```json
{
    "status": 200,
    "data": [
        { "categoria": "Eletrônicos", "mes": "2024-03", "pedidos": 87, "receita": "32400.00" },
        { "categoria": "Roupas",      "mes": "2024-03", "pedidos": 64, "receita": "8750.00"  },
        { "categoria": "Livros",      "mes": "2024-03", "pedidos": 41, "receita": "2100.00"  }
    ]
}
```

---

## Exemplo com Parâmetro de Sort na URL

Quando o usuário clica no cabeçalho "Receita" no front-end, a requisição é enviada com:

```
GET /bi-apis/vendas/widgets/pedidos-por-categoria?sort[col]=receita&sort[dir]=asc
```

O widget encontra `receita` na coleção de métricas e aplica `ORDER BY receita ASC`. A resposta passa a ser ordenada da menor para a maior receita:

```json
{
    "status": 200,
    "data": [
        { "categoria": "Livros",      "mes": "2024-03", "pedidos": 41, "receita": "2100.00"  },
        { "categoria": "Roupas",      "mes": "2024-03", "pedidos": 64, "receita": "8750.00"  },
        { "categoria": "Eletrônicos", "mes": "2024-03", "pedidos": 87, "receita": "32400.00" }
    ]
}
```

---

## Próximos Passos

- [← BigNumber](02-big-number.md) | [→ LineChart](04-line-chart.md)
