# Luminix BI

Pacote Laravel para criação de dashboards analíticos com uma arquitetura componentizável de Widgets, Métricas, Dimensões e Filtros.

```bash
composer require luminix/bi
php artisan bi:install
```

Defina seu primeiro dashboard:

```php
// app/Bi/Dashboards/VendasDashboard.php

class VendasDashboard extends Dashboard
{
    public $uriKey = 'vendas';
    public $name   = 'Vendas';
    public $model  = Pedido::class;

    public function widgets(): array
    {
        return [
            LineChart::create('receita-mensal', 'Receita Mensal')
                ->dimension(new MonthDimension('created_at', 'Mês'))
                ->metric(new SumMetric('total', 'Total')),
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

---

**Requisitos:** PHP 8.2+, Laravel 11+, `luminix/backend ^1.0`

**Licença:** MIT — fork de [laravel-bi/laravel-bi](https://github.com/laravel-bi/laravel-bi) por Alberto Bottarini

**Documentação completa:** [docs/INDEX.md](docs/INDEX.md)
