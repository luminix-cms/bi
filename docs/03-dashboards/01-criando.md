# Criando um Dashboard

Pense em um dashboard como um painel de controle de carro: ele define quais instrumentos (widgets) aparecem na tela, quais controles (filtros) o motorista pode usar para refinar a visualização, e qual é o escopo de dados que o painel deve mostrar. No Luminix BI, cada dashboard é uma classe PHP que estende `Luminix\Bi\Dashboard` e declara essas três responsabilidades.

## Gerando um dashboard com Artisan

O Luminix BI fornece um comando Artisan para criar dashboards a partir de um stub:

```bash
php artisan bi:dashboard NomeDashboard --model=NomeModelo
```

O argumento `NomeDashboard` é o nome da classe PHP a ser gerada. A opção `--model` define qual model Eloquent será a base das queries desse dashboard.

Exemplo para um dashboard de vendas:

```bash
php artisan bi:dashboard VendasDashboard --model=Venda
```

O arquivo será criado em:

```
app/Bi/Dashboards/VendasDashboard.php
```

## O arquivo gerado

O stub gerado pelo comando tem a seguinte estrutura:

```php
<?php

namespace App\Bi\Dashboards;

use Luminix\Bi\Dashboard;

class VendasDashboard extends Dashboard
{
    public $model  = \App\Models\Venda::class;
    public $uriKey = 'vendasDashboard';
    public $name   = 'VendasDashboard';

    public function filters()
    {
        return [];
    }

    public function widgets()
    {
        return [];
    }
}
```

### Propriedade `$model`

Define qual model Eloquent serve como base para todas as queries desse dashboard. Todos os widgets do dashboard partem de uma query sobre esse model, a menos que o widget sobrescreva seu próprio escopo.

```php
public $model = \App\Models\Venda::class;
```

### Propriedade `$uriKey`

Identificador único do dashboard na URL. O `DashboardResolver` usa esse valor para indexar os dashboards no container e para fazer a correspondência com os parâmetros `{dashboard}` nas rotas da API.

O valor padrão gerado pelo stub é o nome da classe em camelCase. Por exemplo, `VendasDashboard` gera `vendasDashboard`.

```php
public $uriKey = 'vendasDashboard';
```

Esse valor determina diretamente as URLs dos endpoints:

```
GET /bi-apis/vendasDashboard/widgets
GET /bi-apis/vendasDashboard/widgets/{widget}
```

### Propriedade `$name`

Nome legível do dashboard, retornado na resposta JSON da API. É exibido pelo frontend como título do painel.

```php
public $name = 'VendasDashboard';
```

## Renomeando o `$uriKey`

O valor gerado pelo stub (`vendasDashboard`) pode ser substituído por qualquer string. A convenção recomendada é usar kebab-case para URLs mais legíveis:

```php
public $uriKey = 'vendas';
public $name   = 'Dashboard de Vendas';
```

Com essa mudança, os endpoints passam a responder em:

```
GET /bi-apis/vendas/widgets
GET /bi-apis/vendas/widgets/total-pedidos
GET /bi-apis/vendas/widgets/total-pedidos/csv
GET /bi-apis/vendas/filters/created_at
```

> O `$uriKey` deve ser único entre todos os dashboards da aplicação. Se dois dashboards tiverem o mesmo `$uriKey`, o segundo sobrescreverá o primeiro no índice do `DashboardResolver`, e o primeiro ficará inacessível.

## Exemplo completo com um widget e um filtro

O exemplo abaixo mostra um dashboard funcional para análise de pedidos, com um widget de contagem e um filtro de data:

```php
<?php

namespace App\Bi\Dashboards;

use Luminix\Bi\Dashboard;
use Luminix\Bi\Widgets\BigNumber;
use Luminix\Bi\Filters\DateFilter;
use Luminix\Bi\Metrics\CountMetric;

class PedidosDashboard extends Dashboard
{
    public $model  = \App\Models\Pedido::class;
    public $uriKey = 'pedidos';
    public $name   = 'Dashboard de Pedidos';

    public function filters()
    {
        return [
            DateFilter::create('created_at', 'Data do pedido'),
        ];
    }

    public function widgets()
    {
        return [
            BigNumber::create('total-pedidos', 'Total de Pedidos')
                ->metric(
                    CountMetric::create('total', 'Total')
                        ->color('#4CAF50')
                )
                ->width('1/3'),
        ];
    }
}
```

Com esse dashboard registrado, a API responde ao seguinte endpoint:

```
GET /bi-apis/pedidos/widgets/total-pedidos
```

A query gerada internamente é equivalente a:

```sql
SELECT COUNT(*) AS `total`
FROM `pedidos`
```

Quando o filtro de data é passado na requisição:

```
GET /bi-apis/pedidos/widgets/total-pedidos?filters[created_at][]=2024-03-15
```

A query passa a incluir a cláusula `WHERE`:

```sql
SELECT COUNT(*) AS `total`
FROM `pedidos`
WHERE `created_at` BETWEEN '2024-03-15 00:00:00' AND '2024-03-15 23:59:59'
```

## Próximos Passos

← [Rotas da API](../02-instalacao/03-rotas.md) | → [Configurando Widgets e Filtros](02-widgets-filtros.md)
