# Criando Métricas Customizadas

O Luminix BI já vem com seis métricas prontas para uso: `CountMetric`, `SumMetric`, `AverageMetric`, `CountManyMetric`, `SumManyMetric` e `RawMetric`. Na maioria dos casos, `RawMetric` é suficiente para expressões SQL incomuns — ela aceita qualquer fragmento SQL bruto e o usa como `SELECT`. No entanto, quando a lógica de exibição ou formatação do valor precisa de comportamento específico que vai além de um simples valor numérico, criar uma métrica customizada é o caminho correto.

## Quando Criar uma Métrica Customizada

Crie uma métrica customizada quando:

- A fórmula SQL envolve lógica condicional complexa que seria ilegível em `RawMetric`
- O método `display()` precisa formatar o resultado de maneira especial (moeda, percentual calculado sobre outro campo, razão entre métricas)
- A métrica precisa de estado interno — por exemplo, parâmetros que afetam o SQL gerado
- Você quer encapsular e reutilizar a mesma lógica em múltiplos dashboards com uma API fluente

## Dois Caminhos para Criar uma Métrica

### Caminho 1: Estender `BaseMetric` (Recomendado)

`BaseMetric` já implementa a interface `Metric` e herda de `Attribute`. Ao estendê-la, você recebe gratuitamente:

- `asPercentage()` — formata o valor como percentual em relação ao total da coluna
- `getEmptyValue()` — retorna `0` quando não há dados (usado pelo `LineChart` para preencher datas sem registros)
- `column(string $column)` — permite separar a chave do campo da coluna física no banco
- `color($color)` — metadado de cor para o front-end
- `create(string $key, string $name)` — fábrica estática

Você implementa apenas o método `apply()`, que recebe o `Builder` e deve adicionar um `addSelect()` com a expressão SQL.

### Caminho 2: Implementar a Interface `Metric` Diretamente

Implemente a interface `Luminix\Bi\Metrics\Metric` quando precisar de total controle sobre todos os três métodos sem nenhuma herança. Isso é útil quando a métrica não representa um número simples — por exemplo, quando o valor retornado é uma string formatada ou uma estrutura de dados que não se encaixa no comportamento padrão de `BaseMetric`.

```php
interface Metric {
    public function apply(Builder $builder, Widget $widget): Builder;
    public function display(Model $value, array $models);
    public function getEmptyValue();
}
```

## Métodos Obrigatórios

| Método | Responsabilidade |
|---|---|
| `apply(Builder $builder, Widget $widget): Builder` | Adiciona o `SELECT` ao builder via `addSelect()` |
| `display(Model $value, array $models)` | Formata o valor para inclusão no JSON de resposta |
| `getEmptyValue()` | Retorna o valor padrão quando não há dados (tipicamente `0`) |

> O método `apply()` deve usar `addSelect()` e nunca `select()`. Usar `select()` substituiria os selects das outras métricas e dimensões já registradas no builder.

## Exemplo Completo: Taxa de Conversão

Imagine um dashboard de pedidos onde você precisa exibir a taxa de conversão: a proporção de pedidos com status `confirmado` em relação ao total.

A fórmula SQL equivale a:

```sql
SUM(CASE WHEN status = 'confirmado' THEN 1 ELSE 0 END) / COUNT(*) * 100
```

### Criando a Classe

```php
<?php

namespace App\Bi\Metrics;

use DB;
use Luminix\Bi\Metrics\BaseMetric;
use Luminix\Bi\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TaxaConversaoMetric extends BaseMetric
{
    private string $statusConfirmado;

    public function __construct(string $key, string $name, string $statusConfirmado = 'confirmado')
    {
        parent::__construct($key, $name);
        $this->statusConfirmado = $statusConfirmado;
    }

    public function apply(Builder $builder, Widget $widget): Builder
    {
        $status = $this->statusConfirmado;

        return $builder->addSelect(
            DB::raw(
                "ROUND(
                    SUM(CASE WHEN {$this->column} = '{$status}' THEN 1 ELSE 0 END)
                    / NULLIF(COUNT(*), 0) * 100,
                2) as `{$this->key}`"
            )
        );
    }

    public function display(Model $value, array $models)
    {
        $raw = $value->{$this->key};

        if ($raw === null) {
            return '0,00%';
        }

        return number_format((float) $raw, 2, ',', '.') . '%';
    }

    public function getEmptyValue()
    {
        return '0,00%';
    }
}
```

O SQL gerado internamente ficará parecido com:

```sql
SELECT
    ROUND(
        SUM(CASE WHEN status = 'confirmado' THEN 1 ELSE 0 END)
        / NULLIF(COUNT(*), 0) * 100,
    2) as `taxa_conversao`
FROM pedidos
```

### Registrando no Widget

Métricas customizadas não precisam de registro extra. Basta instanciá-las diretamente ao montar o widget:

```php
use App\Bi\Metrics\TaxaConversaoMetric;
use Luminix\Bi\Widgets\BigNumber;

BigNumber::create('taxa', 'Taxa de Conversão')
    ->metric(
        TaxaConversaoMetric::create('taxa_conversao', 'Taxa de Conversão')
            ->column('status')
    );
```

Como `TaxaConversaoMetric` estende `BaseMetric`, que herda de `Attribute`, o método estático `create()` já está disponível.

### Usando em um Dashboard Real

```php
<?php

namespace App\Bi\Dashboards;

use App\Bi\Metrics\TaxaConversaoMetric;
use App\Models\Pedido;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Filters\DateIntervalFilter;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Widgets\BigNumber;
use Carbon\Carbon;

class PedidosDashboard extends Dashboard
{
    public $uriKey = 'pedidos';
    public $name   = 'Pedidos';
    public $model  = Pedido::class;

    public function widgets(): array
    {
        return [
            BigNumber::create('total_pedidos', 'Total de Pedidos')
                ->metric(CountMetric::create('total', 'Total')),

            BigNumber::create('taxa', 'Taxa de Conversão')
                ->metric(
                    TaxaConversaoMetric::create('taxa_conversao', 'Taxa de Conversão')
                        ->column('status')
                ),
        ];
    }

    public function filters(): array
    {
        return [
            DateIntervalFilter::create('created_at', 'Período')
                ->defaultDates(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()),
        ];
    }
}
```

### Variação: Implementando `Metric` Diretamente

Se por algum motivo você não quer herdar de `BaseMetric` — por exemplo, porque sua métrica já herda de outra classe — implemente a interface diretamente:

```php
<?php

namespace App\Bi\Metrics;

use DB;
use Luminix\Bi\Metrics\Metric;
use Luminix\Bi\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MargemLucroMetric implements Metric
{
    public string $key   = 'margem';
    public string $name  = 'Margem de Lucro';

    public function apply(Builder $builder, Widget $widget): Builder
    {
        return $builder->addSelect(
            DB::raw("ROUND((SUM(receita) - SUM(custo)) / NULLIF(SUM(receita), 0) * 100, 2) as `margem`")
        );
    }

    public function display(Model $value, array $models)
    {
        return number_format((float) $value->margem, 2, ',', '.') . '%';
    }

    public function getEmptyValue()
    {
        return '0,00%';
    }
}
```

> Ao implementar a interface diretamente, você perde `asPercentage()`, `column()`, `color()` e `create()`. Avalie se estender `BaseMetric` com um construtor personalizado não resolve o seu caso antes de escolher este caminho.

## Próximos Passos

[← Segurança nas Queries](../08-seguranca/03-queries.md) | [→ Criando Dimensões Customizadas](02-dimensao-customizada.md)
