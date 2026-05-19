# LineChart

Imagine um gráfico de receita mensal onde fevereiro simplesmente não existe — não porque não houve receita, mas porque não havia pedidos naquele mês. O gráfico pularia de janeiro para março com uma linha reta, dando a impressão errada de que os dados não existem. O `LineChart` resolve esse problema automaticamente.

---

## Propósito

O widget `LineChart` gera dados para gráficos de linha voltados a **séries temporais** — evolução diária, mensal ou anual de uma ou mais métricas. Seu diferencial é a **interpolação de datas ausentes**: períodos sem dados não são simplesmente ignorados; eles são inseridos na resposta com valores zero, garantindo que o gráfico tenha continuidade visual.

O campo `component` enviado ao front-end é `'line-chart'`.

---

## Requer uma `DateDimension`

O `LineChart` foi projetado para trabalhar com dimensões de data. Para que a interpolação funcione, você precisa usar uma das três classes de data disponíveis:

| Classe | Agrupamento | Formato de saída | Intervalo |
|--------|------------|-----------------|-----------|
| `DayDimension` | Por dia | `"2024-03-15"` | 1 dia |
| `MonthDimension` | Por mês | `"2024-03"` | 1 mês |
| `YearDimension` | Por ano | `"2024"` | 1 ano |

Todas as três estendem `DateDimension`, que é a classe que o `LineChart` verifica com `instanceof` para acionar a lógica de interpolação.

> Se a dimensão configurada **não** for uma `DateDimension`, o `LineChart` se comporta exatamente como um `BaseWidget` padrão e retorna os dados sem interpolação.

---

## Interpolação de Datas Ausentes

Este é o comportamento mais importante do `LineChart`. Quando a dimensão é uma `DateDimension`, após executar a query SQL padrão, o widget percorre todos os períodos entre a data mínima e a data máxima e insere entradas para os períodos que não retornaram dados.

### Como o período é determinado

O `LineChart` decide os extremos do período em duas situações:

**Situação 1: há um filtro de data na requisição**

Se a requisição contém um filtro com chaves `start` e `end` — típico de um `DateIntervalFilter` — esse intervalo é usado como base. Se o filtro existe mas não tem `start`/`end` (por exemplo, um `DateFilter` simples), o widget cai na situação 2.

```json
{
    "filters": {
        "created_at": { "start": "2024-01-01", "end": "2024-03-31" }
    }
}
```

Neste caso, o período vai de `2024-01-01` a `2024-03-31`, mesmo que a query não tenha retornado dados para fevereiro.

**Situação 2: não há filtro de data (ou o filtro não tem start/end)**

O `LineChart` usa `min()` e `max()` sobre os dados retornados pela query. O período começa no menor valor encontrado e termina no maior. Períodos intermediários sem dados são preenchidos.

### O que é inserido nos períodos ausentes

Para cada período sem dados, o `LineChart` cria um objeto com:

- A chave da dimensão preenchida com a data formatada no padrão do tipo de dimensão
- Cada métrica com seu valor zero (`getEmptyValue()` retorna `0`)

```php
// Para um mês sem dados, o objeto inserido seria:
[
    'mes'    => '2024-02',  // chave da dimensão
    'total'  => 0,          // cada métrica com valor zero
]
```

### Implementação com CarbonPeriod

Internamente, o `LineChart` usa `CarbonPeriod` para iterar o intervalo com o passo correto para cada tipo de dimensão (`1 day`, `1 month` ou `1 year`). Para cada data no período, verifica se os dados retornados pela query têm um registro com aquela chave. Se não tiver, insere o objeto com métricas zeradas.

---

## Por que a Interpolação Importa

Sem interpolação, um gráfico de linha com lacunas teria comportamentos indesejados dependendo da biblioteca de gráficos usada:

- Algumas bibliotecas pulam o ponto, criando um buraco na linha
- Outras interpolam visualmente, criando uma linha reta que não reflete a realidade
- A ausência de um ponto pode ser confundida com ausência de dados na série toda

Com a interpolação do `LineChart`, o front-end sempre recebe uma série completa e contígua. O zero explícito comunica que o dado existe, mas é zero — uma informação diferente de "não há dado".

---

## Exemplo: Evolução Mensal com Interpolação

```php
// app/Bi/Dashboards/FinanceiroDashboard.php

namespace App\Bi\Dashboards;

use App\Models\Pedido;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Widgets\LineChart;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Dimensions\MonthDimension;
use Luminix\Bi\Filters\DateIntervalFilter;

class FinanceiroDashboard extends Dashboard
{
    public $uriKey = 'financeiro';
    public $name   = 'Financeiro';
    public $model  = Pedido::class;

    public function widgets(): array
    {
        return [
            LineChart::create('evolucao-mensal', 'Evolução Mensal')
                ->dimension(new MonthDimension('created_at', 'Mês'))
                ->metrics([
                    new CountMetric('pedidos', 'Pedidos'),
                    new SumMetric('receita', 'Receita')->column('total'),
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            DateIntervalFilter::create('created_at', 'Período'),
        ];
    }
}
```

Suponha que a requisição envia o filtro para o primeiro trimestre de 2024:

```json
{
    "filters": {
        "created_at": { "start": "2024-01-01", "end": "2024-03-31" }
    }
}
```

A query SQL executada é padrão:

```sql
SELECT
    DATE_FORMAT(`created_at`, '%Y-%m') as `mes`,
    COUNT(*) as `pedidos`,
    SUM(`total`) as `receita`
FROM `pedidos`
WHERE `created_at` BETWEEN '2024-01-01 00:00:00' AND '2024-03-31 23:59:59'
GROUP BY DATE_FORMAT(`created_at`, '%Y-%m')
```

Suponha que a query retornou apenas dois meses (fevereiro não teve pedidos):

```
// Dados brutos retornados pela query:
[
    { "mes": "2024-01", "pedidos": 312, "receita": "48500.00" },
    { "mes": "2024-03", "pedidos": 287, "receita": "41200.00" }
]
```

Após a interpolação do `LineChart`, a resposta da API é:

```json
{
    "status": 200,
    "data": [
        { "mes": "2024-01", "pedidos": 312, "receita": "48500.00" },
        { "mes": "2024-02", "pedidos": 0,   "receita": 0          },
        { "mes": "2024-03", "pedidos": 287, "receita": "41200.00" }
    ]
}
```

Fevereiro aparece com zero explícito. O front-end recebe três pontos contíguos e pode traçar a linha corretamente.

---

## Exemplo com Granularidade Diária

Para acompanhar volumes diários, troque a `MonthDimension` por `DayDimension`:

```php
LineChart::create('pedidos-diarios', 'Pedidos por Dia')
    ->dimension(new DayDimension('created_at', 'Data'))
    ->metric(new CountMetric('pedidos', 'Pedidos'));
```

O comportamento de interpolação é idêntico, mas agora o passo é de um dia. Um intervalo de 30 dias sempre retornará exatamente 30 pontos na resposta, independentemente de quantos dias tiveram pedidos.

---

## Próximos Passos

- [← Table](03-table.md) | [→ PartitionPie](05-partition-pie.md)
