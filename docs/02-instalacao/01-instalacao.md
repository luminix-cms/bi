# Instalação

## Pré-requisitos

| Requisito | Versão |
|---|---|
| PHP | 8.2+ (8.3+ para Laravel 13) |
| Laravel | 11.x, 12.x ou 13.x |
| luminix/backend | ^1.1 |

O pacote `luminix/backend` é uma dependência direta do Luminix BI e será instalado automaticamente pelo Composer.

## Instalando via Composer

Execute na raiz da aplicação Laravel:

```bash
composer require luminix/bi
```

O `BiServiceProvider` é registrado automaticamente via _package discovery_ do Laravel.

## Executando o comando de instalação

```bash
php artisan bi:install
```

O comando realiza as seguintes etapas:

1. **Publica a configuração** em `config/luminix/bi.php`
2. **Publica o Service Provider** em `app/Providers/BiServiceProvider.php`
3. **Registra o Service Provider** em `config/app.php`
4. **Gera um dashboard de exemplo** baseado no model `User`

## Estrutura gerada

```
app/
├── Bi/
│   └── Dashboards/
│       └── UserDashboard.php     ← dashboard de exemplo
└── Providers/
    └── BiServiceProvider.php     ← provider customizável

config/
└── luminix/
    └── bi.php                    ← configuração publicada
```

O diretório `app/Bi/Dashboards/` é onde o pacote procura dashboards automaticamente. Todo arquivo criado nesse diretório é descoberto e registrado sem configuração adicional.

## Verificando a instalação

Acesse o endpoint de dashboards com um cliente HTTP autenticado:

```bash
curl -H "Accept: application/json" \
     -H "Cookie: <sua_sessao>" \
     http://localhost:8000/bi-apis/dashboards
```

Uma resposta `200` com o dashboard de exemplo na lista confirma que a instalação está funcionando.

> Se retornar `403`, verifique o middleware configurado em `config/luminix/bi.php`. Consulte [Configuração](02-configuracao.md) para ajustá-lo.

> Se retornar `404`, confirme que o `BiServiceProvider` foi registrado em `config/app.php` e execute `php artisan route:list | grep bi-apis` para verificar as rotas.

## Próximos Passos

← [Conceitos Fundamentais](../01-introducao/02-conceitos.md) | → [Configuração](02-configuracao.md) | [Uso Básico](../uso-basico.md)
