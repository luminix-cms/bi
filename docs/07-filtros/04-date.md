# DateFilter

`DateFilter` é o filtro de data única. Pense nele como um seletor de calendário onde o usuário escolhe um dia específico — por exemplo, "mostrar apenas os pedidos feitos em 15 de março de 2024". Internamente, o filtro cobre o dia inteiro, do primeiro ao último segundo, para garantir que todos os registros daquele dia sejam incluídos independentemente do horário gravado.

## SQL Gerado

`DateFilter` usa `whereBetween` para cobrir o intervalo completo do dia selecionado:

```sql
WHERE `created_at` BETWEEN '2024-03-15 00:00:00' AND '2024-03-15 23:59:59'
```

## Por Que `BETWEEN` ao Invés de `=`

Colunas do tipo `DATETIME` ou `TIMESTAMP` armazenam data e hora juntas. Uma comparação direta com `=` exigiria que o valor fosse exatamente `'2024-03-15 00:00:00'`, o que excluiria todos os registros criados em qualquer outro horário do mesmo dia — por exemplo, um pedido criado às `14:32:07` não seria encontrado.

Usando `BETWEEN` com `startOfDay()` e `endOfDay()`, o filtro garante que qualquer registro com timestamp dentro do dia selecionado seja incluído, independentemente do horário:

```php
$date = Carbon::createFromFormat('Y-m-d', $filterData[0]);
return $builder->whereBetween($this->column, [
    $date->copy()->startOfDay(),  // '2024-03-15 00:00:00'
    $date->endOfDay(),            // '2024-03-15 23:59:59'
]);
```

## Formato do `$filterData`

O `$filterData` que chega ao método `apply()` é um array com um único elemento — a data no formato `Y-m-d`:

```json
["2024-03-15"]
```

O Carbon então converte esse string com `createFromFormat('Y-m-d', ...)` e expande para o intervalo completo do dia.

> O formato `Y-m-d` (ano-mês-dia, separado por hífens) é obrigatório. Outros formatos causarão uma exceção do Carbon durante a criação. O front-end é responsável por garantir esse formato antes de enviar a request.

## O Método `->defaultDate()`

`DateFilter` oferece um método auxiliar para definir o valor padrão com uma instância Carbon:

```php
public function defaultDate(Carbon $date): static
{
    $this->defaultValue([$date->format('Y-m-d')]);
    return $this;
}
```

O método formata a data no padrão `Y-m-d` e a envolve em um array, que é exatamente o formato que o back-end espera receber na request. Sem esse método, seria necessário chamar `->defaultValue(['2024-03-15'])` manualmente, o que seria mais trabalhoso ao usar datas relativas como "hoje" ou "ontem".

## Diferença em Relação ao `DateIntervalFilter`

O `DateFilter` recebe uma única data e cobre o dia inteiro dela. O `DateIntervalFilter` recebe duas datas (`start` e `end`) e cobre o intervalo completo entre elas.

| Situação | Filtro correto |
|---|---|
| Relatório de um dia específico | `DateFilter` |
| Relatório de um período (semana, mês, trimestre) | `DateIntervalFilter` |

Escolha `DateFilter` quando o usuário precisa visualizar os dados de um único dia e potencialmente navegar dia a dia. Escolha `DateIntervalFilter` quando o usuário precisa definir um período com início e fim distintos.

## Casos de Uso

- Relatório de faturamento de um dia específico
- Listagem de pedidos criados em determinada data
- Visualização de eventos ocorridos em um dia
- Busca por registros com data de aprovação específica

## Exemplo com `->defaultDate()`

Um filtro de data que inicia com "hoje" como valor padrão:

```php
use Luminix\Bi\Filters\DateFilter;
use Carbon\Carbon;

public function filters(): array
{
    return [
        DateFilter::create('data', 'Data do Pedido')
            ->column('created_at')
            ->defaultDate(Carbon::today()),
    ];
}
```

O front-end receberá no schema do filtro:

```json
{
    "key": "data",
    "name": "Data do Pedido",
    "component": "date",
    "defaultValue": ["2024-03-15"]
}
```

Se o front-end enviar esse `defaultValue` na request, o builder receberá:

```sql
WHERE `created_at` BETWEEN '2024-03-15 00:00:00' AND '2024-03-15 23:59:59'
```

Retornando apenas os registros criados no dia atual.

## Exemplo com Coluna de Data de Entrega

```php
use Luminix\Bi\Filters\DateFilter;
use Carbon\Carbon;

public function filters(): array
{
    return [
        DateFilter::create('entrega', 'Data de Entrega')
            ->column('data_entrega')
            ->defaultDate(Carbon::today()),
    ];
}
```

Quando o front-end enviar `filters[entrega][]=2024-03-20`, o builder aplicará:

```sql
WHERE `data_entrega` BETWEEN '2024-03-20 00:00:00' AND '2024-03-20 23:59:59'
```

## Próximos Passos

← [NumberFilter](03-number.md) | → [DateIntervalFilter](05-date-interval.md)
