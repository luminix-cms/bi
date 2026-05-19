# Formato de Requisição

Todas as requisições aos endpoints de dados do Luminix BI são do tipo `GET`. Os parâmetros de filtragem e ordenação são passados como query string. Esta página descreve como estruturar cada parâmetro para cada tipo de filtro disponível.

## O Parâmetro `filters`

O parâmetro `filters` é um array associativo onde cada chave corresponde ao `key` do filtro registrado no dashboard. O formato do valor depende do tipo do filtro.

### StringFilter

O `StringFilter` espera um array de strings. Ele aplica um `WHERE IN` com os valores recebidos.

```
filters[status][]=confirmado&filters[status][]=pendente
```

Isso gera internamente:

```sql
WHERE status IN ('confirmado', 'pendente')
```

### NumberFilter

O `NumberFilter` espera um objeto com `operator` e `values`. Os operadores suportados são `=`, `!=`, `>`, `>=`, `<`, `<=` e `between`.

Para um único valor:

```
filters[valor][operator]=>=&filters[valor][values][]=100
```

Para um intervalo (`between`):

```
filters[valor][operator]=between&filters[valor][values][]=50&filters[valor][values][]=200
```

Isso gera internamente:

```sql
WHERE valor >= 100
-- ou
WHERE valor BETWEEN 50 AND 200
```

### DateFilter

O `DateFilter` espera um array com uma única data no formato `Y-m-d`. Ele aplica um `WHERE BETWEEN` cobrindo o dia inteiro (das `00:00:00` às `23:59:59`).

```
filters[data][]=2024-03-15
```

Isso gera internamente:

```sql
WHERE data BETWEEN '2024-03-15 00:00:00' AND '2024-03-15 23:59:59'
```

### DateIntervalFilter

O `DateIntervalFilter` espera um objeto com as chaves `start` e `end`, ambas no formato `Y-m-d`.

```
filters[created_at][start]=2024-01-01&filters[created_at][end]=2024-12-31
```

Isso gera internamente:

```sql
WHERE created_at BETWEEN '2024-01-01' AND '2024-12-31'
```

### RelationFilter

O `RelationFilter` espera um array de IDs (chaves primárias do modelo relacionado). Ele aplica um `WHERE HAS` na relação.

```
filters[vendedor][]=1&filters[vendedor][]=3
```

Isso gera internamente:

```sql
WHERE EXISTS (
    SELECT 1 FROM vendedores
    WHERE pedidos.vendedor_id = vendedores.id
    AND vendedores.id IN (1, 3)
)
```

## O Parâmetro `sort` para Table

O widget `Table` suporta ordenação dinâmica via o parâmetro `sort`. Ele aceita um objeto com `col` (a chave da métrica ou dimensão) e `dir` (`asc` ou `desc`).

```
sort[col]=total&sort[dir]=desc
```

```
sort[col]=nome&sort[dir]=asc
```

> Somente o widget `Table` processa o parâmetro `sort`. Os demais widgets ignoram esse parâmetro.

## Filtros Não Enviados

Se um filtro registrado no dashboard não for enviado na requisição, ele é simplesmente ignorado — a query roda sem aquela condição. Ausência do parâmetro não resulta em erro.

```
-- Apenas o filtro de data é enviado; o filtro de status é ignorado
GET /bi-apis/vendas/widgets/receita?filters[created_at][start]=2024-01-01&filters[created_at][end]=2024-03-31
```

## Enviando Como JSON

Para enviar os filtros como JSON no corpo da requisição, defina o header `Content-Type: application/json`. O Laravel decodifica automaticamente o JSON e o disponibiliza em `request->input()`.

```bash
curl -X GET https://seuapp.com/bi-apis/vendas/widgets/receita-mensal \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -H "X-XSRF-TOKEN: {csrf_token}" \
     -d '{
           "filters": {
               "created_at": {
                   "start": "2024-01-01",
                   "end":   "2024-12-31"
               },
               "status": ["confirmado", "pendente"]
           }
         }'
```

## Exemplos de Requisição Completa com Múltiplos Filtros

### Filtro de intervalo de datas e status

```
GET /bi-apis/vendas/widgets/pedidos-por-mes?filters[created_at][start]=2024-01-01&filters[created_at][end]=2024-06-30&filters[status][]=confirmado&filters[status][]=processando
```

### Filtro de data, valor mínimo e vendedor, com ordenação

```
GET /bi-apis/vendas/widgets/ranking-vendedores?filters[created_at][start]=2024-01-01&filters[created_at][end]=2024-12-31&filters[valor][operator]=>=&filters[valor][values][]=500&filters[vendedor][]=2&filters[vendedor][]=5&sort[col]=total&sort[dir]=desc
```

### Download CSV com filtros

```
GET /bi-apis/vendas/widgets/ranking-vendedores/csv?filters[created_at][start]=2024-01-01&filters[created_at][end]=2024-12-31&sort[col]=total&sort[dir]=desc
```

## Próximos Passos

[← Endpoints Disponíveis](01-endpoints.md) | [→ Formato de Resposta](03-resposta.md)
