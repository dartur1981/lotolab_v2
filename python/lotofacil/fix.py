with open('gerador_fechamentos.py', 'r') as f:
    content = f.read()
old = 'if len(dezenas_ordenadas) == 18:'
idx = content.rfind(old)
if idx != -1:
    end_idx = content.find('else:', idx)
    if end_idx != -1:
        new_block = '''if len(dezenas_ordenadas) == 18:
        import itertools
        
        todos_os_jogos = list(itertools.combinations(dezenas_ordenadas, 15))
        todos_os_jogos = [list(j) for j in todos_os_jogos]
        
        jogos_com_score = [(j, pontuar_jogo(j, afinidades_fortes)) for j in todos_os_jogos]
        jogos_com_score.sort(key=lambda x: x[1], reverse=True)
        
        top_jogos = jogos_com_score[:quantidade_jogos]
        jogos_validos = top_jogos
        
        grupos_utilizados = {
            'metodo': 'Todas as Combinacoes (816) ranqueadas por Score',
            'total_jogos_solicitados': quantidade_jogos,
            'jogos_gerados': len(jogos_validos)
        }
        '''
        content = content[:idx] + new_block + content[end_idx:]
with open('gerador_fechamentos.py', 'w') as f:
    f.write(content)
