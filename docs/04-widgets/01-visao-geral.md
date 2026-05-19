# Visão Geral dos Widgets

Um dashboard sem widgets é apenas uma configuração em espera. Os widgets são os componentes que efetivamente executam as queries, formatam os dados e os entregam ao front-end para exibição. Cada widget é uma unidade autônoma de visualização: ele sabe o que perguntar ao banco, como aplicar os filtros e como serializar a resposta.

---

## O que é um Widget

Pense no widget como o **tipo de gráfico** que você escolheria em uma planilha. Você tem os dados, mas precisa decidir se quer vê-los como uma linha evoluindo ao longo do tempo, como uma tabela com múltiplas colunas, como um número único em destaque ou como fatias de um gráfico de pizza. Essa escolha é o widget.

Tecnicamente, um widget é uma classe PHP que:

1. Recebe dimensões e métricas configuradas por você
2. Monta e executa a query SQL correspondente
3. Aplica os filtros declarados no dashboard
4. Retorna os dados formatados para o front-end

Cada widget tem um `component` — uma string que o front-end usa para identificar qual componente de renderização deve ser carregado. O back-end não controla a renderização; ele apenas informa qual componente deve ser usado.

---

## Interface `Widget` e Classe `BaseWidget`

A interface `Widget` define o contrato mínimo que qualquer widget deve implementar:

```php
interface Widget extends \JsonSerializable
{
    public function width($width);
    public function scope(Closure $scope): static;
    public function data(Dashboard $dashboard, BiRequest $request);
}
```

Na prática, você nunca implementa `Widget` diretamente. A classe abstrata `BaseWidget` já implementa todos esses métodos com a lógica padrão — incluindo o ciclo completo de execução da query. Você apenas estende `BaseWidget` ou usa um dos quatro tipos concretos já disponíveis.

```php
abstract class BaseWidget implements Widget
{
    use Traits\HasAttributes; // fornece dimension(), dimensions(), metric(), metrics()

    public $width;
    public $key;
    public $name;
    public $scope;

    // ...
}
```

---

## Método Estático `create($key, $name)`

**Sempre** instancie widgets com o método estático `create()`. Não use `new` diretamente.

```php
// Correto
Table::create('pedidos-por-mes', 'Pedidos por Mês');

// Evitar
new Table('pedidos-por-mes', 'Pedidos por Mês');
```

O `create()` retorna uma instância do próprio tipo (`static`), o que habilita o encadeamento fluente de métodos. O `$key` é o identificador interno do widget — ele aparece nas URLs da API e como nome do arquivo CSV exportado. O `$name` é o nome legível exibido no front-end.

```php
LineChart::create('receita-mensal', 'Receita por Mês')
    ->dimension(new MonthDimension('created_at', 'Mês'))
    ->metric(new SumMetric('total', 'Receita'));
```

---

## Largura com `width()`

O método `width()` define um valor livre que o front-end pode usar para posicionar o widget em um layout de grid ou qualquer sistema de colunas. O pacote repassa esse valor ao front-end sem qualquer validação.

```php
Table::create('pedidos-tabela', 'Pedidos')
    ->width(8)          // ex: 8 colunas de 12 em um grid Bootstrap
    ->dimension(/* ... */)
    ->metric(/* ... */);

BigNumber::create('total-receita', 'Receita Total')
    ->width(4)          // ex: 4 colunas
    ->metric(/* ... */);
```

> O significado concreto de `width` depende inteiramente do front-end. O pacote apenas serializa o valor no JSON do widget. Se omitido, o valor serializado será `null`.

---

## Escopo por Widget com `scope(Closure)`

O método `scope()` aceita um `Closure` que recebe um `Builder` e deve retorná-lo modificado. Esse escopo é aplicado **após** o escopo do dashboard e **antes** das dimensões e métricas.

```php
// O escopo do dashboard restringe ao status 'confirmado'.
// O escopo deste widget restringe ainda mais, ao plano 'vip'.
LineChart::create('receita-vip', 'Receita VIP')
    ->scope(function (Builder $builder) {
        return $builder->where('plano', 'vip');
    })
    ->dimension(new MonthDimension('created_at', 'Mês'))
    ->metric(new SumMetric('total', 'Receita'));
```

O escopo do widget **complementa** — nunca substitui — o escopo do dashboard. Se o dashboard tem `->where('status', 'confirmado')` e o widget tem `->where('plano', 'vip')`, a query final terá ambas as condições.

---

## Trait `HasAttributes`: Dimensões e Métricas

O trait `HasAttributes`, usado pelo `BaseWidget`, fornece quatro métodos para configurar as dimensões e métricas de um widget:

| Método | Parâmetro | Descrição |
|--------|-----------|-----------|
| `dimension(Dimension $d)` | Uma instância | Define **uma** dimensão (substitui a coleção inteira) |
| `dimensions(array $dims)` | Array de instâncias | Define **múltiplas** dimensões de uma vez |
| `metric(Metric $m)` | Uma instância | Define **uma** métrica (substitui a coleção inteira) |
| `metrics(array $metrics)` | Array de instâncias | Define **múltiplas** métricas de uma vez |

Os métodos no singular (`dimension()` e `metric()`) são apenas conveniência — eles chamam internamente os métodos no plural com um array de um único elemento. Quando você precisa de mais de um atributo, use o plural:

```php
// Singular: um de cada
BigNumber::create('ticket-medio', 'Ticket Médio')
    ->metric(new AverageMetric('ticket', 'Ticket Médio'));

// Plural: múltiplos atributos
Table::create('resumo-mensal', 'Resumo Mensal')
    ->dimensions([
        new MonthDimension('created_at', 'Mês'),
        new StringDimension('categoria', 'Categoria'),
    ])
    ->metrics([
        new CountMetric('pedidos', 'Pedidos'),
        new SumMetric('total', 'Receita'),
    ]);
```

---

## O Fluxo Interno de `data()`

Quando o endpoint `GET /bi-apis/{dashboard}/widgets/{widget}` é chamado, o método `data()` do widget é executado. O fluxo é sempre o mesmo:

```
1. getBaseBuilder($dashboard)
   ├── cria o builder a partir do model do dashboard (via QueryService)
   ├── aplica o scope do dashboard (se definido)
   └── aplica o scope do widget

2. applyAttributes($builder)
   └── para cada dimensão e métrica, chama attribute->apply($builder)
       (adiciona SELECTs, GROUP BYs, etc.)

3. applyFilters($builder, $dashboard, $request)
   └── para cada filtro declarado no dashboard que estiver na requisição,
       chama filter->apply($builder, $filterData, $request)

4. $builder->get()
   └── executa a query SQL e retorna uma Collection de models

5. displayModel($model, $rawModels)
   └── para cada registro, chama attribute->display($model, $rawModels)
       (formata os valores: percentuais, labels de data, etc.)
```

O SQL gerado por este fluxo para um widget com `MonthDimension` e `SumMetric` seria:

```sql
SELECT
    DATE_FORMAT(`created_at`, '%Y-%m') as `mes`,
    SUM(`total`) as `total`
FROM `pedidos`
WHERE `status` = 'confirmado'           -- scope do dashboard
  AND `created_at` BETWEEN ? AND ?      -- filtro da requisição
GROUP BY DATE_FORMAT(`created_at`, '%Y-%m')
```

---

## Formato JSON Serializado

Todo widget implementa `\JsonSerializable`. Quando o endpoint de widgets do dashboard é chamado (`GET /bi-apis/{dashboard}/widgets`), cada widget é serializado no seguinte formato:

```json
{
    "width": 8,
    "key": "receita-mensal",
    "name": "Receita por Mês",
    "component": "line-chart",
    "metrics": [...],
    "dimensions": [...],
    "extra": {
        "uniqid": "64a1f3b2c9e4a"
    }
}
```

O campo `extra` varia por tipo de widget. O `BaseWidget` inclui apenas um `uniqid` gerado a cada requisição. Subclasses como `Table` e `PartitionPie` sobrescrevem `extra()` para incluir informações adicionais específicas do tipo.

---

## Tabela Resumo dos Quatro Tipos de Widget

| Widget | `component` | Dimensões | Métricas | Diferencial |
|--------|-------------|-----------|----------|-------------|
| `BigNumber` | `big-number` | Nenhuma | Uma | Retorna um único registro agregado |
| `Table` | `table` | Uma ou mais | Uma ou mais | Suporta ordenação dinâmica via request |
| `LineChart` | `line-chart` | Uma `DateDimension` | Uma ou mais | Interpola datas ausentes com valor zero |
| `PartitionPie` | `partition-pie` | Uma | Uma | Aceita array de cores CSS via `colors()` |

---

## Próximos Passos

- [← Descoberta Automática de Dashboards](../03-dashboards/05-descoberta.md) | [→ BigNumber](02-big-number.md)
