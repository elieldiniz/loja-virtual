# Orvalha Semijoias — Regras de negócio e fluxos

Oct 19, 2026 · @eliel diniz

## 1. Visão geral

O sistema tem duas partes: uma **vitrine on-line sem pagamento no site**, em que a cliente monta a sacola e envia para o WhatsApp da loja, e um **painel administrativo** onde a loja cadastra peças, registra as vendas fechadas e acompanha clientes, crediário e resultados. A venda sempre é fechada na conversa; o site nunca cobra nada.

Diferencial da loja: **atendimento humanizado**. A vitrine fala como "a gente", sem nome de atendente, e convida a cliente a conversar em vez de comprar sozinha.

**Dentro do escopo**

- Vitrine com catálogo, busca, favoritos, sacola e envio para o WhatsApp
- Atendimento: botão com atalhos, convite de ajuda, ajuda para presente, guia de cuidados
- Painel: produtos, categorias, vendas (mini PDV), clientes, crediário, métricas e configurações da loja

**Fora do escopo**

- Pagamento on-line, cálculo automático de frete e emissão de nota fiscal
- Controle de estoque (não há baixa de estoque na venda)
- Edição de venda já registrada (corrige-se cancelando e lançando de novo)

**Glossário**

| Termo | Significado |
| --- | --- |
| Vitrine | O site público onde a cliente vê as peças |
| Sacola | Lista de peças que a cliente separa no site; não é um pedido pago |
| Atendimento | Conversa no WhatsApp da loja, onde a venda é fechada |
| Venda | Registro feito pela loja no painel depois que a venda foi fechada |
| Crediário | Venda parcelada pela própria loja (fiado), com parcelas a receber |
| Parcela vencida | Parcela do crediário não recebida com vencimento antes de hoje |
| Ticket médio | Faturamento dividido pelo número de vendas do período |

**Stack prevista:** Laravel + Livewire + Filament, fotos com Spatie Media Library no Cloudflare R2, hospedagem no Laravel Cloud.

## 2. Perfis e acesso

São dois perfis: a **cliente**, que usa a vitrine sem login, e a **administradora** (equipe da loja), que entra no painel com e-mail e senha.

| Perfil | Onde | Login | O que pode fazer |
| --- | --- | --- | --- |
| Cliente | Vitrine | Não | Ver peças, buscar, favoritar, montar sacola, enviar para o atendimento, pedir ajuda para presente, ler o guia de cuidados |
| Administradora | Painel | E-mail e senha | Tudo do painel: produtos, categorias, vendas, clientes, crediário, painel de métricas e configurações da loja |

**Regras**

- RN-01. A vitrine não guarda dados pessoais da cliente; sacola e favoritos vivem só no navegador dela.
- RN-02. O cadastro de cliente existe apenas no painel e é feito pela loja.
- RN-03. Todas as telas do painel exigem login; "Sair" encerra a sessão e volta para a tela de entrada.
- RN-04. Nesta versão há um único nível de acesso no painel (sem permissões diferentes por pessoa).

## 3. Vitrine: catálogo e exibição

A vitrine mostra apenas peças **ativas** e calcula Pix e parcelas a partir das configurações da loja (hoje 5% no Pix e 3x sem juros).

**O que aparece**

- RN-10. Só aparecem peças com "No site" ligado e pelo menos uma foto.
- RN-11. "Mais vendidos" lista as peças marcadas como Mais vendido; "Lançamentos" mostra até 4 peças marcadas como Lançamento.
- RN-12. A ordem das categorias no menu, nos círculos e no rodapé segue a ordem definida no painel.
- RN-13. O card da peça mostra foto, nome, preço, parcelamento e preço no Pix. **Não mostra o material** (ouro, prata) no card.
- RN-14. Na foto do card, a segunda foto aparece ao passar o mouse.

**Preços**

| Regra | Cálculo | Exemplo (R$ 99,90) |
| --- | --- | --- |
| RN-15. Preço no Pix | preço × (1 − desconto Pix) | R$ 94,90 |
| RN-16. Parcelado | preço ÷ parcelas sem juros | 3x de R$ 33,30 |
| RN-17. Selo de desconto | arredondar (1 − preço ÷ preço antigo) × 100 | De R$ 139,90 por R$ 119,90 = −14% |

- RN-18. O preço antigo só aparece riscado quando é maior que o preço de venda; caso contrário é ignorado.
- RN-19. Selo: peça com desconto mostra o percentual; senão, peça Lançamento mostra "Novo".

**Navegação**

- RN-20. Busca por nome e categoria, enquanto digita; sem resultado, a vitrine convida a falar com a loja.
- RN-21. Filtros: categoria, Promoções (peças com preço antigo) e coleção dourada / prata pelos banners.
- RN-22. Favoritos: o coração marca a peça; o ícone do topo lista as favoritas. Ficam só no aparelho da cliente.
- RN-23. Página da peça: fotos, categoria, preços, detalhes (banho, antialérgico, garantia, caixinha), botões "Quero essa" e "Tirar dúvida sobre esta peça". Anéis têm guia de tamanhos com "Na dúvida, a gente troca".
- RN-24. A faixa do topo alterna as frases de promoção cadastradas no painel a cada 4 segundos.

## 4. Sacola e envio para o atendimento

A sacola não é um pedido: é uma lista que a cliente envia para o WhatsApp da loja, e **nada é cobrado no site**.

- RN-30. "Quero essa" adiciona 1 unidade; se a peça já está na sacola, soma +1.
- RN-31. Na sacola dá para aumentar, diminuir e remover; quantidade 0 remove a peça.
- RN-32. Totais mostrados: subtotal, total no Pix (com o desconto da loja) e o parcelamento sem juros.
- RN-33. O botão final é "Enviar para o atendimento", com o aviso: "A gente confirma se tem as peças e combina entrega e pagamento. Nada é cobrado agora."
- RN-34. A sacola vive só no navegador; o site não registra o envio como venda. A venda só existe quando a loja a registra no painel (seção 8).

**Mensagem enviada ao WhatsApp**

```
Oi! Separei essas peças no site da Orvalha:

• 1x Colar Ponto de Luz (Ouro 18k) — R$ 99,90
• 2x Argola Lisa Média (Ouro 18k) — R$ 179,80

Total: R$ 279,70 (ou R$ 265,72 no Pix)

Pode confirmar se tem tudo disponível?
```

- RN-35. O material vai na mensagem (e não no card) para a atendente identificar a peça certa.
- RN-36. O número de destino é o WhatsApp cadastrado em Loja → Contato.

**Entrega**

- RN-37. Ji-Paraná: entrega pela loja ou retirada na loja.
- RN-38. Outras cidades: envio e frete combinados na conversa; o site não calcula frete.
- RN-39. Formas de pagamento aceitas na conversa: Pix, cartão e dinheiro (e crediário, a critério da loja).

## 5. Atendimento humanizado

Toda a comunicação fala como "a gente", sem nome de atendente, sem status "online" e sem prometer tempo de resposta: só "a gente responde rapidinho".

**Botão de atendimento**

- RN-40. Botão fixo no canto da tela com a logo da Orvalha (ou foto da loja, se configurado) e um selo do WhatsApp.
- RN-41. Ao tocar, abre o painel "Atendimento Orvalha" com a frase de boas-vindas e os atalhos de conversa.
- RN-42. Cada atalho abre o WhatsApp com a pergunta já escrita. Atalhos atuais: ajuda com presente, tamanho de anel, entrega e envio para outras cidades, outra dúvida.
- RN-43. O atalho "Ajuda para escolher um presente" não abre o WhatsApp: leva à seção Ajuda para presente.

**Balão de boas-vindas**

- RN-44. Aparece uma vez por visita. No computador, 5 segundos após abrir o site; no celular, só depois que a cliente rola a página, para não cobrir o topo.
- RN-45. Não aparece se a sacola, a página de uma peça ou o atendimento estiverem abertos. Fechar no X não o mostra de novo na visita.

**Convite de ajuda (cliente em dúvida)**

- RN-46. Abre quando a cliente está há **2 minutos** no site (configurável) e a sacola está **vazia**.
- RN-47. Aparece no máximo uma vez por visita e nunca por cima da sacola, de uma peça, do atendimento ou do guia de cuidados. Pode ser desligado no painel.
- RN-48. Texto: "Ficou na dúvida? A gente te ajuda a escolher… Vai ser um prazer te atender." Botões: Falar com a atendente (WhatsApp), É para presente (seção de presente) e Agora não, obrigada.

**Ajuda para presente**

- RN-49. Formulário com: para quem (Mãe, Namorada, Amiga, Filha, Para mim), ocasião, quanto quer gastar (até R$ 100, R$ 100 a R$ 200, acima de R$ 200) e um detalhe opcional.
- RN-50. Ao enviar, abre o WhatsApp com a mensagem montada; a loja responde com sugestões.

**Guia de cuidados**

- RN-51. Seção acima do rodapé com 4 dicas rápidas; cada dica e o link "Ver o guia completo" abrem o guia.
- RN-52. O guia tem: no dia a dia, o que evitar, como limpar, como guardar, brilho que muda com o tempo e garantia. Abrindo por uma dica, rola até o assunto dela.
- RN-53. Os links do rodapé "Cuidados com as peças" e "Trocas e garantia" abrem o guia.
- RN-54. As dicas são gerais da loja; não há dica por peça.

## 6. Admin: produtos e categorias

Uma peça só vai para o site com nome, preço e pelo menos uma foto; uma categoria só pode ser excluída quando estiver vazia.

**Campos da peça**

| Campo | Obrigatório | Regra |
| --- | --- | --- |
| Fotos | Sim, para ir ao site | A 1ª é a capa; a 2ª aparece no hover. Reordenar e remover na tela |
| Nome | Sim | Texto livre |
| Categoria | Sim | Uma das categorias cadastradas |
| Material | Sim | Ouro 18k ou Prata 925 (usado nos filtros e na mensagem, não no card) |
| Preço de venda | Sim | Maior que zero |
| Preço antigo | Não | Se menor ou igual ao preço de venda, é zerado ao salvar |
| Descrição | Não | Medidas, pedra, fecho |
| No site | — | Liga ou desliga a peça na vitrine |
| Mais vendido / Lançamento | — | Coloca a peça nessas seções |

**Regras**

- RN-60. Ao salvar sem nome ou sem preço, os campos ficam marcados e nada é gravado.
- RN-61. Peça com "No site" ligado precisa de pelo menos 1 foto; o interruptor da lista recusa ligar uma peça sem foto.
- RN-62. A prévia ao lado do formulário mostra o card como ficará no site, com Pix e parcelas calculados.
- RN-63. Excluir peça pede confirmação e não pode ser desfeito. Vendas antigas continuam mostrando o nome e o preço da peça na época.
- RN-64. A lista de peças tem busca por nome e filtro por categoria, e mostra quantas estão no site e quantas ocultas.

**Categorias**

- RN-65. Nome único (sem diferenciar maiúsculas). Nome vazio ou repetido é recusado.
- RN-66. A ordem definida no painel é a ordem do menu, dos círculos e do rodapé do site.
- RN-67. Categoria com peças não pode ser excluída: o painel informa quantas peças há e pede para movê-las antes.

## 7. Clientes e crediário

O cadastro de cliente é opcional na venda, exceto no crediário, que sempre exige uma cliente para controlar as parcelas.

**Cadastro**

| Campo | Obrigatório | Regra |
| --- | --- | --- |
| Nome | Sim | — |
| WhatsApp | Não | Com DDD (mínimo 10 dígitos). Não pode repetir o de outra cliente |
| Cidade | Não | Padrão: a cidade da loja |
| Aniversário | Não | Dia/mês (ex.: 21/06). Alimenta os aniversariantes do Painel |
| Observações | Não | Preferências, combinados |

- RN-70. Cliente pode ser cadastrada na tela Clientes ou no cadastro rápido dentro da venda, sem sair dela; ao salvar, já fica selecionada na venda.
- RN-71. Busca de cliente na venda: pelo começo de qualquer palavra do nome, sem diferenciar acentos ("ma" acha Mariana e Gabriela Martins), ou por 3 ou mais dígitos do telefone. Mostra até 6 resultados.
- RN-72. Não é possível excluir cliente com compras registradas (o histórico depende dela). Sem compras, a exclusão pede confirmação.
- RN-73. A ficha mostra total comprado, número de compras, ticket médio, saldo em aberto, parcelas do crediário, histórico de compras (8 mais recentes, com "ver todas") e botões para chamar no WhatsApp e iniciar uma nova venda para ela.
- RN-74. Vendas canceladas não contam no total, nas compras nem no saldo da cliente.

**Crediário**

- RN-75. Parcelas de 1x a 6x, mensais, a partir do 1º vencimento escolhido na venda (padrão: 1 mês após a venda).
- RN-76. Valor da parcela = total ÷ parcelas, arredondado para baixo no centavo; a última parcela absorve a diferença. Ex.: R$ 100,00 em 3x = R$ 33,33 + R$ 33,33 + R$ 33,34.
- RN-77. Situações da parcela: Em aberto, Vence em N dias (até 7 dias), Vencida (vencimento antes de hoje e não recebida) e Paga (com a data do recebimento).
- RN-78. "Receber" marca a parcela como paga com a data de hoje; "Desfazer" volta para em aberto.
- RN-79. Não há juros, multa nem entrada nesta versão.
- RN-80. A lista de clientes mostra primeiro quem tem parcelas vencidas e tem o filtro "Com parcelas em aberto".

## 8. Vendas

Toda venda fechada (no WhatsApp, na loja ou pelo Instagram) é registrada no painel em Vendas → Nova venda, e só vendas registradas e não canceladas entram nos números.

**Nova venda (mini PDV)**

- RN-90. A venda precisa de pelo menos 1 peça. Tocar na peça adiciona 1; os botões − e + ajustam a quantidade; 0 remove.
- RN-91. O preço de cada item é o preço atual da peça, gravado na venda (mudar o preço depois não altera vendas antigas).
- RN-92. Cliente opcional, exceto no crediário (RN-75). Tentar registrar crediário sem cliente mostra o aviso e leva ao campo de cliente.
- RN-93. Onde vendeu: WhatsApp, Loja física ou Instagram.
- RN-94. Data da venda: padrão hoje; pode ser uma data anterior (venda fechada antes), nunca futura. A hora gravada é a do registro.
- RN-95. Após registrar, abre o detalhe da venda e a tela de nova venda volta vazia.

**Pagamento e descontos**

| Forma | Parcelas | Desconto automático | Observação |
| --- | --- | --- | --- |
| Pix | À vista | Desconto Pix da loja (hoje 5%) | — |
| Dinheiro | À vista | — | — |
| Débito | À vista | — | — |
| Crédito | À vista ou 2x a 6x | — | A operadora recebe; a loja só registra a condição |
| Crediário | 1x a 6x | — | Gera parcelas a receber na ficha da cliente |

- RN-96. "Outro desconto" (em R$) é opcional e soma ao desconto Pix; o desconto total nunca passa do subtotal.
- RN-97. Total = subtotal − desconto Pix − outro desconto. A venda guarda os dois descontos separados.
- RN-98. À vista = 1 parcela; parcelado = 2 ou mais (crédito ou crediário). Ao trocar para crediário, o padrão vira 2x.

**Lista e detalhe**

- RN-99. Numeração sequencial (#0616). A lista abre em Hoje; períodos 7 dias, 30 dias e Tudo; filtros por forma de pagamento e "Só canceladas"; busca por cliente, peça ou número.
- RN-100. Vendas agrupadas por dia, com quantidade e total do dia (sem as canceladas). Mostra 50 por vez, com "Mostrar mais".
- RN-101. Resumo do filtro: número de vendas (e canceladas), faturamento, ticket médio e quanto falta receber dessas vendas.
- RN-102. O detalhe mostra peças, descontos, total, cliente, pagamento (forma, condição, recebido no crediário), parcelas e onde vendeu.

**Cancelamento e recibo**

- RN-103. Venda não se edita. Para corrigir, cancela-se e lança-se de novo.
- RN-104. Cancelar exige motivo. A venda fica no histórico, riscada, com data, hora e motivo, e sai do Painel, da ficha da cliente e do crediário a receber.
- RN-105. Se já houve parcelas recebidas no crediário, o aviso informa o valor para a loja combinar a devolução.
- RN-106. Venda cancelada pode ser reativada (volta a contar em tudo).
- RN-107. "Enviar recibo" (só com cliente que tem WhatsApp e venda não cancelada) abre o WhatsApp dela com número, data, itens, desconto, total, forma de pagamento e as parcelas.

## 9. Painel e métricas

O Painel mede só **vendas registradas e não canceladas**; não há métricas de visitas do site.

- RN-110. Períodos: 7, 30 ou 90 dias, contando hoje. Cada indicador compara com o mesmo número de dias imediatamente antes (▲ verde para alta, ▼ vinho para queda).
- RN-111. O gráfico mostra faturamento, vendas ou peças vendidas por dia; em 90 dias, por semana. Tem visão em tabela.

| Indicador | Cálculo |
| --- | --- |
| Faturamento | Soma dos totais das vendas (já com descontos) |
| Vendas | Quantidade de vendas |
| Ticket médio | Faturamento ÷ vendas |
| Peças vendidas | Soma das quantidades dos itens |
| Peças mais vendidas | Top 6 por quantidade; faturamento da peça com o desconto da venda distribuído proporcionalmente |
| Faturamento por categoria | % do faturamento por categoria, mesmo rateio do desconto |
| Como pagaram | % do faturamento à vista × parcelado, e por forma de pagamento |
| Onde vendeu | Quantidade de vendas por canal |
| Melhores clientes | Top 5 por valor comprado no período |
| A receber no crediário | Soma das parcelas em aberto de todas as vendas (não depende do período) e quanto disso está vencido |

- RN-112. "A receber" lista as 5 próximas parcelas por vencimento, com o botão Receber.
- RN-113. Últimas vendas: as 6 mais recentes não canceladas, clicáveis para o detalhe.

**Aniversariantes do mês**

- RN-114. Lista as clientes com aniversário no mês atual: primeiro as que ainda vão fazer (por dia), depois as que já passaram.
- RN-115. Mostra "Hoje", "Em N dias" ou "Já passou". O botão Parabéns abre o WhatsApp dela com a mensagem e o desconto de presente configurado em Loja (hoje 10%, válido até o fim do mês).

## 10. Configurações da loja

Tudo o que é texto fixo da loja vem da tela Loja e vale para o site inteiro assim que salvo.

| Grupo | Campo | Onde aparece | Regra |
| --- | --- | --- | --- |
| Contato | WhatsApp | Todos os botões de WhatsApp e rodapé | Número de destino das mensagens |
| Contato | Instagram, endereço, cidade, estado | Rodapé, seção Atendimento de verdade | — |
| Horários | Seg a sex, sábado, domingo | Rodapé e painel de atendimento | Texto livre (ex.: 9h às 18h) |
| Pagamento | Desconto no Pix (%) | Preços no Pix e PDV | Entre 0 e 30 |
| Pagamento | Parcelas sem juros | Parcelamento dos cards | Entre 1 e 12 |
| Pagamento | Garantia do banho | Página da peça e guia de cuidados | Hoje "\[X\] meses", a definir |
| Promoções | Frases da faixa do topo | Topo do site, alternando | Frases vazias são descartadas |
| Atendimento | Frase de boas-vindas | Balão e painel de atendimento | — |
| Atendimento | Botão | Botão flutuante | Logo da Orvalha ou foto da loja enviada |
| Atendimento | Atalhos de conversa | Painel de atendimento | Cada um vira uma pergunta pronta no WhatsApp; vazios são descartados |
| Atendimento | Convite de ajuda | Convite após X minutos sem sacola | Liga/desliga; 1, 2, 3 ou 5 minutos |
| Atendimento | Presente de aniversário (%) | Mensagem de parabéns | Entre 0 e 50 |

## 11. Fluxos principais

&#91;embedded content: fluxo da venda · vitrine, WhatsApp e painel\]

A cliente chega ao WhatsApp pela sacola ou pelo convite de ajuda (e também pelos atalhos e pela ajuda para presente); só quando a loja registra a venda fechada ela aparece no Painel, na ficha da cliente e, se for crediário, nas parcelas a receber.

**Fluxo de nova venda no painel**

1. Vendas → Nova venda.
2. Tocar nas peças vendidas e ajustar quantidades.
3. Escolher a cliente pela busca, ou cadastrar na hora (obrigatório no crediário).
4. Escolher pagamento; em crédito ou crediário, o número de parcelas; no crediário, o 1º vencimento.
5. Marcar onde vendeu, a data e, se houver, outro desconto.
6. Registrar venda → abre o detalhe, de onde dá para enviar o recibo.

**Fluxo de crediário**

1. A venda no crediário gera as parcelas mensais na ficha da cliente.
2. Parcela não recebida após o vencimento passa a "Vencida" e aparece no Painel e na lista de clientes.
3. Ao receber, a loja toca em Receber (na ficha, no detalhe da venda ou no Painel); o saldo cai na hora.

**Fluxo de cancelamento**

1. Abrir a venda → Cancelar venda → escrever o motivo.
2. A venda fica riscada no histórico e sai de todos os números e do crediário a receber.
3. Se foi engano, Reativar venda devolve tudo como era.

## 12. Pendências e decisões em aberto

Os mockups usam marca, fotos e dados provisórios; os itens abaixo precisam de resposta da loja antes do desenvolvimento.

**Dados da loja**

- [ ] Prazo real da garantia do banho (hoje "\[X\] meses")
- [ ] Número do WhatsApp, número do endereço e CNPJ
- [ ] Fotos reais das peças e da loja (para o botão de atendimento)
- [ ] Política de troca: prazo e condições (o site promete "não serviu, a gente troca")
- [ ] Taxa ou regra de entrega em Ji-Paraná (grátis, valor fixo ou por bairro)

**Decisões de produto**

- [ ] Controle de estoque: a venda deve baixar o estoque da peça?
- [ ] Crediário: aplicar juros ou multa por atraso? Aceitar entrada?
- [ ] Mais de uma pessoa no painel, com permissões diferentes?
- [ ] Cartão de crédito acima de 3x: repassar juros ou a loja absorve?
- [ ] Texto da política de privacidade (LGPD), já que a loja guarda nome, telefone e aniversário das clientes

**Observação técnica:** nos mockups, vitrine e painel não estão ligados. Na versão final, a vitrine lê peças, categorias e configurações direto do banco do painel.
