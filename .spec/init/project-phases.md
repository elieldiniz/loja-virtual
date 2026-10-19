# Orvalha Semijoias — Project Phases

<!-- inputs: project-description.md@sha256:217727624c12 user-stories.md@sha256:1305fd60c0db database-schema.md@sha256:90ab720eea6e -->

## Overview

A construção é **foundation-first**: primeiro projeto Laravel e tooling (Phase 1), banco de dados completo (Phase 2), models com todas as relações prontas (Phase 3), serviços de domínio puros e testados (Phase 4) e a base de front-end com tokens e componentes fiéis aos mockups (Phase 5). Só então entram os fluxos: login do painel (Phase 6), catálogo, configurações, clientes, PDV, vendas, crediário e métricas do admin (Phases 7 a 14) e, por fim, a vitrine pública (Phases 15 a 18), que lê tudo do banco alimentado pelo painel. A Phase 19 fecha qualidade, segurança e publicação.

São **19 fases**; cada uma (pai + subfases) cabe em uma sessão de agente. **Corte do MVP: tudo entra no MVP** (decisão do desenvolvedor), então a primeira versão publicada é concluída ao fim da **Phase 19**. As fases são entregues a agentes pelo número (`Phase 5.3`). Nada existe ainda no repositório além de `README.md` e `.spec/`, então todas as tarefas começam `[ ]`.

Referências de design (copiadas para `.spec/init/design/`): `Orvalha_Semijoias.html` (vitrine), `Orvalha_Admin.html` (painel, telas `viewLogin`, `viewDash`, `viewItems`, `viewForm`, `viewCats`, `viewSell`, `viewSales`, `viewSale`, `viewClients`, `viewClient`, `viewStore`) e `Orvalha_Regras_de_negocio_e_fluxos.md` (regras RN-xx). A implementação deve ser **fiel** ao mockup (fontes Bodoni Moda e Jost; dourado `#A9803F`/`#8A6630`, vinho `#5B1F2E`, blush `#EFDDD7`, tinta `#221C1D`).

**Conventions:**
- `[ ]` pending · `[x]` done in the codebase.
- Phases and sub-phases are numbered (`Phase 1`, `Phase 5.3`) for reference by AI agents.
- Business-logic tasks list the **feature tests** to generate; frontend-only tasks list validatable **acceptance criteria** and a **Design ref**.
- Testes usam Pest; todo código passa em Pint e Larastan antes de a fase ser considerada pronta.
- Valores monetários são `decimal(10,2)` e calculados em centavos inteiros (nunca float).

---

## Phase 1: Bootstrap do projeto Laravel e tooling

**Goal:** Aplicação Laravel rodando com Livewire, Pest, Pint, Larastan, PostgreSQL e Spatie Media Library configurados. · **Depends on:** none · **Covers:** Tech Stack

### Phase 1.1: Esqueleto da aplicação

- [ ] **Task:** Criar o projeto Laravel (última versão estável compatível com o PHP instalado, mín. 8.3) na raiz do repositório, preservando `.spec/` e `README.md`.
  - **Acceptance criteria:**
    - `composer.json` e `artisan` existem na raiz e `php artisan --version` funciona
    - `.spec/` e o `README.md` existente continuam intactos
    - `php artisan serve` responde 200 em `/`
  - **Traces:** Tech Stack (backend)

- [ ] **Task:** Instalar Livewire (última major, com Alpine.js incluso) e configurar Vite.
  - **Acceptance criteria:**
    - Um componente Livewire de teste renderiza e responde a uma ação
    - `npm run build` conclui sem erros
    - Alpine.js está disponível nas views Blade
  - **Traces:** Tech Stack (vitrine e painel)

- [ ] **Task:** Configurar locale `pt_BR`, timezone `America/Porto_Velho`, moeda BRL e `.env.example` com variáveis de Postgres, R2 e administradora.
  - **Acceptance criteria:**
    - `config('app.locale')` é `pt_BR` e o timezone é `America/Porto_Velho`
    - `.env.example` lista `DB_*` (pgsql), `AWS_*`/R2 e `ADMIN_EMAIL`/`ADMIN_PASSWORD` sem segredos reais
    - Mensagens de validação saem em português
  - **Feature tests:** `LocaleConfigTest` → locale e timezone configurados.
  - **Traces:** Tech Stack; Overview (loja em Ji-Paraná)

### Phase 1.2: Banco, mídia e qualidade

- [ ] **Task:** Configurar PostgreSQL para desenvolvimento e testes (docker-compose ou Sail) e conexão `pgsql` padrão.
  - **Acceptance criteria:**
    - `php artisan migrate` roda contra PostgreSQL
    - `phpunit.xml`/Pest usam PostgreSQL com banco de testes separado
    - README documenta como subir o banco local
  - **Traces:** Tech Stack (banco de dados)

- [ ] **Task:** Instalar Spatie Media Library e configurar o disco `r2` (driver s3, endpoint Cloudflare R2) como disco de mídia.
  - **Acceptance criteria:**
    - `config/media-library.php` aponta para o disco `r2` por variável de ambiente
    - Em testes o disco é `Storage::fake`
    - Nenhuma credencial real é versionada
  - **Traces:** Tech Stack (mídia); table media

- [ ] **Task:** Instalar e configurar Pest, Laravel Pint e Larastan com scripts `composer test`, `composer lint` e `composer analyse`.
  - **Acceptance criteria:**
    - `composer test` roda a suíte Pest e passa
    - `composer lint` (Pint) e `composer analyse` (Larastan) passam no esqueleto
    - Nível do Larastan definido em `phpstan.neon`
  - **Traces:** Tech Stack (testes e qualidade)

- [ ] **Task:** Criar workflow de CI (GitHub Actions) com PostgreSQL, rodando lint, análise e testes.
  - **Acceptance criteria:**
    - O workflow executa `composer lint`, `composer analyse` e `composer test` com serviço PostgreSQL
    - Falha em qualquer etapa reprova o job
  - **Traces:** Tech Stack (tooling)

---

## Phase 2: Fundação do banco de dados

**Goal:** Todas as migrations do schema DBML e os seeders de lookup. · **Depends on:** Phase 1 · **Covers:** database-schema inteiro

### Phase 2.1: Lookups e catálogo

- [ ] **Task:** Migrations dos lookups `materials`, `payment_methods` e `sale_channels` conforme o DBML.
  - **Acceptance criteria:**
    - Colunas, tipos, defaults e `slug` único conforme o schema
    - `payment_methods` tem `allows_installments`, `max_installments`, `applies_pix_discount` e `generates_receivables`
    - Nenhuma coluna enum no banco
  - **Feature tests:** `LookupSchemaTest` → tabelas e colunas existem; `slug` duplicado é recusado.
  - **Traces:** tables materials, payment_methods, sale_channels

- [ ] **Task:** Migrations de `users`, `categories` e `products` com índices e FKs.
  - **Acceptance criteria:**
    - `users` tem `email` único e `password`
    - `categories.name_normalized` é único
    - `products.category_id` usa `restrictOnDelete`; índices `(category_id, is_active)`, `(is_active, is_best_seller)` e `(is_active, is_new_release)`
    - `products.is_active` padrão `false`
  - **Feature tests:** `CatalogSchemaTest` → excluir categoria com peça falha por FK; `name_normalized` duplicado falha.
  - **Traces:** tables users, categories, products; US-5.4

- [ ] **Task:** Publicar e ajustar a migration `media` do Spatie Media Library.
  - **Acceptance criteria:**
    - Tabela `media` com índice `(model_type, model_id)` e `uuid` único
    - Migration roda em PostgreSQL
  - **Traces:** table media; US-5.2

### Phase 2.2: Clientes, vendas e configurações

- [ ] **Task:** Migrations de `customers`, `sales`, `sale_items` e `installments`.
  - **Acceptance criteria:**
    - `customers.whatsapp` único e anulável; `birth_day`/`birth_month` anuláveis; índice em `birth_month`
    - `sales.number` único; `customer_id` anulável com `restrictOnDelete`; índices em `sold_at`, `cancelled_at` e `customer_id`
    - `sale_items.product_id` e `category_id` anuláveis com `nullOnDelete`
    - `installments` com `UNIQUE (sale_id, number)` e índice `(paid_at, due_date)`
  - **Feature tests:** `SalesSchemaTest` → excluir peça zera `sale_items.product_id` sem apagar o item; excluir cliente com venda falha; parcela duplicada `(sale_id, number)` falha.
  - **Traces:** tables customers, sales, sale_items, installments; US-5.3, US-6.3

- [ ] **Task:** Migrations de `store_settings`, `promo_phrases` e `chat_shortcuts`.
  - **Acceptance criteria:**
    - Defaults: `pix_discount_percent` 5, `max_installments` 3, `help_invite_enabled` true, `help_invite_minutes` 2, `birthday_gift_percent` 10
    - `promo_phrases` e `chat_shortcuts` referenciam `store_settings` e têm `position`
  - **Traces:** tables store_settings, promo_phrases, chat_shortcuts; US-10.1, US-10.2

- [ ] **Task:** Constraints `CHECK` do PostgreSQL para os limites do schema.
  - **Acceptance criteria:**
    - `products.price > 0`; `pix_discount_percent` 0–30; `max_installments` 1–12; `birthday_gift_percent` 0–50
    - `help_invite_minutes` em (1, 2, 3, 5); `sales.installments_count` 1–6; `sale_items.quantity >= 1`
  - **Feature tests:** `CheckConstraintsTest` → cada valor fora do limite é rejeitado pelo banco.
  - **Traces:** US-10.1, US-10.2, US-5.1, US-8.1

### Phase 2.3: Sequência e seeders

- [ ] **Task:** Criar a sequence do PostgreSQL para `sales.number` e um gerador de número.
  - **Acceptance criteria:**
    - Números são sequenciais, únicos e exibidos como `#0616` (zeros à esquerda, 4 dígitos mínimo)
    - Chamadas concorrentes nunca geram o mesmo número
  - **Feature tests:** `SaleNumberTest` → números consecutivos crescem de 1 em 1; formatação `#0001`.
  - **Traces:** table sales; US-7.1

- [ ] **Task:** Seeders dos lookups e da linha única de `store_settings`.
  - **Acceptance criteria:**
    - materials: Ouro 18k e Prata 925; sale_channels: WhatsApp, Loja física, Instagram
    - payment_methods: Pix (à vista, aplica desconto Pix), Dinheiro, Débito (à vista), Crédito (1 a 6) e Crediário (1 a 6, `generates_receivables`)
    - `store_settings` tem exatamente 1 linha com os defaults; o seeder é idempotente
  - **Feature tests:** `LookupSeederTest` → rodar duas vezes não duplica registros; flags de cada forma de pagamento conforme o schema.
  - **Traces:** Lookup Table Seeds; tables materials, payment_methods, sale_channels, store_settings

---

## Phase 3: Models, relacionamentos e factories

**Goal:** Todos os models com relações completas, casts, scopes e factories. · **Depends on:** Phase 2 · **Covers:** todas as tabelas

### Phase 3.1: Catálogo

- [ ] **Task:** Models `Material`, `PaymentMethod`, `SaleChannel`, `Category` e `Product` com relações, casts e `$fillable`.
  - **Acceptance criteria:**
    - `Category` hasMany `Product`; `Product` belongsTo `Category` e `Material`
    - `price`/`old_price` com cast decimal de 2 casas; flags booleanas castadas
    - `Category` gera `name_normalized` (minúsculo, sem acento) ao salvar
  - **Feature tests:** `CategoryModelTest` → `name_normalized` de "Anéis" vira "aneis"; `ProductRelationsTest` → relações retornam os modelos esperados.
  - **Traces:** tables categories, products, materials, payment_methods, sale_channels

- [ ] **Task:** `Product` registra a coleção de mídia `photos` (Spatie) com acessores de capa e foto de hover e o scope `visible()`.
  - **Acceptance criteria:**
    - Capa é a foto de menor `order_column`; hover é a segunda (ou nula)
    - `Product::visible()` retorna só peças com `is_active` e pelo menos 1 foto
    - Existe conversão de imagem para cards (miniatura/webp)
  - **Feature tests:** `ProductVisibilityTest` → peça ativa sem foto não entra no scope; ativa com foto entra; inativa com foto não entra.
  - **Traces:** US-1.1, US-5.2; table media

### Phase 3.2: Clientes e vendas

- [ ] **Task:** Models `Customer`, `Sale`, `SaleItem` e `Installment` com relações completas.
  - **Acceptance criteria:**
    - `Customer` hasMany `Sale`; `Sale` belongsTo `Customer`, `PaymentMethod`, `SaleChannel`, hasMany `SaleItem` e `Installment`
    - `SaleItem` belongsTo `Sale`, `Product` (nulo) e `Category` (nulo)
    - `Installment` belongsTo `Sale`; datas castadas para `date`
  - **Feature tests:** `SaleRelationsTest` → todas as relações navegam nos dois sentidos com factories.
  - **Traces:** tables customers, sales, sale_items, installments

- [ ] **Task:** Scopes e acessores de venda e parcela: `Sale::notCancelled()`, `Sale::cancelled()`, `Installment::open()` e situação derivada.
  - **Acceptance criteria:**
    - `notCancelled()` filtra `cancelled_at IS NULL`
    - Situação da parcela: `paid` se `paid_at`; `overdue` se `due_date` < hoje; `due_soon` se vence em até 7 dias; senão `open`
  - **Feature tests:** `InstallmentStatusTest` → paga, vencida (ontem), vence em 7 dias, vence em 8 dias (aberta) e vence hoje.
  - **Traces:** US-8.3, US-7.5

### Phase 3.3: Configurações, usuário e factories

- [ ] **Task:** Models `StoreSetting` (singleton `current()` com cache), `PromoPhrase`, `ChatShortcut` e `User`.
  - **Acceptance criteria:**
    - `StoreSetting::current()` devolve sempre a linha única, criando-a com defaults se faltar
    - `StoreSetting` hasMany `PromoPhrase` e `ChatShortcut`, ordenados por `position`
    - Cache é invalidado ao salvar
  - **Feature tests:** `StoreSettingTest` → `current()` é idempotente; salvar invalida o cache.
  - **Traces:** tables store_settings, promo_phrases, chat_shortcuts, users; US-10.1

- [ ] **Task:** Factories e estados para todos os models.
  - **Acceptance criteria:**
    - Estados: `Product::active()`, `withPhotos()`, `Sale::cancelled()`, `Installment::overdue()`/`paid()`, `Customer::withBirthday()`
    - Todas as factories geram registros válidos para as constraints
  - **Traces:** database-schema (todas as tabelas)

- [ ] **Task:** Habilitar `Model::preventLazyLoading()` fora de produção e rodar Larastan nos models.
  - **Acceptance criteria:**
    - Lazy loading acidental lança exceção em dev/testes
    - `composer analyse` passa
  - **Traces:** Tech Stack (qualidade)

---

## Phase 4: Serviços de domínio

**Goal:** Regras puras de preço, parcelas, busca e mensagens de WhatsApp, totalmente testadas. · **Depends on:** Phase 3 · **Covers:** US-1.2, US-2.2, US-3.4, US-7.2, US-7.3, US-7.6, US-8.1, US-9.5

### Phase 4.1: Preço e parcelas

- [ ] **Task:** Classe `Money` (centavos inteiros) com formatação pt-BR (`R$ 99,90`).
  - **Acceptance criteria:**
    - Soma, subtração, multiplicação por inteiro e divisão sem float
    - Formatação `R$ 1.234,56`
    - Conversão de/para `decimal(10,2)`
  - **Feature tests:** `MoneyTest` → formatação, soma e arredondamento por centavos.
  - **Traces:** database-schema (colunas decimal)

- [ ] **Task:** `PricingCalculator`: preço no Pix, parcela sem juros, preço antigo válido e selo de desconto.
  - **Acceptance criteria:**
    - Pix = preço × (1 − desconto Pix); parcela = preço ÷ parcelas sem juros
    - Preço antigo só é considerado se maior que o preço de venda
    - Selo = `round((1 − preço ÷ preço antigo) × 100)`; sem desconto e peça Lançamento → "Novo"
    - Arredondamento: meio para cima em centavos (R$ 99,90 com 5% = R$ 94,91; R$ 279,70 com 5% = R$ 265,72), centralizado em um único ponto
  - **Feature tests:** `PricingCalculatorTest` → R$ 99,90 em 3x = R$ 33,30; R$ 99,90 com Pix 5% = R$ 94,91; R$ 139,90 → R$ 119,90 = −14%; preço antigo igual ou menor é ignorado; peça Lançamento sem desconto = "Novo"; desconto Pix 0% mantém o preço.
  - **Traces:** US-1.2; RN-15 a RN-19

- [ ] **Task:** `InstallmentSplitter`: divide o total do crediário em parcelas mensais.
  - **Acceptance criteria:**
    - Valor = total ÷ n arredondado para baixo no centavo; a última absorve a diferença
    - Vencimentos mensais a partir do 1º vencimento (padrão 1 mês após a venda); fim de mês respeita o último dia
    - n restrito a 1–6
  - **Feature tests:** `InstallmentSplitterTest` → R$ 100,00 em 3x = 33,33 + 33,33 + 33,34; soma sempre igual ao total; 1x gera 1 parcela; n=7 é recusado; 31/jan + 1 mês = 28/fev (ano não bissexto).
  - **Traces:** US-8.1; RN-75, RN-76

### Phase 4.2: Busca, telefone e mensagens

- [ ] **Task:** Normalização de texto e telefone: `SearchText::normalize()` e `Phone::normalize()`.
  - **Acceptance criteria:**
    - Texto sem acento, minúsculo e sem espaços extras
    - Telefone guarda só dígitos; mínimo 10 dígitos para ser válido; gera número `wa.me` com 55 quando faltar
  - **Feature tests:** `PhoneTest` → "(69) 99999-0000" vira dígitos; 9 dígitos é inválido; `SearchTextTest` → "Anéis" casa com "aneis".
  - **Traces:** US-6.1, US-7.2, US-1.3

- [ ] **Task:** `WhatsAppLink`: monta `https://wa.me/<numero>?text=<mensagem>` com a mensagem codificada.
  - **Acceptance criteria:**
    - Usa o WhatsApp de `store_settings` (ou o da cliente para recibo e parabéns)
    - Acentos, quebras de linha e símbolos são codificados corretamente
    - Sem número configurado, lança erro tratável para a UI
  - **Feature tests:** `WhatsAppLinkTest` → texto codificado e decodificável; número com máscara é normalizado.
  - **Traces:** US-2.2, US-3.1; RN-36

- [ ] **Task:** Construtores de mensagem: sacola, atalho, presente, recibo e parabéns.
  - **Acceptance criteria:**
    - Sacola: "Oi! Separei essas peças no site da Orvalha:" + itens "Nx Nome (Material) — R$ valor" + total e total no Pix + "Pode confirmar se tem tudo disponível?"
    - Presente inclui para quem, ocasião, faixa de gasto e detalhe opcional
    - Recibo: número, data, itens, desconto, total, forma de pagamento e parcelas
    - Parabéns: usa o percentual de presente de aniversário e a validade até o fim do mês
  - **Feature tests:** `MessageBuilderTest` → mensagem da sacola de exemplo do spec; recibo omite parcelas em venda à vista; parabéns usa o % configurado.
  - **Traces:** US-2.2, US-3.4, US-7.6, US-9.5; RN-35

---

## Phase 5: Fundação do front-end

**Goal:** Tokens, layouts e componentes compartilhados fiéis aos mockups. · **Depends on:** Phase 1 · **Covers:** design (vitrine e painel)

### Phase 5.1: Tokens e base

- [ ] **Task:** Portar os design tokens e a tipografia dos mockups para CSS (variáveis, reset e escala).
  - **Acceptance criteria:**
    - Variáveis de cor idênticas às dos mockups (vitrine: `--bg`, `--blush`, `--gold`, `--gold-deep`, `--ink`, `--line`, `--muted`, `--soft`, `--wa`, `--wine`; painel: `--bg #F8F4F2`, `--surface`, `--wine-soft`, `--ok`)
    - Fontes Bodoni Moda (display) e Jost (corpo) carregadas
    - Sem Tailwind: CSS próprio via Vite
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`:root`) e `.spec/init/design/Orvalha_Admin.html` (`:root`)
  - **Traces:** Overview (design fiel); Tech Stack (estilo)

### Phase 5.2: Layouts e componentes da vitrine

- [ ] **Task:** Layout Blade da vitrine: faixa de promoção, cabeçalho com menu/busca/favoritos/sacola e rodapé.
  - **Acceptance criteria:**
    - Estrutura visual idêntica ao mockup em desktop e celular (sem rolagem horizontal)
    - Slots para faixa (`#promo`), busca (`#mSearchRow`), círculos de categoria e rodapé
    - Meta tags e `lang="pt-BR"`
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`#promo`, `#mSearchRow`, `#circles`, rodapé)
  - **Traces:** US-1.1, US-1.6

- [ ] **Task:** Componentes Blade da vitrine: card de peça, botões, gaveta lateral (`#drawer`), modal (`#modal`), scrim e toast.
  - **Acceptance criteria:**
    - Card com foto, nome, preço, parcelamento, preço no Pix e selo; foto de hover
    - Gaveta e modal abrem/fecham com Alpine, foco preso e Esc fecha
    - Toast acessível (`role="status"`)
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`#grid`, `#drawer`, `#modal`, `#toast`, `#scrim`)
  - **Traces:** US-1.1, US-2.1

### Phase 5.3: Layout e componentes do painel

- [ ] **Task:** Layout Blade do painel com navegação (Painel, Peças, Categorias, Vendas, Nova venda, Clientes, Loja, Sair) e barra superior.
  - **Acceptance criteria:**
    - Navegação e item ativo idênticos ao mockup, responsivo no celular
    - Rótulos e ícones conforme as telas `viewDash`, `viewItems`, `viewCats`, `viewSales`, `viewSell`, `viewClients`, `viewStore`
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`#app`, navegação)
  - **Traces:** US-4.1

- [ ] **Task:** Componentes Blade/Livewire do painel: botões, campos, selects, interruptor, chips/badges de situação, tabela, estado vazio, diálogo de confirmação, toast e tile de indicador.
  - **Acceptance criteria:**
    - Campos inválidos ficam marcados com mensagem
    - Diálogo de confirmação reutilizável (exclusão, cancelamento)
    - Badge de situação da parcela nas 4 cores (Em aberto, Vence em N dias, Vencida, Paga)
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`#toast`, formulários, `viewParcels`/`openParcels`)
  - **Traces:** US-5.1, US-8.3

- [ ] **Task:** Rota local `/_design` com galeria dos componentes (somente `APP_ENV=local`).
  - **Acceptance criteria:**
    - Lista todos os componentes da vitrine e do painel em seus estados
    - Retorna 404 fora do ambiente local
  - **Traces:** Overview (fidelidade ao design)

---

## Phase 6: Acesso ao painel

**Goal:** Login, logout e proteção de todas as rotas do painel. · **Depends on:** Phase 3, Phase 5 · **Covers:** US-4.1

### Phase 6.1: Autenticação

- [ ] **Task:** Tela de login (Livewire) com e-mail e senha.
  - **Acceptance criteria:**
    - Credenciais válidas levam ao Painel
    - Credenciais inválidas mostram erro genérico sem revelar qual campo falhou
    - Limite de tentativas por e-mail+IP (5 por minuto)
  - **Feature tests:** `LoginTest` → login válido autentica; senha errada e e-mail inexistente mostram a mesma mensagem; 6ª tentativa em 1 minuto é bloqueada.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewLogin`)
  - **Traces:** US-4.1; RN-03

- [ ] **Task:** Logout e grupo de rotas `/painel` protegido por `auth`.
  - **Acceptance criteria:**
    - Qualquer rota `/painel/*` sem sessão redireciona para a entrada
    - "Sair" invalida a sessão, regenera o token e volta ao login
    - Há um único nível de acesso
  - **Feature tests:** `PanelAccessTest` → visitante é redirecionado em todas as rotas `/painel`; após logout a sessão não acessa mais o painel.
  - **Traces:** US-4.1; RN-03, RN-04

- [ ] **Task:** Criar a administradora por comando e seeder (`admin:create`, usando `ADMIN_EMAIL`/`ADMIN_PASSWORD`).
  - **Acceptance criteria:**
    - Comando cria ou atualiza o usuário com senha em hash
    - Sem variáveis definidas, o comando pede os dados interativamente
    - Não há cadastro público de usuários
  - **Feature tests:** `AdminCreateCommandTest` → cria usuário com hash; reexecutar não duplica.
  - **Traces:** US-4.1; table users

- [ ] **Task:** Tela inicial do painel (casca) com a rota raiz `/painel` e navegação funcionando.
  - **Acceptance criteria:**
    - Após o login abre `/painel` com o layout do admin
    - Itens de navegação apontam para rotas existentes (placeholders até as fases seguintes)
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewDash`, casca)
  - **Traces:** US-4.1

---

## Phase 7: Painel — categorias e peças

**Goal:** Cadastro completo de categorias e peças com fotos e visibilidade no site. · **Depends on:** Phase 3, Phase 5, Phase 6 · **Covers:** US-5.1, US-5.2, US-5.3, US-5.4

### Phase 7.1: Categorias

- [ ] **Task:** Tela de categorias: criar, renomear e reordenar.
  - **Acceptance criteria:**
    - Nome vazio é recusado; nome repetido (sem diferenciar maiúsculas e acentos) é recusado
    - A ordem arrastada/ajustada grava `position` e vale para menu, círculos e rodapé da vitrine
    - Layout fiel à tela de categorias
  - **Feature tests:** `CategoryManagementTest` → "Brincos" e "brincos" conflitam; nome vazio falha; reordenar persiste `position`.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewCats`)
  - **Traces:** US-5.4; RN-65, RN-66; table categories

- [ ] **Task:** Exclusão de categoria somente quando vazia.
  - **Acceptance criteria:**
    - Categoria com peças não é excluída e o painel informa quantas peças há e pede para movê-las
    - Categoria vazia é excluída após confirmação
  - **Feature tests:** `CategoryDeletionTest` → categoria com 3 peças retorna aviso "3 peças" e permanece; vazia é removida.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewCats`)
  - **Traces:** US-5.4; RN-67

### Phase 7.2: Formulário da peça

- [ ] **Task:** Formulário de peça com validação: nome, categoria, material, preço, preço antigo, descrição e flags.
  - **Acceptance criteria:**
    - Obrigatórios: nome, categoria, material e preço de venda > 0
    - Salvar sem nome ou preço marca os campos e não grava nada
    - Preço antigo menor ou igual ao preço de venda é zerado ao salvar
    - "Mais vendido" e "Lançamento" colocam a peça nas seções da vitrine
  - **Feature tests:** `ProductFormTest` → sem nome/preço nada é gravado; preço 0 falha; preço antigo ≤ preço vira nulo; preço antigo maior é mantido; criar e editar persistem os campos.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewForm`)
  - **Traces:** US-5.1; RN-60; table products

- [ ] **Task:** Prévia do card ao lado do formulário, com Pix e parcelas calculados.
  - **Acceptance criteria:**
    - A prévia atualiza enquanto se digita e usa o `PricingCalculator` com as configurações atuais
    - Mostra selo de desconto/Novo conforme as regras
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`#pv`, `#fabPv`)
  - **Traces:** US-5.1; RN-62

### Phase 7.3: Fotos e visibilidade

- [ ] **Task:** Upload, reordenação e remoção de fotos da peça (Spatie Media Library no R2).
  - **Acceptance criteria:**
    - Aceita imagens (jpg, png, webp) com tamanho máximo definido e rejeita outros tipos
    - A 1ª foto é a capa e a 2ª é a de hover; reordenar altera `order_column`
    - Remover foto exclui o arquivo do disco
  - **Feature tests:** `ProductPhotosTest` → upload grava em `media`; reordenar troca capa; tipo inválido é recusado; remover apaga o registro.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewForm`, fotos)
  - **Traces:** US-5.2; table media

- [ ] **Task:** Interruptor "No site" exigindo pelo menos 1 foto, no formulário e na lista.
  - **Acceptance criteria:**
    - Ligar "No site" sem foto é recusado com mensagem
    - Remover a última foto de uma peça ativa a desativa ou impede a remoção, com aviso
  - **Feature tests:** `ProductVisibilityRuleTest` → toggle sem foto falha; com foto liga; peça ativa sem fotos não aparece em `visible()`.
  - **Traces:** US-5.2; RN-61

### Phase 7.4: Lista e exclusão

- [ ] **Task:** Lista de peças com busca por nome, filtro por categoria e contadores de "no site" e "ocultas".
  - **Acceptance criteria:**
    - Busca ignora acentos e maiúsculas
    - Contadores refletem o filtro aplicado
    - Layout fiel à lista de peças
  - **Feature tests:** `ProductListTest` → busca por "colar" acha "Colar Ponto de Luz"; filtro por categoria; contagem de ativas e ocultas.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewItems`)
  - **Traces:** US-5.3; RN-64

- [ ] **Task:** Exclusão de peça com confirmação, preservando o histórico de vendas.
  - **Acceptance criteria:**
    - Pede confirmação e informa que não pode ser desfeito
    - Itens de vendas antigas mantêm nome, categoria, material e preço da época
  - **Feature tests:** `ProductDeletionTest` → excluir peça vendida mantém `sale_items.product_name`/`unit_price` e zera `product_id`; os totais da venda não mudam.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewItems`)
  - **Traces:** US-5.3; RN-63; tables products, sale_items

---

## Phase 8: Painel — configurações da loja

**Goal:** Tela Loja com todos os grupos de configuração refletindo na vitrine. · **Depends on:** Phase 3, Phase 5, Phase 6 · **Covers:** US-10.1, US-10.2

### Phase 8.1: Contato, horários e pagamento

- [ ] **Task:** Tela Loja (casca) com abas/grupos Contato, Horários, Pagamento, Promoções e Atendimento.
  - **Acceptance criteria:**
    - Estrutura idêntica à tela de configurações do mockup
    - Salvar exibe confirmação e mantém os valores
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewStore`)
  - **Traces:** US-10.1, US-10.2

- [ ] **Task:** Salvar contato (WhatsApp, Instagram, endereço, cidade, estado, CNPJ) e horários (seg a sex, sábado, domingo).
  - **Acceptance criteria:**
    - WhatsApp é normalizado para dígitos e validado (mín. 10)
    - Horários são texto livre (ex.: 9h às 18h)
    - O WhatsApp salvo passa a ser o destino de todas as mensagens
  - **Feature tests:** `StoreContactSettingsTest` → WhatsApp inválido é recusado; salvar altera `StoreSetting::current()`.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewStore`)
  - **Traces:** US-10.1; RN-36

- [ ] **Task:** Salvar pagamento: desconto Pix, parcelas sem juros e garantia do banho.
  - **Acceptance criteria:**
    - Desconto Pix entre 0 e 30; parcelas entre 1 e 12; valores fora do limite são recusados
    - Garantia é texto livre e opcional
    - Alteração vale imediatamente para preços da vitrine e do PDV
  - **Feature tests:** `StorePaymentSettingsTest` → 31% e 0 parcelas são recusados; 30% e 12 aceitos; preço no Pix recalculado após salvar.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewStore`)
  - **Traces:** US-10.1; RN-15, RN-16

### Phase 8.2: Promoções e atendimento

- [ ] **Task:** Lista editável de frases da faixa do topo.
  - **Acceptance criteria:**
    - Adicionar, editar, remover e reordenar frases
    - Frases vazias são descartadas ao salvar
  - **Feature tests:** `PromoPhrasesTest` → salvar com 3 frases e 1 vazia grava 3; ordem preservada.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewStore`)
  - **Traces:** US-10.2; table promo_phrases

- [ ] **Task:** Frase de boas-vindas e botão de atendimento (logo da Orvalha ou foto da loja enviada).
  - **Acceptance criteria:**
    - Upload de foto grava `chat_button_photo_path` no R2; remover volta para a logo
    - Tipo e tamanho do arquivo validados
  - **Feature tests:** `ChatButtonSettingsTest` → upload grava o caminho; remover limpa; arquivo não imagem é recusado.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewStore`)
  - **Traces:** US-10.2; table store_settings

- [ ] **Task:** Atalhos de conversa editáveis (rótulo, mensagem do WhatsApp, opção "abre ajuda para presente").
  - **Acceptance criteria:**
    - Atalhos vazios são descartados; ordem preservada
    - O atalho de presente não exige mensagem
  - **Feature tests:** `ChatShortcutsTest` → atalho sem rótulo é descartado; atalho de presente salva com `opens_gift_section` e sem mensagem.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewStore`)
  - **Traces:** US-10.2; table chat_shortcuts

- [ ] **Task:** Convite de ajuda (liga/desliga e 1, 2, 3 ou 5 minutos) e presente de aniversário (0–50%).
  - **Acceptance criteria:**
    - Minutos aceitam apenas 1, 2, 3 ou 5
    - Percentual de presente fora de 0–50 é recusado
  - **Feature tests:** `HelpInviteSettingsTest` → 4 minutos é recusado; 51% é recusado; 50% e 5 min aceitos.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewStore`)
  - **Traces:** US-10.2

---

## Phase 9: Painel — clientes

**Goal:** Cadastro, ficha e exclusão de clientes, com cadastro rápido reutilizável. · **Depends on:** Phase 3, Phase 4, Phase 5, Phase 6 · **Covers:** US-6.1, US-6.2, US-6.3

### Phase 9.1: Cadastro

- [ ] **Task:** Formulário de cliente com validação e componente reutilizável de cadastro rápido.
  - **Acceptance criteria:**
    - Nome obrigatório; WhatsApp opcional com DDD (mín. 10 dígitos) e único; cidade padrão é a da loja; aniversário dia/mês válido; observações livres
    - O componente de cadastro rápido salva sem sair da tela de origem e emite o evento com a cliente criada
  - **Feature tests:** `CustomerFormTest` → WhatsApp com 9 dígitos é recusado; repetido é recusado; sem WhatsApp é aceito; aniversário 31/02 é recusado; cidade em branco usa a cidade da loja.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewClients`, cadastro rápido em `viewSell`)
  - **Traces:** US-6.1; RN-70; table customers

### Phase 9.2: Lista, ficha e exclusão

- [ ] **Task:** Lista de clientes com busca por nome (sem acento) ou telefone.
  - **Acceptance criteria:**
    - Busca ignora acentos e maiúsculas e aceita dígitos do telefone
    - Layout fiel à lista de clientes
  - **Feature tests:** `CustomerListSearchTest` → "mar" acha "Mariana"; dígitos acham pelo telefone.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewClients`)
  - **Traces:** US-6.3

- [ ] **Task:** Serviço `CustomerSummary`: total comprado, nº de compras, ticket médio e saldo em aberto.
  - **Acceptance criteria:**
    - Considera só vendas não canceladas
    - Ticket médio = total ÷ compras (0 se não houver compras)
    - Saldo = soma das parcelas não pagas do crediário de vendas não canceladas
  - **Feature tests:** `CustomerSummaryTest` → venda cancelada não entra em total, compras nem saldo; ticket médio de 2 vendas (100 e 50) = 75; sem compras = 0.
  - **Traces:** US-6.2; RN-73, RN-74

- [ ] **Task:** Ficha da cliente: resumo, parcelas, 8 compras mais recentes com "ver todas", botões WhatsApp e nova venda.
  - **Acceptance criteria:**
    - Mostra os 4 indicadores e a lista de parcelas com situação
    - Histórico mostra 8 e "ver todas" expande
    - Botão WhatsApp só aparece com telefone; "Nova venda" abre o PDV já com a cliente selecionada
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewClient`, `openClient`)
  - **Traces:** US-6.2; RN-73

- [ ] **Task:** Exclusão de cliente com confirmação, bloqueada quando há compras.
  - **Acceptance criteria:**
    - Cliente com qualquer venda registrada (inclusive cancelada) não pode ser excluída, com mensagem explicativa
    - Cliente sem compras é excluída após confirmação
  - **Feature tests:** `CustomerDeletionTest` → com venda falha e mantém o registro; sem venda é removida.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewClient`)
  - **Traces:** US-6.3; RN-72

---

## Phase 10: Painel — registro de venda (mini PDV)

**Goal:** Registrar vendas com itens, cliente, pagamento, descontos e crediário. · **Depends on:** Phase 3, Phase 4, Phase 9 · **Covers:** US-7.1, US-7.2, US-7.3, US-8.1

### Phase 10.1: Regras de registro

- [ ] **Task:** Serviço `SaleRegistrar`: cria a venda com itens congelados em transação.
  - **Acceptance criteria:**
    - Exige pelo menos 1 item e quantidades ≥ 1
    - Grava `product_name`, `category_name`, `material_name`, `unit_price` e `line_total` do momento
    - Atribui o número sequencial e `sold_at`
    - Mudar o preço da peça depois não altera a venda
  - **Feature tests:** `SaleRegistrarTest` → venda sem itens falha; itens congelam preço e nomes; alterar preço depois não muda `unit_price`; números sequenciais.
  - **Traces:** US-7.1; RN-90, RN-91

- [ ] **Task:** Regras de pagamento e descontos no `SaleRegistrar`.
  - **Acceptance criteria:**
    - Pix aplica automaticamente o desconto Pix; Dinheiro, Débito e Crédito não aplicam
    - "Outro desconto" soma ao Pix e o desconto total nunca passa do subtotal
    - Total = subtotal − desconto Pix − outro desconto; ambos os descontos gravados separados
    - À vista = 1 parcela; Crédito aceita 1 ou 2–6x; Crediário 1–6x; débito/dinheiro/Pix só à vista
  - **Feature tests:** `SalePaymentRulesTest` → Pix de R$ 200 com 5% = desconto 10,00; outro desconto de R$ 500 em subtotal de R$ 200 é limitado/recusado; Crédito 7x é recusado; Débito 2x é recusado; total correto nos 5 meios.
  - **Traces:** US-7.3; RN-96, RN-97, RN-98

- [ ] **Task:** Regras de data, canal e cliente da venda.
  - **Acceptance criteria:**
    - Data padrão hoje; data anterior é aceita; data futura é recusada; a hora gravada é a do registro
    - Canal obrigatório (WhatsApp, Loja física ou Instagram)
    - Cliente opcional, exceto no crediário
  - **Feature tests:** `SaleDateAndCustomerTest` → data de amanhã falha; ontem passa com hora atual; crediário sem cliente falha; Pix sem cliente passa.
  - **Traces:** US-7.1, US-8.1; RN-92, RN-93, RN-94

- [ ] **Task:** Geração das parcelas do crediário no registro.
  - **Acceptance criteria:**
    - Cria `installments` com o `InstallmentSplitter`; 1º vencimento padrão = 1 mês após a venda, ou o escolhido
    - Crédito não gera parcelas locais; só grava `installments_count`
    - Sem juros, multa ou entrada
  - **Feature tests:** `CrediarioGenerationTest` → R$ 100,00 em 3x gera 33,33/33,33/33,34 mensais; crédito em 3x não cria `installments`; 1º vencimento customizado respeitado.
  - **Traces:** US-8.1; RN-75, RN-76, RN-79

- [ ] **Task:** Busca de cliente para a venda (`CustomerSearch`).
  - **Acceptance criteria:**
    - Acha pelo começo de qualquer palavra do nome, sem acento; também por 3 ou mais dígitos do telefone
    - Retorna no máximo 6 resultados
  - **Feature tests:** `CustomerSearchTest` → "ma" acha Mariana e Gabriela Martins; "123" acha por telefone; 8 resultados limitam a 6; 2 dígitos não buscam por telefone.
  - **Traces:** US-7.2; RN-71

### Phase 10.2: Tela do PDV

- [ ] **Task:** Grade de peças e carrinho da venda.
  - **Acceptance criteria:**
    - Tocar na peça adiciona 1; − e + ajustam; 0 remove
    - Mostra subtotal e total atualizados ao vivo
    - Somente peças existentes (ativas ou não) podem ser vendidas
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewSell`, `#cList`, `#cart`, `#cartSum`)
  - **Traces:** US-7.1; RN-90

- [ ] **Task:** Seletor de cliente na venda com busca e cadastro rápido.
  - **Acceptance criteria:**
    - Resultados da busca em até 6 itens; selecionar mostra a cliente escolhida e permite trocá-la
    - Cadastro rápido salva e já seleciona a cliente
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewSell`)
  - **Traces:** US-7.2; RN-70, RN-71

- [ ] **Task:** Painel de pagamento: forma, parcelas, 1º vencimento, canal, data e outro desconto.
  - **Acceptance criteria:**
    - Opções de parcelas conforme a forma escolhida; ao trocar para crediário o padrão vira 2x
    - Resumo mostra subtotal, desconto Pix, outro desconto e total
    - Data não permite escolher futuro
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewSell`)
  - **Traces:** US-7.3; RN-98

- [ ] **Task:** Envio do formulário: registrar, abrir o detalhe e limpar a tela; aviso do crediário sem cliente.
  - **Acceptance criteria:**
    - Registrar redireciona ao detalhe da venda e a nova venda abre vazia
    - Crediário sem cliente mostra o aviso e leva o foco ao campo de cliente
    - Erros de validação ficam visíveis sem perder o carrinho
  - **Feature tests:** `SellFlowTest` → fluxo completo registra e redireciona; crediário sem cliente não grava e retorna erro no campo de cliente.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewSell`)
  - **Traces:** US-7.1, US-8.1; RN-92, RN-95

---

## Phase 11: Painel — vendas, cancelamento e recibo

**Goal:** Consultar vendas, ver detalhes, cancelar/reativar e enviar recibo. · **Depends on:** Phase 10 · **Covers:** US-7.4, US-7.5, US-7.6

### Phase 11.1: Lista e resumo

- [ ] **Task:** Consulta de vendas com filtros: período (Hoje, 7 dias, 30 dias, Tudo), forma de pagamento, "Só canceladas" e busca por cliente, peça ou número.
  - **Acceptance criteria:**
    - A lista abre em Hoje; busca ignora acentos e aceita `#0616`
    - Filtros combinam entre si
  - **Feature tests:** `SalesQueryTest` → filtro Hoje exclui ontem; "Só canceladas" mostra só canceladas; busca por nome da peça, cliente e número.
  - **Traces:** US-7.4; RN-99

- [ ] **Task:** Agrupamento por dia, totais do dia e resumo do filtro.
  - **Acceptance criteria:**
    - Cada dia mostra quantidade e total sem contar canceladas
    - Resumo: número de vendas (e canceladas), faturamento, ticket médio e quanto falta receber dessas vendas
    - Paginação de 50 em 50 com "Mostrar mais"
  - **Feature tests:** `SalesSummaryTest` → venda cancelada não soma no dia nem no faturamento; ticket médio = faturamento ÷ vendas; 51 vendas mostram 50 e liberam "Mostrar mais".
  - **Traces:** US-7.4; RN-100, RN-101

- [ ] **Task:** Tela de lista de vendas.
  - **Acceptance criteria:**
    - Filtros, grupos por dia, venda cancelada riscada e resumo conforme o mockup
    - Clique abre o detalhe
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewSales`)
  - **Traces:** US-7.4

- [ ] **Task:** Tela de detalhe da venda.
  - **Acceptance criteria:**
    - Mostra peças, descontos, total, cliente, pagamento (forma, condição, recebido no crediário), parcelas e onde vendeu
    - Venda cancelada exibe data, hora e motivo
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewSale`)
  - **Traces:** US-7.4; RN-102

### Phase 11.2: Cancelamento e recibo

- [ ] **Task:** Serviço de cancelamento e reativação.
  - **Acceptance criteria:**
    - Cancelar exige motivo; grava `cancelled_at` e `cancel_reason`; venda não pode ser editada
    - Se houve parcelas recebidas, devolve o valor já recebido para o aviso
    - Reativar zera os dois campos e a venda volta a contar em tudo
  - **Feature tests:** `SaleCancellationTest` → sem motivo falha; cancelar tira a venda de `notCancelled()`, de `CustomerSummary` e do a receber; aviso traz o valor recebido (ex.: R$ 33,33); reativar restaura os números; tentar editar uma venda é recusado.
  - **Traces:** US-7.5; RN-103, RN-104, RN-105, RN-106

- [ ] **Task:** Interface de cancelar e reativar venda.
  - **Acceptance criteria:**
    - Diálogo com campo de motivo obrigatório e aviso do valor já recebido
    - Venda cancelada mostra o botão "Reativar venda"
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewSale`)
  - **Traces:** US-7.5

- [ ] **Task:** Botão "Enviar recibo" pelo WhatsApp da cliente.
  - **Acceptance criteria:**
    - Só aparece com cliente que tem WhatsApp e venda não cancelada
    - Abre o WhatsApp dela com a mensagem do `MessageBuilder` (número, data, itens, desconto, total, forma e parcelas)
  - **Feature tests:** `SaleReceiptTest` → link gerado para cliente com telefone; sem telefone ou venda cancelada não oferece o recibo.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewSale`)
  - **Traces:** US-7.6; RN-107

---

## Phase 12: Painel — crediário

**Goal:** Receber e desfazer parcelas, ver situação e priorizar clientes com atraso. · **Depends on:** Phase 10, Phase 11 · **Covers:** US-8.2, US-8.3, US-6.3

### Phase 12.1: Regras

- [ ] **Task:** Serviço `InstallmentService`: receber e desfazer parcela.
  - **Acceptance criteria:**
    - Receber grava `paid_at` com a data de hoje; desfazer volta para em aberto
    - Parcela de venda cancelada não pode ser recebida
    - O saldo da cliente cai na hora
  - **Feature tests:** `InstallmentReceiveTest` → receber marca hoje e reduz o saldo; desfazer reabre; receber parcela de venda cancelada falha.
  - **Traces:** US-8.2; RN-78

- [ ] **Task:** Situação das parcelas e consulta de a receber.
  - **Acceptance criteria:**
    - Em aberto, Vence em N dias (até 7), Vencida (antes de hoje) e Paga (com a data)
    - A consulta de a receber soma parcelas não pagas de vendas não canceladas e separa o total vencido
  - **Feature tests:** `ReceivablesTest` → soma só parcelas abertas de vendas ativas; vencido destacado; parcela de venda cancelada ignorada.
  - **Traces:** US-8.3, US-9.4; RN-77

- [ ] **Task:** Ordenação e filtro de clientes por atraso.
  - **Acceptance criteria:**
    - A lista mostra primeiro quem tem parcelas vencidas
    - O filtro "Com parcelas em aberto" restringe a lista
  - **Feature tests:** `CustomerOverdueOrderTest` → cliente com vencida vem antes da sem; filtro retorna só quem tem parcelas abertas.
  - **Traces:** US-6.3, US-8.3; RN-80

### Phase 12.2: Interface

- [ ] **Task:** Componente de parcelas com situação e botões Receber/Desfazer na ficha, no detalhe da venda e no Painel.
  - **Acceptance criteria:**
    - Badge de cada situação conforme o design; Receber atualiza o saldo sem recarregar a página
    - O mesmo componente é reutilizado nas três telas
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`openParcels`, `viewClient`, `viewSale`)
  - **Traces:** US-8.2, US-8.3

- [ ] **Task:** Destaque de clientes com parcelas vencidas e filtro na lista de clientes.
  - **Acceptance criteria:**
    - Clientes em atraso aparecem no topo com marca de vencida
    - O filtro "Com parcelas em aberto" é visível e funcional
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewClients`)
  - **Traces:** US-6.3, US-8.3

---

## Phase 13: Painel — indicadores e gráfico

**Goal:** Indicadores do período com comparação e gráfico de evolução. · **Depends on:** Phase 10, Phase 11 · **Covers:** US-9.1, US-9.2

### Phase 13.1: Regras de métricas

- [ ] **Task:** `PeriodResolver` para 7, 30 e 90 dias (contando hoje) e o período anterior de mesmo tamanho.
  - **Acceptance criteria:**
    - 7 dias = hoje e os 6 dias anteriores; o período anterior termina no dia antes do início
    - Datas no fuso America/Porto_Velho
  - **Feature tests:** `PeriodResolverTest` → 30 dias a partir de 30/set cobre 1/set–30/set e o anterior 2/ago–31/ago; limites inclusivos.
  - **Traces:** US-9.1; RN-110

- [ ] **Task:** `DashboardMetrics`: faturamento, vendas, ticket médio, peças vendidas e variação percentual.
  - **Acceptance criteria:**
    - Faturamento = soma dos totais com descontos; ticket médio = faturamento ÷ vendas; peças = soma das quantidades
    - Só vendas registradas e não canceladas entram
    - Variação vs período anterior: alta (▲) ou queda (▼); anterior zerado não divide por zero
  - **Feature tests:** `DashboardMetricsTest` → cancelada fica fora; ticket médio de 2 vendas (R$ 100 e R$ 50) = 75; variação de 100 → 150 = +50%; período anterior zero retorna "sem base" sem erro.
  - **Traces:** US-9.1; RN-110

- [ ] **Task:** `SalesSeries`: série por dia (7 e 30 dias) ou por semana (90 dias) para faturamento, vendas e peças.
  - **Acceptance criteria:**
    - Dias sem venda aparecem com zero
    - 90 dias agrupa por semana; os totais da série somam ao total do período
  - **Feature tests:** `SalesSeriesTest` → dias vazios = 0; soma da série igual ao faturamento; 90 dias agrupa em semanas.
  - **Traces:** US-9.2; RN-111

### Phase 13.2: Interface

- [ ] **Task:** Tela Painel: seletor de período, tiles de indicadores com ▲ verde / ▼ vinho.
  - **Acceptance criteria:**
    - Seletor 7/30/90 atualiza os tiles sem recarregar
    - Indicadores e setas conforme o mockup
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewDash`)
  - **Traces:** US-9.1

- [ ] **Task:** Gráfico de evolução com alternância faturamento/vendas/peças e visão em tabela.
  - **Acceptance criteria:**
    - Alternar a métrica redesenha o gráfico; a visão em tabela mostra os mesmos valores
    - Tooltip acessível por teclado e legível em celular
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`#plot`, `showTip`)
  - **Traces:** US-9.2

---

## Phase 14: Painel — rankings e widgets

**Goal:** Rankings, composição das vendas, crediário a receber, últimas vendas e aniversariantes. · **Depends on:** Phase 12, Phase 13 · **Covers:** US-9.3, US-9.4, US-9.5

### Phase 14.1: Rankings e composição

- [ ] **Task:** Peças mais vendidas (top 6) e faturamento por categoria com rateio proporcional do desconto.
  - **Acceptance criteria:**
    - Top 6 por quantidade no período
    - O desconto total da venda é distribuído proporcionalmente aos itens (por `line_total`) no faturamento da peça e da categoria
    - A soma do faturamento por categoria é igual ao faturamento do período; % somam 100
  - **Feature tests:** `TopProductsTest` → venda de 2 itens (R$ 100 e R$ 50) com desconto de R$ 15 rateia 10 e 5; 7 peças retornam 6; empate desempata por faturamento; `RevenueByCategoryTest` → soma bate com o faturamento; canceladas fora.
  - **Traces:** US-9.3; RN-111 (tabela de indicadores)

- [ ] **Task:** Composição: como pagaram (à vista × parcelado e por forma), onde vendeu e melhores clientes (top 5).
  - **Acceptance criteria:**
    - % à vista × parcelado sobre o faturamento (parcelado = 2 parcelas ou mais)
    - Vendas por canal em quantidade; top 5 clientes por valor comprado no período, sem canceladas
  - **Feature tests:** `PaymentBreakdownTest` → Pix e débito contam à vista, crédito 3x conta parcelado; `TopCustomersTest` → 6 clientes retornam 5; venda sem cliente não entra; `ChannelBreakdownTest` → contagem por canal.
  - **Traces:** US-9.3

### Phase 14.2: Widgets e interface

- [ ] **Task:** Card "A receber no crediário" com a lista das 5 próximas parcelas e o botão Receber.
  - **Acceptance criteria:**
    - Soma as parcelas em aberto de todas as vendas, independente do período, e mostra quanto está vencido
    - Lista as 5 próximas por vencimento e Receber atualiza o card na hora
  - **Feature tests:** `ReceivableCardTest` → período 7 dias não altera o total; 6 parcelas listam 5 em ordem de vencimento; receber reduz o total.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewDash`, `openParcels`)
  - **Traces:** US-9.4; RN-112

- [ ] **Task:** Lista das 6 últimas vendas não canceladas, clicáveis para o detalhe.
  - **Acceptance criteria:**
    - Ordenadas da mais recente; canceladas ausentes
    - Clique abre o detalhe da venda
  - **Feature tests:** `LatestSalesTest` → 8 vendas (1 cancelada) retornam as 6 mais recentes válidas.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewDash`)
  - **Traces:** US-9.3; RN-113

- [ ] **Task:** Aniversariantes do mês com botão "Parabéns".
  - **Acceptance criteria:**
    - Primeiro as que ainda vão fazer (por dia), depois as que já passaram
    - Rótulo "Hoje", "Em N dias" ou "Já passou"; clientes sem aniversário ficam fora
    - "Parabéns" abre o WhatsApp dela com a mensagem e o desconto de presente configurado (padrão 10%, válido até o fim do mês)
  - **Feature tests:** `BirthdaysTest` → ordem: hoje, depois futuros, depois passados; 28/fev vs 29/fev em ano não bissexto tratado; mensagem usa `birthday_gift_percent`; sem WhatsApp não oferece o botão.
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewDash`)
  - **Traces:** US-9.5; RN-114, RN-115

- [ ] **Task:** Montar a tela Painel completa: rankings, composição, widgets e estados vazios.
  - **Acceptance criteria:**
    - Todos os blocos do mockup aparecem; sem dados mostram estado vazio amigável
    - Responsivo em celular sem rolagem horizontal
  - **Design ref:** `.spec/init/design/Orvalha_Admin.html` (`viewDash`)
  - **Traces:** US-9.3, US-9.4, US-9.5

---

## Phase 15: Vitrine — catálogo e home

**Goal:** Home pública lendo peças, categorias e configurações do banco. · **Depends on:** Phase 4, Phase 5, Phase 7, Phase 8 · **Covers:** US-1.1, US-1.2, US-1.6

### Phase 15.1: Dados e cards

- [ ] **Task:** `StorefrontCatalog`: peças visíveis, seções Mais vendidos e Lançamentos (máx. 4) e categorias ordenadas.
  - **Acceptance criteria:**
    - Só peças com "No site" e pelo menos 1 foto
    - Lançamentos limitados a 4; categorias na ordem do painel
    - Consultas com eager loading, sem N+1
  - **Feature tests:** `StorefrontCatalogTest` → 6 lançamentos retornam 4; peça sem foto ou oculta ausente; ordem das categorias segue `position`.
  - **Traces:** US-1.1; RN-10, RN-11, RN-12

- [ ] **Task:** `ProductCardData` com preços, parcelamento, Pix e selo, sem o material.
  - **Acceptance criteria:**
    - Usa o `PricingCalculator` e as configurações da loja
    - Preço antigo riscado só quando maior; selo de % ou "Novo"
    - O material não é exposto no card
  - **Feature tests:** `ProductCardDataTest` → R$ 99,90 mostra 3x R$ 33,30 e Pix; R$ 139,90→119,90 mostra −14%; sem desconto e Lançamento mostra "Novo"; a saída não contém material.
  - **Traces:** US-1.1, US-1.2; RN-13, RN-15 a RN-19

### Phase 15.2: Página inicial

- [ ] **Task:** Página inicial com faixa de promoções, círculos de categoria, banners das coleções e grades Mais vendidos e Lançamentos.
  - **Acceptance criteria:**
    - Estrutura e textos fiéis ao mockup, com dados reais do banco
    - Cada peça exibe a foto de capa e a segunda foto ao passar o mouse
    - Sem peças, mostra estado vazio da loja
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`#circles`, `#shop`, `#grid`, `#gridNew`)
  - **Traces:** US-1.1; RN-13, RN-14

- [ ] **Task:** Faixa do topo alternando as frases a cada 4 segundos.
  - **Acceptance criteria:**
    - Alterna a cada 4 s via Alpine; frases vazias não aparecem
    - Com uma única frase, não alterna
  - **Feature tests:** `PromoBarTest` → a view renderiza apenas frases preenchidas, na ordem de `position`.
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`#promo`)
  - **Traces:** US-1.6; RN-24

- [ ] **Task:** Seção "Atendimento de verdade" e rodapé com categorias, contato, horários e Instagram.
  - **Acceptance criteria:**
    - Endereço, cidade, estado, Instagram e horários vêm de `store_settings`
    - Categorias do rodapé na ordem do painel
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`#how`, `#insta`, rodapé)
  - **Traces:** US-1.1, US-10.1

- [ ] **Task:** Imagens otimizadas, metadados de SEO e desempenho da home.
  - **Acceptance criteria:**
    - Fotos servidas em webp com tamanhos responsivos e `loading="lazy"`
    - `<title>`, descrição e Open Graph definidos
  - **Traces:** US-1.1 (catálogo público)

---

## Phase 16: Vitrine — busca, filtros, página da peça e favoritos

**Goal:** Navegação refinada do catálogo e página de detalhe. · **Depends on:** Phase 15 · **Covers:** US-1.3, US-1.4, US-1.5

### Phase 16.1: Busca e filtros

- [ ] **Task:** Busca ao vivo (Livewire) por nome e categoria, ignorando acentos.
  - **Acceptance criteria:**
    - Filtra enquanto digita (debounce curto)
    - Sem resultado mostra o convite para falar com a loja (link do WhatsApp)
  - **Feature tests:** `StorefrontSearchTest` → "anel" acha "Anel Solitário" e peças da categoria Anéis; "xyz" retorna vazio com convite; peça oculta nunca aparece.
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`#mSearchRow`)
  - **Traces:** US-1.3; RN-20

- [ ] **Task:** Filtros por categoria, Promoções e coleção ouro/prata (banners).
  - **Acceptance criteria:**
    - Promoções lista só peças com preço antigo maior que o de venda
    - Coleção dourada = Ouro 18k; prata = Prata 925
    - Filtros podem ser limpos
  - **Feature tests:** `StorefrontFiltersTest` → Promoções exclui peça sem preço antigo válido; coleção filtra pelo material; categoria restringe a lista.
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`#circles`, banners)
  - **Traces:** US-1.3; RN-21

### Phase 16.2: Página da peça

- [ ] **Task:** Modal/página da peça com fotos, categoria, preços, detalhes e descrição.
  - **Acceptance criteria:**
    - Galeria com miniaturas; preços iguais aos do card
    - Detalhes (banho, antialérgico, garantia, caixinha) e prazo da garantia vindo das configurações
    - O material aparece aqui (e não no card)
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`#modal`, `#mMain`, `#mThumbs`, `#mInfo`)
  - **Traces:** US-1.4; RN-23

- [ ] **Task:** Botões "Quero essa" e "Tirar dúvida sobre esta peça".
  - **Acceptance criteria:**
    - "Tirar dúvida" abre o WhatsApp com a pergunta pronta citando a peça
    - Dúvidas de tamanho (ex.: anéis) são resolvidas pelo botão "Tirar dúvida" no WhatsApp; não há guia de tamanhos no site
  - **Feature tests:** `ProductQuestionLinkTest` → o link contém o nome da peça e usa o WhatsApp da loja.
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`#mInfo`)
  - **Traces:** US-1.4; RN-23

### Phase 16.3: Favoritos

- [ ] **Task:** Favoritos no navegador (Alpine + localStorage) com coração nos cards e lista no ícone do topo.
  - **Acceptance criteria:**
    - O coração marca/desmarca; a lista abre pelo ícone do topo
    - Nada é enviado ao servidor; ids de peças removidas ou ocultas são ignorados na lista
    - Funciona mesmo com localStorage indisponível (sem quebrar a página)
  - **Feature tests:** `FavoritesEndpointTest` → o endpoint de hidratação devolve apenas peças visíveis para os ids pedidos e ignora ids inexistentes.
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (coração do card, ícone de favoritos)
  - **Traces:** US-1.5; RN-01, RN-22

---

## Phase 17: Vitrine — sacola e envio para o atendimento

**Goal:** Sacola persistida no navegador e envio para o WhatsApp sem registrar venda. · **Depends on:** Phase 4, Phase 15 · **Covers:** US-2.1, US-2.2, US-2.3

### Phase 17.1: Sacola

- [ ] **Task:** Estado da sacola (Alpine + localStorage) com id e quantidade, hidratado pelo servidor.
  - **Acceptance criteria:**
    - "Quero essa" adiciona 1; se já existe, soma +1; − e + ajustam; quantidade 0 remove
    - Preços e totais vêm sempre do servidor; peças removidas ou ocultas saem da sacola com aviso
    - A sacola sobrevive a recarregar a página
  - **Feature tests:** `BagTotalsTest` → endpoint calcula subtotal, total no Pix e parcelamento a partir dos ids e quantidades; peça oculta é descartada; quantidade 0 não entra; mudança de preço reflete no total.
  - **Traces:** US-2.1; RN-30, RN-31, RN-34

- [ ] **Task:** Gaveta da sacola com itens, subtotal, total no Pix e parcelamento sem juros.
  - **Acceptance criteria:**
    - Lista de itens com ajuste de quantidade e remoção
    - Rodapé mostra subtotal, total no Pix e parcelas sem juros
    - Estado vazio amigável
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`#drawer`, `#bagList`, `#bagFoot`)
  - **Traces:** US-2.1; RN-32

### Phase 17.2: Envio e informações

- [ ] **Task:** Botão "Enviar para o atendimento" com aviso e abertura do WhatsApp.
  - **Acceptance criteria:**
    - Mostra "A gente confirma se tem as peças e combina entrega e pagamento. Nada é cobrado agora."
    - Abre `wa.me` com a mensagem da sacola (itens com material, total e total no Pix)
    - Nenhuma venda, pedido ou dado pessoal é gravado no servidor
  - **Feature tests:** `BagMessageTest` → mensagem do exemplo do spec (1x Colar + 2x Argola) gerada exatamente; material presente na mensagem; enviar não cria registros em `sales`.
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`#bagFoot`)
  - **Traces:** US-2.2; RN-33, RN-35, RN-36

- [ ] **Task:** Informações de entrega e pagamento na sacola e no rodapé.
  - **Acceptance criteria:**
    - Ji-Paraná: entrega pela loja ou retirada; outras cidades: envio e frete combinados na conversa
    - Formas aceitas na conversa: Pix, cartão e dinheiro (crediário a critério da loja)
    - O site não calcula frete
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`#drawer`, rodapé)
  - **Traces:** US-2.3; RN-37, RN-38, RN-39

- [ ] **Task:** Teste de ponta a ponta da sacola no navegador (Playwright).
  - **Acceptance criteria:**
    - Adicionar duas peças, ajustar quantidade e abrir o link do WhatsApp com a mensagem esperada
    - Recarregar a página mantém a sacola
  - **Traces:** US-2.1, US-2.2

---

## Phase 18: Vitrine — atendimento humanizado

**Goal:** Botão, balão, convite, presente e guia de cuidados. · **Depends on:** Phase 8, Phase 15, Phase 17 · **Covers:** US-3.1, US-3.2, US-3.3, US-3.4, US-3.5

### Phase 18.1: Botão e atalhos

- [ ] **Task:** Botão fixo de atendimento e painel "Atendimento Orvalha" com atalhos.
  - **Acceptance criteria:**
    - Botão com logo da Orvalha (ou foto da loja) e selo do WhatsApp
    - Painel mostra a frase de boas-vindas e os atalhos da loja; cada atalho abre o WhatsApp com a pergunta pronta
    - O atalho de presente não abre o WhatsApp e leva à seção de presente
    - Nenhum texto usa nome de atendente, status "online" ou prazo de resposta
  - **Feature tests:** `ChatShortcutsRenderTest` → atalhos vêm de `chat_shortcuts` na ordem; atalho de presente renderiza âncora, os demais `wa.me`.
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`#carePanel`, `#careWelcome`, `#careOpts`)
  - **Traces:** US-3.1; RN-40, RN-41, RN-42, RN-43

### Phase 18.2: Balão e convite de ajuda

- [ ] **Task:** Balão de boas-vindas.
  - **Acceptance criteria:**
    - Aparece uma vez por visita: no computador 5 s após abrir; no celular só depois da rolagem
    - Não aparece com sacola, página da peça ou atendimento abertos; fechar no X não o mostra de novo na visita
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`#careBubble`, `showBubble`)
  - **Traces:** US-3.2; RN-44, RN-45

- [ ] **Task:** Convite de ajuda para cliente em dúvida.
  - **Acceptance criteria:**
    - Abre após o tempo configurado (padrão 2 min) com a sacola vazia, no máximo uma vez por visita
    - Nunca aparece sobre sacola, peça, atendimento ou guia; não aparece se desligado no painel
    - Botões: Falar com a atendente (WhatsApp), É para presente (seção de presente) e Agora não, obrigada
  - **Feature tests:** `HelpInviteConfigTest` → a página injeta `enabled` e `minutes` conforme `store_settings`; desligado não injeta o gatilho.
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`#helpBg`, `#helpBox`, `openHelp`)
  - **Traces:** US-3.3; RN-46, RN-47, RN-48

### Phase 18.3: Presente e guia

- [ ] **Task:** Formulário "Ajuda para presente".
  - **Acceptance criteria:**
    - Campos: para quem (Mãe, Namorada, Amiga, Filha, Para mim), ocasião, quanto quer gastar (até R$ 100, R$ 100 a R$ 200, acima de R$ 200) e detalhe opcional
    - Ao enviar, abre o WhatsApp com a mensagem montada
  - **Feature tests:** `GiftMessageTest` → a mensagem contém as respostas escolhidas; detalhe vazio é omitido.
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`#gift`)
  - **Traces:** US-3.4; RN-49, RN-50

- [ ] **Task:** Guia de cuidados e seção com 4 dicas.
  - **Acceptance criteria:**
    - Seção acima do rodapé com 4 dicas e o link "Ver o guia completo"
    - O guia tem: no dia a dia, o que evitar, como limpar, como guardar, brilho que muda e garantia (prazo vindo das configurações)
    - Abrir por uma dica rola até o assunto; os links do rodapé "Cuidados com as peças" e "Trocas e garantia" abrem o guia
    - Dicas são gerais da loja, sem dica por peça
  - **Design ref:** `.spec/init/design/Orvalha_Semijoias.html` (`#guideBg`, `#g-use`, `#g-avoid`, `#g-clean`, `#g-keep`, `#g-shine`, `#g-warranty`, `openGuide`)
  - **Traces:** US-3.5; RN-51, RN-52, RN-53, RN-54

---

## Phase 19: Qualidade, segurança e publicação

**Goal:** Fechar testes ponta a ponta, segurança, desempenho e deploy no Laravel Cloud. · **Depends on:** Phases 1 a 18 · **Covers:** todos os fluxos

### Phase 19.1: Qualidade e segurança

- [ ] **Task:** Testes de ponta a ponta do painel e da vitrine (Playwright).
  - **Acceptance criteria:**
    - Admin: login → cadastrar peça com foto → registrar venda → cancelar → reativar
    - Vitrine: abrir home → favoritar → sacola → link do WhatsApp
    - Rodam no CI
  - **Traces:** US-4.1, US-5.1, US-7.1, US-7.5, US-1.5, US-2.2

- [ ] **Task:** Revisão de segurança: autorização de todas as rotas do painel, proteção CSRF, rate limiting e mass assignment.
  - **Acceptance criteria:**
    - Teste percorre todas as rotas `/painel/*` e confirma redirecionamento sem sessão
    - Uploads validam tipo e tamanho; nenhum model usa `$guarded = []`
    - Cabeçalhos de segurança e cookies `secure`/`httpOnly` em produção
  - **Feature tests:** `PanelRoutesAuthTest` → toda rota com prefixo `painel` exige autenticação; `MassAssignmentTest` → campos sensíveis não são preenchíveis em massa.
  - **Traces:** US-4.1; RN-03

- [ ] **Task:** Passada final de qualidade: Pint, Larastan sem erros e cobertura das regras de negócio.
  - **Acceptance criteria:**
    - `composer lint`, `composer analyse` e `composer test` passam
    - Cada regra RN-xx de cálculo (preço, parcelas, descontos, cancelamento, métricas) tem pelo menos um teste
  - **Traces:** Tech Stack (testes e qualidade)

### Phase 19.2: Desempenho, dados e deploy

- [ ] **Task:** Revisão de desempenho: eager loading, índices usados e cache de configurações da loja.
  - **Acceptance criteria:**
    - Home e Painel sem N+1 (`preventLazyLoading` não dispara nos testes)
    - Consultas de métricas usam os índices de `sales`, `installments` e `products`
  - **Traces:** database-schema (índices); US-9.1, US-1.1

- [ ] **Task:** Dados de demonstração para desenvolvimento (seeder opcional) baseados nos mockups.
  - **Acceptance criteria:**
    - Seeder de demo cria categorias, peças, clientes e vendas coerentes e só roda em ambiente local
    - Não roda em produção
  - **Traces:** Overview (mockups com dados provisórios)

- [ ] **Task:** Configuração de publicação no Laravel Cloud com PostgreSQL e Cloudflare R2.
  - **Acceptance criteria:**
    - Variáveis de ambiente documentadas (`DB_*`, `AWS_*`/R2, `APP_KEY`, `ADMIN_*`)
    - Migrations e seeders de lookup rodam no deploy; `admin:create` documentado
    - Fila e scheduler configurados quando necessários
  - **Traces:** Tech Stack (hospedagem)

- [ ] **Task:** Atualizar o `README.md` com instruções de instalação, testes e deploy.
  - **Acceptance criteria:**
    - Passos de setup local, comandos de qualidade e visão geral do projeto
  - **Traces:** Tech Stack (dev env)

## Open Questions

- Pendências da loja que afetam textos: prazo da garantia do banho, política de troca, regra de entrega em Ji-Paraná, WhatsApp/endereço/CNPJ e fotos reais.
- Fora do MVP até decisão da loja: controle de estoque, juros/multa/entrada no crediário, múltiplos perfis no painel, juros do cartão acima de 3x e política de privacidade (LGPD).
