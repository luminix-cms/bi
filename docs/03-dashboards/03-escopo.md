# Escopo Global

Imagine que um dashboard de pedidos precisa mostrar apenas os pedidos da empresa do usuário logado, independentemente de qualquer filtro que o usuário aplique. Esse tipo de restrição que nunca pode ser removida pelo usuário — e que deve estar sempre presente — é o papel do escopo global do dashboard.

## O método `scope()`

O método `scope()` recebe um `Builder` do Eloquent e deve retornar o mesmo `Builder` com as cláusulas adicionais aplicadas. Ele é definido na classe do dashboard e tem a seguinte assinatura:

```php
public function scope(Builder $builder): Builder
{
    // adicione restrições ao builder e retorne-o
    return $builder;
}
```

A implementação padrão na classe base `Dashboard` não existe — o método é opcional. O `BaseWidget` verifica sua existência antes de chamá-lo:

```php
// BaseWidget::getBaseBuilder()
$builder = method_exists($dashboard, 'scope')
    ? $dashboard->scope($baseQuery)
    : $baseQuery;
```

## Quando o `scope()` é aplicado

O escopo do dashboard é o **primeiro** passo na construção da query de qualquer widget. A ordem de aplicação é:

1. `QueryService::create()` — cria o builder base com a conexão correta e o `allowed()` do Luminix Backend
2. `$dashboard->scope()` — aplica as restrições globais do dashboard
3. `$widget->scope` (Closure) — aplica o escopo específico do widget, se houver
4. `applyAttributes()` — aplica métricas e dimensões (SELECT, GROUP BY)
5. `applyFilters()` — aplica os filtros enviados pelo usuário na requisição

Isso significa que qualquer condição colocada em `scope()` do dashboard está presente em todas as queries, e os filtros do usuário são aplicados **por cima** dessas condições — nunca substituindo-as.

## Casos de uso reais

### Isolamento por tenant

Em sistemas multi-tenant, cada usuário deve ver apenas os dados da sua empresa:

```php
public function scope(Builder $builder): Builder
{
    return $builder->where('empresa_id', auth()->user()->empresa_id);
}
```

A query resultante sempre incluirá:

```sql
WHERE `empresa_id` = 42
```

Mesmo que o usuário passe outros filtros na requisição, essa cláusula permanece.

### Status fixo

Para um dashboard de "pedidos ativos", que nunca deve mostrar pedidos cancelados ou arquivados:

```php
public function scope(Builder $builder): Builder
{
    return $builder->whereIn('status', ['pendente', 'aprovado', 'em_entrega']);
}
```

### Soft deletes

Para excluir registros deletados da análise, mesmo que o model use `SoftDeletes`:

```php
public function scope(Builder $builder): Builder
{
    return $builder->whereNull('deleted_at');
}
```

> Se o model da aplicação usa o trait `SoftDeletes` do Laravel, o Eloquent já exclui registros deletados automaticamente via escopo global do model. Use este padrão apenas se precisar filtrar soft deletes em models que não usam o trait, ou se quiser ser explícito na intenção do dashboard.

### Multi-empresa com filtro adicional

Um dashboard financeiro que mostra apenas lançamentos do exercício atual e da empresa do usuário:

```php
public function scope(Builder $builder): Builder
{
    return $builder
        ->where('empresa_id', auth()->user()->empresa_id)
        ->whereYear('data_lancamento', now()->year);
}
```

## Diferença entre o `scope()` do dashboard e o `scope()` do widget

O dashboard e os widgets possuem mecanismos de escopo com papéis diferentes:

| Aspecto | `scope()` do Dashboard | `scope()` do Widget |
|---|---|---|
| Tipo | Método PHP na classe do dashboard | Closure passada via `->scope()` |
| Aplicado a | Todos os widgets do dashboard | Apenas ao widget específico |
| Ordem de aplicação | Antes do escopo do widget | Após o escopo do dashboard |
| Caso de uso | Restrições de segurança/tenant | Condições específicas de um widget |

Exemplo combinando os dois:

```php
// No dashboard — restrição global de tenant
public function scope(Builder $builder): Builder
{
    return $builder->where('empresa_id', auth()->user()->empresa_id);
}

// No widget — restrição específica deste widget
public function widgets(): array
{
    return [
        BigNumber::create('pedidos-urgentes', 'Pedidos Urgentes')
            ->scope(function (Builder $builder) {
                return $builder->where('prioridade', 'alta')
                               ->where('status', 'pendente');
            })
            ->metric(CountMetric::create('total', 'Total')),
    ];
}
```

A query final do widget `pedidos-urgentes` terá ambas as condições:

```sql
SELECT COUNT(*) AS `total`
FROM `pedidos`
WHERE `empresa_id` = 42          -- escopo do dashboard
  AND `prioridade` = 'alta'      -- escopo do widget
  AND `status` = 'pendente'      -- escopo do widget
```

## Exemplo completo

Dashboard que mostra apenas pedidos do tenant atual, excluindo pedidos de teste:

```php
<?php

namespace App\Bi\Dashboards;

use Illuminate\Database\Eloquent\Builder;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Filters\DateIntervalFilter;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Widgets\BigNumber;

class PedidosDashboard extends Dashboard
{
    public $model  = \App\Models\Pedido::class;
    public $uriKey = 'pedidos';
    public $name   = 'Dashboard de Pedidos';

    public function scope(Builder $builder): Builder
    {
        return $builder
            ->where('empresa_id', auth()->user()->empresa_id)
            ->where('is_teste', false);
    }

    public function filters(): array
    {
        return [
            DateIntervalFilter::create('created_at', 'Período'),
        ];
    }

    public function widgets(): array
    {
        return [
            BigNumber::create('total', 'Total de Pedidos')
                ->metric(CountMetric::create('qtd', 'Quantidade'))
                ->width('1/3'),

            BigNumber::create('receita', 'Receita Total')
                ->metric(
                    SumMetric::create('valor', 'Valor')
                        ->column('valor_total')
                        ->color('#4CAF50')
                )
                ->width('1/3'),
        ];
    }
}
```

Com esse dashboard, um usuário da empresa de `id` `42` que filtra por período verá a seguinte query executada:

```sql
SELECT COUNT(*) AS `qtd`
FROM `pedidos`
WHERE `empresa_id` = 42
  AND `is_teste` = 0
  AND `created_at` BETWEEN '2024-01-01 00:00:00' AND '2024-03-31 23:59:59'
```

## Próximos Passos

← [Configurando Widgets e Filtros](02-widgets-filtros.md) | → [Autorização](04-autorizacao.md)
