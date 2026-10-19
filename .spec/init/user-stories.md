# Orvalha Semijoias — User Stories

<!-- inputs: project-description.md@sha256:9aca46ff8072 -->

## Overview

A Orvalha Semijoias é uma loja de Ji-Paraná (RO) com uma vitrine on-line sem pagamento, que envia a sacola da cliente para o WhatsApp da loja, e um painel administrativo para cadastrar peças, registrar vendas fechadas, gerenciar clientes e crediário e acompanhar métricas. O atendimento é humanizado ("a gente") e a venda é sempre fechada na conversa.

**User Types:**
- **Cliente** - visitante da vitrine pública, sem login e sem dados guardados no servidor.
- **Administradora** - equipe da loja, entra no painel com e-mail e senha (nível de acesso único).

---

## 1. Vitrine: catálogo e navegação

### US-1.1: Ver o catálogo de peças ativas
**As a** Cliente
**I want to** ver as peças da loja organizadas por categoria e seções
**So that** eu encontre o que quero sem precisar perguntar

**Acceptance Criteria:**
- [ ] Só aparecem peças com "No site" ligado e pelo menos 1 foto
- [ ] "Mais vendidos" lista as peças marcadas como Mais vendido
- [ ] "Lançamentos" mostra no máximo 4 peças marcadas como Lançamento
- [ ] Categorias aparecem no menu, nos círculos e no rodapé na ordem definida no painel
- [ ] O card mostra foto, nome, preço, parcelamento e preço no Pix, sem o material
- [ ] A segunda foto aparece ao passar o mouse sobre a foto do card

**Expected Result:** A cliente navega por um catálogo que reflete exatamente o que está ativo no painel.

---

### US-1.2: Ver preços, Pix, parcelas e selos corretos
**As a** Cliente
**I want to** ver o preço no Pix, o parcelamento e o desconto de cada peça
**So that** eu saiba quanto vou pagar

**Acceptance Criteria:**
- [ ] Preço no Pix = preço × (1 − desconto Pix); R$ 99,90 com 5% mostra R$ 94,91 (arredondamento para cima em centavos)
- [ ] Parcelado = preço ÷ parcelas sem juros; R$ 99,90 em 3x mostra 3x de R$ 33,30
- [ ] Preço antigo só aparece riscado quando é maior que o preço de venda
- [ ] Peça com desconto mostra selo com `round((1 − preço ÷ preço antigo) × 100)`%; R$ 139,90 → R$ 119,90 mostra −14%
- [ ] Sem desconto, peça Lançamento mostra selo "Novo"
- [ ] Desconto Pix e número de parcelas vêm das configurações da loja

**Expected Result:** Todos os valores exibidos seguem as regras de preço e as configurações vigentes.

---

### US-1.3: Buscar e filtrar peças
**As a** Cliente
**I want to** buscar por nome ou categoria e filtrar por categoria, promoções e coleção
**So that** eu ache a peça rapidamente

**Acceptance Criteria:**
- [ ] A busca por nome e categoria filtra enquanto digita
- [ ] Busca sem resultado convida a cliente a falar com a loja
- [ ] Filtro Promoções lista apenas peças com preço antigo
- [ ] Os banners filtram a coleção dourada (Ouro 18k) ou prata (Prata 925)
- [ ] Filtro por categoria lista só as peças da categoria

**Expected Result:** A cliente restringe o catálogo e, sem resultado, é levada ao atendimento.

---

### US-1.4: Ver a página da peça
**As a** Cliente
**I want to** ver fotos e detalhes de uma peça
**So that** eu decida se quero comprá-la

**Acceptance Criteria:**
- [ ] A página mostra fotos, categoria, preços, detalhes (banho, antialérgico, garantia, caixinha) e descrição
- [ ] Botões "Quero essa" e "Tirar dúvida sobre esta peça" (abre o WhatsApp com a pergunta pronta)
- [ ] O prazo da garantia vem das configurações da loja

**Expected Result:** A cliente tem as informações para adicionar à sacola ou tirar dúvida.

---

### US-1.5: Favoritar peças
**As a** Cliente
**I want to** marcar peças com um coração
**So that** eu as encontre depois no mesmo aparelho

**Acceptance Criteria:**
- [ ] O coração marca e desmarca a peça
- [ ] O ícone do topo lista as peças favoritas
- [ ] Favoritos ficam apenas no navegador (localStorage); nada é enviado ao servidor
- [ ] Favorito de peça removida ou oculta não aparece na lista

**Expected Result:** A cliente mantém uma lista pessoal sem cadastro.

---

### US-1.6: Ver a faixa de promoções
**As a** Cliente
**I want to** ver as frases promocionais no topo
**So that** eu saiba das condições da loja

**Acceptance Criteria:**
- [ ] A faixa alterna as frases cadastradas a cada 4 segundos
- [ ] Frases vazias não aparecem

**Expected Result:** O topo exibe as frases configuradas na tela Loja.

---

## 2. Sacola e envio para o atendimento

### US-2.1: Montar a sacola
**As a** Cliente
**I want to** adicionar, ajustar e remover peças da sacola
**So that** eu separe o que quero antes de falar com a loja

**Acceptance Criteria:**
- [ ] "Quero essa" adiciona 1 unidade; se já existe, soma +1
- [ ] Os botões − e + alteram a quantidade; quantidade 0 remove a peça
- [ ] A sacola mostra subtotal, total no Pix e parcelamento sem juros
- [ ] A sacola persiste no navegador e o site não registra nada no servidor

**Expected Result:** A cliente tem uma lista de peças com totais corretos.

---

### US-2.2: Enviar a sacola para o WhatsApp
**As a** Cliente
**I want to** enviar a sacola para o atendimento
**So that** a loja confirme as peças e combine entrega e pagamento

**Acceptance Criteria:**
- [ ] O botão "Enviar para o atendimento" mostra o aviso "Nada é cobrado agora"
- [ ] Abre o WhatsApp do número cadastrado em Loja → Contato
- [ ] A mensagem lista "Nx Nome (Material) — R$ valor" por item, o total e o total no Pix, e termina com "Pode confirmar se tem tudo disponível?"
- [ ] O material aparece na mensagem, não no card
- [ ] Nenhuma venda é criada ao enviar

**Expected Result:** A conversa começa no WhatsApp com a lista de peças pronta.

---

### US-2.3: Informar entrega e pagamento
**As a** Cliente
**I want to** saber como funcionam entrega e pagamento
**So that** eu não tenha dúvidas antes de comprar

**Acceptance Criteria:**
- [ ] O site informa: Ji-Paraná por entrega ou retirada na loja; outras cidades com envio e frete combinados na conversa
- [ ] O site informa as formas aceitas na conversa: Pix, cartão e dinheiro (crediário a critério da loja)
- [ ] O site não calcula frete

**Expected Result:** A cliente entende que entrega e pagamento são combinados no atendimento.

---

## 3. Atendimento humanizado

### US-3.1: Botão de atendimento com atalhos
**As a** Cliente
**I want to** abrir o atendimento com atalhos de conversa
**So that** eu fale com a loja sem escrever do zero

**Acceptance Criteria:**
- [ ] Botão fixo com a logo da Orvalha (ou foto da loja, se configurada) e selo do WhatsApp
- [ ] Ao tocar, abre "Atendimento Orvalha" com a frase de boas-vindas e os atalhos
- [ ] Cada atalho abre o WhatsApp com a pergunta já escrita (tamanho de anel, entrega/envio, outra dúvida)
- [ ] O atalho "Ajuda para escolher um presente" não abre o WhatsApp e leva à seção de ajuda para presente
- [ ] Nenhum texto usa nome de atendente, status "online" ou promessa de tempo de resposta

**Expected Result:** A cliente inicia conversas contextualizadas com um toque.

---

### US-3.2: Balão de boas-vindas
**As a** Cliente
**I want to** ser recebida com um balão discreto
**So that** eu saiba que posso conversar com a loja

**Acceptance Criteria:**
- [ ] Aparece uma vez por visita
- [ ] No computador, 5 segundos após abrir o site; no celular, só depois que a cliente rola a página
- [ ] Não aparece com sacola, página da peça ou atendimento abertos
- [ ] Fechar no X não o mostra de novo na visita

**Expected Result:** O balão convida sem atrapalhar a navegação.

---

### US-3.3: Convite de ajuda para cliente em dúvida
**As a** Cliente
**I want to** receber um convite de ajuda quando estou indecisa
**So that** a loja me ajude a escolher

**Acceptance Criteria:**
- [ ] Abre após 2 minutos no site (configurável 1, 2, 3 ou 5) com a sacola vazia
- [ ] Aparece no máximo uma vez por visita
- [ ] Nunca aparece sobre sacola, peça, atendimento ou guia de cuidados
- [ ] Não aparece se desligado no painel
- [ ] Botões: Falar com a atendente (WhatsApp), É para presente (seção de presente) e Agora não, obrigada

**Expected Result:** A cliente em dúvida recebe um convite único e educado.

---

### US-3.4: Ajuda para presente
**As a** Cliente
**I want to** pedir sugestões de presente informando para quem, ocasião e valor
**So that** a loja me sugira peças adequadas

**Acceptance Criteria:**
- [ ] Campos: para quem (Mãe, Namorada, Amiga, Filha, Para mim), ocasião, quanto quer gastar (até R$ 100, R$ 100 a R$ 200, acima de R$ 200) e detalhe opcional
- [ ] Ao enviar, abre o WhatsApp com a mensagem montada com as respostas

**Expected Result:** A loja recebe o pedido de presente estruturado.

---

### US-3.5: Guia de cuidados
**As a** Cliente
**I want to** ler dicas de cuidado com as peças
**So that** elas durem mais e eu conheça a garantia

**Acceptance Criteria:**
- [ ] Seção acima do rodapé com 4 dicas rápidas e o link "Ver o guia completo"
- [ ] O guia tem: no dia a dia, o que evitar, como limpar, como guardar, brilho que muda com o tempo e garantia
- [ ] Abrir por uma dica rola até o assunto dela
- [ ] Os links do rodapé "Cuidados com as peças" e "Trocas e garantia" abrem o guia
- [ ] As dicas são gerais da loja, sem dica por peça

**Expected Result:** A cliente acessa o guia por vários pontos do site.

---

## 4. Acesso ao painel

### US-4.1: Entrar e sair do painel
**As a** Administradora
**I want to** entrar com e-mail e senha e sair quando terminar
**So that** só a equipe acesse os dados da loja

**Acceptance Criteria:**
- [ ] Todas as telas do painel exigem login; acesso sem sessão redireciona para a entrada
- [ ] Credenciais inválidas mostram erro sem revelar qual campo falhou
- [ ] "Sair" encerra a sessão e volta para a tela de entrada
- [ ] Há um único nível de acesso

**Expected Result:** O painel só é acessível por quem fez login.

---

## 5. Produtos e categorias

### US-5.1: Cadastrar e editar peça
**As a** Administradora
**I want to** cadastrar peças com seus dados
**So that** elas apareçam corretamente na vitrine

**Acceptance Criteria:**
- [ ] Obrigatórios: nome, categoria, material (Ouro 18k ou Prata 925) e preço de venda maior que zero
- [ ] Salvar sem nome ou preço marca os campos e não grava nada
- [ ] Preço antigo menor ou igual ao preço de venda é zerado ao salvar
- [ ] Campos opcionais: descrição, "Mais vendido" e "Lançamento"
- [ ] A prévia ao lado mostra o card como ficará no site, com Pix e parcelas calculados

**Expected Result:** A peça é gravada com dados válidos e prévia fiel ao site.

---

### US-5.2: Gerenciar fotos e visibilidade da peça
**As a** Administradora
**I want to** enviar, reordenar e remover fotos e ligar a peça no site
**So that** a vitrine mostre as peças com as imagens certas

**Acceptance Criteria:**
- [ ] A 1ª foto é a capa e a 2ª aparece no hover
- [ ] Fotos podem ser reordenadas e removidas na tela
- [ ] Ligar "No site" exige pelo menos 1 foto; o interruptor da lista recusa ligar peça sem foto
- [ ] Fotos são guardadas no Cloudflare R2 via Spatie Media Library

**Expected Result:** Só peças com foto ficam visíveis na vitrine.

---

### US-5.3: Listar e excluir peças
**As a** Administradora
**I want to** buscar, filtrar e excluir peças
**So that** eu mantenha o catálogo organizado

**Acceptance Criteria:**
- [ ] A lista tem busca por nome, filtro por categoria e contagem de peças no site e ocultas
- [ ] Excluir pede confirmação e não pode ser desfeito
- [ ] Vendas antigas continuam mostrando nome e preço da peça na época

**Expected Result:** O catálogo é gerenciado sem corromper o histórico de vendas.

---

### US-5.4: Gerenciar categorias
**As a** Administradora
**I want to** criar, ordenar e excluir categorias
**So that** o menu da vitrine reflita a organização da loja

**Acceptance Criteria:**
- [ ] Nome obrigatório e único, sem diferenciar maiúsculas; vazio ou repetido é recusado
- [ ] A ordem definida vale para o menu, os círculos e o rodapé do site
- [ ] Categoria com peças não pode ser excluída; o painel informa quantas peças há e pede para movê-las
- [ ] Categoria vazia é excluída após confirmação

**Expected Result:** A navegação do site segue as categorias e a ordem do painel.

---

## 6. Clientes

### US-6.1: Cadastrar cliente
**As a** Administradora
**I want to** cadastrar clientes
**So that** eu acompanhe compras, crediário e aniversários

**Acceptance Criteria:**
- [ ] Nome obrigatório; WhatsApp, cidade, aniversário (dia/mês) e observações opcionais
- [ ] WhatsApp com DDD, mínimo 10 dígitos, e não pode repetir o de outra cliente
- [ ] Cidade padrão é a cidade da loja
- [ ] O cadastro pode ser feito na tela Clientes ou no cadastro rápido dentro da venda

**Expected Result:** A cliente fica salva e disponível para vendas.

---

### US-6.2: Ver a ficha da cliente
**As a** Administradora
**I want to** ver o resumo e o histórico da cliente
**So that** eu a atenda melhor e cobre parcelas

**Acceptance Criteria:**
- [ ] Mostra total comprado, número de compras, ticket médio e saldo em aberto
- [ ] Mostra as parcelas do crediário e as 8 compras mais recentes, com "ver todas"
- [ ] Botões para chamar no WhatsApp e iniciar nova venda para ela
- [ ] Vendas canceladas não contam no total, nas compras nem no saldo

**Expected Result:** A ficha reflete só vendas válidas.

---

### US-6.3: Listar e excluir clientes
**As a** Administradora
**I want to** listar clientes priorizando inadimplentes e excluir cadastros sem uso
**So that** eu cobre quem deve e mantenha a base limpa

**Acceptance Criteria:**
- [ ] A lista mostra primeiro quem tem parcelas vencidas
- [ ] O filtro "Com parcelas em aberto" restringe a lista
- [ ] Cliente com compras registradas não pode ser excluída
- [ ] Cliente sem compras é excluída após confirmação

**Expected Result:** A base de clientes mantém a integridade do histórico.

---

## 7. Vendas

### US-7.1: Registrar nova venda (mini PDV)
**As a** Administradora
**I want to** registrar uma venda fechada
**So that** ela entre nos números da loja

**Acceptance Criteria:**
- [ ] Exige pelo menos 1 peça; tocar adiciona 1, − e + ajustam, 0 remove
- [ ] O preço do item é o preço atual da peça, gravado na venda
- [ ] Onde vendeu: WhatsApp, Loja física ou Instagram
- [ ] Data padrão hoje; pode ser anterior, nunca futura; a hora gravada é a do registro
- [ ] A numeração é sequencial (#0616)
- [ ] Após registrar, abre o detalhe da venda e a tela de nova venda volta vazia

**Expected Result:** A venda fica registrada com numeração e preços congelados.

---

### US-7.2: Escolher ou cadastrar cliente na venda
**As a** Administradora
**I want to** encontrar a cliente rapidamente durante a venda
**So that** eu não perca tempo no atendimento

**Acceptance Criteria:**
- [ ] A busca acha pelo começo de qualquer palavra do nome, sem diferenciar acentos ("ma" acha Mariana e Gabriela Martins)
- [ ] A busca também acha por 3 ou mais dígitos do telefone
- [ ] Mostra no máximo 6 resultados
- [ ] Cadastro rápido sem sair da venda; ao salvar, a cliente já fica selecionada
- [ ] Cliente é opcional, exceto no crediário

**Expected Result:** A cliente é vinculada à venda sem sair da tela.

---

### US-7.3: Definir pagamento e descontos
**As a** Administradora
**I want to** informar forma de pagamento, parcelas e descontos
**So that** o total registrado seja o realmente cobrado

**Acceptance Criteria:**
- [ ] Formas: Pix, Dinheiro, Débito (à vista), Crédito (à vista ou 2x a 6x) e Crediário (1x a 6x)
- [ ] Pix aplica automaticamente o desconto Pix da loja; as demais formas não aplicam desconto automático
- [ ] "Outro desconto" em R$ é opcional e soma ao desconto Pix; o desconto total nunca passa do subtotal
- [ ] Total = subtotal − desconto Pix − outro desconto, e os dois descontos são guardados separados
- [ ] À vista = 1 parcela; parcelado = 2 ou mais; ao trocar para crediário o padrão vira 2x

**Expected Result:** A venda guarda condição de pagamento e descontos de forma auditável.

---

### US-7.4: Listar vendas e ver o detalhe
**As a** Administradora
**I want to** consultar as vendas e seus detalhes
**So that** eu acompanhe o que foi vendido

**Acceptance Criteria:**
- [ ] A lista abre em Hoje; períodos 7 dias, 30 dias e Tudo
- [ ] Filtros por forma de pagamento e "Só canceladas"; busca por cliente, peça ou número
- [ ] Vendas agrupadas por dia, com quantidade e total do dia (sem canceladas); 50 por vez com "Mostrar mais"
- [ ] O resumo mostra número de vendas (e canceladas), faturamento, ticket médio e quanto falta receber
- [ ] O detalhe mostra peças, descontos, total, cliente, pagamento, parcelas e onde vendeu

**Expected Result:** A administradora localiza e audita qualquer venda.

---

### US-7.5: Cancelar e reativar venda
**As a** Administradora
**I want to** cancelar uma venda errada e reativá-la se foi engano
**So that** o histórico fique correto sem editar vendas

**Acceptance Criteria:**
- [ ] Venda não pode ser editada
- [ ] Cancelar exige motivo
- [ ] A venda cancelada fica riscada no histórico com data, hora e motivo
- [ ] Saem dos números do Painel, da ficha da cliente e do crediário a receber
- [ ] Se já houve parcelas recebidas, o aviso informa o valor para combinar a devolução
- [ ] Reativar devolve a venda a todos os números

**Expected Result:** Correções são feitas por cancelamento, preservando o histórico.

---

### US-7.6: Enviar recibo pelo WhatsApp
**As a** Administradora
**I want to** enviar o recibo à cliente
**So that** ela tenha o comprovante da compra

**Acceptance Criteria:**
- [ ] Disponível só com cliente que tem WhatsApp e venda não cancelada
- [ ] Abre o WhatsApp dela com número, data, itens, desconto, total, forma de pagamento e parcelas

**Expected Result:** A cliente recebe o recibo na conversa.

---

## 8. Crediário

### US-8.1: Gerar parcelas do crediário
**As a** Administradora
**I want to** vender no crediário com parcelas mensais
**So that** a cliente pague a prazo e eu controle os recebimentos

**Acceptance Criteria:**
- [ ] Exige cliente; sem cliente mostra o aviso e leva ao campo de cliente
- [ ] Parcelas de 1x a 6x, mensais, a partir do 1º vencimento escolhido (padrão 1 mês após a venda)
- [ ] Parcela = total ÷ n arredondada para baixo no centavo; a última absorve a diferença (R$ 100,00 em 3x = 33,33 + 33,33 + 33,34)
- [ ] Sem juros, multa ou entrada

**Expected Result:** As parcelas aparecem na ficha da cliente.

---

### US-8.2: Receber e desfazer parcela
**As a** Administradora
**I want to** marcar parcelas como recebidas
**So that** o saldo da cliente fique atualizado

**Acceptance Criteria:**
- [ ] "Receber" marca a parcela como paga com a data de hoje
- [ ] "Desfazer" volta a parcela para em aberto
- [ ] Disponível na ficha, no detalhe da venda e no Painel
- [ ] O saldo cai na hora

**Expected Result:** O saldo reflete os recebimentos imediatamente.

---

### US-8.3: Identificar parcelas vencidas
**As a** Administradora
**I want to** ver a situação de cada parcela
**So that** eu saiba quem devo cobrar

**Acceptance Criteria:**
- [ ] Situações: Em aberto, Vence em N dias (até 7 dias), Vencida (vencimento antes de hoje e não recebida) e Paga (com a data)
- [ ] Parcelas vencidas aparecem no Painel e destacam a cliente na lista

**Expected Result:** A inadimplência fica visível em todos os pontos relevantes.

---

## 9. Painel e métricas

### US-9.1: Ver indicadores do período
**As a** Administradora
**I want to** ver os indicadores de 7, 30 ou 90 dias comparados ao período anterior
**So that** eu entenda a evolução das vendas

**Acceptance Criteria:**
- [ ] Indicadores: faturamento (total com descontos), vendas, ticket médio (faturamento ÷ vendas) e peças vendidas
- [ ] Períodos de 7, 30 ou 90 dias, contando hoje
- [ ] Cada indicador compara com o mesmo número de dias imediatamente antes (▲ verde alta, ▼ vinho queda)
- [ ] Só vendas registradas e não canceladas entram

**Expected Result:** A administradora vê o desempenho e a tendência do período.

---

### US-9.2: Ver o gráfico de evolução
**As a** Administradora
**I want to** ver faturamento, vendas ou peças por dia
**So that** eu identifique os melhores dias

**Acceptance Criteria:**
- [ ] O gráfico alterna entre faturamento, vendas e peças vendidas
- [ ] Em 90 dias agrupa por semana; nos demais, por dia
- [ ] Há visão em tabela

**Expected Result:** A série temporal é consultável em gráfico e tabela.

---

### US-9.3: Ver rankings e composição das vendas
**As a** Administradora
**I want to** ver peças, categorias, pagamentos, canais e clientes de destaque
**So that** eu decida o que repor e promover

**Acceptance Criteria:**
- [ ] Peças mais vendidas: top 6 por quantidade, com o desconto da venda distribuído proporcionalmente
- [ ] Faturamento por categoria em %, com o mesmo rateio
- [ ] Como pagaram: % à vista × parcelado e por forma de pagamento
- [ ] Onde vendeu: quantidade de vendas por canal
- [ ] Melhores clientes: top 5 por valor comprado no período
- [ ] Últimas vendas: as 6 mais recentes não canceladas, clicáveis para o detalhe

**Expected Result:** Os rankings refletem apenas vendas válidas do período.

---

### US-9.4: Acompanhar o crediário a receber
**As a** Administradora
**I want to** ver quanto falta receber e as próximas parcelas
**So that** eu planeje o caixa e as cobranças

**Acceptance Criteria:**
- [ ] "A receber" soma as parcelas em aberto de todas as vendas, independente do período, e mostra quanto está vencido
- [ ] Lista as 5 próximas parcelas por vencimento com o botão Receber

**Expected Result:** O saldo a receber é visível e acionável no Painel.

---

### US-9.5: Ver aniversariantes do mês
**As a** Administradora
**I want to** ver as clientes que fazem aniversário no mês
**So that** eu as parabenize e incentive uma compra

**Acceptance Criteria:**
- [ ] Lista as clientes com aniversário no mês atual: primeiro as que ainda vão fazer (por dia), depois as que já passaram
- [ ] Mostra "Hoje", "Em N dias" ou "Já passou"
- [ ] "Parabéns" abre o WhatsApp dela com a mensagem e o desconto de presente configurado (padrão 10%, válido até o fim do mês)

**Expected Result:** A loja contata aniversariantes com um clique.

---

## 10. Configurações da loja

### US-10.1: Configurar contato, horários e pagamento
**As a** Administradora
**I want to** editar os dados fixos da loja
**So that** o site inteiro use as informações corretas

**Acceptance Criteria:**
- [ ] Contato: WhatsApp (destino de todas as mensagens), Instagram, endereço, cidade e estado
- [ ] Horários: seg a sex, sábado e domingo em texto livre
- [ ] Desconto no Pix entre 0 e 30%; parcelas sem juros entre 1 e 12; garantia do banho
- [ ] Valores fora dos limites são recusados
- [ ] Ao salvar, valem imediatamente para o site

**Expected Result:** Alterações na tela Loja refletem na vitrine e no PDV.

---

### US-10.2: Configurar promoções e atendimento
**As a** Administradora
**I want to** configurar frases, atalhos, botão e convite de ajuda
**So that** o atendimento tenha a cara da loja

**Acceptance Criteria:**
- [ ] Frases da faixa do topo; frases vazias são descartadas
- [ ] Frase de boas-vindas para o balão e o painel de atendimento
- [ ] Botão de atendimento com a logo da Orvalha ou foto da loja enviada
- [ ] Atalhos de conversa, cada um vira pergunta pronta; vazios são descartados
- [ ] Convite de ajuda liga/desliga, com 1, 2, 3 ou 5 minutos
- [ ] Presente de aniversário entre 0 e 50%

**Expected Result:** O atendimento da vitrine reflete as configurações salvas.

---

## Open Questions

- Garantia do banho, política de troca e regra de entrega em Ji-Paraná ainda não definidas (afetam US-1.4, US-2.3 e US-3.5).
- Controle de estoque, juros/multa e entrada no crediário, múltiplos perfis no painel e juros do cartão acima de 3x estão fora do MVP até decisão da loja.
- Texto da política de privacidade (LGPD) ainda não existe.
- Prioridades foram definidas por mim a partir do fluxo principal (vitrine → WhatsApp → venda registrada); ajuste se discordar.

## Appendix: User Story Status

| ID | Story | Priority | Status |
|----|-------|----------|--------|
| US-1.1 | Ver o catálogo de peças ativas | High | Pending |
| US-1.2 | Ver preços, Pix, parcelas e selos corretos | High | Pending |
| US-2.1 | Montar a sacola | High | Pending |
| US-2.2 | Enviar a sacola para o WhatsApp | High | Pending |
| US-4.1 | Entrar e sair do painel | High | Pending |
| US-5.1 | Cadastrar e editar peça | High | Pending |
| US-5.2 | Gerenciar fotos e visibilidade da peça | High | Pending |
| US-5.4 | Gerenciar categorias | High | Pending |
| US-7.1 | Registrar nova venda (mini PDV) | High | Pending |
| US-7.3 | Definir pagamento e descontos | High | Pending |
| US-10.1 | Configurar contato, horários e pagamento | High | Pending |
| US-1.3 | Buscar e filtrar peças | Medium | Pending |
| US-1.4 | Ver a página da peça | Medium | Pending |
| US-2.3 | Informar entrega e pagamento | Medium | Pending |
| US-3.1 | Botão de atendimento com atalhos | Medium | Pending |
| US-5.3 | Listar e excluir peças | Medium | Pending |
| US-6.1 | Cadastrar cliente | Medium | Pending |
| US-6.2 | Ver a ficha da cliente | Medium | Pending |
| US-7.2 | Escolher ou cadastrar cliente na venda | Medium | Pending |
| US-7.4 | Listar vendas e ver o detalhe | Medium | Pending |
| US-7.5 | Cancelar e reativar venda | Medium | Pending |
| US-8.1 | Gerar parcelas do crediário | Medium | Pending |
| US-8.2 | Receber e desfazer parcela | Medium | Pending |
| US-8.3 | Identificar parcelas vencidas | Medium | Pending |
| US-9.1 | Ver indicadores do período | Medium | Pending |
| US-10.2 | Configurar promoções e atendimento | Medium | Pending |
| US-1.5 | Favoritar peças | Low | Pending |
| US-1.6 | Ver a faixa de promoções | Low | Pending |
| US-3.2 | Balão de boas-vindas | Low | Pending |
| US-3.3 | Convite de ajuda para cliente em dúvida | Low | Pending |
| US-3.4 | Ajuda para presente | Low | Pending |
| US-3.5 | Guia de cuidados | Low | Pending |
| US-6.3 | Listar e excluir clientes | Low | Pending |
| US-7.6 | Enviar recibo pelo WhatsApp | Low | Pending |
| US-9.2 | Ver o gráfico de evolução | Low | Pending |
| US-9.3 | Ver rankings e composição das vendas | Low | Pending |
| US-9.4 | Acompanhar o crediário a receber | Low | Pending |
| US-9.5 | Ver aniversariantes do mês | Low | Pending |
