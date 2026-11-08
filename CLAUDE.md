# Sobrevivência 72h — landing do "72 Horas: O Manual da Família Preparada"

## O que é
Landing page de vendas do infoproduto 72 Horas (manual de 91 páginas + 3 bônus em PDF), da Freela In Home.
Objetivo: vender e ranquear de forma orgânica (SEO + GEO) no assunto "kit de emergência de 72 horas",
em alta desde que a Defesa Civil Nacional orientou as famílias a montar o kit (06/11/2026), na esteira
da Comissão Europeia (03/11/2026) e do Reino Unido (out/2026). Público: famílias brasileiras.

## Stack
HTML + CSS + JS puro (sem build) e PHP 8 para checkout/webhook/download. Hospedagem Erehost (cPanel/FTP).
- `public/` = conteúdo do `public_html`.
- `privado/` = fica FORA do `public_html` no servidor (config com chaves, PDFs do produto, pedidos).

## Design
- Mesmo sistema visual do ebook: tinta #161513, laranja #E8702A, creme #F7F3EC.
- Tipografia: Big Shoulders Display (títulos), IBM Plex Sans (texto), IBM Plex Mono (rótulos).
- Logo: `public/assets/img/logo-*.svg` (gerado por `marca/logo.py`).

## Comandos
- Preview local: `php -S localhost:8072 -t public` (ou abrir `public/index.html`).
- Deploy: subir `public/` para `public_html` e `privado/` para a pasta irmã `privado` via FTP/cPanel.

## Integrações
- Asaas (API v3): cria cliente + cobrança (billingType UNDEFINED: Pix, cartão ou boleto) e redireciona para a fatura.
  Webhook `PAYMENT_CONFIRMED`/`PAYMENT_RECEIVED` libera o download e envia e-mail com link protegido.
- E-mail de envio: SMTP da Erehost (conta a criar), resposta para sobrevivencia72h@gmail.com.
- Suporte: sobrevivencia72h@gmail.com · WhatsApp (48) 99165-7775.
- Vendedor: Freela In Home · F&F2 SOLUÇÕES LTDA · CNPJ 31.703.023/0001-96.

## Oferta
De R$ 59,90 por R$ 39,90. Countdown REAL e único: no lançamento, `OFERTA_FIM` (privado/config.php)
recebe lançamento + 72h; depois disso página e checkout cobram R$ 59,90 de fato (nada de contador falso).

## Regras do projeto
- Copy em pt-BR, sem travessões, sem emojis, números concretos, tom calmo (preparação, não medo).
- Fatos com fonte (Defesa Civil, Comissão Europeia, Cruz Vermelha, USDA, FEMA). Nada de alarmismo.
- Texto do ebook é intocável; citações do manual na página devem ser literais.

## Estado atual
- 2026-11-07: ebook (91 p.) e 3 bônus diagramados e aprovados (pasta ../Ebooks). Iniciada a landing (fase: build).
