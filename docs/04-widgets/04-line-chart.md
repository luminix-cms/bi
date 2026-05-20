# LineChart

O widget `LineChart` gera dados para gráficos de linha com foco em séries temporais — evolução diária, mensal ou anual de uma ou mais métricas. Seu diferencial é a **interpolação de datas ausentes**: períodos sem dados recebem valor zero na resposta, garantindo uma série contínua para o front-end.

---

## Requer uma Dimensão de Data

O `LineChart` deve ser configurado com uma das três dimensões de data disponíveis:

| Classe | Agrupamento | Formato de saída |
|---|---|---|
| `DayDimension` | Por dia | `"2024-03-15"` |
| `MonthDimension` | Por mês | `"2024-03"` |
| `YearDimension` | Por ano | `"2024"` |

---

## Interpolação de Datas Ausentes

Após executar a query, o `LineChart` percorre todos os períodos do intervalo e insere entradas com valor zero para os períodos sem dados. O intervalo é determinado pelos valores `start` e `end` do filtro de data (quando presente) ou pelo menor e maior valor retornado pela query.

Isso garante que o front-end sempre receba uma série completa e contígua — um zero explícito comunica que o dado existe e é zero, não que o período está ausente.

---

## Configuração

```php
use Luminix\Bi\Widgets\LineChart;
use Luminix\Bi\Dimensions\MonthDimension;
use Luminix\Bi\Dimensions\DayDimension;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Metrics\CountMetric;

// Evolução mensal de receita
LineChart::create('revenue-by-month', 'Receita por Mês')
    ->dimension(MonthDimension::create('created_at', 'Mês'))
    ->metric(SumMetric::create('total_amount', 'Receita'));

// Volume diário de pedidos
LineChart::create('daily-orders', 'Pedidos por Dia')
    ->dimension(DayDimension::create('created_at', 'Data'))
    ->metric(CountMetric::create('orders', 'Pedidos'));
```

---

## Exemplo Completo

```php
// app/Bi/Dashboards/SalesDashboard.php

namespace App\Bi\Dashboards;

use App\Models\Order;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Widgets\LineChart;
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
            LineChart::create('monthly-evolution', 'Evolução Mensal')
                ->width('full')
                ->dimension(MonthDimension::create('created_at', 'Mês'))
                ->metrics([
                    CountMetric::create('orders', 'Pedidos'),
                    SumMetric::create('total_amount', 'Receita'),
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

Com o filtro `{ "created_at": { "start": "2024-01-01", "end": "2024-03-31" } }` e fevereiro sem pedidos, a resposta será:

```json
{
    "status": 200,
    "data": [
        { "created_at": "2024-01", "orders": 312, "total_amount": "48500.00" },
        { "created_at": "2024-02", "orders": 0,   "total_amount": 0          },
        { "created_at": "2024-03", "orders": 287, "total_amount": "41200.00" }
    ]
}
```

Fevereiro aparece com zero explícito. Um intervalo de 30 dias com `DayDimension` sempre retorna exatamente 30 pontos, independentemente de quantos dias tiveram registros.

---

## Próximos Passos

- [← Table](03-table.md) | [→ PartitionPie](05-partition-pie.md)
- [Dimensões de data](../06-dimensoes/01-visao-geral.md)
- [Métricas disponíveis](../05-metricas/01-visao-geral.md)
