# StringFilter

`StringFilter` filtra registros por valores textuais usando `WHERE ... IN (...)`. É o filtro indicado para colunas com valores discretos — como status, tipo ou categoria — onde o usuário seleciona um ou mais valores de uma lista.

## Exemplo

```php
use Luminix\Bi\Filters\StringFilter;

public function filters(): array
{
    return [
        StringFilter::create('status', 'Status'),
    ];
}
```

O primeiro argumento é o `$key` (identificador na request), o segundo é o nome exibido na interface.

Quando a coluna no banco difere do `$key`, use `->column()`:

```php
StringFilter::create('order_status', 'Order Status')
    ->column('status'),
```

## Formato de Envio no Request

O frontend envia os valores selecionados como um array na query string:

```
?filters[status][]=active&filters[status][]=pending
```

Isso gera no banco:

```sql
WHERE `status` IN ('active', 'pending')
```

Mesmo que o usuário selecione apenas um valor, ele deve ser enviado como array:

```
?filters[status][]=active
```

## O Método `extra()` — Opções para o Select

`StringFilter` implementa `extra()`, que é chamado pelo endpoint `GET /bi-apis/{dashboard}/filters/{filter}`. Ele retorna os valores distintos da coluna diretamente do banco:

```json
{
    "status": 200,
    "extra": {
        "options": ["active", "pending", "cancelled"]
    }
}
```

O frontend usa esse array `options` para popular o dropdown ou lista de checkboxes. Não é necessário manter uma lista estática de opções — o controle sempre refletirá os valores presentes no banco.

> Em tabelas com alta cardinalidade ou colunas de texto livre, a query `SELECT DISTINCT` pode retornar muitas opções. Nesses casos, considere um [filtro customizado](../09-extensibilidade/03-filtro-customizado.md) com limites ou uma fonte de dados alternativa.

## Próximos Passos

← [Visão Geral dos Filtros](01-visao-geral.md) | → [NumberFilter](03-number.md)
