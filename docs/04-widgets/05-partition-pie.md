# PartitionPie

O widget `PartitionPie` gera dados de distribuição por categoria para gráficos de pizza. Cada linha do resultado representa uma fatia, com seu rótulo e valor. O pacote fornece os dados estruturados e, opcionalmente, as cores de cada fatia.

---

## Configuração

O `PartitionPie` é configurado com uma dimensão textual e uma métrica de soma ou contagem. A dimensão define os rótulos das fatias; a métrica define o tamanho de cada uma.

```php
use Luminix\Bi\Widgets\PartitionPie;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Metrics\SumMetric;

PartitionPie::create('orders-by-status', 'Pedidos por Status')
    ->dimension(StringDimension::create('status', 'Status'))
    ->metric(CountMetric::create('orders', 'Pedidos'));
```

Resposta da API:

```json
{
    "status": 200,
    "data": [
        { "status": "completed", "orders": 312 },
        { "status": "pending",   "orders": 87  },
        { "status": "cancelled", "orders": 23  }
    ]
}
```

---

## Cores com `colors()`

O método `colors()` aceita um array associativo onde cada chave corresponde ao valor da dimensão e o valor é uma cor CSS. O pacote repassa as cores ao front-end via `extra.colors`.

```php
PartitionPie::create('orders-by-status', 'Pedidos por Status')
    ->dimension(StringDimension::create('status', 'Status'))
    ->metric(CountMetric::create('orders', 'Pedidos'))
    ->colors([
        'completed' => '#10B981',
        'pending'   => '#F59E0B',
        'cancelled' => '#EF4444',
    ]);
```

O campo `extra` serializado para este widget:

```json
{
    "extra": {
        "colors": {
            "completed": "#10B981",
            "pending": "#F59E0B",
            "cancelled": "#EF4444"
        }
    }
}
```

Se `colors()` não for chamado, o valor serializado será `null`.

---

## Exemplo Completo

```php
// app/Bi/Dashboards/SalesDashboard.php

namespace App\Bi\Dashboards;

use App\Models\Order;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Widgets\PartitionPie;
use Luminix\Bi\Dimensions\StringDimension;
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
            PartitionPie::create('revenue-by-category', 'Receita por Categoria')
                ->width('1/2')
                ->dimension(StringDimension::create('category', 'Categoria'))
                ->metric(SumMetric::create('total_amount', 'Receita'))
                ->colors([
                    'Electronics' => '#3B82F6',
                    'Clothing'    => '#10B981',
                    'Books'       => '#F59E0B',
                    'Other'       => '#8B5CF6',
                ]),
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

---

## Próximos Passos

- [← LineChart](04-line-chart.md) | [→ Exportação CSV](06-csv.md)
- [Métricas disponíveis](../05-metricas/01-visao-geral.md)
- [Dimensões disponíveis](../06-dimensoes/01-visao-geral.md)
