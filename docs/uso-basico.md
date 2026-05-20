# Guia de Uso Básico

Este guia mostra como criar um dashboard analítico completo usando o cenário de um **painel de vendas** com o modelo `Order` (tabela `orders`). Ao final você terá widgets funcionais, filtros interativos e controle de acesso básico.

---

## Pré-requisitos

Pacote instalado e configurado. Consulte [Instalação →](02-instalacao/01-instalacao.md).

---

## Criando o Dashboard

Use o comando Artisan para gerar a classe base:

```bash
php artisan bi:dashboard Sales --model=Order
```

O comando cria o arquivo `app/Bi/Dashboards/SalesDashboard.php` com a estrutura inicial:

```php
namespace App\Bi\Dashboards;

use App\Models\Order;
use Luminix\Bi\Dashboard;

class SalesDashboard extends Dashboard
{
    public $uriKey = 'sales';    // URL: /bi-apis/sales/widgets
    public $name   = 'Sales';    // Nome exibido pelo front-end
    public $model  = Order::class;

    public function widgets(): array
    {
        return [];
    }

    public function filters(): array
    {
        return [];
    }
}
```

O dashboard já está disponível via API — basta preencher `widgets()` e `filters()`.

> A descoberta é automática: qualquer classe em `app/Bi/Dashboards/` que estenda `Dashboard` é registrada sem configuração adicional. Saiba mais em [Descoberta de Dashboards →](03-dashboards/05-descoberta.md).

---

## Adicionando Widgets

### BigNumber — Valor Único

Use `BigNumber` para destacar um número importante no painel.

```php
use Luminix\Bi\Widgets\BigNumber;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Metrics\SumMetric;

// Total de pedidos
BigNumber::create('total-orders', 'Total Orders')
    ->metric(CountMetric::create('orders', 'Orders')),

// Receita total
BigNumber::create('total-revenue', 'Total Revenue')
    ->metric(
        SumMetric::create('revenue', 'Revenue')->column('total_amount')
    ),
```

### Table — Tabela de Dados

Use `Table` para exibir dados agrupados com múltiplas colunas. Defina uma dimensão (agrupamento) e uma ou mais métricas (valores calculados).

```php
use Luminix\Bi\Widgets\Table;
use Luminix\Bi\Dimensions\StringDimension;

// Pedidos agrupados por status, com contagem e receita
Table::create('orders-by-status', 'Orders by Status')
    ->dimension(StringDimension::create('status', 'Status'))
    ->metrics([
        CountMetric::create('orders', 'Orders'),
        SumMetric::create('revenue', 'Revenue')->column('total_amount'),
    ])
    ->sortBy('revenue', 'desc'),
```

O método `->sortBy($key, $direction)` define a ordenação padrão da tabela.

### LineChart — Evolução Temporal

Use `LineChart` para visualizar tendências ao longo do tempo. Combine com `MonthDimension`, `DayDimension` ou `YearDimension`.

```php
use Luminix\Bi\Widgets\LineChart;
use Luminix\Bi\Dimensions\MonthDimension;

// Receita por mês
LineChart::create('revenue-by-month', 'Revenue per Month')
    ->dimension(MonthDimension::create('created_at', 'Month'))
    ->metric(
        SumMetric::create('revenue', 'Revenue')->column('total_amount')
    ),
```

### PartitionPie — Distribuição

Use `PartitionPie` para mostrar a distribuição percentual entre categorias. O método `->colors()` define as cores de cada fatia.

```php
use Luminix\Bi\Widgets\PartitionPie;

// Distribuição de pedidos por status
PartitionPie::create('orders-by-status-pie', 'Orders by Status')
    ->dimension(StringDimension::create('status', 'Status'))
    ->metric(CountMetric::create('orders', 'Orders'))
    ->colors([
        'confirmed' => '#22c55e',
        'pending'   => '#f59e0b',
        'cancelled' => '#ef4444',
    ]),
```

### Dashboard completo com os quatro widgets

```php
use Luminix\Bi\Widgets\{BigNumber, Table, LineChart, PartitionPie};
use Luminix\Bi\Metrics\{CountMetric, SumMetric};
use Luminix\Bi\Dimensions\{StringDimension, MonthDimension};

public function widgets(): array
{
    return [
        BigNumber::create('total-orders', 'Total Orders')
            ->metric(CountMetric::create('orders', 'Orders')),

        BigNumber::create('total-revenue', 'Total Revenue')
            ->metric(SumMetric::create('revenue', 'Revenue')->column('total_amount')),

        Table::create('orders-by-status', 'Orders by Status')
            ->dimension(StringDimension::create('status', 'Status'))
            ->metrics([
                CountMetric::create('orders', 'Orders'),
                SumMetric::create('revenue', 'Revenue')->column('total_amount'),
            ])
            ->sortBy('revenue', 'desc'),

        LineChart::create('revenue-by-month', 'Revenue per Month')
            ->dimension(MonthDimension::create('created_at', 'Month'))
            ->metric(SumMetric::create('revenue', 'Revenue')->column('total_amount')),

        PartitionPie::create('orders-by-status-pie', 'Orders by Status')
            ->dimension(StringDimension::create('status', 'Status'))
            ->metric(CountMetric::create('orders', 'Orders'))
            ->colors([
                'confirmed' => '#22c55e',
                'pending'   => '#f59e0b',
                'cancelled' => '#ef4444',
            ]),
    ];
}
```

---

## Adicionando Filtros

Filtros declarados em `filters()` são aplicados automaticamente em **todos** os widgets do dashboard quando o front-end enviar os valores correspondentes.

```php
use Luminix\Bi\Filters\{DateIntervalFilter, StringFilter};

public function filters(): array
{
    return [
        // Seletor de intervalo de datas
        DateIntervalFilter::create('created_at', 'Period'),

        // Multi-select de status — opções preenchidas automaticamente com valores distintos do banco
        StringFilter::create('status', 'Status'),
    ];
}
```

O front-end envia os filtros ativos como parâmetro `filters` na requisição. Filtros não enviados são simplesmente ignorados — a query roda sem aquela restrição.

Para detalhes sobre cada tipo de filtro, veja [Visão Geral dos Filtros →](07-filtros/01-visao-geral.md).

---

## Controle de Acesso

O método `viewable()` controla quem pode acessar o dashboard. Por padrão, todos os usuários autenticados têm acesso.

```php
use Illuminate\Support\Facades\Gate;

public function viewable(): bool
{
    // Apenas usuários com a habilidade 'view-sales-dashboard'
    return Gate::allows('view-sales-dashboard');
}
```

Para controle de acesso mais detalhado — incluindo proteção de rotas e segurança de queries — veja [Segurança →](08-seguranca/01-rotas.md).

---

## Resultado

O dashboard `SalesDashboard` completo:

```php
namespace App\Bi\Dashboards;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Dimensions\{MonthDimension, StringDimension};
use Luminix\Bi\Filters\{DateIntervalFilter, StringFilter};
use Luminix\Bi\Metrics\{CountMetric, SumMetric};
use Luminix\Bi\Widgets\{BigNumber, LineChart, PartitionPie, Table};

class SalesDashboard extends Dashboard
{
    public $uriKey = 'sales';
    public $name   = 'Sales';
    public $model  = Order::class;

    public function widgets(): array
    {
        return [
            BigNumber::create('total-orders', 'Total Orders')
                ->metric(CountMetric::create('orders', 'Orders')),

            BigNumber::create('total-revenue', 'Total Revenue')
                ->metric(SumMetric::create('revenue', 'Revenue')->column('total_amount')),

            Table::create('orders-by-status', 'Orders by Status')
                ->dimension(StringDimension::create('status', 'Status'))
                ->metrics([
                    CountMetric::create('orders', 'Orders'),
                    SumMetric::create('revenue', 'Revenue')->column('total_amount'),
                ])
                ->sortBy('revenue', 'desc'),

            LineChart::create('revenue-by-month', 'Revenue per Month')
                ->dimension(MonthDimension::create('created_at', 'Month'))
                ->metric(SumMetric::create('revenue', 'Revenue')->column('total_amount')),

            PartitionPie::create('orders-by-status-pie', 'Orders by Status')
                ->dimension(StringDimension::create('status', 'Status'))
                ->metric(CountMetric::create('orders', 'Orders'))
                ->colors([
                    'confirmed' => '#22c55e',
                    'pending'   => '#f59e0b',
                    'cancelled' => '#ef4444',
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            DateIntervalFilter::create('created_at', 'Period'),
            StringFilter::create('status', 'Status'),
        ];
    }

    public function viewable(): bool
    {
        return Gate::allows('view-sales-dashboard');
    }
}
```

---

## Próximos Passos

**Widgets avançados**
- [Visão Geral dos Widgets →](04-widgets/01-visao-geral.md)
- [Exportação CSV →](04-widgets/06-csv.md)

**Métricas avançadas**
- [Visão Geral das Métricas →](05-metricas/01-visao-geral.md)
- [CountMany e SumMany (relacionamentos) →](05-metricas/06-count-many.md)
- [Métricas com percentual →](05-metricas/01-visao-geral.md)

**Dimensões**
- [Visão Geral das Dimensões →](06-dimensoes/01-visao-geral.md)
- [BelongsToDimension →](06-dimensoes/04-belongs-to.md)

**Filtros avançados**
- [Visão Geral dos Filtros →](07-filtros/01-visao-geral.md)
- [RelationFilter →](07-filtros/06-relation.md)

**Dashboards**
- [Escopo Global →](03-dashboards/03-escopo.md)
- [Autorização →](03-dashboards/04-autorizacao.md)
- [Descoberta Automática →](03-dashboards/05-descoberta.md)

**Segurança e API**
- [Segurança →](08-seguranca/01-rotas.md)
- [Endpoints da API →](10-api/01-endpoints.md)

**Extensibilidade**
- [Métrica Customizada →](09-extensibilidade/01-metrica-customizada.md)
- [Widget Customizado →](09-extensibilidade/04-widget-customizado.md)
