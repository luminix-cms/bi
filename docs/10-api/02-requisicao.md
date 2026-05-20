# Formato de Requisição

Todas as requisições são do tipo `GET`. Os filtros e a ordenação são passados como query string.

## Parâmetro `filters`

Cada chave do objeto `filters` corresponde ao `key` de um filtro registrado no dashboard. O formato do valor depende do tipo do filtro.

### StringFilter

Array de strings. Gera `WHERE IN`.

```bash
curl "https://app.example.com/bi-apis/orders/widgets/orders-by-status\
?filters[status][]=confirmed&filters[status][]=pending" \
  -H "Accept: application/json"
```

### NumberFilter

Objeto com `operator` e `values`. Operadores suportados: `=`, `!=`, `>`, `>=`, `<`, `<=`, `between`.

```bash
# Valor único
curl "https://app.example.com/bi-apis/orders/widgets/high-value\
?filters[total_amount][operator]=>=&filters[total_amount][values][]=500" \
  -H "Accept: application/json"

# Intervalo
curl "https://app.example.com/bi-apis/orders/widgets/high-value\
?filters[total_amount][operator]=between\
&filters[total_amount][values][]=100\
&filters[total_amount][values][]=500" \
  -H "Accept: application/json"
```

### DateFilter

Array com uma única data no formato `Y-m-d`. Gera `WHERE BETWEEN` cobrindo o dia inteiro (`00:00:00` até `23:59:59`).

```bash
curl "https://app.example.com/bi-apis/orders/widgets/daily-summary\
?filters[created_at][]=2024-03-15" \
  -H "Accept: application/json"
```

### DateIntervalFilter

Objeto com `start` e `end` no formato `Y-m-d`.

```bash
curl "https://app.example.com/bi-apis/orders/widgets/monthly-revenue\
?filters[created_at][start]=2024-01-01\
&filters[created_at][end]=2024-12-31" \
  -H "Accept: application/json"
```

### RelationFilter

Array de IDs. Gera `WHERE EXISTS` na relação.

```bash
curl "https://app.example.com/bi-apis/orders/widgets/orders-by-seller\
?filters[seller][]=1&filters[seller][]=3" \
  -H "Accept: application/json"
```

## Parâmetro `sort` (apenas `Table`)

Objeto com `col` (chave da métrica ou dimensão) e `dir` (`asc` ou `desc`).

```bash
curl "https://app.example.com/bi-apis/orders/widgets/top-sellers\
?sort[col]=total_amount&sort[dir]=desc" \
  -H "Accept: application/json"
```

Outros widgets ignoram o parâmetro `sort`.

## Filtros Não Enviados

Filtros registrados no dashboard mas não enviados na requisição são simplesmente ignorados. A query roda sem aquela condição — ausência do parâmetro não resulta em erro.

## Múltiplos Filtros Combinados

```bash
curl "https://app.example.com/bi-apis/orders/widgets/revenue-by-month\
?filters[created_at][start]=2024-01-01\
&filters[created_at][end]=2024-06-30\
&filters[status][]=confirmed\
&filters[status][]=processing\
&sort[col]=total_amount\
&sort[dir]=desc" \
  -H "Accept: application/json"
```

## Download CSV com Filtros

```bash
curl "https://app.example.com/bi-apis/orders/widgets/revenue-by-month/csv\
?filters[created_at][start]=2024-01-01\
&filters[created_at][end]=2024-12-31\
&sort[col]=total_amount\
&sort[dir]=desc" \
  --output revenue-by-month.csv
```

## Próximos Passos

[← Endpoints](01-endpoints.md) | [→ Formato de Resposta](03-resposta.md)
