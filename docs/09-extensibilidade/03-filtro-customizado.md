# Filtros Customizados

O pacote inclui `StringFilter`, `NumberFilter`, `DateFilter`, `DateIntervalFilter` e `RelationFilter`. Quando nenhum deles cobre a regra de filtragem necessária — por exemplo, um toggle booleano, um slider de faixa ou uma condição que envolve múltiplas colunas — crie seu próprio filtro estendendo `BaseFilter`.

## Contrato

```php
namespace Luminix\Bi\Filters;

abstract class BaseFilter
{
    public $key;
    public $name;
    public $column;
    public $defaultValue;

    // Obrigatório: aplica a condição WHERE ao builder
    abstract public function apply(Builder $builder, array $filterData, BiRequest $request): Builder;

    // Opcional: retorna metadados para o front-end
    public function extra(Dashboard $dashboard, BiRequest $request): array
    {
        return [];
    }
}
```

### `apply()` — Obrigatório

Recebe o builder Eloquent, o valor enviado em `filters[key]` e o request completo. Deve aplicar a condição `WHERE` e retornar o builder.

O método só é chamado quando o front-end envia o parâmetro `filters[key]`. Se o filtro não for enviado, a query roda sem aquela condição — não há necessidade de verificar se `$filterData` está presente.

### `extra()` — Opcional

Sobrescreva para enviar dados ao front-end, como listas de opções ou configurações de controle. O retorno aparece no campo `extra` da resposta de `GET /{path}-apis/{dashboard}/filters/{filter}`.

## Exemplo Completo: `ActiveFilter`

Um filtro booleano que filtra registros por uma coluna `active`. O front-end envia `"true"` ou `"false"` como string:

```php
<?php

namespace App\Bi\Filters;

use Illuminate\Database\Eloquent\Builder;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Filters\BaseFilter;
use Luminix\Bi\Support\BiRequest;

class ActiveFilter extends BaseFilter
{
    public $component = 'boolean-toggle';

    public function apply(Builder $builder, array $filterData, BiRequest $request): Builder
    {
        $value = filter_var($filterData, FILTER_VALIDATE_BOOLEAN);

        return $builder->where($this->column, $value);
    }

    public function extra(Dashboard $dashboard, BiRequest $request): array
    {
        return [
            'label_true'  => 'Active',
            'label_false' => 'Inactive',
        ];
    }
}
```

### Registrando no Dashboard

```php
use App\Bi\Filters\ActiveFilter;

public function filters(): array
{
    return [
        ActiveFilter::create('active', 'Status')
            ->column('active')
            ->defaultValue('true'),
    ];
}
```

### Enviando o Filtro na Requisição

```
GET /bi-apis/customers/widgets/active-customers?filters[active]=true
```

## Exemplo com `extra()`: `ScoreRangeFilter`

Um filtro de faixa numérica que consulta os limites reais do banco para o front-end montar um slider:

```php
<?php

namespace App\Bi\Filters;

use Illuminate\Database\Eloquent\Builder;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Filters\BaseFilter;
use Luminix\Bi\Support\BiRequest;

class ScoreRangeFilter extends BaseFilter
{
    public $component = 'range-slider';

    public function apply(Builder $builder, array $filterData, BiRequest $request): Builder
    {
        if (isset($filterData['min'], $filterData['max'])) {
            $builder->whereBetween($this->column, [
                (int) $filterData['min'],
                (int) $filterData['max'],
            ]);
        }

        return $builder;
    }

    public function extra(Dashboard $dashboard, BiRequest $request): array
    {
        $model = new $dashboard->model();

        return [
            'min'  => (int) $model::query()->min($this->column),
            'max'  => (int) $model::query()->max($this->column),
            'step' => 5,
        ];
    }
}
```

Resposta de `GET /bi-apis/customers/filters/score`:

```json
{
    "status": 200,
    "extra": {
        "min": 0,
        "max": 100,
        "step": 5
    }
}
```

> A propriedade `$component` não é obrigatória pelo contrato de `BaseFilter`, mas é serializada no JSON de configuração do dashboard. Defina-a sempre para que o front-end saiba qual controle renderizar.

## Próximos Passos

[← Dimensões Customizadas](02-dimensao-customizada.md) | [→ Widgets Customizados](04-widget-customizado.md)
