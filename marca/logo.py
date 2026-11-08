"""Logo do 72 Horas: ícone (mostrador de 72 horas) + assinatura. Texto convertido em curvas (não depende de fonte).
Gera em ../public/assets/img e em ./ : SVGs (ícone, horizontal clara/escura, vertical, quadrado redes) e PNGs.
Uso: python logo.py
"""
import math, os, sys
from fontTools.ttLib import TTFont
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen

HERE = os.path.dirname(os.path.abspath(__file__))
FONTS = os.path.join(HERE, '..', '..', 'Ebooks', 'gerador', 'fonts')
OUT = os.path.join(HERE, '..', 'public', 'assets', 'img')
os.makedirs(OUT, exist_ok=True)

INK, ORANGE, CREAM, MUTED = '#161513', '#E8702A', '#F3EEE4', '#A39C90'
_f = {}


def font(name):
    if name not in _f:
        _f[name] = TTFont(os.path.join(FONTS, name + '.ttf'))
    return _f[name]


def text_path(s, fname, size, x, y, ls=0.0, anchor='start'):
    """Texto -> path SVG (y = linha de base). ls em unidades do tamanho (fração do em)."""
    f = font(fname)
    gs, cmap, hmtx = f.getGlyphSet(), f.getBestCmap(), f['hmtx']
    upm = f['head'].unitsPerEm
    sc = size / upm
    adv = []
    for ch in s:
        g = cmap.get(ord(ch))
        adv.append((g, hmtx[g][0] if g else upm * 0.3))
    total = sum(a for _, a in adv) * sc + ls * size * (len(s) - 1)
    if anchor == 'middle':
        x -= total / 2
    elif anchor == 'end':
        x -= total
    d = ''
    cx = x
    for g, a in adv:
        if g:
            pen = SVGPathPen(gs)
            tp = TransformPen(pen, (sc, 0, 0, -sc, cx, y))
            gs[g].draw(tp)
            d += pen.getCommands()
        cx += a * sc + ls * size
    return d, total


def icone(cx, cy, r, fundo=None, cor_txt=CREAM, cor_trilho='#3A3733'):
    """Mostrador: 72 marcações (1 por hora), arco laranja e o '72' no centro."""
    s = ''
    if fundo:
        s += f'<circle cx="{cx}" cy="{cy}" r="{r}" fill="{fundo}"/>'
    rt = r * 0.86
    # trilho e arco (progresso de 0h a ~60h, ponta com bolinha)
    s += f'<circle cx="{cx}" cy="{cy}" r="{rt:.2f}" fill="none" stroke="{cor_trilho}" stroke-width="{r * 0.075:.2f}"/>'
    ang = 300
    a0 = -90
    a1 = a0 + ang
    x0, y0 = cx + rt * math.cos(math.radians(a0)), cy + rt * math.sin(math.radians(a0))
    x1, y1 = cx + rt * math.cos(math.radians(a1)), cy + rt * math.sin(math.radians(a1))
    s += (f'<path d="M{x0:.2f} {y0:.2f} A{rt:.2f} {rt:.2f} 0 1 1 {x1:.2f} {y1:.2f}" fill="none" stroke="{ORANGE}" '
          f'stroke-width="{r * 0.075:.2f}" stroke-linecap="round"/>')
    s += f'<circle cx="{x1:.2f}" cy="{y1:.2f}" r="{r * 0.075:.2f}" fill="{ORANGE}"/>'
    # marcações das horas (maiores em 0, 24 e 48)
    for h in range(72):
        a = math.radians(-90 + h * 5)
        big = h % 24 == 0
        r1, r2 = r * 0.70, r * (0.62 if big else 0.665)
        w = r * (0.035 if big else 0.016)
        col = ORANGE if big else cor_txt
        s += (f'<line x1="{cx + r1 * math.cos(a):.2f}" y1="{cy + r1 * math.sin(a):.2f}" x2="{cx + r2 * math.cos(a):.2f}" '
              f'y2="{cy + r2 * math.sin(a):.2f}" stroke="{col}" stroke-width="{w:.2f}" stroke-linecap="round" opacity="{1 if big else 0.55}"/>')
    d, _ = text_path('72', 'BigShouldersDisplay-Black', r * 0.80, cx, cy + r * 0.21, anchor='middle')
    s += f'<path d="{d}" fill="{cor_txt}"/>'
    d, _ = text_path('HORAS', 'IBMPlexMono-SemiBold', r * 0.13, cx, cy + r * 0.44, ls=0.32, anchor='middle')
    s += f'<path d="{d}" fill="{ORANGE}"/>'
    return s


def assinatura(x, y, h, cor, cor2):
    """'72 HORAS' + 'MANUAL DA FAMÍLIA PREPARADA'. y = topo; h = altura do bloco. Retorna (svg, largura)."""
    d1, w1 = text_path('72 HORAS', 'BigShouldersDisplay-Black', h * 0.78, x, y + h * 0.60, ls=0.02)
    d2, w2 = text_path('MANUAL DA FAMÍLIA PREPARADA', 'IBMPlexMono-SemiBold', h * 0.135, x + h * 0.02, y + h * 0.92, ls=0.22)
    # pinta o "HORAS" de laranja: refaz em duas partes
    d72, w72 = text_path('72', 'BigShouldersDisplay-Black', h * 0.78, x, y + h * 0.60)
    dH, wH = text_path('HORAS', 'BigShouldersDisplay-Black', h * 0.78, x + w72 + h * 0.16, y + h * 0.60, ls=0.02)
    s = f'<path d="{d72}" fill="{cor}"/><path d="{dH}" fill="{cor2}"/><path d="{d2}" fill="{cor}" opacity="0.8"/>'
    return s, max(w72 + h * 0.16 + wH, w2)


def svg(w, h, body, titulo):
    return (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w:.0f} {h:.0f}" width="{w:.0f}" height="{h:.0f}" '
            f'role="img" aria-label="{titulo}"><title>{titulo}</title>{body}</svg>\n')


def salvar(nome, conteudo):
    for pasta in (OUT, HERE):
        open(os.path.join(pasta, nome), 'w', encoding='utf-8').write(conteudo)


def main():
    t = '72 Horas: O Manual da Família Preparada'
    # ícone (fundo transparente, para fundo escuro) e ícone com fundo (favicon/app)
    salvar('logo-icone.svg', svg(200, 200, icone(100, 100, 96), t))
    salvar('logo-icone-fundo.svg', svg(200, 200, f'<rect width="200" height="200" rx="44" fill="{INK}"/>' + icone(100, 100, 92), t))
    # horizontal escura (para fundo escuro) e clara (para fundo claro)
    for nome, cor, trilho, fundo in (('logo-horizontal-escuro.svg', CREAM, '#3A3733', None),
                                     ('logo-horizontal-claro.svg', INK, '#DCD3C4', None)):
        ic = icone(60, 60, 58, cor_txt=cor, cor_trilho=trilho)
        a, w = assinatura(136, 22, 80, cor, ORANGE)
        salvar(nome, svg(136 + w + 6, 120, ic + a, t))
    # quadrado para redes sociais (1080 x 1080)
    body = f'<rect width="1080" height="1080" fill="{INK}"/>' + icone(540, 470, 300)
    d, _ = text_path('MANUAL DA FAMÍLIA PREPARADA', 'IBMPlexMono-SemiBold', 30, 540, 900, ls=0.28, anchor='middle')
    body += f'<path d="{d}" fill="{CREAM}" opacity="0.85"/>'
    d, _ = text_path('sobrevivencia72h.com.br', 'IBMPlexSans-Medium', 26, 540, 960, anchor='middle')
    body += f'<path d="{d}" fill="{MUTED}"/>'
    salvar('logo-redes-1080.svg', svg(1080, 1080, body, t))
    # Open Graph (1200 x 630)
    body = f'<rect width="1200" height="630" fill="{INK}"/>' + icone(300, 315, 215)
    a, w = assinatura(570, 150, 150, CREAM, ORANGE)
    body += a
    d, _ = text_path('O que fazer nos 3 primeiros dias de um apagão,', 'IBMPlexSans-Regular', 26, 572, 380)
    body += f'<path d="{d}" fill="{CREAM}" opacity="0.85"/>'
    d, _ = text_path('enchente ou colapso, hora por hora.', 'IBMPlexSans-Regular', 26, 572, 418)
    body += f'<path d="{d}" fill="{CREAM}" opacity="0.85"/>'
    d, _ = text_path('KIT DE EMERGÊNCIA PARA 72 HORAS', 'IBMPlexMono-SemiBold', 22, 572, 500, ls=0.2)
    body += f'<path d="{d}" fill="{ORANGE}"/>'
    salvar('og-imagem.svg', svg(1200, 630, body, t))
    print('ok')


if __name__ == '__main__':
    main()
