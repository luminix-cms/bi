# Configuração

O Luminix BI é configurado pelo arquivo `config/luminix/bi.php`, publicado durante a instalação. Se precisar republicá-lo em um projeto já instalado:

```bash
php artisan vendor:publish --tag=bi-config
```

## O arquivo de configuração

```php
// config/luminix/bi.php

return [
    'path'       => env('LUMINIX_BI_PATH', 'bi'),
    'middleware' => ['web', 'auth', 'can:read-bi-reports'],
    'connection' => env('BI_DB_CONNECTION', null),
    'debug'      => env('LUMINIX_BI_DEBUG', false),
];
```

## Opções disponíveis

### `path`

**Variável de ambiente:** `LUMINIX_BI_PATH` | **Padrão:** `'bi'`

Define o prefixo das rotas da API. O pacote concatena o valor de `path` com o sufixo `-apis`, portanto o valor padrão `'bi'` resulta em `/bi-apis/`. Consulte os [endpoints disponíveis](../10-api/01-endpoints.md) para ver todas as rotas registradas.

Altere quando o prefixo conflitar com uma rota existente ou quando o projeto exigir um caminho diferente:

```php
'path' => env('LUMINIX_BI_PATH', 'analytics'),
// rotas respondem em /analytics-apis/
```

### `middleware`

**Variável de ambiente:** não possui | **Padrão:** `['web', 'auth', 'can:read-bi-reports']`

Array de middlewares aplicados a todas as rotas da API. O padrão exige sessão web, autenticação e a ability `read-bi-reports` do Gate do Laravel.

Substitua conforme as regras do projeto. Exemplo com Sanctum:

```php
'middleware' => ['auth:sanctum', 'can:read-bi-reports'],
```

Para remover a verificação de ability:

```php
'middleware' => ['web', 'auth'],
```

> Sem o middleware de autorização, qualquer usuário autenticado terá acesso a todos os dashboards.

### `connection`

**Variável de ambiente:** `BI_DB_CONNECTION` | **Padrão:** `null`

Nome da conexão de banco de dados usada nas queries do BI. Quando `null`, utiliza a conexão padrão da aplicação.

O caso de uso mais comum é apontar para uma réplica de leitura, isolando as queries analíticas da conexão principal:

```php
// config/database.php
'connections' => [
    'mysql_replica' => [
        'driver'   => 'mysql',
        'host'     => env('DB_REPLICA_HOST', '127.0.0.1'),
        'database' => env('DB_DATABASE'),
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
    ],
],
```

```php
// config/luminix/bi.php
'connection' => env('BI_DB_CONNECTION', 'mysql_replica'),
```

### `debug`

**Variável de ambiente:** `LUMINIX_BI_DEBUG` | **Padrão:** `false`

Quando habilitado, inclui as queries SQL executadas no campo `debug` da resposta da API. Útil durante o desenvolvimento para inspecionar as queries geradas por widgets, métricas e dimensões.

```json
{
    "status": 200,
    "data": [...],
    "debug": [
        {
            "query": "select COUNT(*) as `count` from `orders` where `created_at` between ? and ?",
            "bindings": ["2024-01-01 00:00:00", "2024-01-01 23:59:59"],
            "time": 3.42
        }
    ]
}
```

> Nunca habilite `debug` em produção — as queries ficam visíveis na resposta HTTP.

## Exemplo de `.env`

```bash
LUMINIX_BI_PATH=analytics
BI_DB_CONNECTION=mysql_replica
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

← [Instalação](01-instalacao.md) | → [Criando um Dashboard](../03-dashboards/01-criando.md) | [Uso Básico](../uso-basico.md)
