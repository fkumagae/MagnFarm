<?php
// footer partial
?>
    <footer>
      <small>© 2025 Magalface — UNESP Sorocaba</small>
      <section class="contato">
        <h3>Contato e dúvidas</h3>
        <div class="form-retangulo">
          <form id="form-contato" method="post" action="contact.php" autocomplete="on">
            <?php echo csrf_field(); ?>
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" required>
            <br>
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" required>
            <br>
            <label for="mensagem">Mensagem ou dúvida:</label>
            <textarea id="mensagem" name="mensagem" rows="4" required></textarea>
            <br>
            <button type="submit">Enviar</button>
            <div id="msg-erro" style="color: #c00; margin-top: 0.5em; display: none;"></div>
            <div id="msg-sucesso" style="color: #090; margin-top: 0.5em; display: none;"></div>
          </form>
        </div>
      </section>
    </footer>
    <script src="js/script.js"></script>
  </body>
</html>
