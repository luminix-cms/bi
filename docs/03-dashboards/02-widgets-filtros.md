# Configurando Widgets e Filtros

Imagine um dashboard como um formulário de relatório: os widgets são as seções que exibem os resultados, e os filtros são os campos que o usuário preenche para refinar o que aparece. A diferença importante é que, no Luminix BI, um único filtro se aplica a todos os widgets ao mesmo tempo — não é necessário configurar o mesmo filtro para cada widget individualmente.

## O método `widgets()`

O método `widgets()` retorna um array de instâncias que implementam a interface `Widget`. Cada widget é independente: executa sua própria query, tem sua própria combinação de métricas e dimensões, e pode ter seu próprio escopo adicional.

```php
public function widgets(): array
{
    return [
        BigNumber::create('total-pedidos', 'Total de Pedidos')
            ->metric(CountMetric::create('total', 'Total'))
            ->width('1/4'),

        LineChart::create('pedidos-por-dia', 'Pedidos por Dia')
            ->dimension(DayDimension::create('dia', 'Dia')->column('created_at'))
            ->metric(CountMetric::create('total', 'Total')->color('#2196F3'))
            ->width('3/4'),
    ];
}
```

Cada widget é criado com o método estático `create($key, $name)`:

- `$key` — identificador único do widget dentro do dashboard, usado na URL (`/bi-apis/{dashboard}/widgets/{key}`)
- `$name` — nome legível exibido no frontend e usado como nome do arquivo CSV no download

Os widgets são retornados ao frontend na mesma ordem em que aparecem no array, permitindo controlar o layout visual.

## O método `filters()`

O método `filters()` retorna um array de instâncias que estendem `BaseFilter`. Os filtros declarados aqui são aplicados automaticamente a todos os widgets do dashboard quando o usuário os ativa na requisição.

```php
public function filters(): array
{
    return [
        DateIntervalFilter::create('created_at', 'Período'),
        StringFilter::create('status', 'Status'),
    ];
}
```

A aplicação dos filtros ocorre no método `applyFilters()` do `BaseWidget`, que itera sobre o array retornado por `$dashboard->filters()` para cada widget:

```php
// Comportamento interno — BaseWidget::applyFilters()
return collect($dashboard->filters())->reduce(function (Builder $builder, $filter) use ($request, $requestedFilters) {
    if (isset($requestedFilters[$filter->key])) {
        return $filter->apply($builder, $requestedFilters[$filter->key], $request);
    }
    return $builder;
}, $builder);
```

Se o usuário não enviar um filtro na requisição, ele é simplesmente ignorado — nenhuma cláusula `WHERE` é adicionada.

## Exemplo com múltiplos widgets e filtros

O exemplo abaixo demonstra um dashboard de vendas com três widgets e dois filtros:

```php
<?php

namespace App\Bi\Dashboards;

use Luminix\Bi\Dashboard;
use Luminix\Bi\Widgets\BigNumber;
use Luminix\Bi\Widgets\LineChart;
use Luminix\Bi\Widgets\PartitionPie;
use Luminix\Bi\Filters\DateIntervalFilter;
use Luminix\Bi\Filters\StringFilter;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Dimensions\DayDimension;
use Luminix\Bi\Dimensions\StringDimension;

class VendasDashboard extends Dashboard
{
    public $model  = \App\Models\Venda::class;
    public $uriKey = 'vendas';
    public $name   = 'Dashboard de Vendas';

    public function filters(): array
    {
        return [
            DateIntervalFilter::create('created_at', 'Período de vendas'),
            StringFilter::create('status', 'Status'),
        ];
    }

    public function widgets(): array
    {
        return [
            // Widget 1: valor total vendido (número único)
            BigNumber::create('receita-total', 'Receita Total')
                ->metric(
                    SumMetric::create('receita', 'Receita')
                        ->column('valor_total')
                        ->color('#4CAF50')
                )
                ->width('1/3'),

            // Widget 2: número de vendas por dia (gráfico de linha)
            LineChart::create('vendas-por-dia', 'Vendas por Dia')
                ->dimension(
                    DayDimension::create('dia', 'Dia')->column('created_at')
                )
                ->metric(
                    CountMetric::create('qtd', 'Quantidade')->color('#2196F3')
                )
                ->width('2/3'),

            // Widget 3: distribuição por status (pizza)
            PartitionPie::create('por-status', 'Vendas por Status')
                ->dimension(
                    StringDimension::create('status', 'Status')
                )
                ->metric(
                    CountMetric::create('total', 'Total')
                )
                ->width('1/2'),
        ];
    }
}
```

Quando o usuário acessa o endpoint de widgets desse dashboard:

```
GET /bi-apis/vendas/widgets
```

O JSON retornado contém os três widgets na mesma ordem do array, cada um com seus metadados de métricas, dimensões e largura:

```json
{
    "status": 200,
    "data": {
        "uriKey": "vendas",
        "name": "Dashboard de Vendas",
        "widgets": [
            { "key": "receita-total", "component": "big-number", "width": "1/3", ... },
            { "key": "vendas-por-dia", "component": "line-chart", "width": "2/3", ... },
            { "key": "por-status",    "component": "partition-pie", "width": "1/2", ... }
        ],
        "filters": [
            { "key": "created_at", "name": "Período de vendas", "component": "date-interval" },
            { "key": "status",     "name": "Status", "component": "string" }
        ]
    }
}
```

## Ordem de widgets e filtros

A ordem dos itens no array retornado por `widgets()` e `filters()` é preservada na resposta JSON. O frontend recebe os dados na mesma sequência declarada no código PHP, o que permite controlar a ordem de exibição no layout sem configuração adicional.

## Reaproveitamento de filtros

Um filtro é instanciado uma vez por dashboard, não por widget. Quando o `BaseWidget` aplica os filtros, ele chama `$dashboard->filters()` a cada requisição de widget — o que significa que o array de filtros é reconstruído a cada chamada.

Por isso, se o mesmo filtro precisar ser reutilizado em múltiplos dashboards, a abordagem recomendada é extrair a definição do filtro para um método ou constante reutilizável:

```php
// Abordagem com método auxiliar para reutilização
protected function filtrosDePeriodo(): array
{
    return [
        DateIntervalFilter::create('created_at', 'Período'),
    ];
}

public function filters(): array
{
    return array_merge(
        $this->filtrosDePeriodo(),
        [
            StringFilter::create('status', 'Status'),
        ]
    );
}
```

> Não é necessário (nem correto) compartilhar a mesma instância de filtro entre dashboards diferentes. Cada instância carrega o estado da requisição atual, portanto deve ser criada de forma independente em cada dashboard.

## Próximos Passos

← [Criando um Dashboard](01-criando.md) | → [Escopo Global](03-escopo.md)
