import os
import sys
import time
import json
import logging
import uiautomator2 as u2
import adbutils

# Diretório e arquivo de log específico da Quina
log_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..', 'logs'))
os.makedirs(log_dir, exist_ok=True)
log_file = os.path.join(log_dir, 'bot_quina.log')

def setup_quina_logger():
    logger = logging.getLogger("QuinaBot")
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

logger = setup_quina_logger()

class QuinaBot:
    def __init__(self, serial=None):
        logger.info("QuinaBot: Conectando ao dispositivo...")
        self.ultimo_erro = None
        self.coords_top = {}     # 01 a 36
        self.coords_mid = {}     # 37 a 48
        self.coords_bottom = {}  # 49 a 80
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
                logger.info("Tela do aparelho desligada. Ligando tela...")
                self.d.screen_on()
                time.sleep(1)
                self.d.swipe_ext("up", scale=0.5)

            # Mapeia coordenadas dos botões do volante de 80 números da Quina nas 3 zonas
            self.mapear_botoes_volante()
        except Exception as e_conn:
            msg = f"Falha ao conectar via uiautomator2 no aparelho {serial}: {e_conn}"
            logger.error(msg)
            self.ultimo_erro = msg
            raise RuntimeError(msg)

    def _get_scroll_volante(self):
        scroll = self.d(resourceId="br.gov.caixa.loterias.apostas:id/cartelaFragmentScrollView")
        if not scroll.exists:
            scroll = self.d(className="android.widget.ScrollView")
        return scroll

    def _ir_para_topo(self, scroll=None):
        if scroll is None:
            scroll = self._get_scroll_volante()
        try:
            if scroll.exists:
                scroll.scroll.toBeginning(max_swipes=5)
        except Exception:
            pass
        # Arraste garantido DENTRO da área do volante [772..1701]
        self.d.drag(540, 900, 540, 1650, duration=0.25)
        time.sleep(0.4)
        self.scroll_pos = "top"

    def _ir_para_base(self, scroll=None):
        if scroll is None:
            scroll = self._get_scroll_volante()
        try:
            if scroll.exists:
                scroll.scroll.toEnd(max_swipes=5)
        except Exception:
            pass
        # Arraste garantido DENTRO da área do volante [772..1701]
        self.d.drag(540, 1650, 540, 900, duration=0.25)
        time.sleep(0.4)
        self.scroll_pos = "bottom"

    def _ir_para_meio(self, scroll=None):
        if scroll is None:
            scroll = self._get_scroll_volante()
        if self.scroll_pos != "top":
            self._ir_para_topo(scroll)
        # Drag vertical exato de 5 linhas (5 * 159 = 795px) dentro da área do volante
        self.d.drag(540, 1600, 540, 1600 - 795, duration=0.4)
        time.sleep(0.5)
        self.scroll_pos = "mid"

    def mapear_botoes_volante(self):
        logger.info("Mapeando posições dos botões no volante da Quina nas 3 zonas...")

        scroll = self._get_scroll_volante()

        # ========================================================
        # ZONA 1 (TOPO): Dezenas 01 a 36 (6 linhas completas)
        # ========================================================
        self._ir_para_topo(scroll)
        time.sleep(0.3)

        toggles_top = self.d(resourceId="br.gov.caixa.loterias.apostas:id/tv_item_numero")
        if not toggles_top.exists or toggles_top.count < 30:
            toggles_top = self.d(className="android.widget.ToggleButton")

        self.coords_top = {}
        for i in range(min(36, toggles_top.count)):
            try:
                info = toggles_top[i].info
                bounds = info.get('visibleBounds') or info.get('bounds')
                if bounds:
                    cx = (bounds['left'] + bounds['right']) // 2
                    cy = (bounds['top'] + bounds['bottom']) // 2
                    self.coords_top[i + 1] = (cx, cy)
            except Exception:
                continue
        logger.info(f"Zona 1 (Topo: 01 a 36): {len(self.coords_top)} botões mapeados.")

        # ========================================================
        # ZONA 2 (MEIO): Dezenas 31 a 60 (drag exato partindo do topo)
        # ========================================================
        self._ir_para_meio(scroll)
        time.sleep(0.3)

        toggles_mid = self.d(resourceId="br.gov.caixa.loterias.apostas:id/tv_item_numero")
        if not toggles_mid.exists or toggles_mid.count < 20:
            toggles_mid = self.d(className="android.widget.ToggleButton")

        cnt_mid = toggles_mid.count
        self.coords_mid = {}
        for i in range(min(30, cnt_mid)):
            num = 31 + i
            if 31 <= num <= 60:
                try:
                    info = toggles_mid[i].info
                    bounds = info.get('visibleBounds') or info.get('bounds')
                    if bounds:
                        cx = (bounds['left'] + bounds['right']) // 2
                        cy = (bounds['top'] + bounds['bottom']) // 2
                        self.coords_mid[num] = (cx, cy)
                except Exception:
                    continue
        logger.info(f"Zona 2 (Meio: 31 a 60): {len(self.coords_mid)} botões mapeados.")

        # ========================================================
        # ZONA 3 (BASE): Dezenas 49 a 80 (final do volante)
        # ========================================================
        self._ir_para_base(scroll)
        time.sleep(0.3)

        toggles_bottom = self.d(resourceId="br.gov.caixa.loterias.apostas:id/tv_item_numero")
        if not toggles_bottom.exists or toggles_bottom.count < 20:
            toggles_bottom = self.d(className="android.widget.ToggleButton")

        self.coords_bottom = {}
        total_bot = toggles_bottom.count
        for offset in range(min(32, total_bot)):
            num = 80 - offset
            idx = (total_bot - 1) - offset
            if 0 <= idx < total_bot:
                try:
                    info = toggles_bottom[idx].info
                    bounds = info.get('visibleBounds') or info.get('bounds')
                    if bounds:
                        cx = (bounds['left'] + bounds['right']) // 2
                        cy = (bounds['top'] + bounds['bottom']) // 2
                        self.coords_bottom[num] = (cx, cy)
                except Exception:
                    continue
        logger.info(f"Zona 3 (Base: 49 a 80): {len(self.coords_bottom)} botões mapeados.")

        logger.info(f"Coordenadas Topo 31..36: {[(i, self.coords_top.get(i)) for i in range(31, 37)]}")
        logger.info(f"Coordenadas Meio 37..42: {[(i, self.coords_mid.get(i)) for i in range(37, 43)]}")
        logger.info(f"Coordenadas Base 75..80: {[(i, self.coords_bottom.get(i)) for i in range(75, 81)]}")

        # Retorna para o topo para iniciar os lançamentos
        self._ir_para_topo(scroll)

    def _capturar_coordenadas_visiveis(self, posicao="top"):
        coords = {}
        # Prioridade 1: ID oficial da Caixa
        toggles = self.d(resourceId="br.gov.caixa.loterias.apostas:id/tv_item_numero")
        if not toggles.exists or toggles.count == 0:
            toggles = self.d(className="android.widget.ToggleButton")
        if not toggles.exists or toggles.count == 0:
            toggles = self.d(className="android.widget.TextView")
        if not toggles.exists or toggles.count == 0:
            toggles = self.d(className="android.widget.Button")

        total_elems = toggles.count
        if total_elems == 0:
            return coords

        # Tenta capturar via texto numérico nos elementos
        for elem in toggles:
            try:
                info = elem.info
                txt = (info.get('text') or info.get('textOn') or info.get('textOff') or info.get('contentDescription') or '').strip()
                if not txt.isdigit():
                    child = elem.child(resourceId="br.gov.caixa.loterias.apostas:id/tv_item_numero")
                    if child.exists:
                        txt = (child.info.get('text') or '').strip()

                if txt.isdigit():
                    num = int(txt)
                    if 1 <= num <= 80:
                        bounds = info.get('visibleBounds') or info.get('bounds')
                        if bounds:
                            cx = (bounds['left'] + bounds['right']) // 2
                            cy = (bounds['top'] + bounds['bottom']) // 2
                            coords[num] = (cx, cy)
            except Exception:
                continue

        # Se conseguiu capturar por texto, retorna
        if len(coords) >= 20:
            return coords

        # FALLBACK SEQUENCIAL (Idêntico ao da Mega-Sena e Lotofácil):
        # Quando o uiautomator não recebe texto numérico nos nós do app da Caixa
        logger.info(f"Texto numérico insuficiente nos elementos ({len(coords)} encontrados). Usando mapeamento por posição sequencial (Total elementos: {total_elems})...")
        coords = {}
        
        # Filtra elementos que sejam botões/toggles da grade
        botoes_grade = self.d(resourceId="br.gov.caixa.loterias.apostas:id/tv_item_numero")
        if not botoes_grade.exists or botoes_grade.count < 20:
            botoes_grade = self.d(className="android.widget.ToggleButton")

        total_grade = botoes_grade.count
        if total_grade == 0:
            return coords

        if posicao == "top":
            # Mapeia os primeiros N botões como dezenas 01 em diante
            qtd = min(total_grade, 45)
            for i in range(qtd):
                try:
                    info = botoes_grade[i].info
                    bounds = info.get('visibleBounds') or info.get('bounds')
                    if bounds:
                        cx = (bounds['left'] + bounds['right']) // 2
                        cy = (bounds['top'] + bounds['bottom']) // 2
                        coords[i + 1] = (cx, cy)
                except Exception:
                    continue
        else:
            # Mapeia os últimos M botões terminando na dezena 80
            qtd = min(total_grade, 45)
            for offset in range(qtd):
                num = 80 - offset
                idx = (total_grade - 1) - offset
                if 0 <= idx < total_grade:
                    try:
                        info = botoes_grade[idx].info
                        bounds = info.get('visibleBounds') or info.get('bounds')
                        if bounds:
                            cx = (bounds['left'] + bounds['right']) // 2
                            cy = (bounds['top'] + bounds['bottom']) // 2
                            coords[num] = (cx, cy)
                    except Exception:
                        continue

        return coords

    def fechar_modal_se_houver(self):
        """Detecta modal 'Entendi' ou botões de confirmação após adicionar ao carrinho."""
        try:
            btn_entendi = self.d(textMatches="(?i)entendi|fechar|ok|confirmar")
            if not btn_entendi.exists:
                btn_entendi = self.d(descriptionMatches="(?i)entendi|fechar|ok|confirmar")
            if btn_entendi.exists(timeout=1.5):
                logger.info("Modal detectado. Clicando em 'Entendi' para dispensar...")
                btn_entendi.click()
                time.sleep(0.8)
                return True
        except Exception as e:
            logger.warning(f"Erro ao tentar fechar modal: {e}")
        return False

    def clicar_dezenas_jogo(self, jogo: list) -> bool:
        scroll = self._get_scroll_volante()

        # Garante a divisão estrita das zonas:
        # Topo: 01 a 36
        # Meio: 37 a 48
        # Base: 49 a 80
        dezenas_top = sorted([n for n in jogo if 1 <= n <= 36 and n in self.coords_top])
        dezenas_mid = sorted([n for n in jogo if 37 <= n <= 48 and n in self.coords_mid])
        dezenas_bottom = sorted([n for n in jogo if 49 <= n <= 80 and n in self.coords_bottom])
        dezenas_outras = sorted([n for n in jogo if n not in dezenas_top and n not in dezenas_mid and n not in dezenas_bottom])

        # ========================================================
        # 1. ZONA 1 (TOPO): Dezenas 01 a 36
        # ========================================================
        if dezenas_top:
            if self.scroll_pos != "top":
                self._ir_para_topo(scroll)

            for d in dezenas_top:
                x, y = self.coords_top[d]
                self.d.click(x, y)
                logger.info(f"Dezena {d:02d} clicada (Topo)")
                time.sleep(0.1)

        # ========================================================
        # 2. ZONA 2 (MEIO): Dezenas 37 a 48
        # ========================================================
        if dezenas_mid:
            if self.scroll_pos != "mid":
                self._ir_para_meio(scroll)

            for d in dezenas_mid:
                x, y = self.coords_mid[d]
                self.d.click(x, y)
                logger.info(f"Dezena {d:02d} clicada (Meio)")
                time.sleep(0.1)

        # ========================================================
        # 3. ZONA 3 (BASE): Dezenas 49 a 80
        # ========================================================
        if dezenas_bottom:
            if self.scroll_pos != "bottom":
                self._ir_para_base(scroll)

            for d in dezenas_bottom:
                x, y = self.coords_bottom[d]
                self.d.click(x, y)
                logger.info(f"Dezena {d:02d} clicada (Base)")
                time.sleep(0.1)

        # 4. Fallback de segurança para qualquer dezena não mapeada
        for d in dezenas_outras:
            self.clicar_numero_fallback(d)
            time.sleep(0.1)

        return True

    def clicar_numero_fallback(self, num: int):
        # Tenta localizar diretamente pelo texto no app da Caixa
        btn = self.d(resourceId="br.gov.caixa.loterias.apostas:id/tv_item_numero", textMatches=f"^0?{num}$")
        if not btn.exists:
            btn = self.d(className="android.widget.ToggleButton", textMatches=f"^0?{num}$")
        if not btn.exists:
            btn = self.d(text=f"{num:02d}")
        if not btn.exists:
            btn = self.d(text=str(num))

        if btn.exists(timeout=1.5):
            btn.click()
            logger.info(f"Dezena {num} clicada via fallback direto.")
        else:
            logger.warning(f"Dezena {num} não pôde ser encontrada na tela.")

    def adicionar_ao_carrinho(self) -> bool:
        btn_adicionar = self.d(resourceId="br.gov.caixa.loterias.apostas:id/btnAdicionarCarrinho")
        if not btn_adicionar.exists:
            btn_adicionar = self.d(text="Adicionar ao carrinho")
        if not btn_adicionar.exists:
            btn_adicionar = self.d(text="Colocar no carrinho")
        if not btn_adicionar.exists:
            btn_adicionar = self.d(resourceId="br.gov.caixa.loterias.apostas:id/botaoAdicionarCompletarCartela")
        if not btn_adicionar.exists:
            btn_adicionar = self.d(textMatches="(?i)adicionar ao carrinho|colocar no carrinho")

        if not btn_adicionar.exists:
            # Rola suavemente para baixo para revelar o botão se necessário
            self.d.swipe_ext("up", scale=0.5)
            time.sleep(0.5)
            self.scroll_pos = "bottom"

            btn_adicionar = self.d(resourceId="br.gov.caixa.loterias.apostas:id/btnAdicionarCarrinho")
            if not btn_adicionar.exists:
                btn_adicionar = self.d(text="Adicionar ao carrinho")
            if not btn_adicionar.exists:
                btn_adicionar = self.d(text="Colocar no carrinho")
            if not btn_adicionar.exists:
                btn_adicionar = self.d(resourceId="br.gov.caixa.loterias.apostas:id/botaoAdicionarCompletarCartela")
            if not btn_adicionar.exists:
                btn_adicionar = self.d(textMatches="(?i)adicionar ao carrinho|colocar no carrinho")

        if btn_adicionar.wait(timeout=4.0):
            time.sleep(0.3)
            try:
                is_enabled = btn_adicionar.info.get('enabled', True)
                if not is_enabled:
                    msg = "Botão 'Adicionar ao carrinho' está DESABILITADO (menos de 5 dezenas marcadas na tela)."
                    logger.warning(msg)
                    self.ultimo_erro = msg
                    return False
            except Exception:
                pass

            qtd_antes = None
            try:
                tv_qtd = self.d(resourceId="br.gov.caixa.loterias.apostas:id/tvQuantidadeApostas")
                if tv_qtd.exists:
                    qtd_antes = tv_qtd.info.get('text', '')
            except Exception:
                pass

            btn_adicionar.click()
            logger.info("Jogo adicionado ao carrinho.")
            time.sleep(1.2)
            self.fechar_modal_se_houver()

            try:
                tv_qtd = self.d(resourceId="br.gov.caixa.loterias.apostas:id/tvQuantidadeApostas")
                if tv_qtd.exists:
                    qtd_depois = tv_qtd.info.get('text', '')
                    logger.info(f"Contador do carrinho: {qtd_antes} -> {qtd_depois}")
            except Exception:
                pass
            return True
        else:
            msg = "Botão 'Adicionar ao carrinho' não encontrado após selecionar as dezenas."
            logger.warning(msg)
            self.ultimo_erro = msg
            return False

    def lancar_jogos(self, jogos: list, on_jogo_adicionado=None) -> bool:
        total = len(jogos)
        logger.info(f"--- INICIANDO FASE 1: LANÇAMENTO DE {total} JOGOS (QUINA) ---")

        if not self.coords_top and not self.coords_bottom:
            self.mapear_botoes_volante()

        scroll = self._get_scroll_volante()

        for idx, jogo in enumerate(jogos, 1):
            logger.info(f"Lançando Jogo {idx}/{total}: {sorted(jogo)}")
            
            # 1. Clica nas dezenas do jogo
            self.clicar_dezenas_jogo(jogo)
            time.sleep(0.4)

            # 2. Adiciona ao carrinho
            sucesso_add = self.adicionar_ao_carrinho()
            if not sucesso_add:
                return False

            if on_jogo_adicionado:
                try:
                    on_jogo_adicionado(idx - 1)
                except Exception as e_cb:
                    logger.warning(f"Erro no callback pós-jogo {idx}: {e_cb}")

            logger.info(f"Jogo {idx} adicionado com sucesso ao carrinho.")
            time.sleep(0.8)

            # 3. Se houver mais jogos, garante que a tela retorne ao topo para o próximo
            if idx < total:
                self._ir_para_topo(scroll)

        logger.info("--- FASE 1 CONCLUÍDA (QUINA) ---")
        return True

    def ir_para_carrinho(self) -> bool:
        logger.info("Navegando para o Carrinho de Apostas da Quina...")
        time.sleep(1)
        self.fechar_modal_se_houver()

        # No app da Caixa, o rodapé com o frameCarrinho fica visível na parte inferior da tela.
        btn_carrinho = self.d(resourceId="br.gov.caixa.loterias.apostas:id/frameCarrinho")
        if not btn_carrinho.exists:
            btn_carrinho = self.d(description="Carrinho de Apostas")
        if not btn_carrinho.exists:
            btn_carrinho = self.d(resourceId="br.gov.caixa.loterias.apostas:id/botaoCarrinho")
        if not btn_carrinho.exists:
            btn_carrinho = self.d(descriptionContains="Carrinho")
        if not btn_carrinho.exists:
            btn_carrinho = self.d(resourceId="br.gov.caixa.loterias.apostas:id/ivCarrinho")
        if not btn_carrinho.exists:
            btn_carrinho = self.d(textMatches="(?i)carrinho")

        if btn_carrinho.exists(timeout=2.5):
            btn_carrinho.click()
            time.sleep(2.5)
            return True

        # Fallback de coordenadas do ícone do carrinho (conforme dump bounds [840,2064][1008,2256])
        try:
            w, h = self.d.window_size()
            logger.info("Tentando clicar nas coordenadas do carrinho (canto inferior direito)...")
            # Clica no centro de [840, 2064] a [1008, 2256] -> (924, 2160)
            self.d.click(int(w * 0.855), int(h * 0.90))
            time.sleep(2.5)
            return True
        except Exception as e:
            logger.warning(f"Fallback de coordenadas falhou: {e}")

        return False

    def ler_jogos_do_carrinho(self, total_esperado: int = None) -> list:
        logger.info("--- INICIANDO FASE 2: LEITURA DO CARRINHO (QUINA) ---")
        jogos_lidos = []

        try:
            xml_carrinho = self.d.dump_hierarchy()
            with open("logs/ui_dump_quina_carrinho.xml", "w", encoding="utf-8") as f:
                f.write(xml_carrinho)
            logger.info("Dump da tela do carrinho salvo em logs/ui_dump_quina_carrinho.xml")
        except Exception:
            pass

        scroll_view = self.d(resourceId="br.gov.caixa.loterias.apostas:id/sv_carrinho")
        if not scroll_view.exists:
            scroll_view = self.d(className="android.widget.ScrollView")

        if scroll_view.exists:
            try:
                scroll_view.scroll.toBeginning(max_swipes=5)
            except Exception:
                pass

        tentativas_sem_novos = 0

        for rolagem in range(25):
            jogos_adicionados_nesta_tela = 0

            # 1. Tenta containers estruturados da Caixa
            containers_aposta = self.d(resourceId="br.gov.caixa.loterias.apostas:id/contentTest")
            if not containers_aposta.exists or containers_aposta.count == 0:
                containers_aposta = self.d(resourceId="br.gov.caixa.loterias.apostas:id/numerosText")

            if containers_aposta.exists and containers_aposta.count > 0:
                for container in containers_aposta:
                    try:
                        if container.info.get('resourceName') == "br.gov.caixa.loterias.apostas:id/numerosText":
                            texto = container.info.get('text', '')
                        else:
                            child = container.child(resourceId="br.gov.caixa.loterias.apostas:id/numerosText")
                            texto = child.info.get('text', '') if child.exists else ""

                        numeros = [int(n) for n in texto.replace('-', ' ').split() if n.isdigit()]
                        if len(numeros) == 5 and all(1 <= n <= 80 for n in numeros):
                            numeros_ord = sorted(numeros)
                            if numeros_ord not in jogos_lidos:
                                jogos_lidos.append(numeros_ord)
                                jogos_adicionados_nesta_tela += 1
                    except Exception:
                        continue
            else:
                # 2. Fallback genérico por TextViews
                for child in self.d(className="android.widget.TextView"):
                    try:
                        texto = child.info.get('text', '')
                        if not texto:
                            continue

                        numeros = [int(n) for n in texto.replace('-', ' ').split() if n.isdigit()]
                        if len(numeros) == 5 and all(1 <= n <= 80 for n in numeros):
                            numeros_ord = sorted(numeros)
                            if numeros_ord not in jogos_lidos:
                                jogos_lidos.append(numeros_ord)
                                jogos_adicionados_nesta_tela += 1
                    except Exception:
                        continue

            if jogos_adicionados_nesta_tela == 0:
                tentativas_sem_novos += 1
                if tentativas_sem_novos >= 3:
                    break
            else:
                tentativas_sem_novos = 0

            self.d.swipe_ext("up", scale=0.45)
            time.sleep(0.9)

        logger.info(f"Total de jogos da Quina lidos no carrinho: {len(jogos_lidos)}")
        return jogos_lidos

    def conciliar(self, jogos_originais: list, jogos_carrinho: list, jogos_anteriores: list = None) -> dict:
        logger.info("--- INICIANDO CONCILIAÇÃO (QUINA) ---")
        originais_ord = [sorted(g) for g in jogos_originais]
        anteriores_ord = [sorted(g) for g in (jogos_anteriores or [])]
        carrinho_ord = [sorted(g) for g in jogos_carrinho]

        logger.info(f"Jogos originais enviados: {originais_ord}")
        if anteriores_ord:
            logger.info(f"Jogos anteriores do fechamento esperados no carrinho: {anteriores_ord}")
        logger.info(f"Jogos encontrados no carrinho: {carrinho_ord}")

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