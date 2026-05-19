# Segurança nas Queries

O middleware garante que apenas usuários autorizados acessem as rotas do BI. O `viewable()` garante que cada usuário veja apenas os dashboards permitidos. Mas há uma terceira camada de segurança que opera dentro das queries: o `QueryService`, responsável por garantir que, mesmo dentro de um dashboard autorizado, o usuário só receba os registros que tem permissão de visualizar.

## O Papel do `QueryService`

O `QueryService` é o ponto central de criação de queries no pacote. Toda vez que um widget, métrica, dimensão ou filtro precisa iniciar uma query Eloquent, ele passa pelo `QueryService.create()` ao invés de chamar `Model::query()` diretamente.

Isso centraliza três responsabilidades em um único lugar:

1. Escolher a conexão de banco de dados correta
2. Aplicar o escopo de segurança do `luminix/backend`, se aplicável
3. Garantir comportamento consistente em todo o pacote

```php
class QueryService
{
    public static function create(string|Model $object): Builder
    {
        $connection = config('luminix.bi.connection');

        $query = $connection ? $object::on($connection) : $object::query();

        if (config('luminix.backend.security.gates_enabled', true)
            && Finder::isLuminixModel($object)) {
            $query->allowed(
                config('luminix.backend.security.permissions.index', 'read')
            );
        }

        return $query;
    }
}
```

## Integração com `luminix/backend`

O pacote `luminix/backend` implementa um sistema de autorização baseado em Gates para modelos Eloquent. Quando ativado, cada modelo registrado no backend define um Gate de leitura — por exemplo, `read-pedidos` — que o Laravel usa para verificar se o usuário tem permissão de leitura sobre registros daquele modelo.

O `QueryService` detecta se o modelo é um "modelo Luminix" através do `Finder::isLuminixModel()`. Se for, ele encadeia o escopo `->allowed('read')` na query antes de devolvê-la.

### O que `->allowed('read')` Faz

O escopo `->allowed('read')` é um escopo Eloquent fornecido pelo `luminix/backend`. Ele adiciona condições à query que restringem os registros retornados àqueles que o usuário autenticado tem permissão de ler, conforme a lógica definida no Gate de leitura do modelo.

O efeito prático é que um analista que só tem acesso às filiais da região Sul nunca receberá dados de outras regiões nos widgets do BI — mesmo que o dashboard não tenha um filtro explícito para isso. A restrição é aplicada automaticamente pela camada de segurança do backend.

## A Configuração `gates_enabled`

A integração com o sistema de gates pode ser desativada através da configuração do `luminix/backend`:

```php
// config/backend.php (ou equivalente)
'security' => [
    'gates_enabled' => false,
]
```

Ou via `.env`:

```bash
LUMINIX_BACKEND_GATES_ENABLED=false
```

Quando `gates_enabled` é `false`, o `QueryService` pula a aplicação do `->allowed()` e a query retorna todos os registros sem restrição por gate. Isso é útil em ambientes de desenvolvimento onde você quer ver todos os dados sem configurar gates, mas **não deve ser usado em produção** em sistemas onde o controle de acesso por registro é necessário.

## Para Modelos Não-Luminix

Se o modelo do dashboard não for um modelo Luminix (ou seja, não estiver registrado no `luminix/backend`), o `QueryService` simplesmente usa `$model::query()` ou `$model::on($connection)` sem nenhuma restrição extra. Nesses casos, a segurança nos dados retornados é de responsabilidade do desenvolvedor — você pode usar o método `scope()` do dashboard para restringir os registros base.

```php
// Dashboard com modelo não-Luminix
class PedidosDashboard extends Dashboard
{
    public string $model = \App\Models\Pedido::class;

    public function scope(Builder $builder): Builder
    {
        // Restrição manual: apenas pedidos do tenant atual
        return $builder->where('empresa_id', auth()->user()->empresa_id);
    }
}
```

## Conexão Dedicada com `BI_DB_CONNECTION`

O `QueryService` também é responsável por garantir que todas as queries do BI usem a conexão de banco de dados configurada em `BI_DB_CONNECTION`:

```php
$query = $connection ? $object::on($connection) : $object::query();
```

O método `->on($connection)` do Eloquent instrui o model a usar aquela conexão específica para aquela query, sem alterar a conexão padrão do modelo. Isso garante que todas as queries — widgets, filtros, opções de dropdown — usem a mesma conexão dedicada, seja um banco de leitura, uma réplica ou um banco analítico separado.

## Segurança com `RawMetric` e `RawDimension`

`RawMetric` e `RawDimension` permitem escrever expressões SQL arbitrárias diretamente no código PHP. Essa flexibilidade vem com uma responsabilidade importante: **nunca passe input do usuário diretamente em uma expressão raw**.

Exemplo de uso seguro:

```php
use Luminix\Bi\Metrics\RawMetric;

// CORRETO: expressão fixa definida no código PHP
RawMetric::create('margem', 'Margem Bruta')
    ->expression('SUM(valor_venda - custo_produto)')
```

Exemplo de uso inseguro — nunca faça isso:

```php
// INCORRETO: input do usuário passado diretamente no SQL
$coluna = $request->input('coluna');
RawMetric::create('valor', 'Valor')
    ->expression("SUM($coluna)") // risco de SQL injection
```

O `QueryService` não tem como proteger contra SQL injection em expressões raw porque elas são injetadas no SQL antes da execução pelo banco de dados, sem passar por prepared statements. A responsabilidade é exclusivamente do desenvolvedor que escreve a expressão.

> Use `RawMetric` e `RawDimension` apenas com valores fixos definidos no código PHP. Se a expressão SQL precisa variar com base em alguma entrada, valide e sanitize rigorosamente antes de usar, ou prefira uma métrica customizada que use os métodos seguros do Eloquent Builder.

## Próximos Passos

← [Controle de Acesso por Dashboard](02-dashboard.md) | → [Criando Métricas Customizadas](../09-extensibilidade/01-metrica-customizada.md)
