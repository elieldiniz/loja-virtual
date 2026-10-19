# Orvalha Semijoias

Loja virtual de semijoias (Ji-Paraná, RO): vitrine pública que envia a sacola para o WhatsApp da loja e painel administrativo para peças, vendas, clientes e crediário.

Stack: Laravel 13, PHP 8.3+, Livewire 4, PostgreSQL, Spatie Media Library (armazenamento S3 compatível), Pest e Pint.

A especificação do projeto está em `.spec/init/` (descrição, histórias, schema e fases).

## Instalação local

```bash
docker compose up -d postgres      # cria os bancos orvalha e orvalha_test
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan admin:create           # cria a administradora do painel
npm install && npm run build
php artisan serve
```

## Dados de demonstração (somente desenvolvimento)

```bash
php artisan db:seed --class=DemoSeeder   # peças, clientes, vendas e a usuária contato@orvalha.test / senha123
php artisan storage:link
```

## Publicação (Laravel Cloud)

- Banco PostgreSQL e fotos no Object Storage do Laravel Cloud: anexe o bucket ao ambiente (ele preenche as variáveis `AWS_*`) e defina só `MEDIA_DISK=s3`.
- No deploy rode `php artisan migrate --force` e `php artisan db:seed --class=LookupSeeder --force` (formas de pagamento, materiais e canais), mais `php artisan db:seed --class=StoreSettingSeeder --force` na primeira vez.
- Crie a administradora com `php artisan admin:create` (usa `ADMIN_NAME`, `ADMIN_EMAIL` e `ADMIN_PASSWORD`).
- As conversões de imagem das peças rodam na hora do upload (sem fila obrigatória).

## Qualidade

```bash
composer test        # Pest
composer lint        # Pint (verificação)
composer lint:fix    # Pint (corrige)
```
