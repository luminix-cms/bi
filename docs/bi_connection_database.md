# Configuração de Conexão Secundária com Banco de Dados no Luminix/BI

Este guia explica como configurar uma conexão secundária com outro banco de dados no sistema que utiliza o luminix/bi.

## 1. Configurar o `database.php`

Adicione a nova conexão no array `connections` do arquivo `config/database.php`:

```php
'connections' => [
    
    // ... outras conexões existentes

    'bi_connection' => [
        'driver' => 'mysql',
        'url' => env('BI_DB_URL'),
        'host' => env('BI_DB_HOST', 'bi-connection-mysql'),
        'port' => env('BI_DB_PORT', '3306'),
        'database' => env('BI_DB_DATABASE', 'laravel'),
        'username' => env('BI_DB_USERNAME', 'sail'),
        'password' => env('BI_DB_PASSWORD', 'password'),
        'unix_socket' => env('BI_DB_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => false,
        'engine' => null,
        'options' => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        ]) : [],
    ],
],
```

## 2. Configurar as Variáveis de Ambiente

Adicione as credenciais da conexão BI no arquivo `.env`:

```env
BI_DB_CONNECTION=bi_connection
BI_DB_HOST=bi-connection-mysql
BI_DB_PORT=3306
BI_DB_DATABASE=laravel
BI_DB_USERNAME=sail
BI_DB_PASSWORD=password
```

## 3. Configurar os Models

Os models que precisam utilizar a conexão BI devem estender `BiModel` ao invés de `Model`:

```php
<?php

namespace Luminix\ELearning\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Luminix\Backend\Model\LuminixModel;
use Luminix\Bi\Models\BiModel;

class School extends BiModel
{
    use HasUuids;
    use LuminixModel;

    protected $fillable = [
        'name',
        'city_id',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
```

## 4. Utilizar nos Dashboards

Depois de configurar o model, você pode utilizá-lo normalmente nos dashboards:

```php
<?php

namespace Luminix\ELearning\Dashboards;

use Luminix\Bi\BaseDashboard;
use Luminix\Bi\Filters\RelationFilter;
use Luminix\Bi\Widgets\BigNumber;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\ELearning\Models\School;
use Illuminate\Support\Facades\DB;

class SchoolsDashboard extends BaseDashboard
{
    public $model  = School::class;
    public $uriKey = 'school';
    public $name   = 'Escola';

    public function filters()
    {
        return [
            RelationFilter::create('city', 'Cidade')
                ->relation('city')
                ->otherColumn(DB::raw('COALESCE(cities.name, "Sem Registros") AS name')),

            RelationFilter::create('state', 'Estado')
                ->relation('city.state')
                ->otherColumn(DB::raw('COALESCE(states.name, "Sem Registros") AS name')),
        ];
    }

    public function widgets()
    {
        return [
            BigNumber::create('total_schools', 'Quantidade de Escolas')
                ->metric(
                    CountMetric::create('count', 'Registros')
                )
                ->width('1/4'),
        ];
    }
}
```

## Resumo

1. Configure a conexão `bi_connection` no `config/database.php`
2. Adicione as credenciais no arquivo `.env`
3. Faça seus models estenderem `BiModel`
4. Use os models normalmente em seus dashboards

A classe `BiModel` já gerencia automaticamente a conexão com o banco de dados secundário configurado.