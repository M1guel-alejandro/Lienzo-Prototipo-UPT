<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear cuenta | Lienzo</title>
    <link rel="stylesheet" href="/css/styles.css">
  </head>
  <body>
    <div class="topbar">
      <a class="brand" href="/">
        <div class="brand-icon">🖼️</div>
        <div class="brand-title">
          <strong>Lienzo</strong>
          <span>Tu estudio gráfico en línea</span>
        </div>
      </a>
      <div class="topbar-actions">
        <a class="button-alt" href="/login">Iniciar sesión</a>
      </div>
    </div>

    <main class="layout">
      <section class="card form-card">
        <h2>Crear cuenta</h2>
        <p>Regístrate para usar Lienzo y guardar tus proyectos en el futuro.</p>

        <form id="signupForm" class="control-group">
          <label>
            Nombre completo
            <input type="text" id="signupName" maxlength="50" placeholder="Tu nombre completo" required>
          </label>
          <label>
            Correo electrónico
            <input type="email" id="signupEmail" maxlength="50" placeholder="ejemplo@correo.com" required>
          </label>
          <label>
            Contraseña
            <input type="password" id="signupPassword" maxlength="20" placeholder="********" required>
          </label>
          <label>
            Confirmar contraseña
            <input type="password" id="signupConfirm" maxlength="20" placeholder="********" required>
          </label>
          <button class="button" type="submit">Crear cuenta</button>
          <div class="error" id="signupError"></div>
          <div class="success" id="signupSuccess"></div>
        </form>
      </section>

      <footer class="footer">¿Ya tienes cuenta? <a href="/login">Inicia sesión aquí</a>.</footer>
    </main>

    <script src="/js/auth.js"></script>
  </body>
</html>
