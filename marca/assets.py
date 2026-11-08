"""Gera os recursos do site: fontes woff2 (subconjunto latino) e prévias das páginas do manual e dos bônus em WebP."""
import os
import pymupdf
from PIL import Image
from fontTools.ttLib import TTFont
from fontTools import subset

HERE = os.path.dirname(os.path.abspath(__file__))
EB = os.path.join(HERE, '..', '..', 'Ebooks')
PUB = os.path.join(HERE, '..', 'public', 'assets')

# ---------- fontes
FONTES = ['BigShouldersDisplay-Black', 'BigShouldersDisplay-ExtraBold', 'IBMPlexSans-Regular', 'IBMPlexSans-Italic',
          'IBMPlexSans-Medium', 'IBMPlexSans-SemiBold', 'IBMPlexSans-Bold', 'IBMPlexMono-Medium', 'IBMPlexMono-SemiBold']
os.makedirs(os.path.join(PUB, 'fonts'), exist_ok=True)
for f in FONTES:
    src = os.path.join(EB, 'gerador', 'fonts', f + '.ttf')
    out = os.path.join(PUB, 'fonts', f + '.woff2')
    opts = subset.Options()
    opts.flavor = 'woff2'
    opts.layout_features = ['*']
    fnt = TTFont(src)
    s = subset.Subsetter(opts)
    s.populate(unicodes=list(range(0x20, 0x7F)) + list(range(0xA0, 0x180)) + [0x2013, 0x2014, 0x2018, 0x2019, 0x201C, 0x201D, 0x2022, 0x2026, 0x20AC, 0x2192, 0x00D7])
    s.subset(fnt)
    fnt.flavor = 'woff2'
    fnt.save(out)
    print('fonte', f, os.path.getsize(out) // 1024, 'KB')

# ---------- prévias
os.makedirs(os.path.join(PUB, 'img'), exist_ok=True)
PREVIAS = [
    ('72-Horas-Manual-completo.pdf', 0, 'manual-capa'),
    ('72-Horas-Manual-completo.pdf', 1, 'manual-sumario'),
    ('72-Horas-Manual-completo.pdf', 7, 'manual-regra-dos-3'),
    ('72-Horas-Manual-completo.pdf', 9, 'manual-fases'),
    ('72-Horas-Manual-completo.pdf', 22, 'manual-agua'),
    ('72-Horas-Manual-completo.pdf', 55, 'manual-mochila'),
    ('72-Horas-Manual-completo.pdf', 85, 'manual-7-dias'),
    ('72-Horas-Bonus-1-Plano-Familiar.pdf', 0, 'bonus-1-capa'),
    ('72-Horas-Bonus-1-Plano-Familiar.pdf', 1, 'bonus-1-pagina'),
    ('72-Horas-Bonus-2-Checklists-das-3-Mochilas.pdf', 0, 'bonus-2-capa'),
    ('72-Horas-Bonus-2-Checklists-das-3-Mochilas.pdf', 1, 'bonus-2-pagina'),
    ('72-Horas-Bonus-3-Guia-de-Bolso.pdf', 0, 'bonus-3-capa'),
    ('72-Horas-Bonus-3-Guia-de-Bolso.pdf', 1, 'bonus-3-pagina'),
]
for pdf, idx, nome in PREVIAS:
    d = pymupdf.open(os.path.join(EB, pdf))
    pix = d[idx].get_pixmap(dpi=110)
    im = Image.frombytes('RGB', (pix.width, pix.height), pix.samples)
    for w, suf in ((900, ''), (480, '-p')):
        h = int(im.height * w / im.width)
        im.resize((w, h), Image.LANCZOS).save(os.path.join(PUB, 'img', f'{nome}{suf}.webp'), 'WEBP', quality=80, method=6)
    print('prévia', nome)

# ---------- foto de fundo do topo (capa do ebook, sem textos)
src = os.path.join(EB, 'gerador', 'img', 'capa.png')
im = Image.open(src).convert('RGB')
for w, suf in ((1400, ''), (760, '-p')):
    h = int(im.height * w / im.width)
    im.resize((w, h), Image.LANCZOS).save(os.path.join(PUB, 'img', f'hero{suf}.webp'), 'WEBP', quality=72, method=6)
print('ok')
