# Orvalha Semijoias

Loja virtual de semijoias (Ji-Paraná, RO): vitrine pública que envia a sacola para o WhatsApp da loja e painel administrativo para peças, vendas, clientes e crediário.

Stack: Laravel 13, PHP 8.3+, Livewire 4, PostgreSQL, Spatie Media Library (Cloudflare R2), Pest e Pint.

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

## Qualidade

```bash
composer test        # Pest
composer lint        # Pint (verificação)
composer lint:fix    # Pint (corrige)
```
