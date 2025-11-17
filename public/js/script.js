document.addEventListener('DOMContentLoaded', () => {
  console.log('Magalface script.js carregado em', location.pathname);

  const menu = document.getElementById('site-menu');
  const btn  = document.querySelector('.menu-toggle');

  if (!menu || !btn) {
    console.warn('menu-toggle ou #site-menu não encontrados nesta página. Algumas funcionalidades do script serão ignoradas.');
  } else {
    // Abrir/fechar pelo botão (hamburguer)
    btn.addEventListener('click', () => {
      const aberto = menu.classList.toggle('aberto');     // alterna classe
      btn.setAttribute('aria-expanded', aberto ? 'true' : 'false');
      btn.setAttribute('aria-label', aberto ? 'Fechar menu' : 'Abrir menu');
      console.log('[Magalface] Botão menu-toggle clicado. Menu está', aberto ? 'aberto' : 'fechado');
    });

    // Fechar ao clicar num link do menu
    menu.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => {
        if (menu.classList.contains('aberto')) {
          menu.classList.remove('aberto');
          btn.setAttribute('aria-expanded', 'false');
          btn.setAttribute('aria-label', 'Abrir menu');
        }
      });
    });

    // Fechar com tecla ESC
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && menu.classList.contains('aberto')) {
        menu.classList.remove('aberto');
        btn.setAttribute('aria-expanded', 'false');
        btn.setAttribute('aria-label', 'Abrir menu');
        btn.focus(); // acessibilidade
      }
    });
  }

  // Title navigation removed (title is plain H1 now)

  // Handle contact form submission via AJAX
  const contatoForm = document.getElementById('form-contato');
  if (contatoForm) {
    contatoForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = contatoForm.querySelector('button[type="submit"]');
      const msgErro = document.getElementById('msg-erro');
      const msgSucesso = document.getElementById('msg-sucesso');
      if (msgErro) msgErro.style.display = 'none';
      if (msgSucesso) msgSucesso.style.display = 'none';

      const formData = new FormData(contatoForm);
      try {
        if (btn) btn.disabled = true;
        const res = await fetch(contatoForm.action || 'contact.php', {
          method: 'POST',
          body: formData,
          // use 'include' to ensure cookies are sent in more hosting setups
          // (safe for local dev and necessary if the host includes ports or
          // different hostnames). If your deployment is strictly same-origin
          // you can keep 'same-origin'.
          credentials: 'include',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (!res.ok) {
          const err = data?.errors ? data.errors.join('; ') : (data?.error || 'Erro desconhecido');
          if (msgErro) { msgErro.textContent = err; msgErro.style.display = 'block'; }
        } else {
          if (msgSucesso) { msgSucesso.textContent = data.message || 'Mensagem enviada com sucesso.'; msgSucesso.style.display = 'block'; }
          // reset form fields
          contatoForm.reset();
        }
      } catch (err) {
        if (msgErro) { msgErro.textContent = 'Erro de rede. Tente novamente.'; msgErro.style.display = 'block'; }
      } finally {
        if (btn) btn.disabled = false;
      }
    });
  }
});
