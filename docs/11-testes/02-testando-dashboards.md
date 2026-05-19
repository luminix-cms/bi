# Testando seus Dashboards

O Luminix BI foi construído pensando em testabilidade. Como toda a lógica de dados passa pelo método `data()` do widget, você pode testar seus dashboards sem fazer chamadas HTTP — basta instanciar o dashboard, montar um `BiRequest` com os filtros desejados e verificar a collection retornada.

## Configuração Mínima com Orchestra Testbench

Os testes de pacote e de aplicações que usam o Luminix BI são executados com o **Orchestra Testbench**. Ele provisiona um ambiente Laravel completo em memória, sem necessidade de uma aplicação Laravel instalada separadamente.

Instale o Testbench como dependência de desenvolvimento:

```bash
composer require --dev orchestra/testbench
```

Seu `TestCase` base deve estender `Orchestra\Testbench\TestCase`:

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

### Por que registrar o `BiServiceProvider`?

O `BiServiceProvider` registra as rotas e o `DashboardResolver` no container. Embora os testes unitários de widgets não precisem das rotas, registrar o provider garante que o container do Laravel esteja configurado corretamente — especialmente se o dashboard usa injeção de dependências.

### Banco em Memória

O SQLite em memória (`database: ':memory:'`) é a configuração ideal para testes:

- Cada execução começa com um banco limpo
- Não requer configuração externa
- É significativamente mais rápido que um banco real

## Criando Tabelas nos Testes

Use `Schema::create()` no método `setUp()` para criar as tabelas necessárias:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

protected function setUp(): void
{
    parent::setUp();

    Schema::create('pedidos', function (Blueprint $table) {
        $table->id();
        $table->string('status');
        $table->decimal('valor', 10, 2)->default(0);
        $table->unsignedBigInteger('vendedor_id')->nullable();
        $table->timestamps();
    });
}
```

> O método `tearDown()` não precisa excluir as tabelas manualmente. Como o banco é em memória, ele é descartado automaticamente ao final de cada teste.

## Instanciando o `BiRequest`

O `BiRequest` é um wrapper sobre o `Illuminate\Http\Request` padrão do Laravel. Para criar um `BiRequest` com filtros simulados nos testes, basta instanciá-lo a partir de um `Request`:

```php
use Illuminate\Http\Request;
use Luminix\Bi\Support\BiRequest;

// Sem filtros
$biRequest = new BiRequest(new Request());

// Com filtros
$biRequest = new BiRequest(new Request([
    'filters' => [
        'status'     => ['confirmado', 'processando'],
        'created_at' => ['start' => '2024-01-01', 'end' => '2024-12-31'],
    ]
]));

// Com parâmetro de ordenação (para Table)
$biRequest = new BiRequest(new Request([
    'sort' => ['col' => 'valor', 'dir' => 'desc'],
]));
```

## Exemplo Completo: Teste de um Widget `Table` com Filtro

O exemplo a seguir testa um widget `Table` que exibe pedidos agrupados por status, com um filtro de intervalo de datas.

```php
<?php

namespace Tests\Bi;

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
use App\Models\Pedido;

class PedidosDashboardTest extends TestCase
{
    // Passo 1: Criar tabela e inserir registros de teste
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->decimal('valor', 10, 2)->default(0);
            $table->timestamps();
        });

        DB::table('pedidos')->insert([
            ['status' => 'confirmado', 'valor' => 150.00, 'created_at' => '2024-03-10', 'updated_at' => '2024-03-10'],
            ['status' => 'confirmado', 'valor' => 200.00, 'created_at' => '2024-03-15', 'updated_at' => '2024-03-15'],
            ['status' => 'cancelado',  'valor' =>  80.00, 'created_at' => '2024-03-20', 'updated_at' => '2024-03-20'],
            ['status' => 'confirmado', 'valor' => 320.00, 'created_at' => '2024-04-05', 'updated_at' => '2024-04-05'],
        ]);
    }

    // Passo 2: Instanciar o dashboard
    private function makeDashboard(): Dashboard
    {
        return new class extends Dashboard {
            public $uriKey = 'pedidos';
            public $name   = 'Pedidos';
            public $model  = Pedido::class;

            public function widgets(): array
            {
                return [];
            }

            public function filters(): array
            {
                return [
                    DateIntervalFilter::create('created_at', 'Período'),
                ];
            }
        };
    }

    public function test_tabela_agrupa_pedidos_por_status(): void
    {
        $widget = Table::create('pedidos-por-status', 'Pedidos por Status')
            ->dimensions([StringDimension::create('status', 'Status')])
            ->metrics([
                CountMetric::create('quantidade', 'Quantidade'),
                SumMetric::create('total', 'Total')->column('valor'),
            ]);

        // Passo 3: Criar BiRequest sem filtros
        $biRequest = new BiRequest(new Request());

        // Passo 4: Chamar data()
        $result = $widget->data($this->makeDashboard(), $biRequest);

        // Passo 5: Verificar a collection retornada
        $this->assertCount(2, $result); // 'confirmado' e 'cancelado'

        $confirmado = $result->firstWhere('status', 'confirmado');
        $this->assertEquals(3, $confirmado->quantidade);
        $this->assertEquals('670.00', $confirmado->total);
    }

    public function test_filtro_de_data_restringe_registros(): void
    {
        $widget = Table::create('pedidos-por-status', 'Pedidos por Status')
            ->dimensions([StringDimension::create('status', 'Status')])
            ->metrics([CountMetric::create('quantidade', 'Quantidade')]);

        // Apenas março de 2024
        $biRequest = new BiRequest(new Request([
            'filters' => [
                'created_at' => [
                    'start' => '2024-03-01',
                    'end'   => '2024-03-31',
                ],
            ],
        ]));

        $result = $widget->data($this->makeDashboard(), $biRequest);

        // O pedido de abril não deve aparecer
        $confirmado = $result->firstWhere('status', 'confirmado');
        $this->assertEquals(2, $confirmado->quantidade);
    }
}
```

## Testando Autorização com `viewable()`

O método `viewable()` da classe `Dashboard` controla se o dashboard aparece na listagem da API. Por padrão retorna `true`. Para testar comportamentos condicionais — como mostrar o dashboard apenas para usuários administradores — você pode mockar o usuário autenticado com as helpers do Laravel:

```php
use Illuminate\Support\Facades\Auth;

public function test_dashboard_nao_visivel_para_usuario_comum(): void
{
    // Cria um usuário simples (sem role admin)
    $user = new \Illuminate\Foundation\Auth\User();
    $user->forceFill(['id' => 1, 'is_admin' => false]);
    Auth::setUser($user);

    $dashboard = new class extends Dashboard {
        public $uriKey = 'financeiro';
        public $name   = 'Financeiro';
        public $model  = \App\Models\Pedido::class;

        public function viewable(): bool
        {
            return Auth::user()?->is_admin === true;
        }

        public function widgets(): array { return []; }
        public function filters(): array { return []; }
    };

    $this->assertFalse($dashboard->viewable());
}

public function test_dashboard_visivel_para_administrador(): void
{
    $admin = new \Illuminate\Foundation\Auth\User();
    $admin->forceFill(['id' => 2, 'is_admin' => true]);
    Auth::setUser($admin);

    $dashboard = new class extends Dashboard {
        public $uriKey = 'financeiro';
        public $name   = 'Financeiro';
        public $model  = \App\Models\Pedido::class;

        public function viewable(): bool
        {
            return Auth::user()?->is_admin === true;
        }

        public function widgets(): array { return []; }
        public function filters(): array { return []; }
    };

    $this->assertTrue($dashboard->viewable());
}
```

## Testando o Endpoint HTTP Completo

Para testar a resposta HTTP do endpoint, use os helpers de teste HTTP do Orchestra Testbench. Primeiro, registre o dashboard no `DashboardResolver` — a forma mais simples em testes é fazer a requisição HTTP usando o `actingAs`:

```php
public function test_endpoint_retorna_dados_do_widget(): void
{
    $user = new \Illuminate\Foundation\Auth\User();
    $user->forceFill(['id' => 1]);
    Auth::setUser($user);

    $response = $this->actingAs($user)
                     ->getJson('/bi-apis/pedidos/widgets/pedidos-por-status?filters[created_at][start]=2024-03-01&filters[created_at][end]=2024-03-31');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'status',
        'data' => [
            '*' => ['status', 'quantidade'],
        ],
    ]);
}
```

> Para que o `DashboardResolver` encontre seus dashboards, eles devem estar em `app/Bi/Dashboards/` e estender `Luminix\Bi\Dashboard`. O resolver escaneia esse diretório automaticamente ao ser instanciado.

## Próximos Passos

[← Executando os Testes](01-executando.md)
