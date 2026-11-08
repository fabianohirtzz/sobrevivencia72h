"""Publica o site na Erehost por FTPS (certificado verificado).
A raiz do FTP é a pasta pública do domínio: public/* vai para /, privado/* vai para /privado (bloqueada por .htaccess).
Uso: set FTP_SENHA=... && python deploy.py [--so-config]
"""
import ftplib, os, ssl, sys

HOST = 'cpbr-sp3.erehost.com.br'          # nome do certificado do servidor
USER = 'sobrev@sobrevivencia72h.com.br'
HERE = os.path.dirname(os.path.abspath(__file__))
IGNORAR = {'.git', '__pycache__', 'pedidos'}


def conecta():
    ftp = ftplib.FTP_TLS(HOST, context=ssl.create_default_context(), timeout=60)
    ftp.login(USER, os.environ['FTP_SENHA'])
    ftp.prot_p()
    return ftp


def mkdirs(ftp, caminho):
    atual = ''
    for parte in caminho.strip('/').split('/'):
        if not parte:
            continue
        atual += '/' + parte
        try:
            ftp.mkd(atual)
        except ftplib.error_perm:
            pass


def envia_pasta(ftp, local, remoto):
    n = 0
    for raiz, dirs, arqs in os.walk(local):
        dirs[:] = [d for d in dirs if d not in IGNORAR]
        rel = os.path.relpath(raiz, local).replace('\\', '/')
        destino = remoto.rstrip('/') + ('' if rel == '.' else '/' + rel)
        mkdirs(ftp, destino)
        for a in arqs:
            if a.endswith('.log'):
                continue
            with open(os.path.join(raiz, a), 'rb') as fh:
                ftp.storbinary(f'STOR {destino}/{a}', fh)
            n += 1
    return n


if __name__ == '__main__':
    ftp = conecta()
    if '--so-config' in sys.argv:
        with open(os.path.join(HERE, 'privado', 'config.php'), 'rb') as fh:
            ftp.storbinary('STOR /privado/config.php', fh)
        print('config.php enviado')
    else:
        print('public :', envia_pasta(ftp, os.path.join(HERE, 'public'), '/'), 'arquivos')
        print('privado:', envia_pasta(ftp, os.path.join(HERE, 'privado'), '/privado'), 'arquivos')
        mkdirs(ftp, '/privado/pedidos')
    ftp.quit()
