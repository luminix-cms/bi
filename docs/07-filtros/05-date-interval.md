# DateIntervalFilter

`DateIntervalFilter` é o filtro de intervalo de datas. Pense nele como um seletor de calendário com dois campos — data de início e data de fim — onde o usuário define um período de análise. Por exemplo: "mostrar os pedidos de janeiro a dezembro de 2024". No SQL, isso gera um `WHERE ... BETWEEN start AND end` que cobre todo o intervalo selecionado.

## SQL Gerado

`DateIntervalFilter` usa `whereBetween` com os limites de início e fim do intervalo:

```sql
WHERE `created_at` BETWEEN '2024-01-01 00:00:00' AND '2024-12-31 23:59:59'
```

O Carbon converte as strings de data recebidas na request para objetos `Carbon` com `Carbon::parse()`, preservando o horário se informado ou usando a meia-noite do dia informado.

## Formato do `$filterData`

O `$filterData` que chega ao método `apply()` é um objeto JSON com os campos `start` e `end`:

```json
{ "start": "2024-01-01", "end": "2024-12-31" }
```

Ambos os campos são strings de data que o Carbon converte com `Carbon::parse()`. O Carbon aceita formatos flexíveis, mas o padrão recomendado — e que o método `->defaultDates()` usa — é `Y-m-d`.

## Uso do `Carbon::parse()`

O `DateIntervalFilter` usa `Carbon::parse()` ao invés de `Carbon::createFromFormat()`. Isso oferece flexibilidade: `Carbon::parse()` aceita datas como `'2024-01-01'`, `'2024-01-01 00:00:00'`, e outros formatos reconhecíveis pelo PHP.

```php
public function apply(Builder $builder, array $filterData, BiRequest $request): Builder
{
    $start = Carbon::parse($filterData['start']);
    $end   = Carbon::parse($filterData['end']);
    return $builder->whereBetween($this->column, [$start, $end]);
}
```

Se o front-end enviar apenas `'2024-01-01'` sem horário, o Carbon interpreta como `2024-01-01 00:00:00`. Para cobrir o dia inteiro no limite final, considere enviar `'2024-12-31 23:59:59'` ou usar `$end->endOfDay()` em um filtro customizado, dependendo da granularidade necessária.

## O Método `->defaultDates()`

`DateIntervalFilter` oferece um método auxiliar para definir os valores padrão com instâncias Carbon:

```php
public function defaultDates(Carbon $startDate, Carbon $endDate): static
{
    $this->defaultValue([
        'start' => $startDate->format('Y-m-d'),
        'end'   => $endDate->format('Y-m-d'),
    ]);
    return $this;
}
```

O método formata ambas as datas no padrão `Y-m-d` e as organiza no formato esperado. Sem esse método, seria necessário chamar `->defaultValue(['start' => '2024-01-01', 'end' => '2024-12-31'])` manualmente.

## Integração com o `LineChart`

O `DateIntervalFilter` tem integração especial com o widget `LineChart`. O `LineChart` precisa conhecer o período completo do gráfico para interpolar as datas ausentes — ou seja, para inserir valor zero nas datas em que não houve registros, mantendo a continuidade da linha.

O `LineChart` lê o `DateIntervalFilter` declarado no dashboard para determinar os limites do eixo X. Se houver um `DateIntervalFilter` com o filtro de período na request, o `LineChart` usa os valores de `start` e `end` como início e fim da interpolação.

Isso significa que, em dashboards com `LineChart`, é recomendável declarar um `DateIntervalFilter` para que o gráfico possa preencher corretamente os dias sem dados.

## Casos de Uso

- Filtro de período para relatórios mensais ou anuais
- Filtro de janela de tempo para análise de tendências
- Filtro por data de entrega ou data de vencimento com intervalo
- Filtro de período de faturamento

## Exemplo com `->defaultDates()`

Um filtro que inicia com o período do mês atual como padrão:

```php
use Luminix\Bi\Filters\DateIntervalFilter;
use Carbon\Carbon;

public function filters(): array
{
    return [
        DateIntervalFilter::create('periodo', 'Período')
            ->column('created_at')
            ->defaultDates(
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ),
    ];
}
```

O front-end receberá no schema do filtro:

```json
{
    "key": "periodo",
    "name": "Período",
    "component": "date-interval",
    "defaultValue": {
        "start": "2024-03-01",
        "end": "2024-03-31"
    }
}
```

Se o front-end enviar esse `defaultValue` na request, o builder receberá:

```sql
WHERE `created_at` BETWEEN '2024-03-01 00:00:00' AND '2024-03-31 00:00:00'
```

## Exemplo com Data de Entrega

Um filtro de intervalo aplicado a uma coluna diferente do `$key`:

```php
use Luminix\Bi\Filters\DateIntervalFilter;
use Carbon\Carbon;

public function filters(): array
{
    return [
        DateIntervalFilter::create('entrega', 'Prazo de Entrega')
            ->column('data_entrega')
            ->defaultDates(
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ),
    ];
}
```

Quando o front-end enviar:

```json
{ "filters": { "entrega": { "start": "2024-03-01", "end": "2024-03-31" } } }
```

O builder aplicará:

```sql
WHERE `data_entrega` BETWEEN '2024-03-01 00:00:00' AND '2024-03-31 00:00:00'
```

## Próximos Passos

← [DateFilter](04-date.md) | → [RelationFilter](06-relation.md)
