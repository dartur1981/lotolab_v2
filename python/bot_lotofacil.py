import uiautomator2 as u2
import time
import logging
import argparse
import json
import sys

import os

# Configuração de log
log_dir = os.path.join(os.path.dirname(__file__), '..', 'logs')
os.makedirs(log_dir, exist_ok=True)
log_file = os.path.join(log_dir, 'bot_lotofacil.log')

# FileHandler (w) para sobrescrever a cada execução (limpando o log anterior), e StreamHandler para stdout/stderr
handlers = [
    logging.FileHandler(log_file, mode='w', encoding='utf-8'),
    logging.StreamHandler(sys.stderr)
]

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s', datefmt='%Y-%m-%d %H:%M:%S', handlers=handlers)

class LotofacilBot:
    def __init__(self, serial=None):
        logging.info("Conectando ao dispositivo...")
        if serial is None:
            import adbutils
            devices = adbutils.adb.device_list()
            if len(devices) > 0:
                serial = devices[0].serial
                logging.info(f"Selecionando o dispositivo: {serial}")
        self.d = u2.connect(serial)
        logging.info(f"Conectado: {self.d.info.get('model', 'Dispositivo Desconhecido')}")

    def aguardar_overlay_sumir(self):
        time.sleep(0.5)

    def clicar_dezena_seguro(self, numero: int) -> bool:
        num_str = f"{numero:02d}"
        
        # O app da Caixa usa ToggleButtons ordenados de 1 a 25 sem texto.
        # Portanto, a dezena N corresponde ao ToggleButton no índice (N - 1).
        botoes = self.d(className="android.widget.ToggleButton")
        if botoes.count != 25:
            logging.error(f"Não foram encontrados 25 botões na tela (encontrados {botoes.count}). Certifique-se de estar na tela do volante da Lotofácil.")
            return False

        elemento = botoes[numero - 1]
        
        if not elemento.exists:
            logging.error(f"Dezena {num_str} não encontrada na tela.")
            return False

        max_tentativas = 3
        for tentativa in range(1, max_tentativas + 1):
            try:
                self.aguardar_overlay_sumir()
                info = elemento.info
                if info.get('enabled') and info.get('visibleBounds'):
                    elemento.click()
                    logging.info(f"Dezena {num_str} clicada com sucesso.")
                    return True
                else:
                    logging.warning(f"Dezena {num_str} encontrada, mas não está habilitada/visível. Tentativa {tentativa}.")
            except u2.exceptions.UiObjectNotFoundError:
                logging.warning(f"Dezena {num_str} sumiu durante o clique. Tentativa {tentativa}.")
            except Exception as e:
                logging.error(f"Erro inesperado ao clicar na dezena {num_str}: {e}")
            
            time.sleep(1.0)

        logging.error(f"Falha ao clicar na dezena {num_str} após {max_tentativas} tentativas.")
        return False

    def adicionar_ao_carrinho(self):
        btn_adicionar = self.d(resourceId="br.gov.caixa.loterias.apostas:id/botaoAdicionarCompletarCartela")
        if btn_adicionar.wait(timeout=5.0):
            self.aguardar_overlay_sumir()
            btn_adicionar.click()
            logging.info("Jogo adicionado ao carrinho.")
            time.sleep(1.5)
            return True
        else:
            logging.warning("Botão 'Adicionar ao Carrinho' não encontrado.")
            return False

    def mapear_botoes(self) -> bool:
        if hasattr(self, 'botoes_coords') and len(self.botoes_coords) == 25:
            return True
            
        logging.info("Mapeando coordenadas dos 25 botões para digitação ultrarrápida...")
        botoes = self.d(className="android.widget.ToggleButton")
        if botoes.count != 25:
            logging.error(f"Não foram encontrados 25 botões na tela (encontrados {botoes.count}).")
            return False
            
        self.botoes_coords = {}
        for i in range(25):
            info = botoes[i].info
            bounds = info.get('visibleBounds') or info.get('bounds')
            if bounds:
                x = (bounds['left'] + bounds['right']) // 2
                y = (bounds['top'] + bounds['bottom']) // 2
                self.botoes_coords[i + 1] = (x, y)
                
        logging.info("Mapeamento concluído com sucesso!")
        return len(self.botoes_coords) == 25

    def clicar_dezena_rapido(self, numero: int) -> bool:
        if not hasattr(self, 'botoes_coords') or numero not in self.botoes_coords:
            return self.clicar_dezena_seguro(numero)
            
        x, y = self.botoes_coords[numero]
        self.d.click(x, y)
        time.sleep(0.05) # Micro-pausa para evitar sobreposição de toques no Android
        return True

    def lancar_jogos(self, lista_jogos: list) -> bool:
        logging.info("--- INICIANDO FASE 1: LANÇAMENTO DE JOGOS ---")
        sucesso_geral = True
        
        self.aguardar_overlay_sumir()
        self.mapear_botoes()
        
        for i, jogo in enumerate(lista_jogos, start=1):
            logging.info(f"Lançando Jogo {i}/{len(lista_jogos)}: {jogo}")
            sucesso_jogo = True
            for dezena in jogo:
                if not self.clicar_dezena_rapido(dezena):
                    logging.error(f"Interrompendo preenchimento do Jogo {i} devido à falha na dezena {dezena}.")
                    sucesso_jogo = False
                    sucesso_geral = False
                    break 
            
            if sucesso_jogo:
                self.adicionar_ao_carrinho()
                logging.info(f"Jogo {i} adicionado com sucesso ao carrinho.")
                
                # Se ainda houver mais jogos para lançar, precisamos voltar para a tela do volante
                if i < len(lista_jogos):
                    logging.info("Retornando à tela do volante para o próximo jogo (via carrinho)...")
                    time.sleep(1)
                    
                    # 1. Clicar no carrinho de apostas
                    btn_carrinho = self.d(resourceId="br.gov.caixa.loterias.apostas:id/botaoCarrinho")
                    if btn_carrinho.exists:
                        btn_carrinho.click()
                    else:
                        # Fallback
                        self.d(textContains="Carrinho").click()
                        
                    time.sleep(1.5)
                        
                    # 2. Clicar em Continuar apostando
                    btn_continuar = self.d(textContains="Continuar")
                    if btn_continuar.exists:
                        btn_continuar.click()
                        time.sleep(1.5)
                    else:
                        logging.warning("Botão 'Continuar' não achado por texto, mas prosseguindo...")
                    
                    self.aguardar_overlay_sumir()
                
        logging.info("--- FASE 1 CONCLUÍDA ---")
        return sucesso_geral

    def ir_para_carrinho(self) -> bool:
        logging.info("Navegando para o Carrinho...")
        btn_carrinho = self.d(resourceId="br.gov.caixa.loterias.apostas:id/botaoCarrinho")
        if btn_carrinho.exists:
            btn_carrinho.click()
            time.sleep(2)
            return True
        else:
            logging.warning("Ícone/Botão do carrinho não encontrado. Talvez já esteja na tela?")
            return False

    def extrair_dezenas_do_container(self, container_ui_object) -> list:
        try:
            numeros = []
            for child in container_ui_object.child(className="android.widget.TextView"):
                texto = child.info['text']
                if texto.isdigit():
                    numeros.append(int(texto))
            
            if not numeros:
                for child in container_ui_object.child(className="android.widget.TextView"):
                    texto = child.info['text']
                    nums = [int(n) for n in texto.split() if n.isdigit()]
                    if len(nums) == 15:
                        return sorted(nums)

            return sorted(numeros) if len(numeros) >= 15 else []
        except Exception as e:
            logging.error(f"Erro ao extrair dezenas do container: {e}")
            return []

    def ler_jogos_do_carrinho(self) -> list:
        logging.info("--- INICIANDO FASE 2: LEITURA DO CARRINHO ---")
        jogos_lidos = []
        
        # O id 'com.app:id/cart_item_container' é um placeholder. 
        # Vamos fazer um dump da tela para que eu (IA) possa ver a estrutura real!
        try:
            xml = self.d.dump_hierarchy()
            with open(r"D:\SISTEMAS-PRIVATE\lotolab_app_v2\python\ui_dump_carrinho_lista.xml", "w", encoding="utf-8") as f:
                f.write(xml)
            logging.info("Dump da tela do carrinho salvo em ui_dump_carrinho_lista.xml para debug.")
        except Exception as e:
            pass

        scroll_view = self.d(resourceId="br.gov.caixa.loterias.apostas:id/sv_carrinho")
        if scroll_view.exists:
            scroll_view.scroll.toBeginning(max_swipes=5)
            
        tentativas_sem_novos = 0
        
        while True:
            containers_aposta = self.d(resourceId="br.gov.caixa.loterias.apostas:id/contentTest") 
            
            jogos_adicionados_nesta_tela = 0
            for container in containers_aposta:
                try:
                    texto = container.child(resourceId="br.gov.caixa.loterias.apostas:id/numerosText").info['text']
                    numeros = [int(n) for n in texto.replace('-', ' ').split() if n.isdigit()]
                    if len(numeros) == 15:
                        if numeros not in jogos_lidos:
                            jogos_lidos.append(numeros)
                            jogos_adicionados_nesta_tela += 1
                except Exception as e:
                    logging.warning(f"Ignorando um container de aposta que não pôde ser lido: {e}")
                    
            if jogos_adicionados_nesta_tela == 0:
                tentativas_sem_novos += 1
                if tentativas_sem_novos >= 2:
                    break
            else:
                tentativas_sem_novos = 0
                
            # Força a rolagem para baixo deslizando o dedo de forma genérica
            self.d.swipe_ext("up", scale=0.6)
            time.sleep(1)
            
        logging.info(f"Total de jogos lidos no carrinho: {len(jogos_lidos)}")
        return jogos_lidos

    def conciliar(self, jogos_originais: list, jogos_carrinho: list) -> dict:
        logging.info("--- INICIANDO CONCILIAÇÃO ---")
        originais_ord = [sorted(g) for g in jogos_originais]
        carrinho_ord = [sorted(g) for g in jogos_carrinho]
        
        resultado = []
        
        for i, original in enumerate(originais_ord, start=1):
            if original in carrinho_ord:
                status = "OK"
                carrinho_ord.remove(original)
            else:
                status = "Divergente"
                
            resultado.append({
                "id": i,
                "jogo": original,
                "status": status
            })
            
        extras = []
        if carrinho_ord:
            for extra in carrinho_ord:
                extras.append({
                    "jogo": extra,
                    "status": "Divergente"
                })
                
        return {
            "jogos_processados": resultado,
            "jogos_extras": extras
        }

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Bot da Lotofácil via ADB")
    parser.add_argument('--action', required=True, choices=['lancar', 'carrinho', 'ler', 'conciliar', 'lancar_e_conciliar'], help='Ação a ser executada')
    parser.add_argument('--jogos', type=str, help='Lista de jogos em JSON para as funções lancar e conciliar')
    parser.add_argument('--lidos', type=str, help='Lista de jogos lidos no carrinho em JSON para a função conciliar')

    args = parser.parse_args()

    bot = LotofacilBot()
    
    response = {"status": "success", "data": None}

    try:
        if args.action == 'lancar':
            if not args.jogos:
                raise ValueError("A ação 'lancar' requer o parâmetro --jogos")
            jogos = json.loads(args.jogos)
            sucesso = bot.lancar_jogos(jogos)
            response["data"] = {"sucesso": sucesso}

        elif args.action == 'carrinho':
            sucesso = bot.ir_para_carrinho()
            response["data"] = {"sucesso": sucesso}

        elif args.action == 'ler':
            jogos_lidos = bot.ler_jogos_do_carrinho()
            response["data"] = {"jogos_lidos": jogos_lidos}

        elif args.action == 'conciliar':
            if not args.jogos or not args.lidos:
                raise ValueError("A ação 'conciliar' requer os parâmetros --jogos e --lidos")
            jogos = json.loads(args.jogos)
            lidos = json.loads(args.lidos)
            resultado = bot.conciliar(jogos, lidos)
            response["data"] = resultado
            
        elif args.action == 'lancar_e_conciliar':
            import datetime
            if not args.jogos:
                raise ValueError("A ação 'lancar_e_conciliar' requer o parâmetro --jogos")
            jogos = json.loads(args.jogos)
            
            hora_inicio = datetime.datetime.now()
            inicio_fase1 = time.time()
            
            sucesso_lancamento = bot.lancar_jogos(jogos)
            fim_fase1 = time.time()
            duracao_fase1 = fim_fase1 - inicio_fase1
            
            duracao_fase2 = 0.0
            if sucesso_lancamento:
                logging.info("--- INICIANDO FASE 2: CONCILIAÇÃO NO CARRINHO ---")
                inicio_fase2 = time.time()
                time.sleep(1) # Pausa rápida visual
                foi_carrinho = bot.ir_para_carrinho()
                if foi_carrinho:
                    jogos_lidos = bot.ler_jogos_do_carrinho()
                    resultado = bot.conciliar(jogos, jogos_lidos)
                    fim_fase2 = time.time()
                    duracao_fase2 = fim_fase2 - inicio_fase2
                    
                    response["data"] = {
                        "sucesso_lancamento": True,
                        "foi_carrinho": True,
                        "conciliacao": resultado
                    }
                else:
                    fim_fase2 = time.time()
                    duracao_fase2 = fim_fase2 - inicio_fase2
                    response["data"] = {
                        "sucesso_lancamento": True,
                        "foi_carrinho": False,
                        "conciliacao": None
                    }
            else:
                response["data"] = {
                    "sucesso_lancamento": False,
                    "foi_carrinho": False,
                    "conciliacao": None
                }
                
            hora_fim = datetime.datetime.now()
            duracao_total = (hora_fim - hora_inicio).total_seconds()
            
            def format_time(seconds):
                return str(datetime.timedelta(seconds=int(seconds))).zfill(8)
            
            logging.info("")
            logging.info("=== RESUMO DA EXECUÇÃO ===")
            logging.info(f"Início: {hora_inicio.strftime('%Y-%m-%d %H:%M:%S')}")
            logging.info(f"Fim:    {hora_fim.strftime('%Y-%m-%d %H:%M:%S')}")
            logging.info(f"Fase 1 (Lançamento):  {format_time(duracao_fase1)} ({duracao_fase1:.2f}s)")
            if sucesso_lancamento:
                logging.info(f"Fase 2 (Conciliação): {format_time(duracao_fase2)} ({duracao_fase2:.2f}s)")
            logging.info(f"Duração Total:        {format_time(duracao_total)} ({duracao_total:.2f}s)")
            logging.info("==========================")
            logging.info("")

    except Exception as e:
        response["status"] = "error"
        response["message"] = str(e)
        logging.error(f"Erro ao executar ação {args.action}: {e}")

    # Imprime apenas o JSON em stdout para o Laravel pegar
    print(json.dumps(response))
