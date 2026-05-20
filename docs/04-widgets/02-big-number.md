# BigNumber

O `BigNumber` exibe um único valor agregado em destaque — ideal para KPIs como receita total, número de pedidos ou ticket médio. É o widget mais simples: sem eixos, sem agrupamentos, apenas um número.

---

## Quando Usar

- Totais gerais: receita do mês, volume de pedidos, usuários ativos
- Médias únicas: ticket médio, tempo médio de entrega
- Contagens simples: pedidos em aberto, clientes cadastrados

Quando precisar ver os dados distribuídos por algum critério (por mês, por categoria), use `LineChart` ou `Table`.

---

## Configuração

O `BigNumber` não requer dimensão. Sem dimensão, não há `GROUP BY` — a query agrega todos os registros em um único resultado.

```php
use Luminix\Bi\Widgets\BigNumber;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Metrics\SumMetric;

BigNumber::create('total-orders', 'Total de Pedidos')
    ->metric(CountMetric::create('orders', 'Pedidos'));

BigNumber::create('total-revenue', 'Receita Total')
    ->metric(SumMetric::create('total_amount', 'Receita'));
```

Resposta da API:

```json
{
    "status": 200,
    "data": [
        { "total_amount": "128540.00" }
    ]
}
```

O `BigNumber` sempre retorna um array com um único elemento. O front-end lê `data[0]` para exibir o valor.

---

## Exemplo Completo

```php
// app/Bi/Dashboards/SalesDashboard.php

namespace App\Bi\Dashboards;

use App\Models\Order;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Widgets\BigNumber;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Metrics\AverageMetric;
use Luminix\Bi\Filters\DateIntervalFilter;

class SalesDashboard extends Dashboard
{
    public $uriKey = 'sales';
    public $name   = 'Vendas';
    public $model  = Order::class;

    public function widgets(): array
    {
        return [
            BigNumber::create('total-orders', 'Total de Pedidos')
                ->width('1/3')
                ->metric(CountMetric::create('orders', 'Pedidos')),

            BigNumber::create('total-revenue', 'Receita Total')
                ->width('1/3')
                ->metric(SumMetric::create('total_amount', 'Receita')),

            BigNumber::create('average-ticket', 'Ticket Médio')
                ->width('1/3')
                ->metric(AverageMetric::create('avg_ticket', 'Ticket Médio')->column('total_amount')),
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

## Múltiplas Métricas

O `BigNumber` aceita múltiplas métricas. Nesse caso, o objeto retornado terá uma chave para cada métrica:

```php
BigNumber::create('quick-summary', 'Resumo Rápido')
    ->metrics([
        CountMetric::create('orders', 'Pedidos'),
        SumMetric::create('total_amount', 'Receita'),
        AverageMetric::create('avg_ticket', 'Ticket Médio')->column('total_amount'),
    ]);
```

```json
{
    "status": 200,
    "data": [
        {
            "orders": 312,
            "total_amount": "48500.00",
            "avg_ticket": "155.45"
        }
    ]
}
```

---

## Escopo por Widget

Para exibir um `BigNumber` com um subconjunto específico de dados, use `scope()`:

```php
use Illuminate\Database\Eloquent\Builder;

BigNumber::create('vip-revenue', 'Receita Clientes VIP')
    ->scope(function (Builder $builder) {
        return $builder->where('plan', 'vip');
    })
    ->metric(SumMetric::create('total_amount', 'Receita'));
```

---

## Próximos Passos

- [← Visão Geral dos Widgets](01-visao-geral.md) | [→ Table](03-table.md)
- [Métricas disponíveis](../05-metricas/01-visao-geral.md)
