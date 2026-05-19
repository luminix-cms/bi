# Executando os Testes do Pacote

O Luminix BI inclui uma suite de testes automatizados que cobre dimensões, filtros, métricas e widgets. Os testes são escritos com PHPUnit e executados via Orchestra Testbench, que provisiona um ambiente Laravel completo sem precisar de uma aplicação Laravel real.

## Comandos

### Executar todos os testes

```bash
composer test
```

Internamente, esse comando executa:

```bash
php vendor/bin/testbench package:test
```

### Executar com cobertura de código

```bash
composer test:coverage
```

Internamente:

```bash
php vendor/bin/testbench package:test --coverage
```

A cobertura requer que uma das seguintes extensões PHP esteja instalada e ativa:

- **Xdebug** — configure `xdebug.mode=coverage` no `php.ini`
- **PCOV** — extensão mais leve, específica para cobertura

> Verifique qual extensão está disponível com `php -m | grep -i xdebug` ou `php -m | grep -i pcov`.

## Estrutura dos Testes

```
tests/
├── Dimensions/
│   ├── AbstractDimensionTestCase.php
│   ├── BelongsToDimensionTest.php
│   ├── DayDimensionTest.php
│   ├── MonthDimensionTest.php
│   ├── RawDimensionTest.php
│   ├── StringDimensionTest.php
│   └── YearDimensionTest.php
├── Filters/
│   ├── AbstractFilterTestCase.php
│   ├── DateFilterTest.php
│   ├── DateIntervalFilterTest.php
│   ├── NumberFilterTest.php
│   └── StringFilterTest.php
├── Metrics/
│   ├── AbstractMetricTestCase.php
│   ├── AverageMetricTest.php
│   ├── CountManyMetricTest.php
│   ├── CountMetricTest.php
│   ├── RawMetricTest.php
│   ├── SumManyMetricTest.php
│   └── SumMetricTest.php
├── Models/
│   ├── BarModel.php
│   └── FooModel.php
└── Widgets/
    ├── ConnectionConfigTest.php
    ├── DashboardTest.php
    └── WidgetFeaturesTest.php
```

## O Que Cada Suite Cobre

### `tests/Dimensions/`

Os testes de dimensões verificam que o método `apply()` gera o SQL correto — especificamente que os `addSelect()` e `groupBy()` são adicionados ao builder com a sintaxe esperada. Cada dimensão tem seu próprio arquivo de teste herdando de `AbstractDimensionTestCase`.

Exemplos do que é verificado:

- `StringDimension` adiciona `SELECT coluna as key` e `GROUP BY key`
- `MonthDimension` adiciona `DATE_FORMAT(coluna, '%Y-%m') as key` e o `GROUP BY` equivalente
- `BelongsToDimension` seleciona a chave estrangeira e usa `with()` para o eager loading da relação

### `tests/Filters/`

Os testes de filtros verificam que o `apply()` produz a cláusula `WHERE` correta para cada operador e tipo de dado. Também verificam o retorno do método `extra()` quando aplicável.

Exemplos do que é verificado:

- `StringFilter` gera `WHERE IN` com os valores enviados
- `NumberFilter` gera `WHERE operador valor` ou `WHERE BETWEEN`
- `DateFilter` gera `WHERE BETWEEN` cobrindo o dia inteiro
- `DateIntervalFilter` gera `WHERE BETWEEN` com o intervalo completo

### `tests/Metrics/`

Os testes de métricas verificam que `apply()` adiciona a expressão SQL de agregação correta e que `display()` formata o valor adequadamente.

Exemplos do que é verificado:

- `CountMetric` gera `SELECT COUNT(*) as key`
- `SumMetric` gera `SELECT SUM(coluna) as key`
- `AverageMetric` gera `SELECT AVG(coluna) as key`
- `RawMetric` usa a expressão SQL bruta fornecida
- Métricas com `asPercentage()` formatam o valor com `%` no `display()`

### `tests/Widgets/`

Os testes de widgets são testes de integração. Eles instanciam um dashboard completo, criam o banco em memória, inserem dados de teste e verificam o resultado do método `data()`.

- **`DashboardTest.php`** — testa a lógica da classe `Dashboard`: `findWidgetOrFail()`, `findFilterOrFail()`, `viewable()` e a serialização JSON
- **`WidgetFeaturesTest.php`** — testa o comportamento de cada tipo de widget com dados reais: aplicação de filtros, ordenação no `Table`, `asPercentage()`, `scope()`, e o trait `HasAttributes`
- **`ConnectionConfigTest.php`** — testa a configuração de conexão alternativa com banco de dados

## Tecnologia

Os testes usam o **Orchestra Testbench**, que provisiona um mini-framework Laravel para testes de pacotes. O banco de dados utilizado é **SQLite em memória** (`database::memory:`), configurado automaticamente pelo Testbench. Isso significa que:

- Não é necessária nenhuma configuração de banco externo
- Os testes são completamente isolados — o banco é criado e destruído a cada execução
- As tabelas são criadas no `setUp()` de cada teste usando `Schema::create()` diretamente

Os modelos de teste `FooModel` (tabela `foo`) e `BarModel` (tabela `bar`) são classes Eloquent simples que servem como base para os testes de integração.

## Próximos Passos

[← Formato de Resposta](../10-api/03-resposta.md) | [→ Testando seus Dashboards](02-testando-dashboards.md)
