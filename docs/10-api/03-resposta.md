# Formato de Resposta

## Envelope Padrão

Todos os endpoints (exceto CSV) retornam JSON com o seguinte envelope:

```json
{ "status": 200, "data": ... }
```

Erros de autenticação (`401`), permissão (`403`) ou recurso não encontrado (`404`) retornam as respostas padrão do Laravel, sem o envelope.

## Resposta por Endpoint

### `GET /{path}-apis/dashboards`

Array de objetos de dashboard. Cada objeto inclui a configuração completa de widgets e filtros.

```json
{
    "status": 200,
    "data": [
        {
            "uriKey": "orders",
            "name": "Orders",
            "csvEnabled": false,
            "widgets": [
                {
                    "key": "monthly-revenue",
                    "name": "Monthly Revenue",
                    "component": "line-chart",
                    "width": 12,
                    "metrics": [
                        { "key": "total_amount", "name": "Revenue" }
                    ],
                    "dimensions": [
                        { "key": "month", "name": "Month" }
                    ],
                    "extra": { "uniqid": "64a3f2..." }
                }
            ],
            "filters": [
                {
                    "key": "created_at",
                    "name": "Period",
                    "component": "date-interval",
                    "defaultValue": { "start": "2024-01-01", "end": "2024-12-31" }
                }
            ]
        }
    ]
}
```

O campo `csvEnabled` indica se o dashboard tem o trait `HasCsvOutput`. O frontend pode usá-lo para exibir ou ocultar o botão de download. Consulte [Exportação CSV](../04-widgets/06-csv.md).

---

### `GET /{path}-apis/{dashboard}/widgets`

Objeto de um único dashboard. A estrutura é idêntica a cada item do array retornado por `/dashboards`.

```json
{
    "status": 200,
    "data": {
        "uriKey": "orders",
        "name": "Orders",
        "csvEnabled": false,
        "widgets": [...],
        "filters": [...]
    }
}
```

---

### `GET /{path}-apis/{dashboard}/widgets/{widget}`

Array de objetos com os dados calculados. Cada chave corresponde ao `key` de uma métrica ou dimensão registrada no widget.

`BigNumber` (uma métrica, sem dimensão):

```json
{
    "status": 200,
    "data": [
        { "total_orders": 1247 }
    ]
}
```

`LineChart` (métrica + dimensão de data):

```json
{
    "status": 200,
    "data": [
        { "month": "2024-01", "total_amount": 48500.00 },
        { "month": "2024-02", "total_amount": 41200.00 },
        { "month": "2024-03", "total_amount": 55800.00 }
    ]
}
```

`Table` (múltiplas métricas e uma dimensão):

```json
{
    "status": 200,
    "data": [
        { "seller": "Alice Johnson", "total_orders": 342, "total_amount": 89450.00 },
        { "seller": "Bob Smith",     "total_orders": 218, "total_amount": 54200.00 }
    ]
}
```

---

### `GET /{path}-apis/{dashboard}/widgets/{widget}/csv`

Não retorna JSON. Retorna um stream de texto para download.

**Headers da resposta:**

```
Content-Type: text/plain
Content-Disposition: attachment; filename=monthly-revenue.csv
```

**Corpo:**

```
month,total_amount
2024-01,48500.00
2024-02,41200.00
2024-03,55800.00
```

A primeira linha contém os cabeçalhos (chaves das métricas e dimensões). O nome do arquivo é o `name` do widget convertido para slug.

---

### `GET /{path}-apis/{dashboard}/filters/{filter}`

Retorna o campo `extra` (não `data`) com os metadados do filtro.

`StringFilter` com opções:

```json
{
    "status": 200,
    "extra": {
        "options": ["confirmed", "pending", "cancelled"]
    }
}
```

`RelationFilter` com lista de registros relacionados:

```json
{
    "status": 200,
    "extra": {
        "options": [
            { "id": 1, "name": "Alice Johnson" },
            { "id": 2, "name": "Bob Smith" }
        ],
        "otherColumn": "name",
        "primaryKey": "id"
    }
}
```

## Modo Debug

Quando `LUMINIX_BI_DEBUG=true` está definido no `.env`, o endpoint de dados do widget inclui o campo `debug` com as queries executadas:

```json
{
    "status": 200,
    "data": [...],
    "debug": [
        {
            "query": "SELECT SUM(total_amount) as `revenue` FROM `orders` WHERE `created_at` BETWEEN ? AND ?",
            "bindings": ["2024-01-01 00:00:00", "2024-12-31 23:59:59"],
            "time": 4.2
        }
    ]
}
```

- `query` — SQL com placeholders `?`
- `bindings` — valores substituídos nos placeholders, na ordem
- `time` — tempo de execução em milissegundos

> Nunca habilite o debug em produção. O log de queries pode expor a estrutura interna do banco.

## Campo `component`

Widgets e filtros incluem o campo `component` em sua serialização. O front-end usa esse valor para determinar qual componente renderizar:

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

Widgets e filtros customizados devem definir um valor único para `$component`.

## Próximos Passos

[← Formato de Requisição](02-requisicao.md) | [→ Executando os Testes](../11-testes/01-executando.md)
