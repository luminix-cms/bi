# Segurança: Rotas e Middleware

Todas as rotas do Luminix BI passam por um conjunto de middlewares configurável. Essa é a primeira linha de defesa: antes de qualquer request chegar a um dashboard ou widget, ela é verificada pelo middleware definido na configuração do pacote.

## Middleware Padrão

O pacote usa por padrão:

```php
// config/luminix/bi.php
'middleware' => ['web', 'auth'],
```

- `web` — habilita sessão e proteção CSRF, necessário para autenticação baseada em sessão.
- `auth` — exige usuário autenticado; redireciona para login ou retorna 401 se não houver sessão.

## Como Personalizar

Publique o arquivo de configuração se ainda não o fez:

```bash
php artisan vendor:publish --tag=luminix-bi-config
```

Edite `config/luminix/bi.php` conforme a necessidade da sua aplicação.

### Adicionar um Gate de autorização

Para exigir que o usuário tenha uma permissão específica além da autenticação:

```php
'middleware' => ['web', 'auth', 'can:view-bi-reports'],
```

Defina o Gate no `AuthServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('view-bi-reports', function ($user) {
    return $user->hasRole('analyst') || $user->is_admin;
});
```

### APIs com Sanctum

Para aplicações que usam autenticação via token ao invés de sessão:

```php
'middleware' => ['api', 'auth:sanctum'],
```

### Ambiente de desenvolvimento

Para desabilitar restrições de acesso globais durante o desenvolvimento:

```php
'middleware' => ['web', 'auth'],
```

> Com apenas `auth`, qualquer usuário autenticado terá acesso a todas as rotas do BI. Use `viewable()` nos dashboards para controle individual nesse cenário — veja [Controle de Acesso por Dashboard](02-dashboard.md).

## Exemplo Completo de Configuração

```php
// config/luminix/bi.php
return [
    'path'       => env('LUMINIX_BI_PATH', 'bi'),
    'middleware' => ['web', 'auth', 'can:view-bi-reports'],
    'connection' => env('BI_DB_CONNECTION', null),
];
```

```bash
# .env
LUMINIX_BI_PATH=analytics
BI_DB_CONNECTION=mysql_readonly
```

## Próximos Passos

← [Visão Geral dos Filtros](../07-filtros/01-visao-geral.md) | → [Controle de Acesso por Dashboard](02-dashboard.md)
