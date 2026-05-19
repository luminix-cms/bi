# Formato de Resposta

Todos os endpoints do Luminix BI (exceto o de download CSV) retornam JSON com um envelope padrão. Esta página descreve a estrutura de resposta de cada endpoint, incluindo o campo de debug e o significado de cada campo.

## Envelope Padrão

```json
{
    "status": 200,
    "data": ...
}
```

O campo `status` sempre reflete o código HTTP `200` em respostas bem-sucedidas. Erros de autenticação (`401`), permissão (`403`) ou recurso não encontrado (`404`) retornam as respostas padrão do Laravel, sem o envelope.

## Com Debug Habilitado

Quando o debug está ativo, a resposta inclui o campo `debug` com o log de queries executadas pelo endpoint:

```json
{
    "status": 200,
    "data": [...],
    "debug": [
        {
            "query":    "SELECT SUM(valor) as `total` FROM `pedidos` WHERE `created_at` BETWEEN ? AND ?",
            "bindings": ["2024-01-01 00:00:00", "2024-12-31 23:59:59"],
            "time":     4.2
        }
    ]
}
```

O campo `debug` é um array com uma entrada por query executada. Cada entrada tem:

- `query` — o SQL com placeholders `?`
- `bindings` — os valores substituídos nos placeholders, na ordem
- `time` — tempo de execução em milissegundos

### Como Habilitar o Debug

Defina a variável de ambiente `LUMINIX_BI_DEBUG` como `true` no arquivo `.env`:

```bash
LUMINIX_BI_DEBUG=true
```

> Nunca habilite o debug em produção. O log de queries pode expor estrutura interna do banco de dados.

## Estrutura de Resposta por Endpoint

### 1. `GET /bi-apis/dashboards`

Retorna um array com todos os dashboards cujo método `viewable()` retorna `true`.

```json
{
    "status": 200,
    "data": [
        {
            "uriKey":  "vendas",
            "name":    "Vendas",
            "widgets": [
                {
                    "key":        "receita-mensal",
                    "name":       "Receita Mensal",
                    "component":  "line-chart",
                    "width":      12,
                    "metrics":    [{ "key": "receita", "name": "Receita", ... }],
                    "dimensions": [{ "key": "mes",     "name": "Mês",     ... }],
                    "extra":      { "uniqid": "64a3f2..." }
                }
            ],
            "filters": [
                {
                    "key":          "created_at",
                    "name":         "Período",
                    "component":    "date-interval",
                    "defaultValue": { "start": "2024-01-01", "end": "2024-12-31" }
                }
            ]
        }
    ]
}
```

### 2. `GET /bi-apis/{dashboard}/widgets`

Retorna o objeto de um único dashboard com seus widgets e filtros. A estrutura do objeto é idêntica a cada item do array retornado por `/dashboards`.

```json
{
    "status": 200,
    "data": {
        "uriKey":  "vendas",
        "name":    "Vendas",
        "widgets": [...],
        "filters": [...]
    }
}
```

### 3. `GET /bi-apis/{dashboard}/widgets/{widget}`

Retorna os dados calculados do widget — o resultado da query com métricas e dimensões aplicadas. O campo `data` é um array de objetos onde cada chave corresponde ao `key` de uma métrica ou dimensão registrada no widget.

```json
{
    "status": 200,
    "data": [
        { "mes": "2024-01", "receita": 48500.00 },
        { "mes": "2024-02", "receita": 41200.00 },
        { "mes": "2024-03", "receita": 55800.00 }
    ]
}
```

Para um `BigNumber` com apenas uma métrica (sem dimensão):

```json
{
    "status": 200,
    "data": [
        { "total_pedidos": 1247 }
    ]
}
```

Para um `Table` com múltiplas métricas e uma dimensão:

```json
{
    "status": 200,
    "data": [
        { "vendedor": "Ana Silva",    "total_pedidos": 342, "receita": 89450.00 },
        { "vendedor": "Bruno Santos", "total_pedidos": 218, "receita": 54200.00 },
        { "vendedor": "Carla Matos",  "total_pedidos": 197, "receita": 49800.00 }
    ]
}
```

### 4. `GET /bi-apis/{dashboard}/widgets/{widget}/csv`

Este endpoint não retorna JSON. Retorna um stream de texto com o arquivo CSV para download.

**Headers da resposta:**

```
Content-Type: text/plain
Content-Disposition: attachment; filename=receita-mensal.csv
Cache-Control: must-revalidate, post-check=0, pre-check=0
Pragma: no-cache
Expires: 0
```

**Corpo da resposta:**

```
mes,receita
2024-01,48500.00
2024-02,41200.00
2024-03,55800.00
```

A primeira linha contém os cabeçalhos — as chaves dos objetos retornados pelo widget. As linhas seguintes contêm os valores correspondentes, separados por vírgula. O nome do arquivo é o `name` do widget convertido para slug (por exemplo, `"Receita Mensal"` gera `receita-mensal.csv`).

### 5. `GET /bi-apis/{dashboard}/filters/{filter}`

Retorna os metadados extras de um filtro, usados pelo front-end para montar o controle de interface. O campo é `extra` (não `data`).

Para um `StringFilter`:

```json
{
    "status": 200,
    "extra": {
        "options": ["confirmado", "pendente", "cancelado"]
    }
}
```

Para um `RelationFilter`:

```json
{
    "status": 200,
    "extra": {
        "options": [
            { "id": 1, "nome": "Ana Silva"    },
            { "id": 2, "nome": "Bruno Santos" },
            { "id": 3, "nome": "Carla Matos"  }
        ],
        "otherColumn": "nome",
        "primaryKey":  "id"
    }
}
```

Para um `DateIntervalFilter` (sem `extra()` customizado):

```json
{
    "status": 200,
    "extra": {}
}
```

## O Campo `component`

Tanto widgets quanto filtros carregam o campo `component` em sua serialização JSON. Esse campo é uma string que identifica qual componente do front-end deve ser instanciado para renderizar o item.

Exemplos de valores:

| Tipo | `component` |
|---|---|
| `BigNumber` | `"big-number"` |
| `LineChart` | `"line-chart"` |
| `PartitionPie` | `"partition-pie"` |
| `Table` | `"table"` |
| `StringFilter` | `"string"` |
| `NumberFilter` | `"number"` |
| `DateFilter` | `"date"` |
| `DateIntervalFilter` | `"date-interval"` |
| `RelationFilter` | `"belongs-to"` |

O front-end usa esse campo para fazer a correspondência entre o objeto JSON e o componente Vue/React que sabe renderizá-lo. Widgets e filtros customizados devem definir um valor único para `$component` que o front-end conheça.

## Próximos Passos

[← Formato de Requisição](02-requisicao.md) | [→ Executando os Testes](../11-testes/01-executando.md)
