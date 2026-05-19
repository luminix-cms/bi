# Luminix BI — Índice da Documentação

Este índice cobre todos os tópicos necessários para instalar, configurar e estender o Luminix BI em uma aplicação Laravel.

---

## 1. Introdução

- [1.1 O que é o Luminix BI](01-introducao/01-o-que-e.md)
  - Visão geral da arquitetura (Dashboard → Widget → Metric/Dimension/Filter)
  - Casos de uso típicos
  - Relação com o pacote `luminix/backend`

- [1.2 Conceitos fundamentais](01-introducao/02-conceitos.md)
  - Dashboard — ponto central de configuração
  - Widget — componente de visualização
  - Métrica — agregação SQL (COUNT, SUM, AVG…)
  - Dimensão — agrupamento SQL (GROUP BY)
  - Filtro — restrição SQL (WHERE)
  - Atributo — abstração comum entre Métrica e Dimensão

---

## 2. Instalação e Configuração

- [2.1 Instalação via Composer](02-instalacao/01-instalacao.md)
  - Pré-requisitos (PHP 8.2+, Laravel 11+, `luminix/backend`)
  - `composer require luminix/bi`
  - `php artisan bi:install`
  - O que o comando `bi:install` faz

- [2.2 Arquivo de configuração](02-instalacao/02-configuracao.md)
  - `config/bi.php` — publicação e opções disponíveis
  - `path` — prefixo das rotas da API
  - `middleware` — autenticação e autorização
  - `connection` — conexão de banco de dados dedicada
  - `debug` — inclusão de queries SQL nas respostas

- [2.3 Rotas da API](02-instalacao/03-rotas.md)
  - Tabela completa de endpoints REST
  - Formato padrão de resposta JSON
  - Modo debug e o campo `debug` na resposta

---

## 3. Dashboards

- [3.1 Criando um Dashboard](03-dashboards/01-criando.md)
  - `php artisan bi:dashboard NomeDashboard --model=Modelo`
  - Estrutura do arquivo gerado em `app/Bi/Dashboards/`
  - Propriedades obrigatórias: `$uriKey`, `$name`, `$model`

- [3.2 Configurando widgets e filtros](03-dashboards/02-widgets-filtros.md)
  - Método `widgets(): array`
  - Método `filters(): array`
  - Retornando múltiplos widgets e filtros

- [3.3 Escopo global (`scope`)](03-dashboards/03-escopo.md)
  - Restringir registros base com `scope(Builder $builder)`
  - Exemplos: tenant, soft-deletes, status fixo

- [3.4 Autorização (`viewable`)](03-dashboards/04-autorizacao.md)
  - Método `viewable(): bool`
  - Integração com Gates e Policies do Laravel
  - Ocultação automática de dashboards não autorizados

- [3.5 Descoberta automática de Dashboards](03-dashboards/05-descoberta.md)
  - Como o `DashboardResolver` escaneia `app/Bi/Dashboards/`
  - Registrando dashboards em outros caminhos

---

## 4. Widgets

- [4.1 Visão geral dos Widgets](04-widgets/01-visao-geral.md)
  - Interface `Widget` e classe `BaseWidget`
  - Método estático `create($key, $name)`
  - Largura com `width()`
  - Escopo por widget com `scope(Closure)`

- [4.2 BigNumber](04-widgets/02-big-number.md)
  - Exibe um único valor agregado
  - Configuração: uma métrica, nenhuma dimensão
  - Exemplo de uso

- [4.3 Table](04-widgets/03-table.md)
  - Tabela com múltiplas colunas e ordenação
  - Configuração: múltiplas dimensões e métricas
  - Ordenação padrão e por coluna

- [4.4 LineChart](04-widgets/04-line-chart.md)
  - Gráfico de linha para séries temporais
  - Interpolação de datas ausentes
  - Configuração: uma dimensão de data, uma ou mais métricas

- [4.5 PartitionPie](04-widgets/05-partition-pie.md)
  - Gráfico de pizza com repartição por dimensão
  - Configuração de cores por fatia
  - Exemplo de uso

- [4.6 Exportação CSV](04-widgets/06-csv.md)
  - Endpoint `/csv` disponível em todos os widgets
  - Comportamento de stream para grandes volumes

---

## 5. Métricas

- [5.1 Visão geral das Métricas](05-metricas/01-visao-geral.md)
  - Interface `Metric` e classe `BaseMetric`
  - Método `column()` — coluna SQL
  - Método `asPercentage()` — exibição percentual

- [5.2 CountMetric](05-metricas/02-count.md)
  - `COUNT(*)` — contagem de registros
  - Exemplo e casos de uso

- [5.3 SumMetric](05-metricas/03-sum.md)
  - `SUM(coluna)` — soma de valores
  - Exemplo e casos de uso

- [5.4 AverageMetric](05-metricas/04-average.md)
  - `AVG(coluna)` — média de valores
  - Exemplo e casos de uso

- [5.5 RawMetric](05-metricas/05-raw.md)
  - Expressão SQL arbitrária
  - Quando usar e cuidados com injeção SQL

- [5.6 CountManyMetric](05-metricas/06-count-many.md)
  - `withCount(relação)` — contagem de relacionamentos
  - Configuração da relação Eloquent

- [5.7 SumManyMetric](05-metricas/07-sum-many.md)
  - `withSum(relação, coluna)` — soma em relacionamentos
  - Configuração da relação e coluna Eloquent

---

## 6. Dimensões

- [6.1 Visão geral das Dimensões](06-dimensoes/01-visao-geral.md)
  - Interface `Dimension` e classe `BaseDimension`
  - Método `column()` — coluna SQL
  - Como dimensões afetam o GROUP BY

- [6.2 StringDimension](06-dimensoes/02-string.md)
  - Agrupa por valor de coluna textual
  - Exemplo: categorias, status, região

- [6.3 DayDimension / MonthDimension / YearDimension](06-dimensoes/03-datas.md)
  - Agrupamento por dia, mês ou ano
  - Formato de saída e integração com LineChart
  - `DATE_FORMAT` gerado automaticamente

- [6.4 BelongsToDimension](06-dimensoes/04-belongs-to.md)
  - Agrupa por modelo relacionado (belongsTo)
  - Configuração de `relation()` e `otherColumn()`
  - Inclusão do modelo relacionado na resposta

- [6.5 RawDimension](06-dimensoes/05-raw.md)
  - Expressão SQL arbitrária para GROUP BY
  - Quando usar e cuidados

---

## 7. Filtros

- [7.1 Visão geral dos Filtros](07-filtros/01-visao-geral.md)
  - Classe `BaseFilter`
  - Propriedades: `$key`, `$name`, `$column`, `$defaultValue`
  - Método `extra()` — dados extras para o frontend
  - Aplicação automática em todos os widgets do dashboard

- [7.2 StringFilter](07-filtros/02-string.md)
  - `WHERE coluna IN (...)` com múltipla seleção
  - Busca automática de valores distintos no banco

- [7.3 NumberFilter](07-filtros/03-number.md)
  - Operadores suportados: `=`, `<`, `>`, `<=`, `>=`, `between`
  - Configuração e exemplo

- [7.4 DateFilter](07-filtros/04-date.md)
  - Filtro por data única com `BETWEEN (início do dia, fim do dia)`
  - Integração com Carbon

- [7.5 DateIntervalFilter](07-filtros/05-date-interval.md)
  - Filtro por intervalo de datas com `start` e `end`
  - Formato de entrada e saída

- [7.6 RelationFilter](07-filtros/06-relation.md)
  - `WHERE HAS(relação, ...)` — filtro por relacionamentos
  - Configuração da relação e coluna

---

## 8. Segurança

- [8.1 Autenticação e autorização das rotas](08-seguranca/01-rotas.md)
  - Middleware padrão (`web`, `auth`, `can:read-bi-reports`)
  - Customizando o middleware em `config/bi.php`

- [8.2 Controle de acesso por Dashboard](08-seguranca/02-dashboard.md)
  - Método `viewable()` e Gates do Laravel

- [8.3 Segurança nas queries](08-seguranca/03-queries.md)
  - `QueryService` e o papel do `luminix/backend` Finder
  - Proteção contra acesso não autorizado a registros
  - Cuidados com `RawMetric` e `RawDimension`

---

## 9. Extensibilidade

- [9.1 Criando Métricas customizadas](09-extensibilidade/01-metrica-customizada.md)
  - Implementar a interface `Metric`
  - Exemplo completo

- [9.2 Criando Dimensões customizadas](09-extensibilidade/02-dimensao-customizada.md)
  - Implementar a interface `Dimension`
  - Exemplo completo

- [9.3 Criando Filtros customizados](09-extensibilidade/03-filtro-customizado.md)
  - Estender `BaseFilter`
  - Sobrescrever `apply()` e `extra()`

- [9.4 Criando Widgets customizados](09-extensibilidade/04-widget-customizado.md)
  - Implementar a interface `Widget` ou estender `BaseWidget`
  - Integração com traits `HasAttributes`, `SingleMetric`, etc.

---

## 10. Referência da API REST

- [10.1 Endpoints disponíveis](10-api/01-endpoints.md)
  - `GET /bi-apis/dashboards`
  - `GET /bi-apis/{dashboard}/widgets`
  - `GET /bi-apis/{dashboard}/widgets/{widget}`
  - `GET /bi-apis/{dashboard}/widgets/{widget}/csv`
  - `GET /bi-apis/{dashboard}/filters/{filter}`

- [10.2 Formato de requisição](10-api/02-requisicao.md)
  - Parâmetro `filters` — estrutura JSON
  - Parâmetro de ordenação para widgets do tipo Table

- [10.3 Formato de resposta](10-api/03-resposta.md)
  - Envelope padrão `{ status, data, debug }`
  - Estrutura de cada tipo de widget na resposta
  - Estrutura de filtros e seus `extra` dados

---

## 11. Testes

- [11.1 Executando os testes do pacote](11-testes/01-executando.md)
  - `composer test`
  - Cobertura com `composer test:coverage`

- [11.2 Testando seus Dashboards](11-testes/02-testando-dashboards.md)
  - Configuração com Orchestra Testbench
  - Exemplo de teste de Widget e Dashboard
