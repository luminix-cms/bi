# Segurança nas Queries

Além do middleware de rotas e do `viewable()` por dashboard, o Luminix BI oferece dois recursos que operam diretamente nas queries: conexão de banco dedicada e integração com o sistema de autorização do `luminix/backend`.

## Conexão de Banco Dedicada

Todas as queries do BI — widgets, filtros, opções de dropdown — podem ser direcionadas para uma conexão de banco de dados específica, independente da conexão padrão da aplicação.

Configure a opção `connection` em `config/luminix/bi.php`:

```php
'connection' => env('BI_DB_CONNECTION', null),
```

```bash
# .env
BI_DB_CONNECTION=mysql_readonly
```

Com isso, todas as queries do pacote usarão a conexão `mysql_readonly` definida em `config/database.php`. Isso é útil para:

- Isolar a carga analítica em uma réplica de leitura
- Usar um banco analítico separado
- Evitar que queries pesadas do BI impactem o banco transacional principal

Quando `connection` é `null` (padrão), as queries usam a conexão padrão do modelo.

## Integração com `luminix/backend`

Se a sua aplicação usa o pacote `luminix/backend` com `gates_enabled` ativo, as queries do BI aplicam automaticamente o escopo de segurança do backend. Isso significa que os widgets retornarão apenas os registros que o usuário autenticado tem permissão de ler — conforme a lógica definida nos Gates do modelo.

Essa integração é transparente: não é necessária nenhuma configuração adicional no dashboard ou nos widgets. O escopo é aplicado automaticamente para modelos registrados no `luminix/backend`.

Para mais detalhes sobre como configurar `gates_enabled`, consulte a documentação do `luminix/backend`.

> Se o modelo do dashboard não for um modelo Luminix, nenhum escopo automático é aplicado. Nesses casos, use o método `scope()` do dashboard para restringir os registros base manualmente.

## Próximos Passos

← [Controle de Acesso por Dashboard](02-dashboard.md) | → [Endpoints da API](../10-api/01-endpoints.md)
