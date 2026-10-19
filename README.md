# Orvalha Semijoias

Loja virtual de semijoias (Ji-Paraná, RO): vitrine pública que envia a sacola para o WhatsApp da loja e painel administrativo para peças, vendas, clientes e crediário.

Stack: Laravel 13, PHP 8.3+, Livewire 4, MySQL 8, Spatie Media Library (disco `public`), Pest e Pint.

A especificação do projeto está em `.spec/init/` (descrição, histórias, schema e fases).

## Instalação local

```bash
docker compose up -d mysql         # cria os bancos orvalha e orvalha_test
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

- Banco MySQL (o do Laravel Cloud) e fotos no Object Storage do Laravel Cloud: anexe o bucket ao ambiente como disco `public` (padrão). Nenhuma variável de armazenamento precisa ser criada à mão.
- No deploy rode `php artisan migrate --force` e `php artisan db:seed --class=LookupSeeder --force` (formas de pagamento, materiais e canais), mais `php artisan db:seed --class=StoreSettingSeeder --force` na primeira vez.
- Crie a administradora com `php artisan admin:create` (usa `ADMIN_NAME`, `ADMIN_EMAIL` e `ADMIN_PASSWORD`).
- As conversões de imagem das peças rodam na hora do upload (sem fila obrigatória).

## Qualidade

```bash
composer test        # Pest
composer lint        # Pint (verificação)
composer lint:fix    # Pint (corrige)
```
