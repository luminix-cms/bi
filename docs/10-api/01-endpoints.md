# Endpoints

O Luminix BI registra cinco endpoints REST automaticamente via `BiServiceProvider`. Não é necessário declarar rotas manualmente.

## Tabela de Endpoints

| Método | Path | Descrição |
|---|---|---|
| `GET` | `/{path}-apis/dashboards` | Lista dashboards visíveis |
| `GET` | `/{path}-apis/{dashboard}/widgets` | Configuração completa do dashboard (widgets e filtros) |
| `GET` | `/{path}-apis/{dashboard}/widgets/{widget}` | Dados calculados do widget |
| `GET` | `/{path}-apis/{dashboard}/widgets/{widget}/csv` | Download dos dados em CSV |
| `GET` | `/{path}-apis/{dashboard}/filters/{filter}` | Metadados extras do filtro |

Os parâmetros de rota `{dashboard}` e `{widget}` correspondem aos valores de `uriKey` e `key` definidos nas respectivas classes.

## Prefixo das Rotas

O prefixo é montado a partir da configuração `luminix.bi.path` com o sufixo `-apis`:

```
{path}-apis
```

O valor padrão é `bi`, resultando em `/bi-apis/...`. Para alterar, defina no `.env`:

```bash
LUMINIX_BI_PATH=analytics
```

Com essa configuração, todos os endpoints ficam sob `/analytics-apis/...`.

Para mais detalhes sobre a configuração do pacote, consulte [Configuração](../02-instalacao/02-configuracao.md).

## Middleware

Todos os endpoints compartilham o mesmo grupo de middleware, configurado em `config/luminix/bi.php`:

```php
'middleware' => ['web', 'auth', 'can:read-bi-reports'],
```

Para personalizar, publique a configuração e edite o array `middleware`. Consulte [Segurança nas Rotas](../08-seguranca/01-rotas.md) para detalhes sobre controle de acesso.

## Contrato de Cada Endpoint

### `GET /{path}-apis/dashboards`

Lista todos os dashboards cujo método `viewable()` retorna `true`.

**Parâmetros:** nenhum.

**Resposta:** array de objetos de dashboard com seus widgets e filtros serializados.

---

### `GET /{path}-apis/{dashboard}/widgets`

Retorna a configuração completa de um único dashboard.

**Parâmetros de rota:**
- `{dashboard}` — `uriKey` do dashboard

**Resposta:** objeto com `uriKey`, `name`, `widgets` e `filters`.

---

### `GET /{path}-apis/{dashboard}/widgets/{widget}`

Executa a query do widget e retorna os dados calculados.

**Parâmetros de rota:**
- `{dashboard}` — `uriKey` do dashboard
- `{widget}` — `key` do widget

**Query params:**
- `filters[{key}]` — valor do filtro, formato depende do tipo (ver [Requisição](02-requisicao.md))
- `sort[col]` e `sort[dir]` — ordenação (apenas para `Table`)

**Resposta:** array de objetos onde cada chave corresponde ao `key` de uma métrica ou dimensão do widget.

---

### `GET /{path}-apis/{dashboard}/widgets/{widget}/csv`

Retorna os dados do widget como arquivo CSV para download. Aceita os mesmos query params do endpoint de dados.

**Resposta:** stream de texto com `Content-Disposition: attachment`.

---

### `GET /{path}-apis/{dashboard}/filters/{filter}`

Retorna o resultado do método `extra()` do filtro — usado pelo front-end para montar controles como selects e sliders.

**Parâmetros de rota:**
- `{dashboard}` — `uriKey` do dashboard
- `{filter}` — `key` do filtro

**Resposta:** objeto com campo `extra` contendo os metadados retornados pelo filtro.

## Próximos Passos

[← Widgets Customizados](../09-extensibilidade/04-widget-customizado.md) | [→ Formato de Requisição](02-requisicao.md)
