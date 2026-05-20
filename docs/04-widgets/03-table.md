# Table

O widget `Table` gera uma tabela analítica com linhas agrupadas pelas dimensões configuradas e colunas numéricas definidas pelas métricas. É o tipo mais versátil: combina múltiplas dimensões e métricas e suporta ordenação dinâmica pelo front-end.

---

## Configuração

```php
use Luminix\Bi\Widgets\Table;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Dimensions\MonthDimension;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Metrics\SumMetric;

Table::create('orders-by-status', 'Pedidos por Status')
    ->dimension(new StringDimension('status', 'Status'))
    ->metrics([
        new CountMetric('orders', 'Pedidos'),
        new SumMetric('total_amount', 'Receita'),
    ]);
```

Para múltiplas dimensões:

```php
Table::create('orders-by-category-month', 'Pedidos por Categoria e Mês')
    ->dimensions([
        new StringDimension('category', 'Categoria'),
        new MonthDimension('created_at', 'Mês'),
    ])
    ->metrics([
        new CountMetric('orders', 'Pedidos'),
        new SumMetric('total_amount', 'Receita'),
    ]);
```

---

## Ordenação Padrão com `orderBy()`

O método `orderBy($column, $direction)` define a ordenação aplicada quando a requisição não envia parâmetros de sort. O valor também é incluído no JSON do widget (`extra.orderBy`) para que o front-end saiba qual coluna está ativa por padrão.

```php
Table::create('top-categories', 'Top Categorias')
    ->dimension(new StringDimension('category', 'Categoria'))
    ->metric(new SumMetric('total_amount', 'Receita'))
    ->orderBy('total_amount', 'desc');
```

---

## Ordenação Dinâmica via Query String

O front-end pode solicitar ordenação por qualquer coluna enviando o parâmetro `sort` na requisição:

```
GET /{dashboard}/widgets/{widget}?sort[col]=total_amount&sort[dir]=asc
```

O widget busca a coluna `sort[col]` primeiro nas dimensões, depois nas métricas, e aplica o `ORDER BY` correspondente. Isso permite cabeçalhos de coluna clicáveis sem nenhuma configuração adicional no back-end.

---

## Exemplo Completo

```php
// app/Bi/Dashboards/SalesDashboard.php

namespace App\Bi\Dashboards;

use App\Models\Order;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Widgets\Table;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Dimensions\MonthDimension;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Filters\DateIntervalFilter;

class SalesDashboard extends Dashboard
{
    public $uriKey = 'sales';
    public $name   = 'Vendas';
    public $model  = Order::class;

    public function widgets(): array
    {
        return [
            Table::create('orders-by-category', 'Pedidos por Categoria e Mês')
                ->width('full')
                ->dimensions([
                    new StringDimension('category', 'Categoria'),
                    new MonthDimension('created_at', 'Mês'),
                ])
                ->metrics([
                    new CountMetric('orders', 'Pedidos'),
                    new SumMetric('total_amount', 'Receita'),
                ])
                ->orderBy('orders', 'desc'),
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

Resposta da API (sem parâmetro de sort):

```json
{
    "status": 200,
    "data": [
        { "category": "Electronics", "mes": "2024-03", "orders": 87, "total_amount": "32400.00" },
        { "category": "Clothing",    "mes": "2024-03", "orders": 64, "total_amount": "8750.00"  },
        { "category": "Books",       "mes": "2024-03", "orders": 41, "total_amount": "2100.00"  }
    ]
}
```

Com o parâmetro `?sort[col]=total_amount&sort[dir]=asc`, a resposta é reordenada do menor para o maior valor de receita.

---

## Exportação CSV

Toda `Table` pode ser exportada como CSV automaticamente:

```
GET /{dashboard}/widgets/{widget}/csv
```

Os mesmos filtros ativos na visualização são aplicados na exportação. Veja mais em [Exportação CSV](06-csv.md).

---

## Próximos Passos

- [← BigNumber](02-big-number.md) | [→ LineChart](04-line-chart.md)
- [Dimensões disponíveis](../06-dimensoes/01-visao-geral.md)
- [Métricas disponíveis](../05-metricas/01-visao-geral.md)
