# O que é o Luminix BI

O **Luminix BI** é um pacote Laravel para criar dashboards analíticos diretamente no back-end da sua aplicação. Você descreve em PHP quais dados quer visualizar, como agrupá-los e quais filtros oferecer — o pacote gera as queries SQL e expõe uma API REST para qualquer front-end consumir.

---

## O problema que ele resolve

Sem o Luminix BI, cada painel analítico exige escrever queries SQL manualmente, criar controllers dedicados, tratar filtros e cuidar de autorização — e repetir tudo isso a cada novo painel.

Com o Luminix BI, o mesmo resultado é obtido declarativamente:

```php
class SalesDashboard extends Dashboard
{
    public $uriKey = 'sales';
    public $name   = 'Sales';
    public $model  = Order::class;

    public function widgets(): array
    {
        return [
            Table::create('revenue-by-month', 'Revenue by Month')
                ->dimension(new MonthDimension('created_at', 'Month'))
                ->metrics([
                    new CountMetric('orders', 'Orders'),
                    new SumMetric('revenue', 'Revenue')->column('total_amount'),
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            DateIntervalFilter::create('created_at', 'Period'),
        ];
    }
}
```

O pacote gera a SQL, aplica os filtros e devolve os dados via API. Você só precisou declarar a intenção.

---

## Casos de uso típicos

O Luminix BI é adequado para cenários onde os dados já estão no banco da sua aplicação Laravel:

- **Painel financeiro** — receita por período, ticket médio, inadimplência
- **Painel de vendas** — pedidos por status, conversão por categoria, comparativo mensal
- **Painel operacional** — volume por status, SLA por cliente, tempo médio de atendimento
- **Painel de uso** — usuários ativos, funcionalidades mais usadas, retenção por coorte

Ele **não** substitui ferramentas como Metabase ou Power BI para análises ad-hoc complexas. O diferencial é a **integração nativa** com a aplicação: os mesmos modelos Eloquent, as mesmas regras de autorização, a mesma conexão de banco.

---

## Relação com o `luminix/backend`

O Luminix BI se integra opcionalmente ao [`luminix/backend`](https://github.com/luminix-cms/backend). Quando o modelo do dashboard é um _Luminix Model_, o pacote aplica automaticamente o scope `allowed('read')` nas queries, garantindo que o dashboard só retorna registros que o usuário tem permissão de ver. Se o modelo não for um Luminix Model, o pacote funciona normalmente com Eloquent padrão.

---

## Próximos Passos

- [Conceitos Fundamentais →](02-conceitos.md)
- [Instalação e Configuração →](../02-instalacao/01-instalacao.md)
- [Guia de Uso Básico →](../uso-basico.md)
