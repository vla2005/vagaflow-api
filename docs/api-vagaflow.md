# VagaFlow em Laravel

A busca, os filtros, a análise Gemini, o currículo PDF, o histórico de vagas e o e-mail agora são executados pelo Laravel em `api/`. Cada usuário mantém os próprios filtros e currículo. `legacy/` é referência do sistema antigo, não uma dependência de execução desta aplicação.

## Execução com Docker

A stack do backend é executada integralmente em containers. O React em `app/` continua fora do Docker e pode ser iniciado normalmente com Vite.

Serviços disponíveis:

- `api`: aplicação Laravel exposta em `http://localhost:8000`;
- `database`: PostgreSQL com volume persistente;
- `migrate`: container temporário que aplica as migrations antes dos demais serviços;
- `queue`: worker dos jobs de análise, currículo e e-mail;
- `scheduler`: agenda as buscas recorrentes pelo scheduler do Laravel.

Defina as integrações no `.env` de `api/` e altere a senha local do PostgreSQL quando necessário:

```env
API_PORT=8000
DOCKER_DB_DATABASE=vagaflow
DOCKER_DB_USERNAME=vagaflow
DOCKER_DB_PASSWORD=troque-esta-senha
```

Na pasta `api/`, construa e inicie a stack:

```bash
docker compose up -d --build
docker compose ps -a
docker compose logs -f api queue scheduler
```

O serviço `migrate` deve terminar com o estado `Exited (0)`. Isso é esperado: ele executa uma vez e libera a inicialização da API, fila e scheduler. Para aplicar migrations manualmente após uma atualização:

```bash
docker compose run --rm migrate
```

Para encerrar os containers sem apagar o banco:

```bash
docker compose down
```

Não use `docker compose down -v` sem intenção de apagar os volumes do PostgreSQL e dos arquivos privados da aplicação.

O frontend permanece independente:

```bash
cd ../app
npm install
npm run dev
```

## Preparação sem Docker

```bash
composer install
php artisan migrate --force
```

Defina no `.env` do Laravel:

```env
VAGAFLOW_MAX_SOURCE_JOBS_PER_LEVEL=20
GEMINI_API_KEY=sua-chave
GEMINI_MODEL=gemini-3.1-flash-lite
GEMINI_FALLBACK_MODEL=gemini-2.5-flash
MAIL_MAILER=smtp
MAIL_HOST=seu-servidor-smtp
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=seu-usuario-smtp
MAIL_PASSWORD=sua-senha-smtp
MAIL_FROM_ADDRESS=remetente@seu-dominio
MAIL_FROM_NAME=VagaFlow
```

Para SMTP na porta 587, normalmente use `MAIL_SCHEME=smtp`. Teste o envio antes de ativar o cron. O armazenamento de PDFs usa o disco `local`, privado, e exige permissão de escrita em `storage/`.

## Execução

```bash
php artisan vagaflow:search junior
php artisan vagaflow:search estagio
php artisan vagaflow:search pleno
php artisan vagaflow:search senior
php artisan schedule:list
```

Sem Docker, configure **apenas uma** entrada de cron do scheduler Laravel para o usuário que executa o app:

```cron
* * * * * cd /caminho/absoluto/api && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

O scheduler chama júnior no minuto 0, estágio no 15, pleno no 30 e sênior no 45 de cada hora. Cada execução coleta as vagas uma vez e envia as avaliações individuais para a fila. Sem Docker, mantenha também um worker supervisionado:

```bash
php artisan queue:work --queue=default --sleep=2 --tries=3 --timeout=180
```

O comando de busca também pode ser executado manualmente. Antes de ativar o scheduler novo na EC2, desative as entradas antigas que chamam as rotas Node, para evitar e-mails duplicados e gastos extras com Gemini. Com Docker, o container `scheduler` substitui o cron do host e o container `queue` substitui o worker supervisionado. O workflow Node em `legacy/.github/workflows/deploy.yml` **não** publica esta nova stack Laravel.

## Currículo base

O endpoint aceita apenas PDF de até 5 MB com texto selecionável. O arquivo é lido diretamente do upload temporário e não é copiado para o storage. PDFs escaneados, protegidos, corrompidos ou com texto insuficiente são recusados com uma mensagem para o usuário. Somente o texto extraído é salvo, usando o cast criptografado do Laravel.

## API privada

As rotas usam sessão do Laravel na mesma origem do futuro PWA, com cookie e proteção CSRF. `GET /api/session` devolve o usuário e o token; pedidos que alteram estado devem enviar `X-CSRF-TOKEN`.

| Método | URL | Uso |
| --- | --- | --- |
| GET | `/api/session` | Usuário atual e token CSRF |
| POST | `/api/session` | Login |
| DELETE | `/api/session` | Logout |
| GET | `/api/automation` | Configuração e estado da automação |
| PUT | `/api/automation` | Salva filtros, nota mínima e ativação |
| POST | `/api/automation/resume` | Extrai o PDF temporário e salva somente o texto |
| DELETE | `/api/automation/resume` | Remove o texto e pausa a automação |
| GET | `/api/jobs` | Vagas aprovadas, com filtros `level`, `status`, `min_score`, `search`, `per_page` |
| GET | `/api/jobs/{id}` | Detalhe e avaliação da vaga |
| GET | `/api/jobs/{id}/resume` | Download autenticado do currículo privado |
| PATCH | `/api/jobs/{id}/status` | `new`, `saved`, `applied` ou `ignored` |

Vagas rejeitadas ficam no banco apenas para deduplicação e não aparecem na API. Uma falha de e-mail faz o job da fila tentar novamente sem recriar a oportunidade.
