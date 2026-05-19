# Controle de Acesso por Dashboard

O middleware das rotas controla o acesso ao BI como um todo. Mas às vezes você precisa de um controle mais refinado: alguns usuários devem ver o dashboard financeiro, outros não. O método `viewable()` resolve exatamente esse problema — ele opera no nível do dashboard individual, permitindo que cada dashboard decida se o usuário atual tem ou não permissão para vê-lo.

## O Método `viewable()`

Todo dashboard pode sobrescrever o método `viewable()`, que por padrão retorna `true`:

```php
public function viewable(): bool
{
    return true;
}
```

Quando `viewable()` retorna `false`, o dashboard é silenciosamente removido de todos os endpoints do BI. Ele não aparece na listagem de dashboards, não responde a requisições de widgets e não retorna dados de filtros. Não há erro 403 — o dashboard simplesmente não existe para aquele usuário.

## Comportamento: Remoção Silenciosa

A ausência de um código de erro 403 é intencional. Em vez de revelar a existência do dashboard e negar o acesso, o pacote remove o dashboard da listagem como se ele nunca tivesse existido. Isso é mais seguro em cenários onde você não quer que um usuário saiba que determinado relatório existe.

## Como Funciona no `DashboardResolver`

O `DashboardResolver` é responsável por descobrir e indexar todos os dashboards da aplicação. Durante a indexação, ele instancia cada dashboard e verifica o retorno de `viewable()` antes de incluí-lo na listagem:

```php
// DashboardResolver (simplificado)
$dashboards = collect($discoveredClasses)
    ->map(fn($class) => new $class)
    ->filter(fn($dashboard) => $dashboard->viewable());
```

O resultado é que apenas dashboards onde `viewable()` retorna `true` são registrados e ficam disponíveis via API.

## Exemplos de Implementação

### Gate do Laravel

A abordagem mais idiomática: delegar a decisão a um Gate previamente definido no `AuthServiceProvider`.

```php
use Illuminate\Support\Facades\Gate;

class FinanceiroDashboard extends Dashboard
{
    public function viewable(): bool
    {
        return Gate::allows('view-financial-dashboard');
    }
}
```

O Gate pode ter qualquer lógica — verificar papéis, permissões, grupos, etc.

### Policy do Laravel

Para dashboards associados a um modelo específico, use uma Policy:

```php
class RelatorioPedidosDashboard extends Dashboard
{
    public function viewable(): bool
    {
        return auth()->user()->can('view', RelatorioPedidosDashboard::class);
    }
}
```

### Verificação por Papel (Role)

Útil em aplicações que usam pacotes de controle de acesso baseado em papéis (como Spatie Laravel Permission):

```php
class RHDashboard extends Dashboard
{
    public function viewable(): bool
    {
        return auth()->user()->hasRole('manager') || auth()->user()->hasRole('hr');
    }
}
```

### Verificação por Atributo do Usuário

Para regras simples baseadas diretamente em atributos do modelo `User`:

```php
class AdminDashboard extends Dashboard
{
    public function viewable(): bool
    {
        return auth()->user()->is_admin;
    }
}
```

## Diferença em Relação ao Middleware das Rotas

O middleware e o `viewable()` operam em níveis diferentes e se complementam:

| Aspecto | Middleware das rotas | `viewable()` |
|---|---|---|
| Escopo | Todas as rotas do BI | Um dashboard específico |
| Comportamento ao negar | Retorna 401 ou 403 | Remove o dashboard silenciosamente |
| Onde configurar | `config/bi.php` | Dentro da classe do dashboard |
| Granularidade | Global para o pacote | Por dashboard individual |

O middleware barra na portaria — ninguém entra sem autenticação e sem o gate global. O `viewable()` opera dentro do edifício — mesmo quem entrou pela portaria não vê todos os andares.

Uma estratégia comum é usar o middleware para garantir que apenas usuários autenticados acessem qualquer rota do BI, e usar `viewable()` para controlar quais dashboards específicos cada perfil de usuário pode visualizar.

## Próximos Passos

← [Autenticação e Autorização das Rotas](01-rotas.md) | → [Segurança nas Queries](03-queries.md)
