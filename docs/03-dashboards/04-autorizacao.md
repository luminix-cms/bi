# Autorização

Imagine que o sistema tem um dashboard financeiro com dados sensíveis de margem de lucro. Ele não deve aparecer para analistas de operações — mas também não deve retornar um erro `403` quando eles navegam pela interface. O comportamento correto é que esse dashboard simplesmente não exista para eles. É exatamente isso que o método `viewable()` faz.

## O método `viewable()`

O método `viewable()` controla se um dashboard deve ser incluído na listagem retornada pela API. A assinatura é simples:

```php
public function viewable(): bool
{
    return true; // padrão — visível para todos
}
```

O valor padrão é `true`, o que significa que, sem sobrescrever o método, o dashboard estará disponível para qualquer usuário autenticado (sujeito apenas ao middleware das rotas).

## Como o `DashboardResolver` usa o `viewable()`

Durante a descoberta automática, o `DashboardResolver` instancia cada dashboard encontrado em `app/Bi/Dashboards/` e chama `viewable()`. Apenas os dashboards que retornam `true` são adicionados ao índice:

```php
// DashboardResolver::__construct()
$dashboardInstance = App::make($dashboard);
if ($dashboardInstance->viewable()) {
    $this->dashboards->put($dashboardInstance->uriKey, $dashboardInstance);
}
```

Um dashboard cujo `viewable()` retorna `false` não é registrado no container. Isso tem duas consequências:

1. Ele não aparece na resposta de `GET /bi-apis/dashboards`
2. As rotas `/bi-apis/{dashboard}/widgets`, `/bi-apis/{dashboard}/widgets/{widget}` e `/bi-apis/{dashboard}/filters/{filter}` retornam `404` para esse dashboard

> O comportamento é de **ocultação**, não de **rejeição**. Não há resposta `403 Forbidden`. Para o usuário sem permissão, o dashboard simplesmente não existe — o que é mais seguro, pois não revela que o recurso existe mas está protegido.

## Usando Gates do Laravel

A forma mais direta de integrar com o sistema de autorização do Laravel é usar `Gate::allows()`:

```php
use Illuminate\Support\Facades\Gate;

public function viewable(): bool
{
    return Gate::allows('view-financial-dashboard');
}
```

O Gate precisa estar registrado em um Service Provider, tipicamente o `AuthServiceProvider`:

```php
// app/Providers/AuthServiceProvider.php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('view-financial-dashboard', function ($user) {
        return $user->hasRole('financeiro') || $user->hasRole('admin');
    });
}
```

Exemplo com verificação de role e condição adicional:

```php
public function viewable(): bool
{
    return Gate::allows('view-financial-dashboard')
        && auth()->user()->empresa->plano === 'premium';
}
```

## Usando Policies

Quando a autorização está atrelada a um model específico, o uso de uma Policy é mais organizado:

```php
// app/Policies/DashboardPolicy.php
class DashboardPolicy
{
    public function viewFinanceiro(User $user): bool
    {
        return $user->perfil === 'financeiro';
    }
}
```

```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    Dashboard::class => DashboardPolicy::class,
];
```

No dashboard:

```php
use Illuminate\Support\Facades\Gate;
use Luminix\Bi\Dashboard;

public function viewable(): bool
{
    return Gate::allows('viewFinanceiro', Dashboard::class);
}
```

Ou usando `can()` diretamente no usuário:

```php
public function viewable(): bool
{
    return auth()->user()->can('viewFinanceiro', Dashboard::class);
}
```

## Exemplo com Gate simples

Dashboard de folha de pagamento visível apenas para o departamento de RH:

```php
<?php

namespace App\Bi\Dashboards;

use Illuminate\Support\Facades\Gate;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Filters\DateIntervalFilter;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Widgets\BigNumber;

class FolhaPagamentoDashboard extends Dashboard
{
    public $model  = \App\Models\Pagamento::class;
    public $uriKey = 'folha-pagamento';
    public $name   = 'Folha de Pagamento';

    public function viewable(): bool
    {
        return Gate::allows('ver-folha-pagamento');
    }

    public function filters(): array
    {
        return [
            DateIntervalFilter::create('competencia', 'Competência'),
        ];
    }

    public function widgets(): array
    {
        return [
            BigNumber::create('total-bruto', 'Total Bruto')
                ->metric(
                    SumMetric::create('bruto', 'Valor Bruto')
                        ->column('valor_bruto')
                )
                ->width('1/3'),
        ];
    }
}
```

Para um usuário sem o Gate `ver-folha-pagamento`, a resposta de `GET /bi-apis/dashboards` não incluirá esse dashboard na lista.

## Exemplo com Policy

Dashboard de comissões visível apenas para o próprio vendedor ou para gerentes:

```php
// app/Policies/ComissoesDashboardPolicy.php
class ComissoesDashboardPolicy
{
    public function view(User $user): bool
    {
        return $user->perfil === 'vendedor'
            || $user->perfil === 'gerente';
    }
}
```

```php
// app/Bi/Dashboards/ComissoesDashboard.php
public function viewable(): bool
{
    return auth()->user()->can('view', \App\Policies\ComissoesDashboardPolicy::class);
}
```

## Verificando o comportamento

Para confirmar que o dashboard está sendo ocultado corretamente, acesse o endpoint de listagem com dois usuários diferentes — um com e outro sem permissão — e compare as respostas:

```bash
# Usuário com permissão
curl -s -H "Cookie: laravel_session=<sessao_admin>" \
     http://localhost:8000/bi-apis/dashboards | jq '.[].uriKey'
# Resultado: ["vendas", "folha-pagamento", "pedidos"]

# Usuário sem permissão
curl -s -H "Cookie: laravel_session=<sessao_analista>" \
     http://localhost:8000/bi-apis/dashboards | jq '.[].uriKey'
# Resultado: ["vendas", "pedidos"]
```

## Próximos Passos

← [Escopo Global](03-escopo.md) | → [Descoberta Automática](05-descoberta.md)
