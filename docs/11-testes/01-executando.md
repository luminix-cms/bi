# Executando os Testes

O pacote inclui uma suite de testes automatizados cobrindo métricas, dimensões, filtros e widgets.

## Comandos

```bash
# Executar todos os testes
composer test

# Executar com cobertura de código
composer test:coverage
```

A cobertura requer Xdebug (`xdebug.mode=coverage`) ou PCOV instalado. Verifique com:

```bash
php -m | grep -i xdebug
php -m | grep -i pcov
```

## O Que a Suite Cobre

Os testes verificam o comportamento externo de cada tipo — o SQL gerado, os valores formatados pelo `display()` e o resultado dos widgets com dados reais:

- **Dimensões** — `apply()` adiciona o `addSelect()` e `groupBy()` corretos
- **Filtros** — `apply()` produz a cláusula `WHERE` correta para cada operador; `extra()` retorna os metadados esperados
- **Métricas** — `apply()` adiciona a expressão de agregação correta; `display()` formata o valor adequadamente
- **Widgets** — testes de integração que instanciam dashboards completos, inserem dados e verificam o resultado de `data()`

Os testes usam SQLite em memória: nenhuma configuração de banco externo é necessária.

## Próximos Passos

[← Formato de Resposta](../10-api/03-resposta.md) | [→ Testando seus Dashboards](02-testando-dashboards.md)
