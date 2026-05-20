# Controle de Acesso por Dashboard

O middleware das rotas controla o acesso ao BI como um todo. Para controle mais granular — onde determinados usuários podem ver certos dashboards e outros não — use o método `viewable()` em cada classe de dashboard.

## O Método `viewable()`

Todo dashboard pode sobrescrever `viewable()`, que por padrão retorna `true`:

```php
public function viewable(): bool
{
    return true;
}
```

Quando `viewable()` retorna `false`, o dashboard é removido silenciosamente: não aparece na listagem de dashboards e retorna 404 se acessado diretamente. Não há resposta 403 — o dashboard simplesmente não existe para aquele usuário.

Esse comportamento é intencional: em vez de revelar a existência do dashboard e negar o acesso, o pacote age como se o dashboard não existisse.

## Exemplos

### Gate do Laravel

```php
use Illuminate\Support\Facades\Gate;

class FinancialDashboard extends Dashboard
{
    public function viewable(): bool
    {
        return Gate::allows('view-financial-reports');
    }
}
```

Defina o Gate no `AuthServiceProvider`:

```php
Gate::define('view-financial-reports', function ($user) {
    return $user->hasRole('finance') || $user->is_admin;
});
```

### Policy do Laravel

```php
class OrdersDashboard extends Dashboard
{
    public function viewable(): bool
    {
        return auth()->user()->can('view-orders-dashboard');
    }
}
```

### Atributo do usuário

```php
class AdminDashboard extends Dashboard
{
    public function viewable(): bool
    {
        return auth()->user()->is_admin;
    }
}
```

## Middleware vs `viewable()`

Os dois mecanismos atuam em níveis diferentes e se complementam:

| Aspecto | Middleware | `viewable()` |
|---|---|---|
| Escopo | Todas as rotas do BI | Um dashboard específico |
| Comportamento ao negar | 401 ou 403 | 404 silencioso |
| Onde configurar | `config/luminix/bi.php` | Dentro da classe do dashboard |

Use o middleware para garantir que apenas usuários autenticados acessem o BI. Use `viewable()` para controlar quais dashboards específicos cada perfil pode visualizar.

## Próximos Passos

← [Rotas e Middleware](01-rotas.md) | → [Segurança nas Queries](03-queries.md)
