# Exportação CSV

Todo widget do Luminix BI pode ser exportado como arquivo CSV sem nenhuma configuração adicional. O endpoint de exportação está disponível automaticamente assim que o widget é declarado no dashboard — é uma funcionalidade embutida, não um recurso opcional.

---

## Endpoint

```
GET /bi-apis/{dashboard}/widgets/{widget}/csv
```

Onde:

- `{dashboard}` é o `$uriKey` do dashboard (ex: `financeiro`)
- `{widget}` é a chave do widget (ex: `receita-mensal`)

Exemplo de URL completa:

```
GET /bi-apis/financeiro/widgets/receita-mensal/csv
```

---

## Disponível em Todos os Widgets Automaticamente

O endpoint `/csv` é registrado pelo `BiServiceProvider` para todos os widgets, sem exceção. Você não precisa adicionar nenhum método ao widget, nem declarar nada no dashboard. Se o widget existe, o endpoint existe.

O mesmo middleware configurado para as demais rotas da API (`config/bi.php` → `middleware`) também protege o endpoint de exportação.

---

## Aceita os Mesmos Filtros

A exportação CSV usa exatamente o mesmo método `data()` do widget, com os mesmos filtros aplicados. Isso significa que você pode exportar apenas o subconjunto de dados que está sendo visualizado:

```
GET /bi-apis/financeiro/widgets/receita-mensal/csv
    ?filters[created_at][start]=2024-01-01
    &filters[created_at][end]=2024-03-31
```

O arquivo gerado conterá apenas os dados do primeiro trimestre de 2024 — o mesmo recorte que o front-end estaria exibindo.

---

## Formato do Arquivo CSV

O arquivo segue o formato CSV padrão:

- **Primeira linha:** cabeçalho com os nomes das chaves do objeto (os `$key` das dimensões e métricas)
- **Linhas seguintes:** valores de cada registro na mesma ordem do cabeçalho
- **Separador:** vírgula (padrão da função `fputcsv` do PHP)
- **Codificação:** UTF-8

Exemplo de saída para um widget `Table` com `MonthDimension`, `CountMetric` e `SumMetric`:

```
mes,pedidos,receita
2024-01,312,48500.00
2024-02,0,0
2024-03,287,41200.00
```

---

## Nome do Arquivo

O nome do arquivo segue o padrão `{slug-do-widget}.csv`, onde o slug é gerado a partir do `$name` do widget com `Str::slug()`.

| `$name` do widget | Nome do arquivo gerado |
|------------------|----------------------|
| `"Receita Mensal"` | `receita-mensal.csv` |
| `"Pedidos por Categoria"` | `pedidos-por-categoria.csv` |
| `"Top 10 Clientes"` | `top-10-clientes.csv` |

O cabeçalho HTTP enviado é:

```
Content-Disposition: attachment; filename=receita-mensal.csv
```

---

## Implementação com `response()->stream()`

O endpoint usa `response()->stream()` do Laravel, que envia o conteúdo em partes conforme é gerado, sem carregar todos os registros na memória de uma vez. Isso torna a exportação adequada para grandes volumes de dados.

Internamente, o stream:

1. Abre um buffer de saída (`php://output`)
2. Escreve o cabeçalho (primeira linha) com as chaves do primeiro objeto
3. Itera sobre todos os registros e escreve cada um como linha CSV
4. Fecha o buffer

```php
// Fluxo simplificado da implementação no WidgetController:
return response()->stream(function () use ($data) {
    $file = fopen('php://output', 'w');

    fputcsv($file, array_keys(get_object_vars($data[0])));  // cabeçalho

    foreach ($data as $row) {
        fputcsv($file, get_object_vars($row));              // dados
    }

    fclose($file);
}, 200, $headers);
```

---

## Exemplo de Uso com curl

Para baixar o CSV diretamente do terminal, incluindo autenticação e filtros:

```bash
curl -X GET \
  "https://sua-app.com/bi-apis/financeiro/widgets/receita-mensal/csv?filters[created_at][start]=2024-01-01&filters[created_at][end]=2024-12-31" \
  -H "Accept: text/csv" \
  -H "Cookie: laravel_session=SEU_TOKEN_DE_SESSAO" \
  -o receita-mensal.csv
```

---

## Exemplo de Link de Download no Front-end

Para gerar um link de download que respeita os filtros ativos na interface:

```javascript
// Exemplo em JavaScript/Vue — construir a URL de exportação com os filtros ativos
function gerarUrlCsv(dashboardKey, widgetKey, filtros) {
    const params = new URLSearchParams();

    Object.entries(filtros).forEach(([chave, valor]) => {
        if (typeof valor === 'object') {
            Object.entries(valor).forEach(([subChave, subValor]) => {
                params.append(`filters[${chave}][${subChave}]`, subValor);
            });
        } else {
            params.append(`filters[${chave}]`, valor);
        }
    });

    return `/bi-apis/${dashboardKey}/widgets/${widgetKey}/csv?${params.toString()}`;
}

// Uso:
const url = gerarUrlCsv('financeiro', 'receita-mensal', {
    created_at: { start: '2024-01-01', end: '2024-12-31' }
});
// Resultado: /bi-apis/financeiro/widgets/receita-mensal/csv?filters[created_at][start]=2024-01-01&...

// Para acionar o download, basta navegar para a URL:
window.location.href = url;
// ou abrir em nova aba:
window.open(url, '_blank');
```

---

## Limitação: Valores Já Processados pelo `display()`

Os valores no CSV são os mesmos retornados pelo endpoint de dados — ou seja, já passaram pelo método `display()` de cada atributo. Isso tem implicações práticas:

- **`asPercentage()`** — o valor será a string `"61.71%"`, não o número `61.71`
- **`DateDimension`** — datas são formatadas (`"2024-03"`, não um timestamp)
- **`BelongsToDimension`** — o valor pode ser um objeto JSON serializado como string

Se o CSV precisar de valores brutos para importação em outras ferramentas, evite usar `asPercentage()` nos widgets que serão exportados, ou crie um widget separado sem essa formatação.

---

## Próximos Passos

- [← PartitionPie](05-partition-pie.md) | [→ Visão Geral das Métricas](../05-metricas/01-visao-geral.md)
