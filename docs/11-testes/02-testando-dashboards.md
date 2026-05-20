# Testando seus Dashboards

O Luminix BI foi construído pensando em testabilidade. Você pode testar seus dashboards instanciando-os diretamente e chamando `data()` no widget, ou via HTTP com os helpers do Orchestra Testbench.

## Configuração com Orchestra Testbench

Adicione o Testbench como dependência de desenvolvimento:

```bash
composer require --dev orchestra/testbench
```

Crie um `TestCase` base que registra o `BiServiceProvider` e configura o SQLite em memória:

```php
<?php

namespace Tests\Bi;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            \Luminix\Bi\BiServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
```

## Exemplo Completo: Testando um Dashboard de Vendas

O exemplo abaixo cobre os casos mais comuns: verificar os dados retornados pelo widget e verificar que um filtro restringe os resultados corretamente.

```php
<?php

namespace Tests\Bi;

use App\Models\Order;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Luminix\Bi\Dashboard;
use Luminix\Bi\Dimensions\StringDimension;
use Luminix\Bi\Filters\DateIntervalFilter;
use Luminix\Bi\Metrics\CountMetric;
use Luminix\Bi\Metrics\SumMetric;
use Luminix\Bi\Support\BiRequest;
use Luminix\Bi\Widgets\Table;

class SalesDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        DB::table('orders')->insert([
            ['status' => 'confirmed', 'total_amount' => 150.00, 'created_at' => '2024-03-10', 'updated_at' => '2024-03-10'],
            ['status' => 'confirmed', 'total_amount' => 200.00, 'created_at' => '2024-03-15', 'updated_at' => '2024-03-15'],
            ['status' => 'cancelled', 'total_amount' =>  80.00, 'created_at' => '2024-03-20', 'updated_at' => '2024-03-20'],
            ['status' => 'confirmed', 'total_amount' => 320.00, 'created_at' => '2024-04-05', 'updated_at' => '2024-04-05'],
        ]);
    }

    private function makeDashboard(): Dashboard
    {
        return new class extends Dashboard {
            public $uriKey = 'orders';
            public $name   = 'Orders';
            public $model  = Order::class;

            public function widgets(): array { return []; }

            public function filters(): array
            {
                return [
                    DateIntervalFilter::create('created_at', 'Period'),
                ];
            }
        };
    }

    public function test_groups_orders_by_status(): void
    {
        $widget = Table::create('orders-by-status', 'Orders by Status')
            ->dimensions([StringDimension::create('status', 'Status')])
            ->metrics([
                CountMetric::create('count', 'Count'),
                SumMetric::create('revenue', 'Revenue')->column('total_amount'),
            ]);

        $result = $widget->data($this->makeDashboard(), new BiRequest(new Request()));

        $this->assertCount(2, $result);

        $confirmed = $result->firstWhere('status', 'confirmed');
        $this->assertEquals(3, $confirmed->count);
        $this->assertEquals('670.00', $confirmed->revenue);
    }

    public function test_date_filter_restricts_results(): void
    {
        $widget = Table::create('orders-by-status', 'Orders by Status')
            ->dimensions([StringDimension::create('status', 'Status')])
            ->metrics([CountMetric::create('count', 'Count')]);

        $biRequest = new BiRequest(new Request([
            'filters' => [
                'created_at' => ['start' => '2024-03-01', 'end' => '2024-03-31'],
            ],
        ]));

        $result = $widget->data($this->makeDashboard(), $biRequest);

        // April order must not appear
        $confirmed = $result->firstWhere('status', 'confirmed');
        $this->assertEquals(2, $confirmed->count);
    }
}
```

## Testando Endpoints HTTP

Para testar a resposta HTTP completa, use os helpers do Testbench. O dashboard precisa ser descoberto automaticamente pelo `DashboardResolver` — isso ocorre quando a classe está em `app/Bi/Dashboards/` e estende `Luminix\Bi\Dashboard`.

```php
public function test_widget_endpoint_returns_data(): void
{
    $user = new \Illuminate\Foundation\Auth\User();
    $user->forceFill(['id' => 1]);

    $response = $this->actingAs($user)
        ->getJson('/bi-apis/orders/widgets/orders-by-status?filters[created_at][start]=2024-03-01&filters[created_at][end]=2024-03-31');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'status',
        'data' => [
            '*' => ['status', 'count'],
        ],
    ]);
}
```

## Testando Autorização com `viewable()`

```php
public function test_dashboard_not_visible_to_regular_user(): void
{
    $user = new \Illuminate\Foundation\Auth\User();
    $user->forceFill(['id' => 1, 'is_admin' => false]);
    \Illuminate\Support\Facades\Auth::setUser($user);

    $dashboard = new class extends Dashboard {
        public $uriKey = 'financials';
        public $name   = 'Financials';
        public $model  = Order::class;

        public function viewable(): bool
        {
            return \Illuminate\Support\Facades\Auth::user()?->is_admin === true;
        }

        public function widgets(): array { return []; }
        public function filters(): array { return []; }
    };

    $this->assertFalse($dashboard->viewable());
}
```

## Próximos Passos

[← Executando os Testes](01-executando.md) | [← Uso Básico](../uso-basico.md)
