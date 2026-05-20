# Visão Geral dos Widgets

Widgets são os componentes de visualização de um dashboard. Cada widget executa uma consulta ao banco de dados, aplica os filtros ativos e retorna os dados formatados para o front-end. Você escolhe o tipo de widget de acordo com a forma como quer apresentar os dados.

---

## Tipos de Widget

| Widget | Uso principal | Dimensões | Métricas |
|---|---|---|---|
| [`BigNumber`](02-big-number.md) | Valor único em destaque (KPI) | Nenhuma | Uma ou mais |
| [`Table`](03-table.md) | Tabela com agrupamentos e totais | Uma ou mais | Uma ou mais |
| [`LineChart`](04-line-chart.md) | Evolução temporal (linha) | Uma `DateDimension` | Uma ou mais |
| [`PartitionPie`](05-partition-pie.md) | Distribuição por categoria (pizza) | Uma | Uma |

---

## Criando um Widget

Sempre use o método estático `create($key, $name)` para instanciar um widget. O `$key` identifica o widget nas URLs da API e no nome do arquivo CSV exportado. O `$name` é o rótulo exibido no front-end.

```php
use Luminix\Bi\Widgets\BigNumber;
use Luminix\Bi\Widgets\Table;
use Luminix\Bi\Widgets\LineChart;
use Luminix\Bi\Widgets\PartitionPie;

BigNumber::create('total-orders', 'Total de Pedidos');
Table::create('orders-by-status', 'Pedidos por Status');
LineChart::create('revenue-by-month', 'Receita por Mês');
PartitionPie::create('orders-by-category', 'Pedidos por Categoria');
```

Os métodos retornam a própria instância, permitindo encadeamento fluente:

```php
LineChart::create('revenue-by-month', 'Receita por Mês')
    ->width('2/3')
    ->dimension(MonthDimension::create('created_at', 'Mês'))
    ->metric(SumMetric::create('total_amount', 'Receita'));
```

---

## Largura com `width()`

O método `width()` repassa um valor livre ao front-end para posicionamento no grid. O pacote não valida nem interpreta esse valor — a convenção depende do front-end utilizado.

```php
BigNumber::create('total-orders', 'Total de Pedidos')
    ->width('1/3')
    ->metric(CountMetric::create('orders', 'Pedidos'));

Table::create('orders-by-status', 'Pedidos por Status')
    ->width('full')
    ->dimension(StringDimension::create('status', 'Status'))
    ->metric(CountMetric::create('orders', 'Pedidos'));
```

Se `width()` não for chamado, o valor serializado será `null`.

---

## Métricas e Dimensões

Todo widget é configurado com **métricas** (agregações numéricas) e **dimensões** (critérios de agrupamento). Use os métodos no singular para um único atributo ou no plural para múltiplos:

```php
// Singular
BigNumber::create('total-revenue', 'Receita Total')
    ->metric(SumMetric::create('total_amount', 'Receita'));

// Plural
Table::create('summary', 'Resumo')
    ->dimensions([
        MonthDimension::create('created_at', 'Mês'),
        StringDimension::create('status', 'Status'),
    ])
    ->metrics([
        CountMetric::create('orders', 'Pedidos'),
        SumMetric::create('total_amount', 'Receita'),
    ]);
```

Para conhecer as métricas disponíveis, consulte [Visão Geral das Métricas](../05-metricas/01-visao-geral.md). Para dimensões, consulte [Visão Geral das Dimensões](../06-dimensoes/01-visao-geral.md).

---

## Escopo por Widget com `scope()`

Use `scope()` para restringir os dados de um widget específico sem afetar os demais widgets do dashboard. O closure recebe um `Builder` do Eloquent e deve retorná-lo modificado.

```php
use Illuminate\Database\Eloquent\Builder;

BigNumber::create('vip-revenue', 'Receita Clientes VIP')
    ->scope(function (Builder $builder) {
        return $builder->where('plan', 'vip');
    })
    ->metric(SumMetric::create('total_amount', 'Receita'));
```

O escopo do widget é aplicado **após** o escopo do dashboard — ambas as restrições coexistem na mesma query.

---

## Exportação CSV

Todos os widgets possuem um endpoint de exportação automático:

```
GET /{dashboard}/widgets/{widget}/csv
```

Nenhuma configuração adicional é necessária. Veja mais em [Exportação CSV](06-csv.md).

---

## Próximos Passos

- [← Escopo do Dashboard](../03-dashboards/03-escopo.md) | [→ BigNumber](02-big-number.md)
