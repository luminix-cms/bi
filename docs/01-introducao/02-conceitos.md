# Conceitos Fundamentais

Esta página explica os cinco conceitos centrais do Luminix BI. Entendê-los bem é a base para tudo que vem depois.

> **Dica de leitura:** cada conceito é apresentado com uma analogia, depois com o papel técnico que desempenha e, por fim, com um exemplo de código real.

---

## 1. Dashboard

### O que é

O `Dashboard` é o ponto de entrada e o elemento central de configuração. Ele responde a duas perguntas:

1. **Sobre qual dado estou analisando?** (o modelo Eloquent)
2. **O que quero ver e como filtrar?** (widgets e filtros)

Pense no Dashboard como a **planta de um painel**. Ele não exibe nada por conta própria — ele apenas declara o que deve existir e coordena as peças.

### Papel técnico

- É uma classe PHP abstrata que você estende em `app/Bi/Dashboards/`
- É descoberta automaticamente pelo `DashboardResolver` na inicialização
- Expõe um endpoint REST em `/bi-apis/{uriKey}/widgets`
- Aplica um escopo global opcional em todas as queries do painel

### Exemplo

```php
// app/Bi/Dashboards/PedidosDashboard.php

namespace App\Bi\Dashboards;

use App\Models\Pedido;
use Luminix\Bi\Dashboard;
use Illuminate\Database\Eloquent\Builder;

class PedidosDashboard extends Dashboard
{
    public $uriKey = 'pedidos';        // URL: /bi-apis/pedidos/widgets
    public $name   = 'Pedidos';        // Nome legível para o front-end
    public $model  = Pedido::class;    // Modelo base das queries

    public function widgets(): array
    {
        return [/* ... */];
    }

    public function filters(): array
    {
        return [/* ... */];
    }

    // Opcional: restrição global — aqui só pedidos confirmados
    public function scope(Builder $builder): Builder
    {
        return $builder->where('status', 'confirmado');
    }
}
```

> O método `scope()` é aplicado **antes** de qualquer filtro ou dimensão. É o lugar certo para restrições que nunca devem ser removidas pelo usuário.

---

## 2. Widget

### O que é

O `Widget` é o **componente de visualização**. Cada widget define como os dados serão apresentados — uma tabela, um número em destaque, um gráfico de linha, um gráfico de pizza.

Pense no widget como o **tipo de gráfico** que você escolheria em uma planilha: você tem os dados, mas precisa decidir se quer vê-los como linha, barra ou número.

### Papel técnico

- Recebe as dimensões e métricas configuradas e monta a query SQL correspondente
- Aplica os filtros do dashboard sobre a query
- Retorna os dados formatados para o front-end consumir
- Cada tipo de widget tem um `component` que o front-end usa para escolher o componente de renderização correto

### Os quatro tipos disponíveis

| Widget | Quando usar |
|--------|-------------|
| `BigNumber` | Um único número em destaque (total de pedidos, receita do mês) |
| `Table` | Múltiplas linhas com colunas ordenáveis |
| `LineChart` | Evolução ao longo do tempo (diária, mensal, anual) |
| `PartitionPie` | Distribuição percentual entre categorias |

### Exemplo

```php
use Luminix\Bi\Widgets\Table;
use Luminix\Bi\Widgets\BigNumber;
use Luminix\Bi\Widgets\LineChart;

// Tabela de pedidos por vendedor
Table::create('pedidos-por-vendedor', 'Pedidos por Vendedor')
    ->dimension(new StringDimension('vendedor', 'Vendedor'))
    ->metrics([
        new CountMetric('pedidos', 'Pedidos'),
        new SumMetric('total', 'Receita'),
    ]);

// Número único: total geral de receita
BigNumber::create('receita-total', 'Receita Total')
    ->metric(new SumMetric('total', 'Receita'));

// Evolução mensal da receita
LineChart::create('receita-mensal', 'Receita por Mês')
    ->dimension(new MonthDimension('created_at', 'Mês'))
    ->metric(new SumMetric('total', 'Receita'));
```

> Um widget é construído com um método estático `create($key, $name)` e depois configurado com encadeamento de métodos (_method chaining_).

---

## 3. Métrica

### O que é

A `Métrica` define **o que calcular** — ela representa uma agregação SQL sobre os registros do grupo. Em termos SQL, é tudo que vai no `SELECT` junto com uma função de agregação: `COUNT(*)`, `SUM(coluna)`, `AVG(coluna)`.

Pense na métrica como a **pergunta numérica** que você faz sobre cada grupo: _"quantos pedidos esse vendedor fez?"_, _"qual o total de receita desse mês?"_.

### Papel técnico

- Implementa a interface `Metric`
- O método `apply(Builder $builder)` adiciona um `SELECT` com agregação à query
- O método `display($value, $models)` formata o valor para exibição (ex: percentual)
- Estende `Attribute`, que é a classe base compartilhada com Dimensão

### Como funciona internamente

Para `SumMetric('total', 'Receita')` sobre a coluna `valor_total`:

```sql
-- O que a métrica adiciona à query:
SELECT SUM(valor_total) as `total`
```

O `key` (`total`) é o identificador no JSON de resposta. O `column` (`valor_total`) é o nome real da coluna no banco — por padrão, `column` assume o mesmo valor que `key`, mas pode ser sobrescrito com `->column('outra_coluna')`.

### As métricas disponíveis

| Classe | SQL gerado | Caso de uso |
|--------|-----------|-------------|
| `CountMetric` | `COUNT(*)` | Contagem de registros |
| `SumMetric` | `SUM(coluna)` | Soma de valores numéricos |
| `AverageMetric` | `AVG(coluna)` | Média de valores numéricos |
| `RawMetric` | Expressão livre | Cálculos customizados |
| `CountManyMetric` | `withCount(relação)` | Contar registros relacionados |
| `SumManyMetric` | `withSum(relação, coluna)` | Somar valores de relacionamentos |

### Exemplo

```php
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Metrics\AverageMetric;

// Conta todos os registros do grupo
new CountMetric('pedidos', 'Pedidos');

// Soma a coluna "valor_total" do banco, exibida como "Receita"
new SumMetric('receita', 'Receita')->column('valor_total');

// Média com exibição percentual em relação ao total geral
new AverageMetric('ticket', 'Ticket Médio');

// Percentual: cada linha mostra sua fatia do total
new SumMetric('participacao', 'Participação')->column('total')->asPercentage();
```

> O método `->asPercentage()` divide o valor da linha pelo total de todos os valores da mesma métrica e multiplica por 100. É calculado em PHP após a query — não no SQL.

---

## 4. Dimensão

### O que é

A `Dimensão` define **como agrupar os dados** — ela representa o critério de agrupamento SQL (`GROUP BY`). Cada linha do resultado corresponde a um valor distinto da dimensão.

Pense na dimensão como o **eixo de uma tabela ou gráfico**: _"quero ver os dados por mês"_, _"por categoria"_, _"por vendedor"_.

### Papel técnico

- Implementa a interface `Dimension`
- O método `apply(Builder $builder)` adiciona o `SELECT` e o `GROUP BY` à query
- O método `display($value, $models)` formata o valor para exibição (ex: labels de data)
- Também estende `Attribute`, assim como as métricas

### Como funciona internamente

Para `StringDimension('categoria', 'Categoria')`:

```sql
-- O que a dimensão adiciona à query:
SELECT `categoria` as `categoria`
-- ...
GROUP BY `categoria`
```

Para `MonthDimension('created_at', 'Mês')`:

```sql
-- O que a dimensão de data adiciona à query:
SELECT DATE_FORMAT(`created_at`, '%Y-%m') as `mes`
-- ...
GROUP BY `mes`
```

### As dimensões disponíveis

| Classe | Comportamento | Exemplo de saída |
|--------|--------------|-----------------|
| `StringDimension` | Agrupa por coluna textual | `"confirmado"`, `"cancelado"` |
| `DayDimension` | Agrupa por dia | `"2024-03-15"` |
| `MonthDimension` | Agrupa por mês | `"2024-03"` |
| `YearDimension` | Agrupa por ano | `"2024"` |
| `BelongsToDimension` | Agrupa por relacionamento | `{ id: 1, nome: "João" }` |
| `RawDimension` | Expressão SQL livre | Depende da expressão |

### Exemplo

```php
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Dimensions\MonthDimension;
use Luminix\Bi\Dimensions\BelongsToDimension;

// Agrupa pelo valor da coluna "status"
new StringDimension('status', 'Status');

// Agrupa por mês da coluna "created_at"
new MonthDimension('created_at', 'Mês');

// Agrupa pelo vendedor relacionado (belongsTo)
new BelongsToDimension('vendedor_id', 'Vendedor')
    ->relation('vendedor')         // nome da relação no modelo
    ->otherColumn('nome');         // coluna a exibir do modelo relacionado
```

> **Dimensão vs. Métrica:** a dimensão define as _linhas_ da tabela (os grupos); a métrica define as _colunas_ numéricas (os valores calculados para cada grupo).

---

## 5. Filtro

### O que é

O `Filtro` define **como o usuário pode restringir os dados** — ele representa uma cláusula `WHERE` que é aplicada quando o front-end envia um valor. Filtros são **opcionais**: se o usuário não enviar um valor, a query roda sem aquela restrição.

Pense no filtro como os **controles interativos do painel**: o seletor de período, o dropdown de categoria, o campo de busca por cliente.

### Papel técnico

- Estende a classe abstrata `BaseFilter`
- É declarado no dashboard e aplicado automaticamente em **todos** os widgets daquele dashboard
- O método `apply(Builder $builder, array $filterData)` adiciona o `WHERE` à query
- O método `extra(Dashboard $dashboard)` retorna dados para o front-end montar o controle (ex: lista de opções para um dropdown)

### Como funciona internamente

O front-end envia os filtros como parâmetro `filters` na requisição:

```json
{
  "filters": {
    "created_at": { "start": "2024-01-01", "end": "2024-12-31" },
    "status": ["confirmado", "pendente"]
  }
}
```

O `BaseWidget` itera sobre os filtros declarados no dashboard e, para cada um que estiver presente na requisição, chama `filter->apply($builder, $filterData, $request)`. O builder resultante é então executado.

### Os filtros disponíveis

| Classe | WHERE gerado | Controle típico no front |
|--------|-------------|--------------------------|
| `StringFilter` | `WHERE col IN (...)` | Checkbox múltiplo ou multi-select |
| `NumberFilter` | `WHERE col = ?` / `BETWEEN` | Input numérico com operador |
| `DateFilter` | `WHERE col BETWEEN (início do dia, fim do dia)` | Seletor de data única |
| `DateIntervalFilter` | `WHERE col BETWEEN (start, end)` | Seletor de intervalo |
| `RelationFilter` | `WHERE HAS (relação, ...)` | Filtro por relacionamento |

### Exemplo

```php
use Luminix\Bi\Filters\StringFilter;
use Luminix\Bi\Filters\DateIntervalFilter;
use Luminix\Bi\Filters\NumberFilter;

// Dropdown com todos os status distintos do banco
StringFilter::create('status', 'Status');

// Seletor de período aplicado em created_at
DateIntervalFilter::create('created_at', 'Período');

// Filtro numérico em uma coluna diferente da chave
NumberFilter::create('valor_minimo', 'Valor Mínimo')->column('total');
```

> O `StringFilter` busca automaticamente os valores distintos no banco para preencher as opções — você não precisa declarar a lista manualmente. Esse comportamento vem do método `extra()`, que é chamado pelo endpoint `/filters/{filter}`.

---

## Como os cinco conceitos se encaixam

Veja como uma query completa é construída a partir das peças:

```
Dashboard (Pedido::class, scope: status = 'confirmado')
    │
    ├── Widget: Table
    │       │
    │       ├── Dimensão: MonthDimension('created_at')
    │       │    └── adiciona: SELECT DATE_FORMAT(created_at, '%Y-%m') as mes  GROUP BY mes
    │       │
    │       └── Métrica: SumMetric('total')
    │            └── adiciona: SELECT SUM(total) as total
    │
    └── Filtros aplicados ao builder:
            ├── DateIntervalFilter('created_at') → WHERE created_at BETWEEN ? AND ?
            └── StringFilter('status')           → WHERE status IN (?)
```

Query final gerada:

```sql
SELECT
    DATE_FORMAT(created_at, '%Y-%m') as mes,
    SUM(total) as total
FROM pedidos
WHERE status = 'confirmado'          -- escopo do dashboard
  AND created_at BETWEEN ? AND ?     -- filtro de período
  AND status IN (?)                  -- filtro de status
GROUP BY mes
```

> Nenhuma linha desse SQL foi escrita manualmente — ela emergiu da composição das classes PHP.

---

## O papel do Atributo

`Attribute` é a classe base compartilhada entre `BaseMetric` e `BaseDimension`. Ela carrega as propriedades comuns:

| Propriedade | Descrição |
|-------------|-----------|
| `$key` | Identificador no JSON de resposta |
| `$name` | Nome legível para o front-end |
| `$column` | Coluna SQL (padrão: igual ao `$key`) |
| `$color` | Cor opcional para visualizações |

O método `->column('outra_coluna')` é disponível em qualquer métrica ou dimensão e permite separar o identificador da resposta do nome real da coluna no banco — útil quando você quer uma chave diferente do nome da coluna.

```php
// key = 'receita', name = 'Receita Total', column = 'valor_pedido'
new SumMetric('receita', 'Receita Total')->column('valor_pedido');
```

---

## Próximos Passos

- [← O que é o Luminix BI](01-o-que-e.md)
- [Instalação via Composer →](../02-instalacao/01-instalacao.md)
- [Criando um Dashboard →](../03-dashboards/01-criando.md)
