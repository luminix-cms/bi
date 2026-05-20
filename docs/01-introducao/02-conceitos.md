# Conceitos Fundamentais

O Luminix BI é construído sobre cinco conceitos. Entendê-los bem é suficiente para criar dashboards funcionais.

---

## 1. Dashboard

O `Dashboard` é o ponto de entrada e coordenador central. Ele responde a duas perguntas:

1. **Sobre qual dado estou analisando?** (o modelo Eloquent)
2. **O que quero ver e como filtrar?** (widgets e filtros)

Pense no Dashboard como a **planta de um painel**: ele não exibe nada por conta própria — declara o que deve existir e coordena as peças.

```php
// app/Bi/Dashboards/OrdersDashboard.php

class OrdersDashboard extends Dashboard
{
    public $uriKey = 'orders';       // URL: /bi-apis/orders/widgets
    public $name   = 'Orders';       // Nome exibido pelo front-end
    public $model  = Order::class;   // Modelo base das queries

    public function widgets(): array { return [/* ... */]; }
    public function filters(): array { return [/* ... */]; }

    // Opcional: restrição global que nunca pode ser removida pelo usuário
    public function scope(Builder $builder): Builder
    {
        return $builder->where('status', '!=', 'draft');
    }
}
```

---

## 2. Widget

O `Widget` é o **componente de visualização**. Cada widget define como os dados serão apresentados — tabela, número em destaque, gráfico de linha ou pizza.

| Widget | Quando usar |
|--------|-------------|
| `BigNumber` | Um único número em destaque (total de pedidos, receita do mês) |
| `Table` | Múltiplas linhas com colunas ordenáveis |
| `LineChart` | Evolução ao longo do tempo (diária, mensal, anual) |
| `PartitionPie` | Distribuição percentual entre categorias |

```php
// Tabela: pedidos por status
Table::create('orders-by-status', 'Orders by Status')
    ->dimension(new StringDimension('status', 'Status'))
    ->metrics([
        new CountMetric('orders', 'Orders'),
        new SumMetric('revenue', 'Revenue')->column('total_amount'),
    ]);

// Número único: total de receita
BigNumber::create('total-revenue', 'Total Revenue')
    ->metric(new SumMetric('revenue', 'Revenue')->column('total_amount'));

// Gráfico de linha: evolução mensal
LineChart::create('monthly-revenue', 'Revenue per Month')
    ->dimension(new MonthDimension('created_at', 'Month'))
    ->metric(new SumMetric('revenue', 'Revenue')->column('total_amount'));
```

Widgets são construídos com `create($key, $name)` e configurados por encadeamento de métodos.

---

## 3. Métrica

A `Métrica` define **o que calcular** — uma agregação sobre os registros de cada grupo (`COUNT`, `SUM`, `AVG`). É a pergunta numérica: _"quantos pedidos?"_, _"qual o total de receita?"_.

| Classe | Caso de uso |
|--------|-------------|
| `CountMetric` | Contagem de registros |
| `SumMetric` | Soma de valores numéricos |
| `AverageMetric` | Média de valores numéricos |
| `RawMetric` | Expressão SQL livre |
| `CountManyMetric` | Contar registros de um relacionamento |
| `SumManyMetric` | Somar valores de um relacionamento |

```php
new CountMetric('orders', 'Orders');

// 'revenue' é a chave no JSON; 'total_amount' é a coluna real no banco
new SumMetric('revenue', 'Revenue')->column('total_amount');

new AverageMetric('avg_ticket', 'Average Ticket')->column('total_amount');

// Exibe cada linha como percentual do total geral (calculado em PHP após a query)
new SumMetric('share', 'Share')->column('total_amount')->asPercentage();
```

---

## 4. Dimensão

A `Dimensão` define **como agrupar os dados** — o critério de `GROUP BY`. Cada linha do resultado corresponde a um valor distinto da dimensão. É o eixo da tabela ou gráfico: _"por mês"_, _"por status"_, _"por vendedor"_.

| Classe | Exemplo de saída |
|--------|-----------------|
| `StringDimension` | `"confirmed"`, `"cancelled"` |
| `DayDimension` | `"2024-03-15"` |
| `MonthDimension` | `"2024-03"` |
| `YearDimension` | `"2024"` |
| `BelongsToDimension` | `{ id: 1, name: "Acme Corp" }` |
| `RawDimension` | Depende da expressão |

```php
new StringDimension('status', 'Status');

new MonthDimension('created_at', 'Month');

new BelongsToDimension('customer_id', 'Customer')
    ->relation('customer')      // nome da relação no modelo
    ->otherColumn('name');      // coluna a exibir do modelo relacionado
```

> **Dimensão vs. Métrica:** a dimensão define as _linhas_ (os grupos); a métrica define as _colunas numéricas_ (os valores calculados para cada grupo).

---

## 5. Filtro

O `Filtro` define **como o usuário pode restringir os dados** — uma cláusula `WHERE` aplicada quando o front-end envia um valor. Filtros são opcionais: se nenhum valor for enviado, a query roda sem aquela restrição.

Pense no filtro como os **controles interativos do painel**: seletor de período, dropdown de categoria, campo de busca.

| Classe | Tipo de filtro |
|--------|---------------|
| `StringFilter` | Seleção de valores textuais (multi-select) |
| `NumberFilter` | Comparação numérica |
| `DateFilter` | Data única |
| `DateIntervalFilter` | Intervalo de datas |
| `RelationFilter` | Filtro por relacionamento Eloquent |

```php
// Popula automaticamente as opções com valores distintos do banco
StringFilter::create('status', 'Status');

// Seletor de período
DateIntervalFilter::create('created_at', 'Period');

// Filtro numérico com coluna diferente da chave
NumberFilter::create('min_value', 'Minimum Value')->column('total_amount');
```

Filtros declarados no dashboard são aplicados automaticamente em todos os widgets daquele dashboard.

---

## Como os cinco conceitos se encaixam

```
Dashboard (Order::class)
    ├── Widget: Table
    │       ├── Dimensão: MonthDimension('created_at')  → GROUP BY mês
    │       └── Métrica:  SumMetric('total_amount')     → SUM(total_amount)
    │
    └── Filtros aplicados em todos os widgets:
            ├── DateIntervalFilter('created_at')  → WHERE created_at BETWEEN ? AND ?
            └── StringFilter('status')            → WHERE status IN (?)
```

Nenhuma linha de SQL precisa ser escrita manualmente — ela emerge da composição das classes PHP.

---

## Próximos Passos

- [← O que é o Luminix BI](01-o-que-e.md)
- [Guia de Uso Básico →](../uso-basico.md)
- [Instalação via Composer →](../02-instalacao/01-instalacao.md)
- [Criando um Dashboard →](../03-dashboards/01-criando.md)
