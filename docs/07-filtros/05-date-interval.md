# DateIntervalFilter

`DateIntervalFilter` filtra registros por um intervalo de datas com início e fim. É o filtro indicado para relatórios de período — semana, mês, trimestre, ano ou qualquer janela de tempo com dois limites distintos.

## Exemplo

```php
use Luminix\Bi\Filters\DateIntervalFilter;
use Carbon\Carbon;

public function filters(): array
{
    return [
        DateIntervalFilter::create('period', 'Period')
            ->column('created_at')
            ->defaultDates(
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ),
    ];
}
```

## Formato de Envio no Request

```
?filters[period][start]=2024-01-01&filters[period][end]=2024-03-31
```

O formato recomendado para as datas é `Y-m-d`. Isso gera:

```sql
WHERE `created_at` BETWEEN '2024-01-01 00:00:00' AND '2024-03-31 00:00:00'
```

## O Método `->defaultDates()`

Define os valores padrão de início e fim com instâncias Carbon:

```php
DateIntervalFilter::create('period', 'Period')
    ->column('created_at')
    ->defaultDates(
        Carbon::now()->startOfMonth(),
        Carbon::now()->endOfMonth()
    ),
```

O frontend recebe o `defaultValue` no schema do filtro:

```json
{
    "key": "period",
    "component": "date-interval",
    "defaultValue": {
        "start": "2024-03-01",
        "end": "2024-03-31"
    }
}
```

Assim como nos demais filtros, o valor padrão não é aplicado automaticamente no backend — ele só tem efeito quando o frontend o envia na request.

## Integração com LineChart

O widget `LineChart` usa o `DateIntervalFilter` declarado no dashboard para interpolar datas sem registros (inserindo valor zero nos dias em que não houve dados). Quando um `DateIntervalFilter` está presente na request, o `LineChart` usa `start` e `end` como limites do eixo X. Por isso, dashboards com `LineChart` se beneficiam de ter um `DateIntervalFilter` declarado.

## Próximos Passos

← [DateFilter](04-date.md) | → [RelationFilter](06-relation.md)
