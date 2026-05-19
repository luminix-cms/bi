# O que é o Luminix BI

O **Luminix BI** é um pacote Laravel que permite criar dashboards analíticos diretamente no back-end da sua aplicação, sem a necessidade de ferramentas externas de Business Intelligence.

A ideia central é simples: você descreve _o que_ quer visualizar em PHP — quais dados, como agrupá-los, quais filtros oferecer — e o pacote gera automaticamente as queries SQL e expõe uma API REST que qualquer front-end pode consumir.

---

## O problema que ele resolve

Imagine que você precisa de um painel como este:

```
┌─────────────────────────────────────────────────────┐
│  Receita por mês  [Filtro: Jan 2024 → Dez 2024]     │
├────────────┬───────────────┬────────────────────────┤
│ Mês        │ Pedidos       │ Receita Total          │
├────────────┼───────────────┼────────────────────────┤
│ 2024-01    │ 312           │ R$ 48.500,00           │
│ 2024-02    │ 287           │ R$ 41.200,00           │
│ ...        │ ...           │ ...                    │
└────────────┴───────────────┴────────────────────────┘
```

Sem o Luminix BI, você provavelmente escreveria uma query SQL manualmente, criaria um controller dedicado, trataria os filtros na mão e ainda teria que se preocupar com autorização. Depois repetiria tudo isso para o próximo painel.

Com o Luminix BI, o mesmo resultado é obtido assim:

```php
class VendasDashboard extends Dashboard
{
    public $uriKey = 'vendas';
    public $name   = 'Vendas';
    public $model  = Pedido::class;

    public function widgets(): array
    {
        return [
            Table::create('receita-por-mes', 'Receita por Mês')
                ->dimension(new MonthDimension('created_at', 'Mês'))
                ->metrics([
                    new CountMetric('pedidos', 'Pedidos'),
                    new SumMetric('total', 'Receita Total'),
                ]),
        ];
    }

    public function filters(): array
    {
        return [
            DateIntervalFilter::create('created_at', 'Período'),
        ];
    }
}
```

O pacote gera a SQL, aplica os filtros e devolve os dados via API. Você só precisou declarar a intenção.

---

## Visão geral da arquitetura

A arquitetura é organizada em quatro camadas que se encaixam:

```
┌──────────────────────────────────────────────────────────┐
│                        Dashboard                         │
│   (ponto de entrada — define modelo, widgets e filtros)  │
│                                                          │
│  ┌──────────────────────────────────────────────────┐    │
│  │                    Widget                        │    │
│  │  (componente de visualização — Table, LineChart) │    │
│  │                                                  │    │
│  │  ┌───────────────┐    ┌───────────────────────┐  │    │
│  │  │   Dimensão    │    │       Métrica         │  │    │
│  │  │  (GROUP BY)   │    │ (COUNT, SUM, AVG...)  │  │    │
│  │  └───────────────┘    └───────────────────────┘  │    │
│  └──────────────────────────────────────────────────┘    │
│                                                          │
│  ┌──────────────────────────────────────────────────┐    │
│  │                    Filtros                       │    │
│  │          (WHERE aplicado em todos os widgets)    │    │
│  └──────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────┘
```

Cada camada tem uma responsabilidade clara e única:

| Camada | Responsabilidade | Equivalente SQL |
|--------|-----------------|-----------------|
| **Dashboard** | Configuração central - define qual model será usada na consulta base | - |
| **Widget** | Tipo de visualização | Estrutura da query |
| **Dimensão** | Como agrupar os dados | `GROUP BY` |
| **Métrica** | O que calcular | `SELECT COUNT(*), SUM(col)` |
| **Filtro** | Como restringir os dados | `WHERE` |

---

## Casos de uso típicos

O Luminix BI é adequado para cenários onde os dados já estão no banco da sua aplicação Laravel e você precisa de análises sobre eles:
u
- **Painel financeiro** — receita por período, ticket médio, inadimplência
- **Painel de vendas** — pedidos por vendedor, conversão por categoria, comparativo mensal
- **Painel operacional** — tempo médio de atendimento, volume por status, SLA por cliente
- **Painel de uso** — usuários ativos, fncionalidades mais usadas, retenção por coorte

Ele **não** substitui ferramentas como Metabase ou Power BI para análises ad-hoc complexas. O ponto forte é a **integração nativa** com a aplicação: os mesmos modelos, as mesmas regras de autorização, a mesma conexão de banco.

---

## Relação com o `luminix/backend`

O Luminix BI depende do pacote [`luminix/backend`](https://github.com/luminix-cms/backend) e usa dois recursos dele:

1. **`Finder`** — ao construir queries, o `QueryService` verifica se o modelo é um _Luminix Model_ e, caso seja, aplica automaticamente o scope `allowed('read')`. Isso garante que o dashboard só retorna registros que o usuário tem permissão de ver, sem nenhuma configuração extra.

2. **Gates de segurança** — o comportamento acima é controlado pela configuração `luminix.backend.security.gates_enabled`. Se desabilitado, as queries rodam sem o filtro de permissão.

Se o seu modelo **não** é um Luminix Model, o pacote funciona normalmente — o `QueryService` ignora a verificação e executa uma query padrão do Eloquent.

---

## Próximos Passos

- [Conceitos Fundamentais →](02-conceitos.md)
- [Instalação e Configuração →](../02-instalacao/01-instalacao.md)
