# Criando um Dashboard

Um dashboard é uma classe PHP que estende `Luminix\Bi\Dashboard` e declara quais widgets exibir, quais filtros o usuário pode aplicar e qual é o escopo de dados da análise.

## Gerando com Artisan

```bash
php artisan bi:dashboard {Name} --model={Model}
```

Exemplo para um dashboard de vendas:

```bash
php artisan bi:dashboard SalesDashboard --model=Sale
```

O arquivo é criado em:

```
app/Bi/Dashboards/SalesDashboard.php
```

## O arquivo gerado

```php
<?php

namespace App\Bi\Dashboards;

use Luminix\Bi\Dashboard;

class SalesDashboard extends Dashboard
{
    public $model  = \App\Models\Sale::class;
    public $uriKey = 'salesDashboard';
    public $name   = 'SalesDashboard';

    public function filters(): array
    {
        return [];
    }

    public function widgets(): array
    {
        return [];
    }
}
```

## Propriedades

### `$model`

Model Eloquent que serve como base para todas as queries do dashboard. Todos os widgets partem de uma query sobre esse model.

```php
public $model = \App\Models\Sale::class;
```

### `$uriKey`

Identificador único do dashboard nas URLs da API. O valor padrão gerado pelo stub é o nome da classe em camelCase.

```php
public $uriKey = 'sales';
```

Esse valor determina os endpoints:

```
GET /bi-apis/sales/widgets
GET /bi-apis/sales/widgets/{widget}
```

O `$uriKey` deve ser único entre todos os dashboards da aplicação. Se dois dashboards compartilharem o mesmo valor, o segundo sobrescreverá o primeiro.

### `$name`

Nome legível retornado na resposta JSON e exibido pelo frontend como título do painel.

```php
public $name = 'Sales Dashboard';
```

## Exemplo completo

Dashboard de pedidos com um widget de contagem e um filtro de data:

```php
<?php

namespace App\Bi\Dashboards;

use Luminix\Bi\Dashboard;
use Luminix\Bi\Filters\DateFilter;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Widgets\BigNumber;

class OrdersDashboard extends Dashboard
{
    public $model  = \App\Models\Order::class;
    public $uriKey = 'orders';
    public $name   = 'Orders Dashboard';

    public function filters(): array
    {
        return [
            DateFilter::create('created_at', 'Order date'),
        ];
    }

    public function widgets(): array
    {
        return [
            BigNumber::create('total-orders', 'Total Orders')
                ->metric(
                    CountMetric::create('total', 'Total')
                        ->color('#4CAF50')
                )
                ->width('1/3'),
        ];
    }
}
```

Com esse dashboard registrado, a API responde em:

```
GET /bi-apis/orders/widgets/total-orders
```

## Próximos Passos

← [Configuração](../02-instalacao/02-configuracao.md) | → [Configurando Widgets e Filtros](02-widgets-filtros.md) | [Uso Básico](../uso-basico.md)
