# Visão Geral dos Filtros

Pense em um filtro como um painel de controle que o usuário usa para recortar os dados que quer visualizar. No nível do banco de dados, cada filtro se traduz em uma cláusula `WHERE` opcional — opcional porque se o usuário não escolher nenhum valor para aquele filtro, a query roda normalmente sem aquela restrição.

No Luminix BI, um filtro é declarado uma única vez no dashboard e aplicado automaticamente em todos os widgets. O usuário escolhe os valores na interface, o front-end envia esses valores na request, e o pacote injeta os `WHERE` correspondentes em cada query antes de executá-la.

## A Classe `BaseFilter`

Toda implementação de filtro estende a classe abstrata `Luminix\Bi\Filters\BaseFilter`. Essa classe define as propriedades fundamentais e o contrato que cada filtro deve cumprir.

### Propriedades

| Propriedade | Tipo | Papel |
|---|---|---|
| `$key` | `string` | Identificador do filtro; usado como chave em `$request->input('filters')` |
| `$name` | `string` | Nome legível enviado ao front-end como rótulo do controle |
| `$column` | `string` | Coluna do banco usada no `WHERE` (padrão: igual ao `$key`) |
| `$defaultValue` | `mixed` | Valor padrão que o front-end deve pré-carregar no controle |

O `$key` é o identificador pelo qual o back-end localiza os dados do filtro dentro da request. Por exemplo, se `$key = 'status'`, o back-end vai buscar o valor em `$request->input('filters.status')`.

O `$name` é exclusivamente informativo — vai para o front-end como rótulo legível para o usuário e não tem nenhum efeito no SQL.

O `$component` é uma string definida pelas subclasses que indica ao front-end qual componente de UI deve ser renderizado para aquele filtro (um dropdown, um campo de número, um seletor de data, etc.).

### O Método Abstrato `apply()`

```php
abstract public function apply(Builder $builder, array $filterData, BiRequest $request): Builder;
```

Esse é o único método obrigatório em um filtro concreto. Ele recebe o `Builder` Eloquent já parcialmente construído e deve retornar o builder com a cláusula `WHERE` adicionada.

O parâmetro `$filterData` é exatamente o que veio de `$request->input('filters')[$key]` — cada implementação define o formato esperado para esse array.

### O Construtor Estático `create()`

Todos os filtros são instanciados via método estático:

```php
use Luminix\Bi\Filters\StringFilter;

StringFilter::create('status', 'Status do Pedido')
```

O primeiro argumento é o `$key`; o segundo é o `$name`.

## Declarando Filtros no Dashboard

Os filtros são declarados no método `filters()` do dashboard e se aplicam a **todos os widgets** daquele dashboard automaticamente:

```php
use Luminix\Bi\Dashboard;
use Luminix\Bi\Filters\StringFilter;
use Luminix\Bi\Filters\DateIntervalFilter;
use Carbon\Carbon;

class PedidosDashboard extends Dashboard
{
    public string $uriKey = 'pedidos';
    public string $name = 'Pedidos';
    public string $model = \App\Models\Pedido::class;

    public function widgets(): array
    {
        return [
            // widgets aqui
        ];
    }

    public function filters(): array
    {
        return [
            StringFilter::create('status', 'Status'),
            DateIntervalFilter::create('periodo', 'Período')
                ->column('created_at')
                ->defaultDates(
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ),
        ];
    }
}
```

Quando a request chegar com `filters[status][]=aprovado&filters[periodo][start]=2024-01-01&filters[periodo][end]=2024-01-31`, o pacote injeta automaticamente os `WHERE` correspondentes em todos os widgets do dashboard.

## Como os Filtros São Aplicados

O `BaseWidget` contém o seguinte mecanismo de aplicação:

```php
protected function applyFilters(Builder $builder, Dashboard $dashboard, BiRequest $request)
{
    $requestedFilters = $request->filters(); // $request->input('filters', [])
    return collect($dashboard->filters())->reduce(function ($builder, $filter) use ($request, $requestedFilters) {
        if (isset($requestedFilters[$filter->key])) {
            return $filter->apply($builder, $requestedFilters[$filter->key], $request);
        }
        return $builder; // filtro ausente → query sem WHERE
    }, $builder);
}
```

O ponto central desse código é a condição `isset($requestedFilters[$filter->key])`. Se o front-end não enviar um valor para determinado filtro, o builder é retornado inalterado — nenhuma cláusula `WHERE` é adicionada para aquele filtro, e a query retorna todos os registros como se o filtro não existisse.

Isso significa que **filtros são aditivos e opcionais por design**: adicionar um filtro ao dashboard não restringe os dados por padrão. A restrição só ocorre quando o usuário escolhe um valor e o front-end envia esse valor na request.

## O Método `->column()`

Por padrão, o pacote assume que a coluna no banco de dados tem o mesmo nome que o `$key` do filtro. Quando eles diferem, use `->column()`:

```php
// O filtro se chama 'periodo' no front-end,
// mas a coluna no banco é 'created_at'
DateIntervalFilter::create('periodo', 'Período')
    ->column('created_at')
```

Sem `->column()`, o filtro tentaria aplicar o `WHERE` na coluna `periodo`, que provavelmente não existe no banco e causaria um erro de SQL.

## O Método `->defaultValue()`

O `$defaultValue` é um metadado enviado ao front-end para que ele pré-carregue o controle com um valor inicial. **Ele não é aplicado automaticamente no back-end.** Se o front-end não enviar o valor na request, o filtro não será aplicado — o `defaultValue` apenas informa ao front-end o que exibir inicialmente.

```php
StringFilter::create('status', 'Status')
    ->defaultValue(['aprovado'])
```

O front-end receberá `"defaultValue": ["aprovado"]` no schema do filtro e pode usar esse valor para pré-selecionar "aprovado" na interface.

> O `defaultValue` é uma sugestão para o front-end, não uma restrição automática do back-end. Se quiser restringir os dados independentemente da request, use o método `scope()` do dashboard.

## O Método `extra()`

O método `extra()` retorna dados adicionais que o front-end precisa para montar o controle de UI. Por exemplo, um dropdown precisa saber quais opções exibir.

```php
public function extra(Dashboard $dashboard, BiRequest $request): array
{
    return []; // padrão: sem dados extras
}
```

Esse método é chamado pelo endpoint `/bi-apis/{dashboard}/filters/{filter}` quando o front-end precisa carregar as opções. A implementação padrão em `BaseFilter` retorna um array vazio, mas cada subclasse pode sobrescrever o método para retornar os dados necessários.

Por exemplo, o `StringFilter` sobrescreve `extra()` para retornar os valores distintos da coluna, e o `RelationFilter` retorna a lista de registros do modelo relacionado com `id` e nome para montar o dropdown.

## O Campo `$component`

Cada subclasse concreta de `BaseFilter` define uma propriedade `$component` com uma string que identifica o tipo de controle de UI esperado pelo front-end:

| Filtro | `$component` |
|---|---|
| `StringFilter` | `'string'` |
| `NumberFilter` | `'number'` |
| `DateFilter` | `'date'` |
| `DateIntervalFilter` | `'date-interval'` |
| `RelationFilter` | `'belongs-to'` |

Essa string é enviada ao front-end no schema do filtro e permite que a interface carregue o componente visual correto para cada tipo de filtro.

## Tabela Resumo dos Filtros

| Filtro | `$component` | WHERE gerado | Formato do `$filterData` |
|---|---|---|---|
| `StringFilter` | `string` | `WHERE col IN ('a', 'b')` | `['aprovado', 'pendente']` |
| `NumberFilter` | `number` | `WHERE col >= 100` ou `WHERE col BETWEEN 100 AND 500` | `{"operator": ">=", "values": [100]}` |
| `DateFilter` | `date` | `WHERE col BETWEEN '2024-03-15 00:00:00' AND '2024-03-15 23:59:59'` | `['2024-03-15']` |
| `DateIntervalFilter` | `date-interval` | `WHERE col BETWEEN '2024-01-01' AND '2024-12-31'` | `{"start": "2024-01-01", "end": "2024-12-31"}` |
| `RelationFilter` | `belongs-to` | `WHERE EXISTS (SELECT 1 FROM rel WHERE rel.id IN (1, 3))` | `[1, 3, 7]` |

## Próximos Passos

← [RawDimension](../06-dimensoes/05-raw.md) | → [StringFilter](02-string.md)
