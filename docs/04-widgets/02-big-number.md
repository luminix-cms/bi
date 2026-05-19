# BigNumber

Imagine o painel de um CEO: no topo, três números grandes — receita do mês, total de pedidos, ticket médio. Sem tabelas, sem gráficos. Apenas o número que importa, em destaque. É exatamente para isso que o `BigNumber` existe.

---

## Propósito

O `BigNumber` exibe um **único valor agregado** de forma prominente. Ele é o tipo de widget mais simples e direto: sem eixos, sem linhas, sem colunas. Apenas um número — ou, mais precisamente, um único objeto com as chaves das métricas configuradas.

O campo `component` enviado ao front-end é `'big-number'`. O front-end usa esse valor para escolher o componente de renderização adequado.

---

## Quando Usar

Use o `BigNumber` para:

- **KPIs principais** — receita total do mês, número de usuários ativos, volume de pedidos
- **Totais gerais** — soma ou contagem sobre todos os registros (com ou sem filtro)
- **Médias únicas** — ticket médio, tempo médio de resposta, nota média de avaliação
- **Contagens simples** — total de clientes cadastrados, pedidos em aberto

Não use o `BigNumber` quando você quiser ver os dados distribuídos por algum critério. Se precisar ver a receita *por mês* ou *por categoria*, use `LineChart` ou `Table`.

---

## Configuração: Sem Dimensão, Uma Métrica

A configuração canônica do `BigNumber` é: **nenhuma dimensão e uma métrica**. Sem dimensão, não há `GROUP BY` — a query agrega todos os registros em um único resultado.

```php
BigNumber::create('receita-total', 'Receita Total')
    ->metric(new SumMetric('total', 'Receita'));
```

O SQL gerado internamente:

```sql
SELECT SUM(`total`) as `total`
FROM `pedidos`
```

O retorno da API é um array com um único objeto:

```json
{
    "status": 200,
    "data": [
        { "total": "128540.00" }
    ]
}
```

> O `BigNumber` retorna sempre um array com um único elemento. O front-end normalmente lê `data[0]` para exibir o valor.

---

## Exemplo Completo: Receita Total do Mês

O exemplo abaixo mostra um `BigNumber` dentro de um dashboard que já declara um `DateIntervalFilter`. O filtro é aplicado automaticamente pelo `BaseWidget` em todos os widgets do dashboard — incluindo este `BigNumber`.

```php
// app/Bi/Dashboards/FinanceiroDashboard.php

namespace App\Bi\Dashboards;

use App\Models\Pedido;
use Luminix\BI\Dashboard;
use Luminix\Bi\Widgets\BigNumber;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Filters\DateIntervalFilter;

class FinanceiroDashboard extends Dashboard
{
    public $uriKey = 'financeiro';
    public $name   = 'Financeiro';
    public $model  = Pedido::class;

    public function widgets(): array
    {
        return [
            BigNumber::create('receita-total', 'Receita Total')
                ->metric(new SumMetric('total', 'Receita')),
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

Quando o front-end envia o filtro de período:

```json
{
    "filters": {
        "created_at": { "start": "2024-01-01", "end": "2024-01-31" }
    }
}
```

O SQL executado passa a ser:

```sql
SELECT SUM(`total`) as `total`
FROM `pedidos`
WHERE `created_at` BETWEEN '2024-01-01 00:00:00' AND '2024-01-31 23:59:59'
```

---

## Exemplo com Escopo por Widget

Se você tem um dashboard geral de pedidos mas quer um `BigNumber` exclusivo para pedidos VIP, use `scope()` no próprio widget:

```php
use Illuminate\Database\Eloquent\Builder;

BigNumber::create('receita-vip', 'Receita Clientes VIP')
    ->scope(function (Builder $builder) {
        return $builder->where('plano', 'vip');
    })
    ->metric(new SumMetric('total', 'Receita VIP'));
```

O escopo do widget é adicionado **após** o escopo do dashboard. Se o dashboard já restringe a `status = 'confirmado'`, a query final terá as duas condições:

```sql
SELECT SUM(`total`) as `total`
FROM `pedidos`
WHERE `status` = 'confirmado'   -- escopo do dashboard
  AND `plano` = 'vip'           -- escopo do widget
```

---

## Múltiplas Métricas no BigNumber

Embora o uso típico seja uma única métrica, o `BigNumber` aceita múltiplas métricas. Nesse caso, o objeto retornado terá múltiplas chaves:

```php
BigNumber::create('resumo-rapido', 'Resumo Rápido')
    ->metrics([
        new CountMetric('pedidos', 'Total de Pedidos'),
        new SumMetric('receita', 'Receita Total')->column('total'),
        new AverageMetric('ticket', 'Ticket Médio')->column('total'),
    ]);
```

```json
{
    "status": 200,
    "data": [
        {
            "pedidos": 312,
            "receita": "48500.00",
            "ticket": "155.45"
        }
    ]
}
```

---

## O que NÃO Funciona: Dimensão no BigNumber

Adicionar uma dimensão ao `BigNumber` não faz sentido semântico. Se você adicionar uma `MonthDimension`, o widget passará a ter um `GROUP BY` e retornará múltiplos registros — um por mês — que o componente `big-number` do front-end não sabe renderizar corretamente.

Se você quer ver um valor por categoria ou por período, use `Table` ou `LineChart`. O `BigNumber` é, por definição, um único valor em destaque.

---

## Próximos Passos

- [← Visão Geral dos Widgets](01-visao-geral.md) | [→ Table](03-table.md)
