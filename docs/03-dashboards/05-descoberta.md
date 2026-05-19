# Descoberta Automática de Dashboards

Em vez de exigir que o desenvolvedor registre cada dashboard manualmente em um arquivo de configuração, o Luminix BI usa um mecanismo de descoberta automática: ao primeiro request que acessa a API, o pacote escaneia o diretório de dashboards, instancia cada classe elegível e monta um índice em memória. Nas requisições seguintes, o índice já está pronto.

## Como o `DashboardResolver` funciona

O `DashboardResolver` é a classe responsável por encontrar, filtrar e indexar os dashboards disponíveis. Seu construtor é executado uma única vez por request, graças ao registro como singleton no container do Laravel.

O processo de descoberta segue estas etapas:

### 1. Escaneamento do diretório

O `DashboardResolver` usa o componente `Symfony\Component\Finder\Finder` para listar todos os arquivos PHP dentro de `app/Bi/Dashboards/`:

```php
$directory = app_path('Bi/Dashboards');
$namespace = app()->getNamespace();

foreach ((new Finder())->in($directory)->files() as $dashboard) {
    // converte o caminho do arquivo para o nome da classe PHP
    $dashboard = $namespace . str_replace(
        ['/', '.php'],
        ['\\', ''],
        Str::after($dashboard->getPathname(), app_path() . DIRECTORY_SEPARATOR)
    );
    // ...
}
```

O `Finder` retorna os arquivos em ordem alfabética por padrão. Não há uma garantia de ordem específica além disso.

### 2. Verificação de elegibilidade

Para cada arquivo encontrado, o `DashboardResolver` verifica três critérios:

```php
if (is_subclass_of($dashboard, Dashboard::class) &&
    !(new ReflectionClass($dashboard))->isAbstract()) {
    $dashboardInstance = App::make($dashboard);
    if ($dashboardInstance->viewable()) {
        $this->dashboards->put($dashboardInstance->uriKey, $dashboardInstance);
    }
}
```

| Critério | Verificação | Por quê |
|---|---|---|
| Subclasse de `Dashboard` | `is_subclass_of()` | Garante que apenas dashboards válidos são carregados |
| Não abstrata | `ReflectionClass::isAbstract()` | Evita tentar instanciar classes base ou parciais |
| `viewable()` retorna `true` | Chamada ao método de instância | Filtra por autorização do usuário atual |

### 3. Indexação pelo `$uriKey`

Dashboards que passam pelos três critérios são adicionados a uma `Collection` indexada pelo `$uriKey`:

```php
$this->dashboards->put($dashboardInstance->uriKey, $dashboardInstance);
```

Isso permite que os controllers busquem um dashboard em O(1) pelo `uriKey` passado na URL, sem precisar reiterar a lista a cada requisição.

## Comportamento de singleton

O `DashboardResolver` é registrado como singleton no container pelo `BiServiceProvider`:

```php
$this->app->singleton(DashboardResolver::class, function () {
    return new DashboardResolver();
});
```

Isso significa que o construtor — e portanto todo o processo de escaneamento e instanciação — é executado **uma única vez por ciclo de vida do request**. Em servidores com workers persistentes (como Octane ou FrankenPHP), o singleton persiste entre requests, o que melhora a performance mas requer atenção: se o método `viewable()` depende do usuário autenticado no momento do request, o singleton pode retornar resultados de um request anterior.

> Em aplicações que usam Laravel Octane, considere registrar o `DashboardResolver` como `scoped` em vez de `singleton` para garantir que a descoberta aconteça uma vez por request e não uma vez por worker.

## Cada dashboard é indexado pelo `$uriKey`

O `$uriKey` é a chave primária do índice. Se dois dashboards em `app/Bi/Dashboards/` tiverem o mesmo `$uriKey`, o segundo encontrado pelo `Finder` sobrescreverá o primeiro no índice sem avisar. Mantenha os `$uriKey` únicos na aplicação.

Para consultar o índice programaticamente:

```php
use Luminix\Bi\DashboardResolver;

// Buscar um dashboard específico
$dashboard = app(DashboardResolver::class)->find('vendas');

// Listar todos os dashboards visíveis
$todos = app(DashboardResolver::class)->all();

// Obter o primeiro dashboard registrado
$primeiro = app(DashboardResolver::class)->first();
```

## Limitação atual: diretório fixo

O `DashboardResolver` escaneia exclusivamente o diretório `app/Bi/Dashboards/`. Esse caminho não é configurável via `config/luminix/bi.php`. Não é possível mover dashboards para outro diretório e ter a descoberta automática funcionando sem modificar o pacote.

Consequências práticas:

- Todos os dashboards devem estar diretamente em `app/Bi/Dashboards/` ou em subdiretórios dentro dele
- O `Finder` é chamado com `.in($directory)` sem restrições de profundidade, portanto subdiretórios são escaneados automaticamente

Exemplo de estrutura com subdiretórios:

```
app/Bi/Dashboards/
├── Financeiro/
│   ├── ReceitaDashboard.php      ← descoberto automaticamente
│   └── DespesasDashboard.php     ← descoberto automaticamente
├── Operacional/
│   └── PedidosDashboard.php      ← descoberto automaticamente
└── UserDashboard.php             ← descoberto automaticamente
```

Todos os arquivos acima serão descobertos, desde que as classes satisfaçam os três critérios de elegibilidade.

## Ordem de exibição na API

O endpoint `GET /bi-apis/dashboards` retorna os dashboards na ordem em que foram inseridos na `Collection` do `DashboardResolver`. Como o `Finder` ordena por nome de arquivo, a ordem é alfabética. Para controlar a ordem de exibição no frontend, a opção mais simples é prefixar os nomes dos arquivos com números:

```
app/Bi/Dashboards/
├── 01-ResumoExecutivoDashboard.php
├── 02-VendasDashboard.php
└── 03-OperacionalDashboard.php
```

## Próximos Passos

← [Autorização](04-autorizacao.md) | → [Visão Geral dos Widgets](../04-widgets/01-visao-geral.md)
