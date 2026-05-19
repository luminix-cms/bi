# PartitionPie

Quando você precisa mostrar não apenas os valores absolutos, mas a **participação de cada categoria no todo**, o gráfico de pizza é a representação natural. O `PartitionPie` gera os dados para esse tipo de visualização, incluindo suporte a cores personalizadas para cada fatia.

---

## Propósito

O widget `PartitionPie` produz dados de distribuição por categoria — cada linha do resultado representa uma fatia do gráfico de pizza, com seu nome e valor. A lógica de cálculo das fatias em si (converter valores em ângulos e percentuais visuais) é responsabilidade do componente front-end. O `PartitionPie` fornece os dados estruturados e, opcionalmente, um array de cores.

O campo `component` enviado ao front-end é `'partition-pie'`.

---

## Configuração: Uma Dimensão e Uma Métrica

O `PartitionPie` normalmente é configurado com uma dimensão textual (`StringDimension`) e uma métrica de soma ou contagem. A dimensão define os rótulos das fatias; a métrica define o tamanho de cada uma.

```php
PartitionPie::create('receita-por-categoria', 'Receita por Categoria')
    ->dimension(new StringDimension('categoria', 'Categoria'))
    ->metric(new SumMetric('receita', 'Receita')->column('total'));
```

O SQL gerado internamente:

```sql
SELECT
    `categoria` as `categoria`,
    SUM(`total`) as `receita`
FROM `pedidos`
GROUP BY `categoria`
```

A resposta:

```json
{
    "status": 200,
    "data": [
        { "categoria": "Eletrônicos", "receita": "58400.00" },
        { "categoria": "Roupas",      "receita": "23100.00" },
        { "categoria": "Livros",      "receita": "8750.00"  }
    ]
}
```

---

## Método `colors(array $colors)`

O método `colors()` aceita um array de strings CSS que representam as cores das fatias. O pacote apenas repassa esse array ao front-end via `extra.colors` — é o componente front-end que decide como mapear cada cor a cada fatia (normalmente na ordem de aparição dos dados).

```php
PartitionPie::create('receita-por-categoria', 'Receita por Categoria')
    ->dimension(new StringDimension('categoria', 'Categoria'))
    ->metric(new SumMetric('receita', 'Receita')->column('total'))
    ->colors(['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6']);
```

O campo `extra` serializado para este widget:

```json
{
    "extra": {
        "colors": ["#3B82F6", "#10B981", "#F59E0B", "#EF4444", "#8B5CF6"]
    }
}
```

> O pacote não valida o número de cores nem o formato das strings. Se você passar menos cores do que categorias, o front-end precisará lidar com as fatias sem cor atribuída. Se não chamar `colors()`, o valor serializado será `null`.

---

## Exemplo Completo com Cores Definidas

```php
// app/Bi/Dashboards/VendasDashboard.php

namespace App\Bi\Dashboards;

use App\Models\Pedido;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Widgets\PartitionPie;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Filters\DateIntervalFilter;

class VendasDashboard extends Dashboard
{
    public $uriKey = 'vendas';
    public $name   = 'Vendas';
    public $model  = Pedido::class;

    public function widgets(): array
    {
        return [
            PartitionPie::create('distribuicao-categorias', 'Distribuição por Categoria')
                ->dimension(new StringDimension('categoria', 'Categoria'))
                ->metric(new SumMetric('receita', 'Receita')->column('total'))
                ->colors([
                    '#3B82F6',  // Eletrônicos — azul
                    '#10B981',  // Roupas — verde
                    '#F59E0B',  // Livros — âmbar
                    '#EF4444',  // Outros — vermelho
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            DateIntervalFilter::create('created_at', 'Período'),
        ];
    }
}
```

Resposta completa da API, incluindo o campo `extra` com as cores:

```json
{
    "status": 200,
    "data": [
        { "categoria": "Eletrônicos", "receita": "58400.00" },
        { "categoria": "Roupas",      "receita": "23100.00" },
        { "categoria": "Livros",      "receita": "8750.00"  },
        { "categoria": "Outros",      "receita": "4200.00"  }
    ]
}
```

E a serialização do widget no endpoint de widgets do dashboard (`GET /bi-apis/vendas/widgets`):

```json
{
    "width": null,
    "key": "distribuicao-categorias",
    "name": "Distribuição por Categoria",
    "component": "partition-pie",
    "metrics": [...],
    "dimensions": [...],
    "extra": {
        "colors": ["#3B82F6", "#10B981", "#F59E0B", "#EF4444"]
    }
}
```

---

## Combinando com `asPercentage()`

Para exibir percentuais diretamente no dado (em vez de deixar o front-end calcular), use `asPercentage()` na métrica. O cálculo é feito em PHP após a query — cada valor é dividido pela soma de todos os valores da mesma métrica e multiplicado por 100.

```php
PartitionPie::create('participacao-categorias', 'Participação por Categoria')
    ->dimension(new StringDimension('categoria', 'Categoria'))
    ->metric(
        new SumMetric('participacao', 'Participação')
            ->column('total')
            ->asPercentage()
    )
    ->colors(['#3B82F6', '#10B981', '#F59E0B', '#EF4444']);
```

A resposta passa a incluir percentuais já calculados:

```json
{
    "status": 200,
    "data": [
        { "categoria": "Eletrônicos", "participacao": "61.71%" },
        { "categoria": "Roupas",      "participacao": "24.40%" },
        { "categoria": "Livros",      "participacao": "9.24%"  },
        { "categoria": "Outros",      "participacao": "4.44%"  }
    ]
}
```

> O valor retornado por `asPercentage()` é uma string formatada com o símbolo `%` (ex: `"61.71%"`). Se o front-end precisar de um número puro para cálculos, faça o parse no lado cliente ou não use `asPercentage()` — deixe o front-end calcular.

---

## Próximos Passos

- [← LineChart](04-line-chart.md) | [→ Exportação CSV](06-csv.md)
