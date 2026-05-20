# Descoberta Automática de Dashboards

O Luminix BI descobre dashboards automaticamente. Basta criar a classe no diretório correto — nenhum registro manual é necessário.

## Onde colocar os dashboards

O diretório padrão é:

```
app/Bi/Dashboards/
```

Subdiretórios são suportados e escaneados automaticamente:

```
app/Bi/Dashboards/
├── Financial/
│   ├── RevenueDashboard.php      ← descoberto automaticamente
│   └── ExpensesDashboard.php     ← descoberto automaticamente
├── Operations/
│   └── OrdersDashboard.php       ← descoberto automaticamente
└── UserDashboard.php             ← descoberto automaticamente
```

## Critérios para descoberta

Uma classe é registrada se atender a todos estes critérios:

1. Está em `app/Bi/Dashboards/` (ou em subdiretório)
2. Estende `Luminix\Bi\Dashboard`
3. Não é abstrata
4. O método `viewable()` retorna `true` para o usuário atual

## Comportamento de singleton

O processo de descoberta ocorre uma única vez por ciclo de vida do request. O resultado é armazenado em memória e reutilizado em todas as requisições subsequentes do mesmo ciclo.

> Em aplicações que usam Laravel Octane ou workers persistentes, o singleton persiste entre requests. Se o método `viewable()` depende do usuário autenticado, considere registrar o resolver como `scoped` no container para garantir a descoberta por request.

## Ordem de exibição

Os dashboards são descobertos em ordem alfabética por nome de arquivo. Para controlar a ordem de exibição no frontend, prefixe os arquivos com números:

```
app/Bi/Dashboards/
├── 01-SummaryDashboard.php
├── 02-SalesDashboard.php
└── 03-OperationsDashboard.php
```

## Unicidade do `$uriKey`

O `$uriKey` é a chave primária do índice de dashboards. Se dois dashboards tiverem o mesmo `$uriKey`, o segundo sobrescreverá o primeiro silenciosamente. Mantenha os valores únicos na aplicação.

## Acessando o índice programaticamente

```php
use Luminix\Bi\DashboardResolver;

// Buscar um dashboard específico
$dashboard = app(DashboardResolver::class)->find('sales');

// Listar todos os dashboards visíveis
$all = app(DashboardResolver::class)->all();
```

## Próximos Passos

← [Autorização](04-autorizacao.md) | → [Visão Geral dos Widgets](../04-widgets/01-visao-geral.md)
