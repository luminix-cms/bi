# Rotas da API

O Luminix BI expõe cinco endpoints REST que o frontend consome para listar dashboards, carregar dados de widgets, baixar exportações CSV e buscar opções de filtros. Todos os endpoints seguem o mesmo formato de resposta e respeitam o middleware configurado.

## Tabela de endpoints

| Método | Path | Controller@action | O que retorna |
|---|---|---|---|
| `GET` | `/bi-apis/dashboards` | `DashboardController@getDashboards` | Lista todos os dashboards visíveis para o usuário |
| `GET` | `/bi-apis/{dashboard}/widgets` | `DashboardController@getWidgets` | Configuração completa de um dashboard (widgets e filtros) |
| `GET` | `/bi-apis/{dashboard}/widgets/{widget}` | `WidgetController@getWidget` | Dados calculados de um widget específico |
| `GET` | `/bi-apis/{dashboard}/widgets/{widget}/csv` | `WidgetController@download` | Stream CSV com os dados do widget |
| `GET` | `/bi-apis/{dashboard}/filters/{filter}` | `FilterController@getFilter` | Dados extras de um filtro (ex.: opções de seleção) |

O parâmetro `{dashboard}` corresponde ao `$uriKey` do dashboard. O parâmetro `{widget}` corresponde ao `$key` do widget.

## Prefixo das rotas

O prefixo padrão é `bi-apis`, formado pela concatenação de `path` (padrão `'bi'`) com o sufixo fixo `-apis`. Para alterar o prefixo, modifique a opção `path` na configuração:

```php
// config/luminix/bi.php
'path' => env('LUMINIX_BI_PATH', 'relatorios'),
```

Com essa configuração, todos os endpoints passam a responder sob `/relatorios-apis/`:

```
GET /relatorios-apis/dashboards
GET /relatorios-apis/{dashboard}/widgets
...
```

## Formato de resposta padrão

Todos os endpoints retornam um objeto JSON com o envelope padrão:

```json
{
    "status": 200,
    "data": [...]
}
```

O campo `status` sempre contém o código HTTP da operação. O campo `data` contém o payload específico de cada endpoint.

Quando `LUMINIX_BI_DEBUG=true`, os endpoints de widget incluem um campo adicional com o log de queries executadas:

```json
{
    "status": 200,
    "data": [...],
    "debug": [
        {
            "query": "select COUNT(*) as `total` from `orders` group by `status`",
            "bindings": [],
            "time": 12.5
        }
    ]
}
```

> O campo `debug` nunca aparece em endpoints de dashboard ou filtro, apenas nos endpoints `getWidget`. Isso ocorre porque o log de queries é ativado e coletado exclusivamente no `WidgetController`.

## Exemplos de resposta por endpoint

### `GET /bi-apis/dashboards`

Lista todos os dashboards registrados em `app/Bi/Dashboards/` cujo método `viewable()` retorna `true` para o usuário atual.

```json
{
    "status": 200,
    "data": [
        {
            "uriKey": "vendas",
            "name": "Dashboard de Vendas",
            "widgets": [
                {
                    "key": "total-vendas",
                    "name": "Total de Vendas",
                    "component": "big-number",
                    "width": "1/3",
                    "metrics": [...],
                    "dimensions": [],
                    "extra": { "uniqid": "64f3a1b2c9e8d" }
                }
            ],
            "filters": [
                {
                    "key": "created_at",
                    "name": "Data de criação",
                    "component": "date"
                }
            ]
        }
    ]
}
```

### `GET /bi-apis/vendas/widgets`

Retorna a configuração completa de um único dashboard, incluindo todos os widgets e filtros configurados.

```json
{
    "status": 200,
    "data": {
        "uriKey": "vendas",
        "name": "Dashboard de Vendas",
        "widgets": [
            {
                "key": "total-vendas",
                "name": "Total de Vendas",
                "component": "big-number",
                "width": "1/3",
                "metrics": [
                    { "key": "total", "name": "Total", "color": "#4CAF50" }
                ],
                "dimensions": [],
                "extra": { "uniqid": "64f3a1b2c9e8d" }
            },
            {
                "key": "vendas-por-status",
                "name": "Vendas por Status",
                "component": "partition-pie",
                "width": "2/3",
                "metrics": [...],
                "dimensions": [...],
                "extra": { "uniqid": "64f3a1c2d1f4e" }
            }
        ],
        "filters": [
            {
                "key": "created_at",
                "name": "Data de criação",
                "component": "date",
                "defaultValue": null
            }
        ]
    }
}
```

### `GET /bi-apis/vendas/widgets/total-vendas`

Executa as queries do widget e retorna os dados calculados. Este é o endpoint que o frontend chama para popular os gráficos e números.

```json
{
    "status": 200,
    "data": [
        { "total": 1547 }
    ]
}
```

Para um widget com dimensão (ex.: agrupado por mês):

```json
{
    "status": 200,
    "data": [
        { "mes": "2024-01", "total": 312 },
        { "mes": "2024-02", "total": 289 },
        { "mes": "2024-03", "total": 401 }
    ]
}
```

### `GET /bi-apis/vendas/widgets/total-vendas/csv`

Inicia um stream de download com o conteúdo do widget em formato CSV. A resposta não segue o envelope JSON padrão — é um arquivo de texto com cabeçalho `Content-Disposition`.

```
Content-Disposition: attachment; filename=total-vendas.csv
Content-Type: text/plain

mes,total
2024-01,312
2024-02,289
2024-03,401
```

O nome do arquivo CSV é gerado a partir do `$name` do widget convertido para slug (ex.: `"Total de Vendas"` vira `total-de-vendas.csv`).

### `GET /bi-apis/vendas/filters/created_at`

Retorna dados extras de um filtro, geralmente usados pelo frontend para popular opções de seleção. O formato do campo `extra` varia conforme o tipo de filtro.

```json
{
    "status": 200,
    "extra": {
        "options": [
            { "value": "pending", "label": "Pendente" },
            { "value": "completed", "label": "Concluído" },
            { "value": "cancelled", "label": "Cancelado" }
        ]
    }
}
```

> Para filtros simples como `DateFilter` e `NumberFilter`, o campo `extra` retorna um array vazio `{}`, pois não há dados adicionais a fornecer ao frontend.

## Passando filtros nas requisições

Os filtros são enviados como parâmetros de query string. Cada filtro é identificado pela sua chave:

```bash
GET /bi-apis/vendas/widgets/total-vendas?filters[created_at][]=2024-03-15
```

Para filtros de intervalo de datas:

```bash
GET /bi-apis/vendas/widgets/total-vendas?filters[periodo][start]=2024-01-01&filters[periodo][end]=2024-03-31
```

## Testando os endpoints

### Com curl

```bash
# Listar todos os dashboards (requer autenticação por cookie de sessão)
curl -s \
     -H "Accept: application/json" \
     -H "Cookie: laravel_session=<valor>" \
     http://localhost:8000/bi-apis/dashboards | jq .

# Buscar dados de um widget com filtro de data
curl -s \
     -H "Accept: application/json" \
     -H "Cookie: laravel_session=<valor>" \
     "http://localhost:8000/bi-apis/vendas/widgets/total-vendas?filters[created_at][]=2024-03-15" | jq .

# Baixar CSV de um widget
curl -s \
     -H "Cookie: laravel_session=<valor>" \
     -o "total-vendas.csv" \
     "http://localhost:8000/bi-apis/vendas/widgets/total-vendas/csv"
```

### Com Postman

1. Crie uma requisição `GET` para `http://localhost:8000/bi-apis/dashboards`
2. Na aba **Headers**, adicione `Accept: application/json`
3. Na aba **Cookies**, adicione o cookie de sessão do Laravel (`laravel_session`)
4. Envie a requisição e verifique a resposta na aba **Body**

Para testar com filtros, use a aba **Params** e adicione os pares chave-valor no formato `filters[chave][]` = `valor`.

## Próximos Passos

← [Configuração](02-configuracao.md) | → [Criando um Dashboard](../03-dashboards/01-criando.md)
