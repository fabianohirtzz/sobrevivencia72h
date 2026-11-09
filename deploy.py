"""Publica o site na Erehost por FTPS com reconexão por arquivo para evitar timeout de dados do firewall.
"""
import ftplib, os, ssl, sys, time

HOST = 'cpbr-sp3.erehost.com.br'
USER = 'sobre@sobrevivencia72h.com.br'
HERE = os.path.dirname(os.path.abspath(__file__))
IGNORAR = {'.git', '__pycache__', 'pedidos'}

def conecta():
    ftp = ftplib.FTP_TLS(HOST, context=ssl.create_default_context(), timeout=30)
    ftp.login(USER, os.environ['FTP_SENHA'])
    ftp.prot_p()
    ftp.set_pasv(True)
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

def envia_arquivo_com_retry(remoto_arqui, local_caminho, max_tentativas=3):
    for tentativa in range(1, max_tentativas + 1):
        try:
            ftp = conecta()
            with open(local_caminho, 'rb') as fh:
                ftp.storbinary(f'STOR {remoto_arqui}', fh)
            ftp.quit()
            return True
        except Exception as e:
            print(f'Tentativa {tentativa} falhou para {remoto_arqui}: {e}')
            time.sleep(2)
    return False

def envia_pasta(local, remoto):
    # Primeiro garante as pastas criadas
    ftp = conecta()
    for raiz, dirs, arqs in os.walk(local):
        dirs[:] = [d for d in dirs if d not in IGNORAR]
        rel = os.path.relpath(raiz, local).replace('\\', '/')
        destino = remoto.rstrip('/') + ('' if rel == '.' else '/' + rel)
        mkdirs(ftp, destino)
    ftp.quit()

    n = 0
    for raiz, dirs, arqs in os.walk(local):
        dirs[:] = [d for d in dirs if d not in IGNORAR]
        rel = os.path.relpath(raiz, local).replace('\\', '/')
        destino = remoto.rstrip('/') + ('' if rel == '.' else '/' + rel)
        for a in arqs:
            if a.endswith('.log'):
                continue
            local_arqui = os.path.join(raiz, a)
            remoto_arqui = f'{destino}/{a}'
            print(f'Enviando {remoto_arqui}...')
            if envia_arquivo_com_retry(remoto_arqui, local_arqui):
                n += 1
            else:
                print(f'ERRO CRÍTICO ao enviar {remoto_arqui}')
    return n

if __name__ == '__main__':
    if '--so-config' in sys.argv:
        envia_arquivo_com_retry('/privado/config.php', os.path.join(HERE, 'privado', 'config.php'))
        print('config.php enviado com sucesso!')
    else:
        print('Iniciando deploy...')
        n_pub = envia_pasta(os.path.join(HERE, 'public'), '/')
        print(f'public: {n_pub} arquivos enviados.')
        n_priv = envia_pasta(os.path.join(HERE, 'privado'), '/privado')
        print(f'privado: {n_priv} arquivos enviados.')
        ftp = conecta()
        mkdirs(ftp, '/privado/pedidos')
        ftp.quit()
        print('Deploy finalizado com sucesso!')
