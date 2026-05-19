# Criando Dimensões Customizadas

Uma dimensão define o eixo de agrupamento de um widget. Ela diz ao banco de dados "agrupe os resultados por este critério" e ao front-end "chame esse agrupamento por este nome". O pacote já oferece `StringDimension`, `RawDimension`, `BelongsToDimension`, `MonthDimension`, `YearDimension` e `DayDimension`. Quando nenhuma delas cobre o agrupamento desejado — como agrupar por trimestre, por faixa de valor ou por qualquer expressão SQL arbitrária com formatação de exibição customizada — você cria sua própria dimensão.

## Quando Criar uma Dimensão Customizada

Crie uma dimensão customizada quando:

- O agrupamento não é coberto pelas dimensões prontas (trimestre, semana ISO, faixa etária, intervalo de valores)
- O método `display()` precisa transformar o valor armazenado em algo legível (por exemplo, converter `1` em `"Q1 2024"`)
- A expressão SQL depende de parâmetros configuráveis por instância

## Dois Caminhos para Criar uma Dimensão

### Caminho 1: Estender `BaseDimension` (Recomendado)

`BaseDimension` estende `Attribute` e implementa a interface `Dimension`. Ao herdá-la, você recebe `column()`, `color()`, `create()` e o `display()` padrão (que retorna `$value->{$this->key}`). Você implementa apenas `apply()`.

### Caminho 2: Implementar a Interface `Dimension` Diretamente

Para casos onde a dimensão já herda de outra classe, implemente `Luminix\Bi\Dimensions\Dimension` diretamente:

```php
interface Dimension {
    public function apply(Builder $builder, Widget $widget): Builder;
    public function display(Model $value, array $models);
}
```

## Métodos Obrigatórios

| Método | Responsabilidade |
|---|---|
| `apply(Builder $builder, Widget $widget): Builder` | Adiciona o `SELECT` e o `GROUP BY` ao builder |
| `display(Model $value, array $models)` | Formata o valor do agrupamento para o JSON de resposta |

> O método `apply()` deve usar `addSelect()` e não `select()`, pelo mesmo motivo das métricas: substituir o select removeria as outras colunas já registradas.

## O Papel do `display()`

O `display()` recebe dois argumentos:

- `$value` — o model Eloquent correspondente à linha atual (com o atributo já carregado como `$value->{$this->key}`)
- `$models` — array com todos os models resultantes da query, em formato array

Isso permite que a dimensão acesse o contexto completo para calcular valores relativos ou para buscar dados em outros registros do resultado.

## Exemplo Completo: `QuarterDimension`

A `QuarterDimension` agrupa os registros por trimestre do ano. O banco retorna um valor como `2024-2` e o `display()` o converte para `"Q2 2024"`.

```php
<?php

namespace App\Bi\Dimensions;

use Illuminate\Support\Facades\DB;
use Luminix\Bi\Dimensions\BaseDimension;
use Luminix\Bi\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class QuarterDimension extends BaseDimension
{
    public function apply(Builder $builder, Widget $widget): Builder
    {
        // Gera uma chave no formato "YYYY-Q" (ex: "2024-2")
        return $builder
            ->addSelect(
                DB::raw(
                    "CONCAT(YEAR({$this->column}), '-', QUARTER({$this->column})) as `{$this->key}`"
                )
            )
            ->groupBy(
                DB::raw("CONCAT(YEAR({$this->column}), '-', QUARTER({$this->column}))")
            );
    }

    public function display(Model $value, array $models)
    {
        $raw = $value->getRawOriginal($this->key);

        if (!$raw) {
            return null;
        }

        [$year, $quarter] = explode('-', $raw);

        return "Q{$quarter} {$year}";
    }
}
```

O SQL gerado internamente será equivalente a:

```sql
SELECT
    CONCAT(YEAR(created_at), '-', QUARTER(created_at)) as `trimestre`
FROM pedidos
GROUP BY CONCAT(YEAR(created_at), '-', QUARTER(created_at))
```

E a resposta JSON de cada linha ficará como:

```json
{ "trimestre": "Q1 2024", "total": 148500.00 }
```

### Usando no Dashboard

```php
use App\Bi\Dimensions\QuarterDimension;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Widgets\Table;

Table::create('receita-trimestral', 'Receita por Trimestre')
    ->dimensions([
        QuarterDimension::create('trimestre', 'Trimestre')
            ->column('created_at'),
    ])
    ->metrics([
        SumMetric::create('total', 'Total')
            ->column('valor'),
    ]);
```

## Integração com `LineChart`

O `LineChart` tem um comportamento especial: ele preenche automaticamente os períodos sem dados com o valor vazio da métrica. Esse preenchimento só ocorre quando a dimensão é uma instância de `DateDimension`.

`QuarterDimension`, como estende `BaseDimension` diretamente, **não é** reconhecida pelo `LineChart` como uma dimensão temporal. Se você usar `QuarterDimension` em um `LineChart`, os dados serão exibidos sem o preenchimento de períodos ausentes — o comportamento será idêntico ao de um `Table`.

### Como Criar uma Dimensão Temporal Compatível com `LineChart`

Para que o `LineChart` preencha os períodos ausentes, a dimensão deve estender `DateDimension` e configurar quatro campos:

| Campo | Propósito |
|---|---|
| `$carbonFormat` | Formato Carbon/PHP para interpretar o valor retornado pelo banco (ex: `'Y-m'`) |
| `$carbonInterval` | Unidade do intervalo para iterar o período (ex: `'month'`, `'year'`) |
| `$carbonStartFunction` | Método Carbon para obter o início do período (ex: `'startOfMonth'`) |
| `$carbonEndFunction` | Método Carbon para obter o fim do período (ex: `'endOfMonth'`) |

Para um agrupamento por semana ISO, por exemplo:

```php
<?php

namespace App\Bi\Dimensions;

use Illuminate\Support\Facades\DB;
use Luminix\Bi\Dimensions\DateDimension;
use Luminix\Bi\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class WeekDimension extends DateDimension
{
    public function __construct(string $key, string $name)
    {
        parent::__construct($key, $name);

        // Formato retornado pelo banco: "2024-03" (ano-semana ISO)
        $this->sqlFormat('%Y-%u');
        $this->carbonFormat('Y-W', 'week');
        $this->carbonFunctions('startOfWeek', 'endOfWeek');
    }

    public function apply(Builder $builder, Widget $widget): Builder
    {
        return $builder
            ->addSelect(
                DB::raw("DATE_FORMAT({$this->column}, '%Y-%u') as `{$this->key}`")
            )
            ->groupBy(
                DB::raw("DATE_FORMAT({$this->column}, '%Y-%u')")
            );
    }
}
```

> A `DateDimension` usa `DATE_FORMAT` do MySQL para formatar a data. Se o seu banco for PostgreSQL ou SQLite, a função de formatação de data é diferente (`TO_CHAR` ou `strftime`). Adapte o SQL dentro de `apply()` conforme o banco utilizado.

## Próximos Passos

[← Criando Métricas Customizadas](01-metrica-customizada.md) | [→ Criando Filtros Customizados](03-filtro-customizado.md)
