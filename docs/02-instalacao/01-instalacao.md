# Instalação

O Luminix BI é distribuído como um pacote Composer e requer apenas dois comandos para estar operacional: um para baixar o pacote e outro para publicar os arquivos necessários na aplicação.

## Pré-requisitos

Antes de instalar o pacote, verifique se o ambiente atende aos requisitos abaixo.

| Requisito | Versão mínima |
|---|---|
| PHP | 8.2 |
| Laravel | 11.0 |
| luminix/backend | ^1.0 |

O pacote `luminix/backend` é uma dependência direta do Luminix BI. Ele fornece o mecanismo de segurança de queries (`Finder`) e os modelos base que o BI utiliza internamente. Se o `luminix/backend` ainda não estiver instalado na aplicação, o Composer o instalará automaticamente como dependência transitiva.

## Instalando via Composer

Execute o comando abaixo na raiz da aplicação Laravel:

```bash
composer require luminix/bi
```

O Composer resolverá as dependências, incluindo `luminix/backend`, e registrará o `BiServiceProvider` automaticamente via _package discovery_ do Laravel.

## Executando o comando de instalação

Após a instalação do pacote, execute o comando de instalação fornecido pelo Luminix BI:

```bash
php artisan bi:install
```

Esse comando realiza quatro etapas em sequência:

### Etapa 1 — Publicação da configuração

Publica o arquivo de configuração do pacote usando a tag `bi-config`:

```bash
# equivalente interno
php artisan vendor:publish --tag=bi-config
```

O arquivo é copiado para `config/luminix/bi.php`. A partir desse momento, todas as opções de configuração podem ser ajustadas diretamente nesse arquivo ou via variáveis de ambiente.

### Etapa 2 — Publicação do Service Provider

Publica um Service Provider customizável para a aplicação usando a tag `bi-provider`:

```bash
# equivalente interno
php artisan vendor:publish --tag=bi-provider
```

O arquivo é criado em `app/Providers/BiServiceProvider.php`. Esse provider é o ponto de extensão recomendado para registrar dashboards, macros e qualquer customização que precise ocorrer no boot da aplicação.

### Etapa 3 — Registro do Service Provider

O comando insere automaticamente `App\Providers\BiServiceProvider::class` no array de providers em `config/app.php`, logo abaixo de `EventServiceProvider`. Isso garante que o provider publicado seja carregado em todos os requests, sem necessidade de edição manual.

### Etapa 4 — Geração do dashboard de exemplo

O comando gera um dashboard de exemplo baseado no modelo `User` da aplicação:

```bash
# equivalente interno
php artisan bi:dashboard UserDashboard
```

O arquivo gerado em `app/Bi/Dashboards/UserDashboard.php` serve como ponto de partida e demonstra como definir widgets e filtros. Ele pode ser editado livremente ou removido caso não seja necessário.

## Estrutura gerada em `app/`

Após a execução de `bi:install`, a estrutura de arquivos gerada na aplicação é a seguinte:

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

O diretório `app/Bi/Dashboards/` é o local padrão onde o `DashboardResolver` procura por dashboards automaticamente. Todo arquivo de dashboard criado nesse diretório será descoberto e registrado sem configuração adicional.

## Verificando a instalação

Para confirmar que a instalação foi bem-sucedida, acesse o endpoint de dashboards com um cliente HTTP autenticado:

```bash
curl -H "Accept: application/json" \
     -H "Cookie: <sua_sessao>" \
     http://localhost:8000/bi-apis/dashboards
```

A resposta esperada é:

```json
{
    "status": 200,
    "data": [
        {
            "uriKey": "users",
            "name": "User dashboard",
            "widgets": [...],
            "filters": [...]
        }
    ]
}
```

Se a resposta retornar `200` com o dashboard de exemplo na lista, a instalação está funcionando corretamente.

> Se o endpoint retornar `403`, verifique se o usuário autenticado possui a ability `read-bi-reports`. Esse é o middleware padrão configurado no pacote. Consulte a seção [Configuração](02-configuracao.md) para saber como ajustar o middleware.

> Se o endpoint retornar `404`, confirme que o `BiServiceProvider` foi registrado em `config/app.php` e que as rotas foram carregadas corretamente executando `php artisan route:list | grep bi-apis`.

## Próximos Passos

← [Conceitos Fundamentais](../01-introducao/02-conceitos.md) | → [Configuração](02-configuracao.md)
