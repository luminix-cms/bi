# Autorização

O método `viewable()` controla se um dashboard deve ser incluído na listagem retornada pela API. Quando retorna `false`, o dashboard simplesmente não existe para o usuário — não há resposta `403`. Esse comportamento de ocultação é mais seguro do que uma rejeição explícita, pois não revela que o recurso existe.

## O método `viewable()`

```php
public function viewable(): bool
{
    return true; // padrão — visível para todos os usuários autenticados
}
```

Um dashboard cujo `viewable()` retorna `false`:

- Não aparece em `GET /bi-apis/dashboards`
- Retorna `404` em qualquer endpoint específico (`/bi-apis/{dashboard}/widgets`, etc.)

## Usando Gates do Laravel

```php
use Illuminate\Support\Facades\Gate;

public function viewable(): bool
{
    return Gate::allows('view-financial-reports');
}
```

Registre o Gate em um Service Provider:

```php
// app/Providers/AuthServiceProvider.php
Gate::define('view-financial-reports', function ($user) {
    return $user->hasRole('finance') || $user->hasRole('admin');
});
```

Condições compostas também são suportadas:

```php
public function viewable(): bool
{
    return Gate::allows('view-financial-reports')
        && auth()->user()->company->plan === 'premium';
}
```

## Usando Policies

Quando a autorização está atrelada a um model específico, uma Policy é mais organizada:

```php
// app/Policies/DashboardPolicy.php
class DashboardPolicy
{
    public function viewSales(User $user): bool
    {
        return $user->role === 'sales_manager';
    }
}
```

```php
// No dashboard
public function viewable(): bool
{
    return auth()->user()->can('viewSales', Dashboard::class);
}
```

## Exemplo completo

Dashboard de receita visível apenas para usuários com o Gate `view-financial-reports`:

```php
<?php

namespace App\Bi\Dashboards;

use Illuminate\Support\Facades\Gate;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Filters\DateIntervalFilter;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Widgets\BigNumber;

class RevenueDashboard extends Dashboard
{
    public $model  = \App\Models\Order::class;
    public $uriKey = 'revenue';
    public $name   = 'Revenue Dashboard';

    public function viewable(): bool
    {
        return Gate::allows('view-financial-reports');
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
            BigNumber::create('total-revenue', 'Total Revenue')
                ->metric(
                    SumMetric::create('revenue', 'Revenue')
                        ->column('total_amount')
                )
                ->width('1/3'),
        ];
    }
}
```

Para um usuário sem o Gate, a resposta de `GET /bi-apis/dashboards` não incluirá esse dashboard na lista.

> Para entender como as rotas são protegidas antes mesmo de chegar ao `viewable()`, consulte [Segurança de Rotas](../08-seguranca/01-rotas.md) e [Segurança de Dashboards](../08-seguranca/02-dashboard.md).

## Próximos Passos

← [Escopo Global](03-escopo.md) | → [Descoberta Automática](05-descoberta.md)
