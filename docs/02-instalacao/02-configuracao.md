# Configuração

O Luminix BI é configurado por um único arquivo PHP que controla o prefixo das rotas, os middlewares de autenticação, a conexão com o banco de dados e o modo de depuração. Entender cada opção é essencial para integrar o pacote corretamente em ambientes de produção.

## Publicando a configuração manualmente

O comando `bi:install` publica a configuração automaticamente. Caso precise republicar o arquivo em um projeto já instalado, execute:

```bash
php artisan vendor:publish --tag=bi-config
```

O arquivo será gerado em `config/luminix/bi.php`. Se o arquivo já existir, o Laravel pedirá confirmação antes de sobrescrevê-lo.

## O arquivo de configuração

O conteúdo padrão do arquivo publicado é o seguinte:

```php
// config/luminix/bi.php

return [
    'path'       => env('LUMINIX_BI_PATH', 'bi'),
    'middleware' => ['web', 'auth', 'can:read-bi-reports'],
    'connection' => env('BI_DB_CONNECTION', null),
    'debug'      => env('LUMINIX_BI_DEBUG', false),
];
```

A configuração é lida no container do Laravel pela chave `luminix.bi.*`. Por exemplo, `config('luminix.bi.path')` retorna o valor de `path`.

## Opções disponíveis

### `path`

**Variável de ambiente:** `LUMINIX_BI_PATH`
**Padrão:** `'bi'`

Define o prefixo das rotas da API. O pacote concatena o valor de `path` com o sufixo `-apis` para formar o prefixo real das rotas. Com o valor padrão `'bi'`, todas as rotas ficam disponíveis sob `/bi-apis/`.

**Quando alterar:** troque o valor quando o prefixo `bi` conflitar com uma rota existente na aplicação, ou quando a convenção de nomenclatura do projeto exigir um caminho diferente.

Exemplo: alterar para `'analytics'` faz as rotas responderem em `/analytics-apis/`:

```php
'path' => env('LUMINIX_BI_PATH', 'analytics'),
```

```bash
# .env
LUMINIX_BI_PATH=analytics
```

Com essa configuração, o endpoint de listagem de dashboards passa a ser:

```
GET /analytics-apis/dashboards
```

### `middleware`

**Variável de ambiente:** não possui (configurado diretamente no PHP)
**Padrão:** `['web', 'auth', 'can:read-bi-reports']`

Array de middlewares aplicados a todas as rotas da API do BI. O middleware padrão exige:

- `web` — sessão e proteção CSRF
- `auth` — usuário autenticado
- `can:read-bi-reports` — ability do Gate do Laravel

**Quando alterar:** substitua ou adicione middlewares conforme as regras de autenticação e autorização do projeto. Por exemplo, em uma API que utiliza tokens Sanctum em vez de sessão:

```php
'middleware' => ['auth:sanctum', 'can:read-bi-reports'],
```

Para remover a verificação de ability e deixar apenas a autenticação:

```php
'middleware' => ['web', 'auth'],
```

> Cuidado ao remover o middleware de autorização em produção. Sem ele, qualquer usuário autenticado terá acesso a todos os dashboards e dados analíticos.

### `connection`

**Variável de ambiente:** `BI_DB_CONNECTION`
**Padrão:** `null`

Nome da conexão de banco de dados a ser usada nas queries do BI. Quando `null`, o pacote utiliza a conexão padrão da aplicação (`DB_CONNECTION`).

**Quando alterar:** o caso de uso mais comum é apontar para uma réplica de leitura, isolando as queries analíticas — que costumam ser pesadas — da conexão principal usada pela aplicação operacional.

Exemplo com réplica de leitura MySQL:

```php
// config/database.php
'connections' => [
    'mysql' => [...],
    'mysql_replica' => [
        'driver'   => 'mysql',
        'host'     => env('DB_REPLICA_HOST', '127.0.0.1'),
        'database' => env('DB_DATABASE'),
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
        // demais opções...
    ],
],
```

```php
// config/luminix/bi.php
'connection' => env('BI_DB_CONNECTION', 'mysql_replica'),
```

```bash
# .env
BI_DB_CONNECTION=mysql_replica
```

Internamente, o `QueryService` usa essa configuração ao criar o builder base de cada widget:

```php
$query = $connection
    ? $object::on($connection)
    : $object::query();
```

### `debug`

**Variável de ambiente:** `LUMINIX_BI_DEBUG`
**Padrão:** `false`

Quando habilitado, o pacote ativa o log de queries do Laravel (`DB::enableQueryLog()`) durante a execução de cada widget e inclui o resultado no campo `debug` da resposta da API.

**Quando alterar:** habilite temporariamente durante o desenvolvimento para inspecionar as queries SQL geradas por widgets, métricas e dimensões. Nunca habilite em produção, pois as queries ficam visíveis na resposta HTTP.

Resposta com `debug` habilitado:

```json
{
    "status": 200,
    "data": [...],
    "debug": [
        {
            "query": "select COUNT(*) as `count` from `users` where `created_at` between ? and ?",
            "bindings": ["2024-01-01 00:00:00", "2024-01-01 23:59:59"],
            "time": 3.42
        }
    ]
}
```

## Exemplo completo de `.env`

```bash
# Prefixo das rotas — gera /analytics-apis/
LUMINIX_BI_PATH=analytics

# Conexão dedicada para queries analíticas
BI_DB_CONNECTION=mysql_replica

# Ativa o campo debug nas respostas (apenas em desenvolvimento)
LUMINIX_BI_DEBUG=true
```

## Tabela resumo

| Opção | Env var | Padrão | Impacto |
|---|---|---|---|
| `path` | `LUMINIX_BI_PATH` | `bi` | Prefixo das rotas (`bi` → `/bi-apis/`) |
| `middleware` | — | `['web', 'auth', 'can:read-bi-reports']` | Middlewares de todas as rotas BI |
| `connection` | `BI_DB_CONNECTION` | `null` | Conexão usada nas queries analíticas |
| `debug` | `LUMINIX_BI_DEBUG` | `false` | Inclui queries SQL na resposta da API |

## Próximos Passos

← [Instalação](01-instalacao.md) | → [Rotas da API](03-rotas.md)
