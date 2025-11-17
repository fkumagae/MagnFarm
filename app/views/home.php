<main>
  <div class="bem-vindo-barra">
    <h2><?php echo function_exists('t') ? t('page.home.title') : 'Bem-vindo ao Magalface'; ?></h2>
  </div>

  <section class="quem-somos">
    <h3><?php echo function_exists('t') ? t('page.home.quemSomos') : 'Quem somos nós'; ?></h3>
    <p><?php echo function_exists('t') ? t('page.home.quemSomos.p1') : 'Somos estudantes da UNESP Sorocaba apaixonados por tecnologia, inovação e sustentabilidade...'; ?></p>
    <p><?php echo function_exists('t') ? t('page.home.quemSomos.p2') : 'O Magalface nasceu da vontade de aplicar conceitos de IoT, Inteligência Artificial e automação em projetos reais...'; ?></p>
  </section>

  <div class="mídia-centralizada">
    <img
      src="../img/homemAlface.png"
      alt="Homem agachado segurando notebook e olhando para alfaces no campo, indicando dados de sensores analisados com IA"
      style="max-width: 460px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: block; margin: 0 auto;"
    >
  </div>

  <div class="apresentacao">
    <p><?php echo function_exists('t') ? t('page.home.apresentacao.p1') : 'Magalface é um projeto da área de IoT...'; ?></p>
    <p><?php echo function_exists('t') ? t('page.home.apresentacao.p2') : 'O projeto envolve diversas etapas, incluindo o planejamento de sensores...'; ?></p>
    <p><?php echo function_exists('t') ? t('page.home.apresentacao.p3') : 'Além disso, o Magalface busca promover a sustentabilidade e a eficiência no campo...'; ?></p>
    <p><?php echo function_exists('t') ? t('page.home.apresentacao.p4') : 'Com o avanço das pesquisas, pretendemos expandir o escopo do Magalface...'; ?></p>
  </div>
</main>
