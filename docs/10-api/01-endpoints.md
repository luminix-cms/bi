# Endpoints Disponíveis

O Luminix BI expõe cinco endpoints REST que cobrem toda a comunicação entre o front-end e o back-end: listagem de dashboards, dados de widgets, download CSV e metadados de filtros. Todos os endpoints são registrados automaticamente pelo `BiServiceProvider` ao instalar o pacote.

## Tabela de Endpoints

| Método | Path (prefixo padrão) | Controller@Action | Descrição |
|---|---|---|---|
| `GET` | `/bi-apis/dashboards` | `DashboardController@getDashboards` | Lista todos os dashboards visíveis |
| `GET` | `/bi-apis/{dashboard}/widgets` | `DashboardController@getWidgets` | Retorna um dashboard com seus widgets e filtros |
| `GET` | `/bi-apis/{dashboard}/widgets/{widget}` | `WidgetController@getWidget` | Retorna os dados de um widget específico |
| `GET` | `/bi-apis/{dashboard}/widgets/{widget}/csv` | `WidgetController@download` | Faz o download dos dados do widget em CSV |
| `GET` | `/bi-apis/{dashboard}/filters/{filter}` | `FilterController@getFilter` | Retorna os metadados extras de um filtro |

Os parâmetros `{dashboard}` e `{widget}` são os valores de `uriKey` e `key` definidos nas respectivas classes.

## Como o Prefixo é Montado

O prefixo das rotas é construído a partir da configuração `luminix.bi.path`, com o sufixo `-apis` adicionado automaticamente:

```
{path}-apis
```

O valor padrão de `path` é `bi`, resultando no prefixo `bi-apis`. Para alterar, defina a variável de ambiente `LUMINIX_BI_PATH` no arquivo `.env`:

```bash
LUMINIX_BI_PATH=analytics
```

Com essa configuração, o prefixo passa a ser `analytics-apis` e todos os endpoints ficam sob `/analytics-apis/...`.

Para confirmar o caminho configurado, consulte o arquivo `config/luminix/bi.php` (ou `config/bi.php` antes da publicação):

```php
'path' => env('LUMINIX_BI_PATH', 'bi'),
```

## Middleware Aplicado

Todos os endpoints compartilham o mesmo grupo de middleware, configurado em `config/luminix/bi.php`:

```php
'middleware' => ['web', 'auth', 'can:read-bi-reports'],
```

O middleware padrão exige:

- `web` — habilita a sessão e o CSRF do Laravel
- `auth` — o usuário deve estar autenticado
- `can:read-bi-reports` — o usuário deve ter a ability `read-bi-reports` (Gate ou Policy)

Para personalizar, publique a configuração com `php artisan vendor:publish --tag=bi-config` e edite o array `middleware`.

> Além do middleware de grupo, cada dashboard pode restringir o acesso individualmente sobrescrevendo o método `viewable()` na classe do dashboard. Dashboards onde `viewable()` retorna `false` não aparecem na listagem e retornam 404 se acessados diretamente.

## Exemplos de Requisição

### Listar todos os dashboards

```bash
curl -X GET https://seuapp.com/bi-apis/dashboards \
     -H "Accept: application/json" \
     -H "X-XSRF-TOKEN: {csrf_token}"
```

### Buscar widgets e filtros de um dashboard

```bash
curl -X GET https://seuapp.com/bi-apis/vendas/widgets \
     -H "Accept: application/json" \
     -H "X-XSRF-TOKEN: {csrf_token}"
```

### Buscar dados de um widget com filtros

```bash
curl -X GET "https://seuapp.com/bi-apis/vendas/widgets/receita-mensal?filters[created_at][start]=2024-01-01&filters[created_at][end]=2024-12-31" \
     -H "Accept: application/json" \
     -H "X-XSRF-TOKEN: {csrf_token}"
```

### Fazer download CSV com filtros

```bash
curl -X GET "https://seuapp.com/bi-apis/vendas/widgets/receita-mensal/csv?filters[created_at][start]=2024-01-01&filters[created_at][end]=2024-12-31" \
     -H "X-XSRF-TOKEN: {csrf_token}" \
     --output receita-mensal.csv
```

### Buscar metadados de um filtro

```bash
curl -X GET https://seuapp.com/bi-apis/vendas/filters/status \
     -H "Accept: application/json" \
     -H "X-XSRF-TOKEN: {csrf_token}"
```

## Próximos Passos

[← Criando Widgets Customizados](../09-extensibilidade/04-widget-customizado.md) | [→ Formato de Requisição](02-requisicao.md)
