# STyli Prates — Database Schema

<!-- inputs: project-description.md@sha256:37dbaf66aeb6 user-stories.md@sha256:d2f58522dd32 -->

## Overview

O modelo gira em torno de **products** (peças) organizados em **categories** e feitos de um **material**, e de **sales** (vendas registradas no painel), que têm **sale_items** com nome e preço congelados, uma forma de pagamento, um canal de venda e, opcionalmente, uma **customer**. Vendas no crediário geram **installments** (parcelas). As configurações da loja ficam em uma única linha de **store_settings**, com listas filhas (**promo_phrases**, **chat_shortcuts**).

Convenções (Laravel + Eloquent + MySQL 8): tabelas plurais em snake_case, `id` bigint, chaves estrangeiras `<singular>_id`, `created_at`/`updated_at`. Campos categóricos (material, forma de pagamento, canal) usam **tabelas de lookup**, sem enums. Fotos das peças usam a tabela `media` do Spatie Media Library (Cloudflare R2). A vitrine é pública e não guarda dados da cliente no servidor; sacola e favoritos ficam no navegador e por isso não têm tabela.

## Schema (DBML)

```dbml
// Lookup tables first, then domain tables.

Table materials {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  is_active boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp
}

Table payment_methods {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  allows_installments boolean [not null, default: false]
  max_installments smallint [not null, default: 1]
  applies_pix_discount boolean [not null, default: false]
  generates_receivables boolean [not null, default: false]
  is_active boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp
}

Table sale_channels {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  is_active boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp
}

Table users {
  id bigint [pk, increment]
  name varchar [not null]
  email varchar [unique, not null]
  password varchar [not null]
  remember_token varchar [null]
  created_at timestamp
  updated_at timestamp
}

Table categories {
  id bigint [pk, increment]
  name varchar [not null]
  name_normalized varchar [unique, not null]
  position integer [not null, default: 0]
  created_at timestamp
  updated_at timestamp
}

Table products {
  id bigint [pk, increment]
  category_id bigint [ref: > categories.id, not null]
  material_id bigint [ref: > materials.id, not null]
  name varchar [not null]
  description text [null]
  price decimal(10,2) [not null]
  old_price decimal(10,2) [null]
  is_active boolean [not null, default: false]
  is_best_seller boolean [not null, default: false]
  is_new_release boolean [not null, default: false]
  created_at timestamp
  updated_at timestamp

  indexes {
    (category_id, is_active)
    (is_active, is_best_seller)
    (is_active, is_new_release)
  }
}

// Spatie Media Library (fotos das peças, coleção "photos"; order_column define capa e hover)
Table media {
  id bigint [pk, increment]
  model_type varchar [not null]
  model_id bigint [not null]
  uuid uuid [unique, null]
  collection_name varchar [not null]
  name varchar [not null]
  file_name varchar [not null]
  mime_type varchar [null]
  disk varchar [not null]
  conversions_disk varchar [null]
  size bigint [not null]
  manipulations json [not null]
  custom_properties json [not null]
  generated_conversions json [not null]
  responsive_images json [not null]
  order_column integer [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (model_type, model_id)
  }
}

Table customers {
  id bigint [pk, increment]
  name varchar [not null]
  whatsapp varchar [unique, null]
  city varchar [null]
  birth_day smallint [null]
  birth_month smallint [null]
  notes text [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    birth_month
  }
}

// Contador usado para a numeração sequencial das vendas (#0616).
Table counters {
  name varchar [pk]
  value bigint [not null, default: 0]
}

Table sales {
  id bigint [pk, increment]
  number bigint [unique, not null]
  customer_id bigint [ref: > customers.id, null]
  payment_method_id bigint [ref: > payment_methods.id, not null]
  sale_channel_id bigint [ref: > sale_channels.id, not null]
  installments_count smallint [not null, default: 1]
  first_due_date date [null]
  subtotal decimal(10,2) [not null]
  pix_discount decimal(10,2) [not null, default: 0]
  other_discount decimal(10,2) [not null, default: 0]
  total decimal(10,2) [not null]
  sold_at timestamp [not null]
  cancelled_at timestamp [null]
  cancel_reason text [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    sold_at
    cancelled_at
    customer_id
  }
}

Table sale_items {
  id bigint [pk, increment]
  sale_id bigint [ref: > sales.id, not null]
  product_id bigint [ref: > products.id, null]
  category_id bigint [ref: > categories.id, null]
  product_name varchar [not null]
  category_name varchar [null]
  material_name varchar [null]
  unit_price decimal(10,2) [not null]
  quantity smallint [not null, default: 1]
  line_total decimal(10,2) [not null]
  created_at timestamp
  updated_at timestamp
}

Table installments {
  id bigint [pk, increment]
  sale_id bigint [ref: > sales.id, not null]
  number smallint [not null]
  amount decimal(10,2) [not null]
  due_date date [not null]
  paid_at date [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (sale_id, number) [unique]
    (paid_at, due_date)
  }
}

Table store_settings {
  id bigint [pk, increment]
  whatsapp varchar [null]
  instagram varchar [null]
  address varchar [null]
  city varchar [null]
  state varchar [null]
  cnpj varchar [null]
  hours_weekdays varchar [null]
  hours_saturday varchar [null]
  hours_sunday varchar [null]
  pix_discount_percent smallint [not null, default: 5]
  max_installments smallint [not null, default: 3]
  plating_warranty varchar [null]
  welcome_message varchar [null]
  chat_button_photo_path varchar [null]
  help_invite_enabled boolean [not null, default: true]
  help_invite_minutes smallint [not null, default: 2]
  birthday_gift_percent smallint [not null, default: 10]
  banner_enabled boolean [not null, default: false]
  banner_eyebrow varchar [null]
  banner_title varchar [null]
  banner_text varchar [null]
  banner_button varchar [null]
  banner_link varchar [not null, default: 'all'] // all | sale | gift | id da categoria
  banner_photo_path varchar [null]
  created_at timestamp
  updated_at timestamp
}

Table promo_phrases {
  id bigint [pk, increment]
  store_setting_id bigint [ref: > store_settings.id, not null]
  phrase varchar [not null]
  position integer [not null, default: 0]
  created_at timestamp
  updated_at timestamp
}

Table chat_shortcuts {
  id bigint [pk, increment]
  store_setting_id bigint [ref: > store_settings.id, not null]
  label varchar [not null]
  whatsapp_message varchar [null]
  opens_gift_section boolean [not null, default: false]
  position integer [not null, default: 0]
  created_at timestamp
  updated_at timestamp
}
```

## Relationships

- Uma **category** tem muitas **products**; cada **product** pertence a uma **category** e a um **material**.
- Uma **product** tem muitas fotos na tabela **media** (relação polimórfica do Spatie por `model_type`/`model_id`).
- Uma **customer** tem muitas **sales**; a venda pode não ter cliente, exceto no crediário (regra de aplicação).
- Uma **sale** pertence a uma **payment_method** e a um **sale_channel**, e tem muitos **sale_items**.
- Um **sale_item** referencia opcionalmente a **product** e a **category** de origem e guarda nome, categoria, material e preço da época.
- Uma **sale** no crediário tem muitas **installments**.
- **store_settings** tem uma única linha e muitas **promo_phrases** e **chat_shortcuts**.

## Lookup Table Seeds

- **materials:** Ouro 18k (`ouro-18k`), Prata 925 (`prata-925`).
- **payment_methods:** Pix (`pix`, à vista, aplica desconto Pix), Dinheiro (`dinheiro`, à vista), Débito (`debito`, à vista), Crédito (`credito`, parcelas 1 a 6), Crediário (`crediario`, parcelas 1 a 6, gera parcelas a receber).
- **sale_channels:** WhatsApp (`whatsapp`), Loja física (`loja-fisica`), Instagram (`instagram`).
- **store_settings:** uma linha inicial com desconto Pix 5, parcelas sem juros 3, convite de ajuda ligado em 2 minutos e presente de aniversário 10.

## Notes & Conventions

- **Sem soft deletes:** peças são excluídas de verdade (US-5.3) e o histórico depende dos dados congelados em `sale_items` (`product_name`, `category_name`, `material_name`, `unit_price`). `product_id` e `category_id` em `sale_items` usam `nullOnDelete`.
- **Cancelamento** (US-7.5) é estado da venda: `cancelled_at` + `cancel_reason`. Reativar zera os dois. Todas as métricas filtram `cancelled_at IS NULL`.
- **Situação da parcela** (Em aberto, Vence em N dias, Vencida, Paga) é derivada de `due_date` e `paid_at`; não é coluna.
- **Parcelas:** a última parcela absorve a diferença de centavos (US-8.1); `UNIQUE (sale_id, number)`. Vendas em crédito guardam só `installments_count`, sem linhas em `installments`, porque a operadora recebe (usa `payment_methods.generates_receivables`).
- **Exclusão de cliente** (US-6.3): `sales.customer_id` com `restrictOnDelete`; clientes com compras não podem ser removidas.
- **Categorias:** `name_normalized` (minúsculo, sem acento) garante nome único sem diferenciar maiúsculas; excluir exige categoria sem peças (`restrictOnDelete` em `products.category_id`).
- **Fotos:** usam `media` do Spatie (exceção deliberada à regra do `_path`, já que a biblioteca é parte do stack). A capa é a de menor `order_column` e a segunda é a do hover. "No site" só liga com pelo menos 1 foto (validação de aplicação). `chat_button_photo_path` é a foto da loja para o botão de atendimento.
- **Aniversário:** `birth_day` e `birth_month` (dia/mês, sem ano), conforme US-6.1 e US-9.5.
- **`sales.number`:** sequencial exibido como `#0616`, gerado pela tabela de apoio `counters` (linha `sales`) travada com `lockForUpdate` dentro da transação do registro, sem duplicidade e sem buracos; `sold_at` guarda a data escolhida (nunca futura) com a hora do registro.
- **Limites** a validar na aplicação e, quando possível, com `CHECK`: `price > 0`, `pix_discount_percent` 0–30, `max_installments` 1–12, `birthday_gift_percent` 0–50, `help_invite_minutes` em 1, 2, 3 ou 5, `installments_count` 1–6.
- **Tabelas padrão do Laravel** (`sessions`, `cache`, `jobs`, `password_reset_tokens`, `failed_jobs`, `migrations`) vêm das migrations do framework e não foram repetidas aqui.
- **Conceitos sem tabela:** Vitrine, Sacola e Favoritos (ficam no navegador, RN-01), Preço no Pix, Parcelamento sem juros e Selo de desconto (calculados a partir de `products` e `store_settings`), Painel de métricas (consultas agregadas sobre `sales`, `sale_items` e `installments`), Convite de ajuda, Ajuda para presente e Guia de cuidados (comportamento e conteúdo da vitrine; só os parâmetros ficam em `store_settings`) e Atendimento (WhatsApp por link, sem persistir conversas).

## Open Questions

- Garantia do banho: `plating_warranty` é texto livre até a loja definir o prazo.
- Se o controle de estoque entrar no futuro, `products` ganhará quantidade e `sale_items` baixará o estoque.
- Se juros ou entrada no crediário forem aprovados, `sales` e `installments` precisarão de colunas novas.
- Se houver perfis diferentes no painel, `users` precisará de uma tabela de papéis.
- A política de privacidade (LGPD) pode exigir registro de consentimento em `customers`.
