import os
import sys
import time
import json
import logging
import uiautomator2 as u2
import adbutils

# Diretório e arquivo de log específico da Mega-Sena
log_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..', 'logs'))
os.makedirs(log_dir, exist_ok=True)
log_file = os.path.join(log_dir, 'bot_megasena.log')

def setup_megasena_logger():
    logger = logging.getLogger("MegasenaBot")
    logger.setLevel(logging.INFO)
    logger.handlers.clear()
    
    fh = logging.FileHandler(log_file, mode='a', encoding='utf-8')
    fh.setFormatter(logging.Formatter('%(asctime)s - %(levelname)s - %(message)s', '%Y-%m-%d %H:%M:%S'))
    
    sh = logging.StreamHandler(sys.stderr)
    sh.setFormatter(logging.Formatter('%(asctime)s - %(levelname)s - %(message)s', '%Y-%m-%d %H:%M:%S'))
    
    logger.addHandler(fh)
    logger.addHandler(sh)
    logger.propagate = False
    return logger

logger = setup_megasena_logger()

class MegasenaBot:
    def __init__(self, serial=None):
        logger.info("MegasenaBot: Conectando ao dispositivo...")
        self.ultimo_erro = None
        self.coords_top = {}
        self.coords_bottom = {}
        self.scroll_pos = "top"

        try:
            devices = adbutils.adb.device_list()
        except Exception as e_adb:
            msg = f"Erro ao consultar servidor ADB: {e_adb}"
            logger.error(msg)
            self.ultimo_erro = msg
            raise RuntimeError(msg)

        if not devices:
            msg = "Nenhum dispositivo Android encontrado via ADB! Conecte seu aparelho antes de iniciar o robô."
            logger.error(msg)
            self.ultimo_erro = msg
            raise RuntimeError(msg)

        if serial is None:
            serial = devices[0].serial
            logger.info(f"Selecionando o dispositivo: {serial}")

        try:
            self.d = u2.connect(serial)
            model = self.d.info.get('model', serial)
            logger.info(f"Conectado ao dispositivo: {model}")
            
            if not self.d.info.get('screenOn', True):
                logger.info("Tela desligada. Ligando tela...")
                self.d.screen_on()
                time.sleep(0.5)
        except Exception as e_u2:
            msg = f"Erro ao conectar via uiautomator2 no aparelho {serial}: {e_u2}"
            logger.error(msg)
            self.ultimo_erro = msg
            raise RuntimeError(msg)

    def aguardar_overlay_sumir(self):
        time.sleep(0.4)

    def mapear_botoes(self) -> bool:
        if len(self.coords_top) == 30 and len(self.coords_bottom) == 30:
            return True

        logger.info("Mapeando coordenadas dos 60 botões da Mega-Sena...")
        
        scroll = self.d(resourceId="br.gov.caixa.loterias.apostas:id/cartelaFragmentScrollView")
        if not scroll.exists:
            scroll = self.d(className="android.widget.ScrollView")

        if not scroll.exists:
            msg = "Volante da Mega-Sena não encontrado. Certifique-se de que o app da Caixa está aberto na tela da Mega-Sena."
            logger.error(msg)
            self.ultimo_erro = msg
            return False

        # 1. Rola para o topo (Início)
        try:
            scroll.scroll.toBeginning(max_swipes=5)
        except Exception as e:
            logger.warning(f"Aviso ao rolar para o topo: {e}")
        time.sleep(0.8)

        toggles_top = self.d(resourceId="br.gov.caixa.loterias.apostas:id/tv_item_numero")
        if not toggles_top.exists or toggles_top.count < 30:
            toggles_top = self.d(className="android.widget.ToggleButton")

        if toggles_top.count < 30:
            msg = f"Botões insuficientes no topo (encontrados {toggles_top.count}). Abra o volante da Mega-Sena."
            logger.error(msg)
            self.ultimo_erro = msg
            return False

        self.coords_top = {}
        for i in range(30):
            info = toggles_top[i].info
            bounds = info.get('visibleBounds') or info.get('bounds')
            if bounds:
                x = (bounds['left'] + bounds['right']) // 2
                y = (bounds['top'] + bounds['bottom']) // 2
                self.coords_top[i + 1] = (x, y)

        logger.info("Dezenas 01 a 30 mapeadas com sucesso.")

        # 2. Rola para o fim (Base)
        try:
            scroll.scroll.toEnd(max_swipes=5)
        except Exception as e:
            logger.warning(f"Aviso ao rolar para o fim: {e}")
        time.sleep(0.8)

        toggles_bottom = self.d(resourceId="br.gov.caixa.loterias.apostas:id/tv_item_numero")
        if not toggles_bottom.exists or toggles_bottom.count < 30:
            toggles_bottom = self.d(className="android.widget.ToggleButton")

        if toggles_bottom.count < 30:
            msg = f"Botões insuficientes na base (encontrados {toggles_bottom.count})."
            logger.error(msg)
            self.ultimo_erro = msg
            return False

        self.coords_bottom = {}
        # O último botão visível é a dezena 60
        total_bot = toggles_bottom.count
        for num in range(31, 61):
            idx = (total_bot - 1) - (60 - num)
            if 0 <= idx < total_bot:
                info = toggles_bottom[idx].info
                bounds = info.get('visibleBounds') or info.get('bounds')
                if bounds:
                    x = (bounds['left'] + bounds['right']) // 2
                    y = (bounds['top'] + bounds['bottom']) // 2
                    self.coords_bottom[num] = (x, y)

        logger.info("Dezenas 31 a 60 mapeadas com sucesso.")

        # 3. Retorna para o topo
        try:
            scroll.scroll.toBeginning(max_swipes=5)
        except Exception:
            pass
        self.scroll_pos = "top"
        time.sleep(0.6)

        logger.info("Mapeamento completo dos 60 números da Mega-Sena concluído!")
        return len(self.coords_top) == 30 and len(self.coords_bottom) == 30

    def clicar_dezenas_jogo(self, jogo: list) -> bool:
        scroll = self.d(resourceId="br.gov.caixa.loterias.apostas:id/cartelaFragmentScrollView")
        if not scroll.exists:
            scroll = self.d(className="android.widget.ScrollView")

        dezenas_top = [n for n in jogo if n <= 30]
        dezenas_bottom = [n for n in jogo if n > 30]

        # Clica nas dezenas do topo (1 a 30)
        if dezenas_top:
            if self.scroll_pos != "top":
                if scroll.exists:
                    scroll.scroll.toBeginning(max_swipes=5)
                self.scroll_pos = "top"
                time.sleep(0.3)

            for d in dezenas_top:
                if d in self.coords_top:
                    x, y = self.coords_top[d]
                    self.d.click(x, y)
                    time.sleep(0.06)

        # Clica nas dezenas da base (31 a 60)
        if dezenas_bottom:
            if self.scroll_pos != "bottom":
                if scroll.exists:
                    scroll.scroll.toEnd(max_swipes=5)
                self.scroll_pos = "bottom"
                time.sleep(0.3)

            for d in dezenas_bottom:
                if d in self.coords_bottom:
                    x, y = self.coords_bottom[d]
                    self.d.click(x, y)
                    time.sleep(0.06)

        return True

    def fechar_modal_se_houver(self):
        # Verifica se apareceu modal com "Entendi" ou "OK"
        btn_entendi = self.d(textMatches="(?i)entendi")
        if not btn_entendi.exists:
            btn_entendi = self.d(descriptionMatches="(?i)entendi")
        if not btn_entendi.exists:
            btn_entendi = self.d(textMatches="(?i)ok|fechar|continuar")

        if btn_entendi.exists:
            logger.info("Modal detectado. Clicando em 'Entendi' para dispensar...")
            try:
                btn_entendi.click()
                time.sleep(0.8)
                return True
            except Exception as e:
                logger.warning(f"Erro ao clicar em 'Entendi': {e}")
        return False

    def adicionar_ao_carrinho(self) -> bool:
        btn_adicionar = self.d(resourceId="br.gov.caixa.loterias.apostas:id/btnAdicionarCarrinho")
        if not btn_adicionar.exists:
            btn_adicionar = self.d(text="Adicionar ao carrinho")
        if not btn_adicionar.exists:
            btn_adicionar = self.d(resourceId="br.gov.caixa.loterias.apostas:id/botaoAdicionarCompletarCartela")

        if btn_adicionar.wait(timeout=4.0):
            self.aguardar_overlay_sumir()
            btn_adicionar.click()
            logger.info("Jogo adicionado ao carrinho.")
            time.sleep(1.2)
            
            # Trata o modal "Entendi" que surge logo após clicar em adicionar
            self.fechar_modal_se_houver()
            return True
        else:
            msg = "Botão 'Adicionar ao carrinho' não encontrado após selecionar as dezenas."
            logger.warning(msg)
            self.ultimo_erro = msg
            return False

    def lancar_jogos(self, lista_jogos: list, on_jogo_adicionado=None) -> bool:
        logger.info("--- INICIANDO FASE 1: LANÇAMENTO DE JOGOS (MEGA-SENA) ---")
        sucesso_geral = True

        if not self.mapear_botoes():
            return False

        scroll = self.d(resourceId="br.gov.caixa.loterias.apostas:id/cartelaFragmentScrollView")
        if not scroll.exists:
            scroll = self.d(className="android.widget.ScrollView")

        for i, jogo in enumerate(lista_jogos, start=1):
            logger.info(f"Lançando Jogo {i}/{len(lista_jogos)}: {jogo}")
            
            sucesso_clique = self.clicar_dezenas_jogo(jogo)
            if not sucesso_clique:
                logger.error(f"Falha ao clicar nas dezenas do Jogo {i}.")
                sucesso_geral = False
                break

            if not self.adicionar_ao_carrinho():
                logger.error(f"Falha ao adicionar o Jogo {i} ao carrinho.")
                sucesso_geral = False
                break

            logger.info(f"Jogo {i} adicionado com sucesso ao carrinho.")
            if on_jogo_adicionado:
                try:
                    on_jogo_adicionado(i - 1)
                except Exception as e_cb:
                    logger.warning(f"Erro no callback on_jogo_adicionado: {e_cb}")

            # Se ainda houver mais jogos, garante que a tela retorne ao topo para o próximo
            if i < len(lista_jogos):
                if scroll.exists:
                    scroll.scroll.toBeginning(max_swipes=5)
                self.scroll_pos = "top"
                time.sleep(0.5)

        logger.info("--- FASE 1 CONCLUÍDA (MEGA-SENA) ---")
        return sucesso_geral

    def ir_para_carrinho(self) -> bool:
        logger.info("Navegando para o Carrinho...")
        # Garante que nenhum modal remanescente esteja bloqueando a tela
        self.fechar_modal_se_houver()

        btn_carrinho = self.d(description="Carrinho de Apostas")
        if not btn_carrinho.exists:
            btn_carrinho = self.d(descriptionContains="Carrinho")
        if not btn_carrinho.exists:
            btn_carrinho = self.d(resourceId="br.gov.caixa.loterias.apostas:id/frameCarrinho")
        if not btn_carrinho.exists:
            btn_carrinho = self.d(resourceId="br.gov.caixa.loterias.apostas:id/ivCarrinho")
        if not btn_carrinho.exists:
            btn_carrinho = self.d(resourceId="br.gov.caixa.loterias.apostas:id/botaoCarrinho")
        if not btn_carrinho.exists:
            btn_carrinho = self.d(textContains="Carrinho")

        if btn_carrinho.exists:
            btn_carrinho.click()
            time.sleep(2)
            return True

        # Fallback inteligente por coordenadas (ícone do carrinho no canto inferior direito)
        try:
            w, h = self.d.window_size()
            cx = int(w * 0.855)
            cy = int(h * 0.90)
            logger.info(f"Tentando tocar nas coordenadas do carrinho ({cx}, {cy})...")
            self.d.click(cx, cy)
            time.sleep(2)
            return True
        except Exception as e:
            logger.warning(f"Fallback de coordenadas falhou: {e}")

        msg = "Ícone/Botão do carrinho não encontrado. Certifique-se de que o app da Caixa está aberto."
        logger.warning(msg)
        self.ultimo_erro = msg
        return False

    def _extrair_jogos_do_xml(self, xml_str: str) -> list:
        import xml.etree.ElementTree as ET
        jogos_tela = []
        try:
            root = ET.fromstring(xml_str)
        except Exception as e:
            logger.warning(f"Falha ao fazer parse do XML do carrinho (Mega-Sena): {e}")
            return jogos_tela

        nos_numeros = [
            node for node in root.iter('node')
            if node.attrib.get('resource-id') == 'br.gov.caixa.loterias.apostas:id/numerosText'
        ]
        if not nos_numeros:
            nos_numeros = [
                node for node in root.iter('node')
                if node.attrib.get('class') == 'android.widget.TextView'
            ]

        for node in nos_numeros:
            texto = (node.attrib.get('text') or '').strip()
            if not texto:
                continue
            numeros = [int(n) for n in texto.replace('-', ' ').split() if n.isdigit()]
            if len(numeros) == 6 and all(1 <= n <= 60 for n in numeros):
                jogos_tela.append(sorted(numeros))

        return jogos_tela

    def ler_jogos_do_carrinho(self) -> list:
        logger.info("--- INICIANDO FASE 2: LEITURA DO CARRINHO (MEGA-SENA) ---")
        jogos_lidos = []
        jogos_vistos = set()

        scroll_view = self.d(resourceId="br.gov.caixa.loterias.apostas:id/sv_carrinho")
        if not scroll_view.exists:
            scroll_view = self.d(className="android.widget.ScrollView")

        if scroll_view.exists:
            try:
                scroll_view.scroll.toBeginning(max_swipes=30)
                time.sleep(0.5)
            except Exception:
                pass

        tentativas_sem_novos = 0

        for rolagem in range(1, 151):
            try:
                xml_tela = self.d.dump_hierarchy()
            except Exception as e:
                logger.warning(f"Erro ao capturar hierarquia na rolagem {rolagem}: {e}")
                tentativas_sem_novos += 1
                if tentativas_sem_novos >= 3:
                    break
                time.sleep(0.6)
                continue

            jogos_adicionados_nesta_tela = 0
            for jogo in self._extrair_jogos_do_xml(xml_tela):
                chave = tuple(jogo)
                if chave not in jogos_vistos:
                    jogos_vistos.add(chave)
                    jogos_lidos.append(jogo)
                    jogos_adicionados_nesta_tela += 1

            if rolagem == 1 or rolagem % 5 == 0 or jogos_adicionados_nesta_tela == 0:
                logger.info(f"Rolagem {rolagem}: +{jogos_adicionados_nesta_tela} novos (total lidos: {len(jogos_lidos)})")

            if jogos_adicionados_nesta_tela == 0:
                tentativas_sem_novos += 1
                if tentativas_sem_novos >= 3:
                    break
            else:
                tentativas_sem_novos = 0

            self.d.swipe_ext("up", scale=0.55)
            time.sleep(0.65)

        logger.info(f"Total de jogos da Mega-Sena lidos no carrinho: {len(jogos_lidos)}")
        return jogos_lidos

    def conciliar(self, jogos_originais: list, jogos_carrinho: list, jogos_anteriores: list = None) -> dict:
        logger.info("--- INICIANDO CONCILIAÇÃO (MEGA-SENA) ---")
        originais_ord = [sorted(g) for g in jogos_originais]
        anteriores_ord = [sorted(g) for g in (jogos_anteriores or [])]
        carrinho_ord = [sorted(g) for g in jogos_carrinho]

        if anteriores_ord:
            logger.info(f"Jogos anteriores do fechamento esperados no carrinho: {anteriores_ord}")

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

        anteriores_resultado = []
        for i, anterior in enumerate(anteriores_ord, start=1):
            if anterior in carrinho_ord:
                status = "OK"
                carrinho_ord.remove(anterior)
            else:
                status = "Divergente"

            anteriores_resultado.append({
                "id": i,
                "jogo": anterior,
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
            "jogos_anteriores_processados": anteriores_resultado,
            "jogos_extras": extras,
            "jogos_lidos": [sorted(g) for g in jogos_carrinho]
        }
