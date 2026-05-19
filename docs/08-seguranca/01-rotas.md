# Autenticação e Autorização das Rotas

A primeira linha de defesa do Luminix BI é o middleware aplicado às suas rotas. Pense no middleware como a portaria de um edifício: antes de qualquer pedido chegar a um dashboard ou widget, ele passa por verificações que confirmam a identidade do visitante e se ele tem permissão para entrar.

## Middleware Padrão

O pacote vem configurado com três middlewares aplicados a todas as suas rotas:

```php
// config/bi.php
'middleware' => ['web', 'auth', 'can:read-bi-reports'],
```

Cada middleware cumpre um papel distinto:

### `web`

O middleware `web` do Laravel é necessário para habilitar o gerenciamento de sessão e a proteção CSRF. Sem ele, o Laravel não consegue ler a sessão do usuário, o que impede o middleware `auth` de funcionar corretamente. Se a sua aplicação usa autenticação baseada em sessão (o caso mais comum com Laravel Breeze, Fortify ou Jetstream), esse middleware é obrigatório.

### `auth`

O middleware `auth` verifica se há um usuário autenticado na sessão atual. Se não houver, a requisição é redirecionada para a rota de login (ou retorna 401, dependendo da configuração). Esse middleware garante que nenhuma rota do BI seja acessível por visitantes anônimos.

### `can:read-bi-reports`

O middleware `can:read-bi-reports` usa o sistema de autorização por Gates do Laravel para verificar se o usuário autenticado tem permissão para acessar os relatórios do BI. Se o Gate retornar `false`, o Laravel retorna uma resposta 403 Forbidden.

Esse middleware oferece controle granular: você pode criar uma lógica de autorização complexa no Gate sem alterar o middleware do pacote.

## Como o Middleware é Lido

O middleware é lido diretamente da configuração em tempo de execução — não é fixado durante o boot do pacote:

```php
// BiServiceProvider
Config::get('luminix.bi.middleware')
```

Isso significa que você pode sobrescrever o valor via `config/bi.php` sem precisar estender ou modificar o `BiServiceProvider`. A leitura acontece quando a rota é registrada, e como o Laravel registra rotas na inicialização, a configuração deve estar disponível nesse momento.

## Criando o Gate `read-bi-reports`

O Gate `read-bi-reports` não existe por padrão na aplicação — você precisa registrá-lo. O local correto é o `AuthServiceProvider` da sua aplicação:

```php
// app/Providers/AuthServiceProvider.php
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('read-bi-reports', function ($user) {
            return $user->hasRole('analyst') || $user->is_admin;
        });
    }
}
```

A lógica dentro do Gate pode ser tão simples ou complexa quanto necessário — verificar papéis, permissões de um pacote ACL, atributos do usuário, ou qualquer outra condição de negócio.

## Como Personalizar o Middleware

Publique o arquivo de configuração se ainda não o fez:

```bash
php artisan vendor:publish --tag=luminix-bi-config
```

Depois edite `config/bi.php`:

### Substituir Completamente (APIs com Sanctum)

Para aplicações que usam autenticação via token ao invés de sessão:

```php
'middleware' => ['api', 'auth:sanctum'],
```

> Ao substituir o middleware `web` por `api`, a proteção CSRF é removida. Certifique-se de que sua aplicação tem outra camada de proteção adequada para APIs com token.

### Adicionar um Gate Específico

Para usar um Gate diferente de `read-bi-reports`:

```php
'middleware' => ['web', 'auth', 'can:view-bi'],
```

Lembre-se de definir o Gate `view-bi` no `AuthServiceProvider`.

### Remover a Verificação de Gate (não recomendado)

Para ambientes de desenvolvimento ou situações onde a autenticação é suficiente:

```php
'middleware' => ['web', 'auth'],
```

> Remover o Gate de autorização significa que qualquer usuário autenticado terá acesso a todos os dashboards do BI. Use apenas em ambientes controlados ou quando a autorização por dashboard (via `viewable()`) for suficiente.

### Exemplo Completo de `config/bi.php`

```php
return [
    'path'       => env('LUMINIX_BI_PATH', 'bi'),
    'middleware' => ['web', 'auth', 'can:view-bi'],
    'connection' => env('BI_DB_CONNECTION', null),
    'debug'      => env('LUMINIX_BI_DEBUG', false),
];
```

Com o `.env` correspondente:

```bash
LUMINIX_BI_PATH=analytics
BI_DB_CONNECTION=mysql_readonly
LUMINIX_BI_DEBUG=false
```

E o Gate definido no `AuthServiceProvider`:

```php
Gate::define('view-bi', function ($user) {
    return $user->hasPermissionTo('access-bi-reports');
});
```

## Próximos Passos

← [RelationFilter](../07-filtros/06-relation.md) | → [Controle de Acesso por Dashboard](02-dashboard.md)
