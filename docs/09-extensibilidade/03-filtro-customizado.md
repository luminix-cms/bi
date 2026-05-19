# Criando Filtros Customizados

Os filtros controlam quais registros chegam à query de cada widget. O pacote oferece cinco filtros prontos: `StringFilter`, `NumberFilter`, `DateFilter`, `DateIntervalFilter` e `RelationFilter`. Quando nenhum deles cobre a regra de filtragem necessária — por exemplo, um toggle booleano, um filtro por proximidade geográfica ou uma lógica que combina múltiplas colunas — você cria seu próprio filtro estendendo `BaseFilter`.

## Quando Criar um Filtro Customizado

Crie um filtro customizado quando:

- A condição `WHERE` não pode ser expressa com os operadores dos filtros existentes
- O front-end precisa de um controle com formato de dado específico (toggle, slider de range, seletor de região)
- O filtro precisa de metadados extras enviados ao front-end para montar o controle (opções, configurações, valores pré-carregados)
- A lógica de filtragem envolve múltiplas colunas ou subconsultas

## Estendendo `BaseFilter`

`BaseFilter` é a classe abstrata base para todos os filtros. Ela fornece:

- `key` — identificador único do filtro (usado no parâmetro `filters[key]`)
- `name` — rótulo legível
- `column` — coluna física do banco (por padrão igual à `key`)
- `defaultValue` — valor padrão enviado ao front-end
- `column(string $column): static` — fluent setter para a coluna
- `defaultValue($value): static` — fluent setter para o valor padrão
- `create(string $key, string $name): static` — fábrica estática

```php
abstract class BaseFilter
{
    public $key;
    public $name;
    public $column;
    public $defaultValue;

    abstract public function apply(Builder $builder, array $filterData, BiRequest $request);

    public function extra(Dashboard $dashboard, BiRequest $request): array
    {
        return [];
    }
}
```

## Métodos para Implementar

### `apply()` — Obrigatório

```php
public function apply(Builder $builder, array $filterData, BiRequest $request): Builder
```

Este é o único método obrigatório. Ele recebe:

- `$builder` — o `Builder` Eloquent da query do widget
- `$filterData` — o que o front-end enviou em `filters[key]` (o formato é o que você definir)
- `$request` — o `BiRequest` completo, para acessar outros parâmetros da requisição se necessário

O método deve aplicar a condição `WHERE` ao builder e retorná-lo.

> O `$filterData` só chega ao `apply()` se o front-end enviou o parâmetro `filters[key]`. Se o filtro não for enviado, o método não é chamado e a query roda sem aquela condição. Você não precisa checar se `$filterData` existe.

### `extra()` — Opcional

```php
public function extra(Dashboard $dashboard, BiRequest $request): array
```

Sobrescreva `extra()` para enviar dados do back-end para o front-end, como listas de opções, configurações do controle ou valores pré-calculados. O retorno desse método vai diretamente no campo `extra` da resposta do endpoint `GET /bi-apis/{dashboard}/filters/{filter}`.

## Exemplo Completo: `BooleanFilter`

Um `BooleanFilter` filtra registros por uma coluna booleana. O front-end envia `"true"` ou `"false"` como string (formato comum de checkboxes e toggles HTML), e o filtro converte para o tipo correto antes de aplicar ao builder.

```php
<?php

namespace App\Bi\Filters;

use Luminix\Bi\Dashboard;
use Luminix\Bi\Filters\BaseFilter;
use Luminix\Bi\Support\BiRequest;
use Illuminate\Database\Eloquent\Builder;

class BooleanFilter extends BaseFilter
{
    public $component = 'boolean-toggle';

    public function apply(Builder $builder, array $filterData, BiRequest $request): Builder
    {
        // $filterData é o que chega de filters[key]
        // O front-end envia "true" ou "false" como string
        $value = filter_var($filterData, FILTER_VALIDATE_BOOLEAN);

        return $builder->where($this->column, $value);
    }

    public function extra(Dashboard $dashboard, BiRequest $request): array
    {
        return [
            'label_true'  => 'Ativo',
            'label_false' => 'Inativo',
        ];
    }
}
```

### Como o Front-end Envia o Valor

O front-end enviará o filtro como parte do parâmetro `filters`:

```
GET /bi-apis/clientes/widgets/clientes-ativos?filters[ativo]=true
```

Dentro do `apply()`, `$filterData` será a string `"true"`. O `filter_var` com `FILTER_VALIDATE_BOOLEAN` converte para o booleano PHP `true`.

### Registrando no Dashboard

```php
use App\Bi\Filters\BooleanFilter;
use Luminix\Bi\Widgets\Table;
use Luminix\Bi\Metrics\CountMetric;

// No dashboard:
public function filters(): array
{
    return [
        BooleanFilter::create('ativo', 'Apenas Ativos')
            ->column('ativo')
            ->defaultValue('true'),
    ];
}
```

## Exemplo de `extra()` Customizado

Imagine um filtro de faixa de score que precisa enviar os limites mínimo e máximo existentes no banco para o front-end montar um slider:

```php
<?php

namespace App\Bi\Filters;

use Luminix\Bi\Dashboard;
use Luminix\Bi\Filters\BaseFilter;
use Luminix\Bi\Support\BiRequest;
use Illuminate\Database\Eloquent\Builder;

class ScoreRangeFilter extends BaseFilter
{
    public $component = 'range-slider';

    public function apply(Builder $builder, array $filterData, BiRequest $request): Builder
    {
        // $filterData esperado: ['min' => 10, 'max' => 90]
        if (isset($filterData['min']) && isset($filterData['max'])) {
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

A resposta do endpoint de filtro ficará assim:

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

O front-end consulta esse endpoint ao carregar o dashboard e usa os valores para configurar os limites do slider.

## A Propriedade `$component`

Embora não seja obrigatória pelo contrato de `BaseFilter`, a propriedade `$component` é serializada junto com o filtro no endpoint de listagem do dashboard. O front-end usa essa string para determinar qual componente de interface renderizar. Defina sempre que criar um filtro customizado para garantir que o front-end saiba como exibi-lo.

## Próximos Passos

[← Criando Dimensões Customizadas](02-dimensao-customizada.md) | [→ Criando Widgets Customizados](04-widget-customizado.md)
