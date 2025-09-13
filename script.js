document.addEventListener('DOMContentLoaded', () => {
  console.log('Magalface script.js carregado em', location.pathname);

  const menu = document.getElementById('site-menu');
  const btn  = document.querySelector('.menu-toggle');

  if (!menu || !btn) {
    console.warn('menu-toggle ou #site-menu não encontrados nesta página.');
    return;
  }

  // Abrir/fechar pelo botão (hamburguer)
  btn.addEventListener('click', () => {
    const aberto = menu.classList.toggle('is-open');     // alterna classe
    btn.setAttribute('aria-expanded', aberto ? 'true' : 'false');
    btn.setAttribute('aria-label', aberto ? 'Fechar menu' : 'Abrir menu');
    console.log('[Magalface] Botão menu-toggle clicado. Menu está', aberto ? 'aberto' : 'fechado');
  });

  // Fechar ao clicar num link do menu
  menu.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => {
      if (menu.classList.contains('is-open')) {
        menu.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
        btn.setAttribute('aria-label', 'Abrir menu');
      }
    });
  });

  // Fechar com tecla ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && menu.classList.contains('is-open')) {
      menu.classList.remove('is-open');
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-label', 'Abrir menu');
      btn.focus(); // acessibilidade
    }
  });
});
