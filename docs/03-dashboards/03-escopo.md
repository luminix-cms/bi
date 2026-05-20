# Escopo Global

O método `scope()` permite definir restrições que se aplicam a todos os widgets do dashboard, independentemente dos filtros que o usuário enviar. É o mecanismo adequado para condições de segurança e isolamento de dados que nunca devem ser removidas pelo usuário.

## O método `scope()`

```php
use Illuminate\Database\Eloquent\Builder;

public function scope(Builder $builder): Builder
{
    return $builder->where('user_id', auth()->id());
}
```

O método é opcional. Quando declarado, é aplicado antes dos filtros do usuário — portanto qualquer condição colocada aqui estará sempre presente na query final.

## Ordem de aplicação

1. Escopo do dashboard (`scope()`)
2. Escopo do widget (Closure passada via `->scope()`)
3. Métricas e dimensões (SELECT, GROUP BY)
4. Filtros enviados pelo usuário na requisição

## Casos de uso

### Isolamento por tenant

```php
public function scope(Builder $builder): Builder
{
    return $builder->where('company_id', auth()->user()->company_id);
}
```

### Status fixo

Para um dashboard que nunca deve mostrar pedidos cancelados:

```php
public function scope(Builder $builder): Builder
{
    return $builder->whereIn('status', ['pending', 'approved', 'shipped']);
}
```

### Combinando condições

```php
public function scope(Builder $builder): Builder
{
    return $builder
        ->where('company_id', auth()->user()->company_id)
        ->where('is_test', false);
}
```

## Escopo do dashboard vs. escopo do widget

| Aspecto | `scope()` do Dashboard | `scope()` do Widget |
|---|---|---|
| Tipo | Método PHP na classe | Closure passada via `->scope()` |
| Aplicado a | Todos os widgets | Apenas ao widget específico |
| Caso de uso | Segurança / isolamento de tenant | Condições específicas de um widget |

Exemplo combinando os dois:

```php
// No dashboard — restrição de tenant
public function scope(Builder $builder): Builder
{
    return $builder->where('company_id', auth()->user()->company_id);
}

// Em um widget específico — restrição adicional
public function widgets(): array
{
    return [
        BigNumber::create('urgent-orders', 'Urgent Orders')
            ->scope(function (Builder $builder) {
                return $builder->where('priority', 'high')
                               ->where('status', 'pending');
            })
            ->metric(CountMetric::create('total', 'Total')),
    ];
}
```

A query final do widget terá ambas as condições:

```sql
SELECT COUNT(*) AS `total`
FROM `orders`
WHERE `company_id` = 42       -- escopo do dashboard
  AND `priority` = 'high'     -- escopo do widget
  AND `status` = 'pending'    -- escopo do widget
```

## Exemplo completo

```php
<?php

namespace App\Bi\Dashboards;

use Illuminate\Database\Eloquent\Builder;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Filters\DateIntervalFilter;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Widgets\BigNumber;

class OrdersDashboard extends Dashboard
{
    public $model  = \App\Models\Order::class;
    public $uriKey = 'orders';
    public $name   = 'Orders Dashboard';

    public function scope(Builder $builder): Builder
    {
        return $builder
            ->where('company_id', auth()->user()->company_id)
            ->where('is_test', false);
    }

    public function filters(): array
    {
        return [
            DateIntervalFilter::create('created_at', 'Period'),
        ];
    }

    public function widgets(): array
    {
        return [
            BigNumber::create('total', 'Total Orders')
                ->metric(CountMetric::create('qty', 'Quantity'))
                ->width('1/3'),

            BigNumber::create('revenue', 'Total Revenue')
                ->metric(
                    SumMetric::create('amount', 'Amount')
                        ->column('total_amount')
                        ->color('#4CAF50')
                )
                ->width('1/3'),
        ];
    }
}
```

## Próximos Passos

← [Configurando Widgets e Filtros](02-widgets-filtros.md) | → [Autorização](04-autorizacao.md)
