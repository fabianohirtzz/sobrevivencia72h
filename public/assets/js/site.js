/* 72 Horas · landing: checklist com progresso, calculadora de água, oferta com prazo real e checkout. */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var store = {
    get: function (k) { try { return JSON.parse(localStorage.getItem(k)); } catch (e) { return null; } },
    set: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
  };

  /* ---------- checklist (salva no aparelho) ---------- */
  var caixas = $$('#checklist input[type=checkbox]');
  var salvos = store.get('s72_check') || [];
  function progresso() {
    var n = caixas.filter(function (c) { return c.checked; }).length;
    $('#prog-barra').style.width = (caixas.length ? n / caixas.length * 100 : 0) + '%';
    $('#prog-texto').textContent = n + ' de ' + caixas.length + ' itens';
    store.set('s72_check', caixas.map(function (c) { return c.checked; }));
  }
  caixas.forEach(function (c, i) { c.checked = !!salvos[i]; c.addEventListener('change', progresso); });
  if (caixas.length) progresso();

  /* ---------- calculadora de água ---------- */
  var pessoas = 4;
  function calc() {
    $('#calc-pessoas').textContent = pessoas;
    $('#calc-min').textContent = pessoas * 6;
    $('#calc-ideal').textContent = pessoas * 12;
    var galoes = pessoas;
    $('#calc-galoes').textContent = 'São ' + galoes + (galoes === 1 ? ' galão' : ' galões') + ' de 12 litros' +
      (galoes <= 4 ? '. Cabem embaixo da pia.' : '. Cabem no fundo do guarda-roupa ou na área de serviço.');
  }
  $$('#calc [data-delta]').forEach(function (b) {
    b.addEventListener('click', function () { pessoas = Math.min(12, Math.max(1, pessoas + +b.dataset.delta)); calc(); });
  });
  if ($('#calc')) calc();

  /* ---------- oferta: preço e prazo vêm do servidor (prazo real) ---------- */
  var urlParams = new URLSearchParams(window.location.search);
  var pathSeg = window.location.pathname.replace(/^\/|\/$/g, '');
  var promoHash = urlParams.get('p') || urlParams.get('promo') || (pathSeg.length >= 6 && pathSeg !== 'link' && pathSeg !== 'index.html' ? pathSeg : '') || store.get('s72_promo') || '';
  if (urlParams.get('p') || urlParams.get('promo') || (pathSeg.length >= 6 && pathSeg !== 'link' && pathSeg !== 'index.html')) {
    store.set('s72_promo', promoHash);
  }
  var oferta = { preco: '39,90', fim: null, promo: false };
  function moeda(v) { return v.replace('.', ','); }
  function aplicaPreco() {
    $$('[data-preco]').forEach(function (el) { el.textContent = oferta.preco; });
    var pv = $('#preco-valor'); if (pv) pv.textContent = oferta.preco;
    var de = $('.preco__de');
    if (de) de.hidden = oferta.preco === '59,90';
  }
  function contador() {
    var box = $('#contador');
    if (!box || !oferta.fim) return;
    box.hidden = false;
    function tick() {
      var resta = Math.max(0, oferta.fim - Date.now());
      if (resta <= 0) { box.hidden = true; oferta.preco = '59,90'; oferta.promo = false; aplicaPreco(); return; }
      var h = Math.floor(resta / 3600000), m = Math.floor(resta % 3600000 / 60000), s = Math.floor(resta % 60000 / 1000);
      $('#cd-h').textContent = String(h).padStart(2, '0');
      $('#cd-m').textContent = String(m).padStart(2, '0');
      $('#cd-s').textContent = String(s).padStart(2, '0');
      setTimeout(tick, 1000);
    }
    tick();
  }
  var ofertaUrl = 'api/oferta.php' + (promoHash ? '?p=' + encodeURIComponent(promoHash) : '');
  fetch(ofertaUrl, { cache: 'no-store' })
    .then(function (r) { if (!r.ok) throw 0; return r.json(); })
    .then(function (d) {
      oferta.preco = moeda(d.preco);
      oferta.fim = d.fim ? d.fim * 1000 : null;
      oferta.promo = !!d.promo;
      aplicaPreco();
      contador();
    })
    .catch(function () { /* sem PHP (prévia): mantém o preço do HTML e esconde o contador */ });

  /* ---------- checkout ---------- */
  var dlg = $('#checkout');
  var form = $('#form-compra');
  var erro = $('#checkout-erro');
  $$('[data-comprar], a[href="#oferta"].btn').forEach(function (b) {
    if (b.matches('[data-comprar]')) b.addEventListener('click', function () { abrir(); });
  });
  function abrir() {
    if (dlg && dlg.showModal) { dlg.showModal(); var f = $('input[name=nome]', form); if (f) f.focus(); }
  }
  function cpfValido(c) {
    c = (c || '').replace(/\D/g, '');
    if (c.length !== 11 || /^(\d)\1+$/.test(c)) return false;
    for (var t = 9; t < 11; t++) {
      var s = 0;
      for (var i = 0; i < t; i++) s += +c[i] * (t + 1 - i);
      if (((10 * s) % 11) % 10 !== +c[t]) return false;
    }
    return true;
  }
  var cpf = form && $('input[name=cpf]', form);
  if (cpf) cpf.addEventListener('input', function () {
    var v = cpf.value.replace(/\D/g, '').slice(0, 11);
    cpf.value = v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
  });
  var cel = form && $('input[name=celular]', form);
  if (cel) cel.addEventListener('input', function () {
    var v = cel.value.replace(/\D/g, '').slice(0, 11);
    cel.value = v.length > 10 ? v.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3') : v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
  });
  function falha(msg) { erro.textContent = msg; erro.hidden = false; }
  if (form) form.addEventListener('submit', function (e) {
    e.preventDefault();
    erro.hidden = true;
    var d = Object.fromEntries(new FormData(form));
    if ((d.nome || '').trim().split(/\s+/).length < 2) return falha('Informe o nome completo.');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(d.email || '')) return falha('Confira o e-mail.');
    if ((d.email || '').trim().toLowerCase() !== (d.email2 || '').trim().toLowerCase()) return falha('Os dois e-mails estão diferentes.');
    if (!cpfValido(d.cpf)) return falha('CPF inválido. Ele é exigido para emitir a cobrança.');
    var btn = $('button[type=submit]', form);
    btn.disabled = true;
    if (promoHash) d.promo = promoHash;
    fetch('api/checkout.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(d) })
      .then(function (r) { return r.json().catch(function () { return {}; }); })
      .then(function (res) {
        if (res && res.url) { window.location.href = res.url; return; }
        btn.disabled = false;
        falha((res && res.erro) || 'Não foi possível gerar o pagamento agora. Tente de novo ou chame no WhatsApp (48) 99165-7775.');
      })
      .catch(function () {
        btn.disabled = false;
        falha('Pagamento indisponível nesta prévia. No site oficial o botão leva direto ao pagamento.');
      });
  });
})();
