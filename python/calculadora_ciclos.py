from dataclasses import dataclass
from typing import List, Set, Dict, Any

@dataclass
class Concurso:
    numero: int
    dezenas: List[int]

@dataclass
class CicloFechado:
    numero_ciclo: int
    concurso_inicial: int
    concurso_final: int
    duracao: int
    json_matriz: Dict[str, Any]

@dataclass
class CicloAtual:
    numero_ciclo: int
    concurso_inicial: int
    concursos_transcorridos: int
    dezenas_sorteadas: List[int]
    dezenas_faltantes: List[int]
    json_matriz: Dict[str, Any]

@dataclass
class RelatorioCiclos:
    ciclos_fechados: List[CicloFechado]
    ciclo_atual: CicloAtual

def calcular_ciclos_lotofacil(concursos: List[Concurso]) -> RelatorioCiclos:
    ciclos_fechados: List[CicloFechado] = []
    todas_as_dezenas = set(range(1, 26))
    
    if not concursos:
        return RelatorioCiclos(
            ciclos_fechados=[],
            ciclo_atual=CicloAtual(
                1, 1, 0, [], list(todas_as_dezenas), 
                {
                    "status": "Aberto", 
                    "sorteadas": [], 
                    "faltantes": list(todas_as_dezenas), 
                    "concursos": 0, 
                    "inicio_ciclo": 1, 
                    "matriz": []
                }
            )
        )
        
    concursos_ordenados = sorted(concursos, key=lambda c: c.numero)
    
    numero_ciclo = 1
    dezenas_rastreador: Set[int] = set()
    concurso_inicio = concursos_ordenados[0].numero
    qtd_concursos = 0
    matriz = []
    
    for concurso in concursos_ordenados:
        if len(dezenas_rastreador) == 0:
            concurso_inicio = concurso.numero
            qtd_concursos = 0
            matriz = []
            
        acumulado_anterior = sorted(list(dezenas_rastreador))
        
        # Insere sempre na primeira posição para ordem decrescente do concurso
        matriz.insert(0, {
            "concurso": concurso.numero,
            "dezenas": sorted(concurso.dezenas),
            "acumulado_anterior": acumulado_anterior
        })
            
        dezenas_rastreador.update(concurso.dezenas)
        qtd_concursos += 1
        
        if len(dezenas_rastreador) == 25:
            json_matriz_fechado = {
                "status": "Fechado",
                "sorteadas": sorted(list(dezenas_rastreador)),
                "faltantes": [],
                "concursos": qtd_concursos,
                "inicio_ciclo": concurso_inicio,
                "matriz": matriz
            }
            
            ciclos_fechados.append(CicloFechado(
                numero_ciclo=numero_ciclo,
                concurso_inicial=concurso_inicio,
                concurso_final=concurso.numero,
                duracao=qtd_concursos,
                json_matriz=json_matriz_fechado
            ))
            
            numero_ciclo += 1
            dezenas_rastreador.clear()
            concurso_inicio = 0 
            qtd_concursos = 0
            matriz = []

    dezenas_faltantes = todas_as_dezenas - dezenas_rastreador
    
    if concurso_inicio == 0 and len(concursos_ordenados) > 0:
        concurso_inicio = concursos_ordenados[-1].numero + 1
        
    json_matriz_aberto = {
        "status": "Aberto",
        "sorteadas": sorted(list(dezenas_rastreador)),
        "faltantes": sorted(list(dezenas_faltantes)),
        "concursos": qtd_concursos,
        "inicio_ciclo": concurso_inicio,
        "matriz": matriz
    }
        
    ciclo_atual = CicloAtual(
        numero_ciclo=numero_ciclo,
        concurso_inicial=concurso_inicio,
        concursos_transcorridos=qtd_concursos,
        dezenas_sorteadas=sorted(list(dezenas_rastreador)),
        dezenas_faltantes=sorted(list(dezenas_faltantes)),
        json_matriz=json_matriz_aberto
    )
    
    return RelatorioCiclos(ciclos_fechados, ciclo_atual)
