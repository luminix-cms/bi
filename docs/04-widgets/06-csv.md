# Exportação CSV

A exportação CSV é **desativada por padrão**. Para habilitá-la em um dashboard, adicione o trait `HasCsvOutput` à classe do dashboard.

---

## Habilitando a Exportação

```php
<?php

namespace App\Bi\Dashboards;

use Luminix\Bi\Dashboard;
use Luminix\Bi\Concerns\HasCsvOutput;

class SalesDashboard extends Dashboard
{
    use HasCsvOutput;

    // ...
}
```

Com o trait presente, **todos os widgets** do dashboard passam a expor o endpoint de download. Sem ele, requisições ao endpoint retornam `404`.

O campo `csvEnabled` na resposta JSON do dashboard reflete o estado atual:

```json
{
    "uriKey": "sales",
    "name": "Sales",
    "csvEnabled": true,
    "widgets": [...],
    "filters": [...]
}
```

O frontend pode usar esse campo para exibir ou ocultar o botão de download.

---

## Endpoint

```
GET /{dashboard}/widgets/{widget}/csv
```

Onde `{dashboard}` é o `$uriKey` do dashboard e `{widget}` é a chave do widget declarada no `create()`.

Exemplo:

```
GET /bi-apis/sales/widgets/revenue-by-month/csv
```

O mesmo middleware configurado para as demais rotas da API também protege o endpoint de exportação. Veja [Rotas e Middleware](../08-seguranca/01-rotas.md).

---

## Aceita os Mesmos Filtros

A exportação aplica os mesmos filtros do endpoint de dados. Para exportar apenas um subconjunto, inclua os parâmetros de filtro na URL:

```
GET /bi-apis/sales/widgets/revenue-by-month/csv
    ?filters[created_at][start]=2024-01-01
    &filters[created_at][end]=2024-03-31
```

O arquivo gerado conterá apenas os dados do intervalo filtrado.

---

## Formato do Arquivo

- Primeira linha: cabeçalho com as chaves das dimensões e métricas
- Linhas seguintes: valores de cada registro
- Separador: vírgula
- Codificação: UTF-8

Exemplo de saída para um `LineChart` com `MonthDimension`, `CountMetric` e `SumMetric`:

```
created_at,orders,total_amount
2024-01,312,48500.00
2024-02,0,0
2024-03,287,41200.00
```

---

## Nome do Arquivo

O nome do arquivo é gerado a partir do `$name` do widget via `Str::slug()`:

| Nome do widget | Arquivo gerado |
|---|---|
| `"Revenue by Month"` | `revenue-by-month.csv` |
| `"Orders by Category"` | `orders-by-category.csv` |

O cabeçalho HTTP enviado é:

```
Content-Disposition: attachment; filename=revenue-by-month.csv
```

---

## Exemplo com curl

```bash
curl -X GET \
  "https://your-app.com/bi-apis/sales/widgets/revenue-by-month/csv?filters[created_at][start]=2024-01-01&filters[created_at][end]=2024-12-31" \
  -H "Accept: text/csv" \
  -H "Cookie: laravel_session=YOUR_SESSION_TOKEN" \
  -o revenue-by-month.csv
```

---

## Próximos Passos

- [← PartitionPie](05-partition-pie.md) | [→ Visão Geral das Métricas](../05-metricas/01-visao-geral.md)
- [Rotas e autenticação](../08-seguranca/01-rotas.md)
