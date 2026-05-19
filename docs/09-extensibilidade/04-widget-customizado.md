# Criando Widgets Customizados

Os quatro widgets incluídos no pacote — `BigNumber`, `LineChart`, `PartitionPie` e `Table` — cobrem os padrões mais comuns de visualização analítica. Quando a visualização desejada é radicalmente diferente — um funil de conversão, um mapa de calor, uma matrix de cohort, um gauge — você cria seu próprio widget estendendo `BaseWidget`.

## Quando Criar um Widget Customizado

Crie um widget customizado quando:

- A estrutura de dados retornada pelo widget não é uma lista plana de objetos com métricas e dimensões
- O método `data()` precisa executar múltiplas queries e combinar os resultados
- O front-end precisa de metadados específicos no JSON do widget (etapas de funil, configurações de escala, paleta de cores calculada dinamicamente)
- A lógica de transformação de dados antes de retornar ao front-end é complexa o suficiente para merecer sua própria classe

## Estendendo `BaseWidget`

`BaseWidget` implementa a interface `Widget` e o contrato `JsonSerializable`. Ao herdá-la, você recebe:

| Recurso | Descrição |
|---|---|
| `scope(Closure $scope)` | Restringe a query base com uma closure |
| `width($width)` | Define a largura do widget no grid |
| `dimension()` / `dimensions()` | Fluent setters para dimensões (via trait `HasAttributes`) |
| `metric()` / `metrics()` | Fluent setters para métricas (via trait `HasAttributes`) |
| `getBaseBuilder(Dashboard $dashboard)` | Cria o `Builder` base a partir do model do dashboard |
| `applyAttributes(Builder $builder)` | Aplica todos as métricas e dimensões registradas ao builder |
| `applyFilters(Builder $builder, Dashboard $dashboard, BiRequest $request)` | Aplica os filtros enviados na requisição |
| `displayModel($model, $rawModels)` | Converte um model Eloquent em `stdClass` usando o `display()` de cada atributo |
| `create(string $key, string $name)` | Fábrica estática |

## Campos e Métodos Obrigatórios

### `$component` — Identificador do Componente Front-end

```php
protected $component = 'meu-widget';
```

Esta propriedade é serializada no JSON do widget como `"component": "meu-widget"`. O front-end usa essa string para determinar qual componente Vue/React renderizar. Defina sempre.

### `data()` — Retorna os Dados do Widget

```php
public function data(Dashboard $dashboard, BiRequest $request)
```

Este é o método que o controller chama ao receber uma requisição para `GET /bi-apis/{dashboard}/widgets/{widget}`. Ele deve retornar uma `Collection` ou um `array`. O retorno será serializado diretamente como o campo `data` da resposta JSON.

### `extra()` — Metadados Adicionais (Opcional)

```php
protected function extra(): array
{
    return [
        'minha_configuracao' => $this->minhaConfiguracao,
    ];
}
```

O retorno de `extra()` é incluído no campo `extra` da serialização JSON do widget (disponível no endpoint de listagem do dashboard). Use para enviar configurações que o componente front-end precisa para se configurar — cores, rótulos, limites de escala.

> Na implementação padrão de `BaseWidget`, `extra()` retorna `['uniqid' => uniqid()]`. Ao sobrescrever, você substitui esse comportamento completamente.

## Exemplo Completo: `FunnelChart`

Um funil de conversão exibe etapas sequenciais onde cada etapa tem um volume de registros e um percentual de conversão em relação à etapa anterior.

### A Classe

```php
<?php

namespace App\Bi\Widgets;

use Illuminate\Support\Collection;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Support\BiRequest;
use Luminix\Bi\Widgets\BaseWidget;

class FunnelChart extends BaseWidget
{
    protected $component = 'funnel-chart';

    /**
     * Cada etapa é definida como ['label' => '...', 'scope' => fn($builder) => $builder].
     */
    private array $steps = [];

    public function step(string $label, \Closure $scope): static
    {
        $this->steps[] = ['label' => $label, 'scope' => $scope];

        return $this;
    }

    public function data(Dashboard $dashboard, BiRequest $request): array
    {
        $result    = [];
        $previous  = null;

        foreach ($this->steps as $step) {
            // Parte do builder base do dashboard
            $builder = $this->getBaseBuilder($dashboard);

            // Aplica os filtros da requisição
            $builder = $this->applyFilters($builder, $dashboard, $request);

            // Aplica o escopo desta etapa
            $builder = ($step['scope'])($builder);

            $count = $builder->count();

            $result[] = [
                'label'      => $step['label'],
                'count'      => $count,
                'conversion' => $previous > 0
                    ? round($count / $previous * 100, 2)
                    : 100.0,
            ];

            $previous = $count;
        }

        return $result;
    }

    protected function extra(): array
    {
        return [
            'steps' => array_column($this->steps, 'label'),
        ];
    }
}
```

### Usando no Dashboard

```php
use App\Bi\Widgets\FunnelChart;
use Luminix\Bi\Filters\DateIntervalFilter;
use Carbon\Carbon;

// No método widgets() do dashboard:
FunnelChart::create('funil-checkout', 'Funil de Checkout')
    ->step('Visitaram o carrinho', function ($builder) {
        return $builder->where('evento', 'carrinho');
    })
    ->step('Iniciaram o checkout', function ($builder) {
        return $builder->where('evento', 'checkout_inicio');
    })
    ->step('Concluíram a compra', function ($builder) {
        return $builder->where('evento', 'compra_concluida');
    })
    ->width(12);
```

### Resposta JSON Gerada

```json
{
    "status": 200,
    "data": [
        { "label": "Visitaram o carrinho",    "count": 1200, "conversion": 100.0 },
        { "label": "Iniciaram o checkout",    "count":  480, "conversion":  40.0 },
        { "label": "Concluíram a compra",     "count":  192, "conversion":  40.0 }
    ]
}
```

E a serialização do widget no endpoint de listagem:

```json
{
    "key":        "funil-checkout",
    "name":       "Funil de Checkout",
    "component":  "funnel-chart",
    "width":      12,
    "metrics":    [],
    "dimensions": [],
    "extra": {
        "steps": [
            "Visitaram o carrinho",
            "Iniciaram o checkout",
            "Concluíram a compra"
        ]
    }
}
```

## Usando Métricas e Dimensões em um Widget Customizado

O `HasAttributes` já está incluído em `BaseWidget`. Você pode usar `dimension()`, `dimensions()`, `metric()` e `metrics()` normalmente no widget customizado, e chamar `applyAttributes()` dentro do `data()` para que o builder receba os `addSelect()` correspondentes:

```php
public function data(Dashboard $dashboard, BiRequest $request)
{
    $builder = $this->getBaseBuilder($dashboard);
    $builder = $this->applyAttributes($builder); // aplica métricas e dimensões
    $builder = $this->applyFilters($builder, $dashboard, $request);

    $rawModels = $builder->get();

    // Transformações customizadas aqui...

    return $rawModels->map(function ($model) use ($rawModels) {
        return $this->displayModel($model, $rawModels->toArray());
    });
}
```

> Se o seu widget customizado não usa métricas nem dimensões (como o `FunnelChart` acima), não é necessário chamar `applyAttributes()`.

## Próximos Passos

[← Criando Filtros Customizados](03-filtro-customizado.md) | [→ Endpoints Disponíveis](../10-api/01-endpoints.md)
