# Orvalha Semijoias — Project Description

## Overview

A Orvalha Semijoias é uma loja de semijoias de Ji-Paraná (RO) com duas partes: uma **vitrine on-line sem pagamento no site**, em que a cliente monta uma sacola e a **envia para o WhatsApp da loja**, e um **painel administrativo** onde a equipe cadastra peças e categorias, registra as vendas fechadas (mini PDV), gerencia clientes e crediário, e acompanha métricas. **A venda é sempre fechada na conversa; o site nunca cobra nada.**

O diferencial é o **atendimento humanizado**: a vitrine fala como "a gente", sem nome de atendente, sem status "online" e sem prometer tempo de resposta. Há botão flutuante de atendimento com atalhos, balão de boas-vindas, convite de ajuda para cliente em dúvida, ajuda para presente e guia de cuidados.

O público é a **cliente** (vitrine pública, sem login, sem dados pessoais guardados no servidor) e a **administradora** (equipe da loja, login com e-mail e senha, um único nível de acesso). O design **deve seguir exatamente** os mockups HTML entregues (`Orvalha_Semijoias.html` e `Orvalha_Admin.html`): fontes Bodoni Moda (display) e Jost (corpo), paleta dourado (`#A9803F`/`#8A6630`), vinho (`#5B1F2E`), blush (`#EFDDD7`), tinta (`#221C1D`) e verde WhatsApp/ok (`#1A7F45`). Na versão final a vitrine lê peças, categorias e configurações direto do banco do painel (nos mockups os dois não estão ligados).

**Fora do escopo do MVP:** pagamento on-line, cálculo automático de frete, nota fiscal, controle de estoque (sem baixa na venda), edição de venda registrada (corrige-se cancelando e relançando), juros/multa/entrada no crediário, múltiplos níveis de acesso e métricas de visitas do site.

### Key Concepts

- **Vitrine:** site público com catálogo, busca, favoritos, sacola e envio para o WhatsApp. Só mostra peças com "No site" ligado e ao menos 1 foto.
- **Sacola:** lista de peças que a cliente separa; não é pedido. Vive só no navegador (localStorage); "Quero essa" soma +1; quantidade 0 remove. Mostra subtotal, total no Pix e parcelamento sem juros.
- **Favoritos:** peças marcadas com coração, só no aparelho da cliente.
- **Atendimento:** conversa no WhatsApp da loja (número em Loja → Contato) onde a venda é fechada; atalhos geram perguntas prontas.
- **Peça (produto):** nome, categoria, material (Ouro 18k ou Prata 925), preço de venda (> 0), preço antigo opcional (zerado se ≤ preço de venda), descrição, fotos (1ª capa, 2ª no hover), flags "No site", "Mais vendido" e "Lançamento". Material não aparece no card, só na mensagem do WhatsApp.
- **Categoria:** nome único (case-insensitive), com ordem definida no painel (menu, círculos e rodapé); só pode ser excluída vazia.
- **Preço no Pix:** preço × (1 − desconto Pix); padrão 5%, configurável de 0 a 30%.
- **Parcelamento sem juros:** preço ÷ parcelas; padrão 3x, configurável de 1 a 12.
- **Selo de desconto:** `round((1 − preço ÷ preço antigo) × 100)`; sem desconto, peça Lançamento mostra "Novo". "Lançamentos" exibe até 4 peças.
- **Cliente (cadastro):** existe só no painel. Nome obrigatório; WhatsApp opcional com DDD (mín. 10 dígitos, único); cidade (padrão a da loja); aniversário dia/mês; observações. Não pode ser excluída se tiver compras.
- **Venda:** registro feito pela loja (WhatsApp, Loja física ou Instagram), numeração sequencial (#0616), itens com preço gravado na hora, cliente opcional (obrigatória no crediário), data até hoje (nunca futura). Não se edita; cancela-se com motivo e pode ser reativada.
- **Formas de pagamento:** Pix (à vista, desconto Pix automático), Dinheiro, Débito (à vista), Crédito (à vista ou 2x–6x) e Crediário (1x–6x). "Outro desconto" em R$ soma ao Pix e nunca passa do subtotal; total = subtotal − desconto Pix − outro desconto, com os dois descontos guardados separados.
- **Crediário:** venda parcelada pela loja; parcelas mensais de 1x a 6x a partir do 1º vencimento (padrão 1 mês após a venda). Parcela = total ÷ n arredondada para baixo no centavo, última absorve a diferença (R$ 100 em 3x = 33,33 + 33,33 + 33,34). Situações: Em aberto, Vence em N dias (≤ 7), Vencida, Paga. Receber marca paga com a data de hoje; Desfazer reabre.
- **Venda cancelada:** fica no histórico riscada com data, hora e motivo; sai do painel, da ficha da cliente e do crediário a receber. Se já houve parcelas recebidas, o aviso informa o valor para a loja combinar a devolução.
- **Painel (métricas):** considera só vendas registradas e não canceladas; períodos 7/30/90 dias comparados ao período anterior de mesmo tamanho; faturamento, vendas, ticket médio, peças vendidas, top 6 peças, faturamento por categoria (desconto rateado proporcionalmente), como pagaram, onde vendeu, top 5 clientes, a receber no crediário e aniversariantes do mês.
- **Configurações da loja:** contato (WhatsApp, Instagram, endereço, cidade, estado), horários, desconto Pix, parcelas sem juros, garantia do banho, frases da faixa do topo (alternam a cada 4 s), frase de boas-vindas, botão de atendimento (logo ou foto), atalhos de conversa, convite de ajuda (liga/desliga; 1, 2, 3 ou 5 min; padrão 2) e presente de aniversário (0–50%, padrão 10%).
- **Convite de ajuda:** abre após 2 min (configurável) com a sacola vazia, no máximo uma vez por visita e nunca sobre sacola, peça, atendimento ou guia.
- **Ajuda para presente:** formulário (para quem, ocasião, faixa de gasto, detalhe opcional) que abre o WhatsApp com a mensagem montada.
- **Guia de cuidados:** seção/overlay geral da loja (sem dicas por peça) com no dia a dia, o que evitar, como limpar, como guardar, brilho que muda e garantia.

## Tech Stack

Repositório pré-código (apenas README); stack derivada da intenção do desenvolvedor e confirmada em entrevista. Versões: últimas estáveis de PHP e Laravel na criação do projeto (ambiente tem PHP 8.3.6 e o Composer ainda não rodou).

| Layer | Technology |
| --- | --- |
| Backend | PHP (última estável, mín. 8.3) + Laravel (última estável) |
| Vitrine (frontend) | Blade + Livewire + Alpine.js, renderizada no servidor; sacola e favoritos em localStorage |
| Painel admin | Livewire + Blade custom, portando fielmente o `Orvalha_Admin.html` (sem Filament) |
| Estilo | CSS fiel aos mockups (tokens de cor), Bodoni Moda + Jost via Google Fonts |
| Banco de dados | PostgreSQL |
| Mídia | Spatie Media Library, fotos no Cloudflare R2 |
| Autenticação | Login do painel por e-mail e senha (nível único) |
| Testes | Pest |
| Qualidade | Laravel Pint + Larastan |
| Hospedagem | Laravel Cloud |
| Integrações | WhatsApp via links `wa.me` com mensagem pré-montada (sem API) |

## Core Workflows

### 1. Montar sacola e enviar para o atendimento

1. Cliente navega por categorias, busca (nome e categoria, enquanto digita), Promoções ou coleções ouro/prata, e favorita peças.
2. Em "Quero essa" a peça entra na sacola (+1 se já existir); na sacola ajusta quantidades e remove.
3. Vê subtotal, total no Pix e parcelamento sem juros, com o aviso "Nada é cobrado agora".
4. "Enviar para o atendimento" abre o WhatsApp da loja com a mensagem:

```
Oi! Separei essas peças no site da Orvalha:

• 1x Colar Ponto de Luz (Ouro 18k) — R$ 99,90
• 2x Argola Lisa Média (Ouro 18k) — R$ 179,80

Total: R$ 279,70 (ou R$ 265,72 no Pix)

Pode confirmar se tem tudo disponível?
```

5. O site não registra o envio; a venda só existe quando a loja a registra (fluxo 3). Entrega: Ji-Paraná por entrega ou retirada; outras cidades combinadas na conversa.

### 2. Atendimento humanizado

1. Botão flutuante abre o painel "Atendimento Orvalha" com boas-vindas e atalhos (presente, tamanho de anel, entrega/envio, outra dúvida); cada atalho abre o WhatsApp com a pergunta pronta, exceto "presente", que leva à seção de ajuda para presente.
2. Balão de boas-vindas: uma vez por visita, 5 s após abrir no computador e só após rolar no celular; não aparece com sacola, peça ou atendimento abertos.
3. Convite de ajuda: 2 min no site com sacola vazia → "Ficou na dúvida?" com Falar com a atendente, É para presente e Agora não, obrigada.
4. Ajuda para presente: formulário vira mensagem no WhatsApp; a loja responde com sugestões.
5. Guia de cuidados abre pelas 4 dicas, pelos links do rodapé ou por "Ver o guia completo", rolando ao assunto.

### 3. Registrar nova venda (mini PDV)

1. Vendas → Nova venda; tocar nas peças e ajustar quantidades (mín. 1 item).
2. Escolher cliente pela busca (começo de qualquer palavra do nome sem acento, ou 3+ dígitos do telefone, até 6 resultados) ou cadastrar na hora; obrigatório no crediário.
3. Escolher pagamento; crédito/crediário pedem parcelas (crediário: 1º vencimento; ao trocar para crediário o padrão vira 2x).
4. Marcar onde vendeu, a data (hoje ou anterior) e outro desconto, se houver.
5. Registrar venda → abre o detalhe e a tela volta vazia; dali é possível enviar o recibo pelo WhatsApp (só com cliente com WhatsApp e venda não cancelada).

### 4. Crediário e recebimento de parcelas

1. Venda no crediário gera parcelas mensais na ficha da cliente.
2. Parcela vencida e não paga aparece como Vencida no Painel e na lista de clientes (que prioriza quem tem vencidas e filtra "Com parcelas em aberto").
3. Receber (na ficha, no detalhe da venda ou no Painel) marca como paga hoje e o saldo cai na hora; Desfazer reabre.

### 5. Cancelar e reativar venda

1. Abrir a venda → Cancelar venda → informar o motivo (obrigatório).
2. A venda fica riscada no histórico e sai de todos os números e do crediário a receber; se houve parcelas recebidas, o aviso informa o valor.
3. Reativar venda devolve tudo como era.

### 6. Cadastro de peças e categorias

1. Peça exige nome, categoria, material e preço; "No site" só liga com pelo menos 1 foto (o interruptor da lista recusa sem foto).
2. Upload, reordenação e remoção de fotos; a prévia mostra o card como ficará no site com Pix e parcelas.
3. Excluir peça pede confirmação e é irreversível; vendas antigas mantêm nome e preço da época.
4. Categorias: nome único, ordem editável (reflete no site), exclusão só se vazia (o painel informa quantas peças e pede para movê-las).
5. Lista de peças com busca, filtro por categoria e contagem no site/ocultas.

### 7. Clientes (cadastro, ficha e aniversariantes)

1. Cadastro na tela Clientes ou rápido dentro da venda (já fica selecionada); WhatsApp único.
2. Ficha: total comprado, nº de compras, ticket médio, saldo em aberto, parcelas, 8 compras mais recentes (com "ver todas"), botões WhatsApp e nova venda; canceladas não contam.
3. Exclusão só sem compras, com confirmação.
4. Painel lista aniversariantes do mês (Hoje / Em N dias / Já passou); Parabéns abre o WhatsApp com a mensagem e o desconto de presente configurado.

### 8. Painel de métricas

1. Escolher período (7, 30 ou 90 dias); cada indicador compara com o período anterior de mesmo tamanho (▲ verde / ▼ vinho).
2. Gráfico de faturamento, vendas ou peças por dia (por semana em 90 dias), com visão em tabela.
3. Cards e rankings: faturamento, vendas, ticket médio, peças vendidas, peças mais vendidas, faturamento por categoria, como pagaram, onde vendeu, melhores clientes, a receber no crediário (independe do período) e as 6 últimas vendas.

### 9. Configurações da loja

1. A tela Loja concentra contato, horários, pagamento, promoções e atendimento.
2. Ao salvar, valem para o site inteiro (preços no Pix, parcelas, faixa do topo, atalhos, botão, convite, presente de aniversário); frases e atalhos vazios são descartados.

### 10. Acesso ao painel

1. Todas as telas do painel exigem login por e-mail e senha; "Sair" encerra a sessão e volta à entrada.
2. Nível de acesso único nesta versão.

## Open Questions

- Prazo real da garantia do banho (mockup mostra "[X] meses").
- WhatsApp, endereço completo e CNPJ da loja; fotos reais das peças e da loja.
- Política de troca (prazo e condições) e regra de entrega em Ji-Paraná (grátis, fixa ou por bairro).
- Controle de estoque: a venda deve baixar o estoque no futuro?
- Crediário: juros/multa por atraso e entrada?
- Mais de uma pessoa no painel com permissões diferentes?
- Cartão acima de 3x: repasse de juros ou loja absorve?
- Texto da política de privacidade (LGPD), pois a loja guarda nome, telefone e aniversário das clientes.
