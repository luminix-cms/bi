# DateFilter

`DateFilter` filtra registros por uma única data. O usuário seleciona um dia específico e o filtro cobre o intervalo completo desse dia — do primeiro ao último segundo — para incluir todos os registros independentemente do horário armazenado.

## Exemplo

```php
use Luminix\Bi\Filters\DateFilter;
use Carbon\Carbon;

public function filters(): array
{
    return [
        DateFilter::create('date', 'Order Date')
            ->column('created_at')
            ->defaultDate(Carbon::today()),
    ];
}
```

## Formato de Envio no Request

```
?filters[date][]=2024-03-15
```

O valor deve estar no formato `Y-m-d`. Isso gera:

```sql
WHERE `created_at` BETWEEN '2024-03-15 00:00:00' AND '2024-03-15 23:59:59'
```

O `BETWEEN` é necessário porque colunas `DATETIME` armazenam data e hora juntas — uma comparação com `=` excluiria qualquer registro criado fora da meia-noite exata.

## O Método `->defaultDate()`

Define o valor padrão com uma instância Carbon:

```php
DateFilter::create('date', 'Order Date')
    ->column('created_at')
    ->defaultDate(Carbon::today()),
```

O frontend recebe o `defaultValue` no schema do filtro e pode pré-selecionar a data na interface. O valor padrão não é aplicado automaticamente no backend — ele só tem efeito quando o frontend o envia na request.

## Quando Usar DateFilter vs DateIntervalFilter

| Situação | Filtro correto |
|---|---|
| Relatório de um dia específico | `DateFilter` |
| Relatório de um período (semana, mês, trimestre) | [`DateIntervalFilter`](05-date-interval.md) |

## Próximos Passos

← [NumberFilter](03-number.md) | → [DateIntervalFilter](05-date-interval.md)
