<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | Lienzo</title>
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
        <a class="button" href="/signup">Crear cuenta</a>
      </div>
    </div>

    <main class="layout">
      <section class="card form-card">
        <h2>Iniciar sesión</h2>
        <p>Accede con tu correo y contraseña para continuar en Lienzo.</p>

        <form id="loginForm" class="control-group">
          <label>
            Correo electrónico
            <input type="email" id="loginEmail" maxlength="50" placeholder="ejemplo@correo.com" required>
          </label>
          <label>
            Contraseña
            <input type="password" id="loginPassword" maxlength="20" placeholder="********" required>
          </label>
          <button class="button" type="submit">Entrar</button>
          <div class="error" id="loginError"></div>
          <div class="success" id="loginSuccess"></div>
        </form>
      </section>

      <footer class="footer">¿Aún no tienes cuenta? <a href="/signup">Regístrate aquí</a>.</footer>
    </main>

    <script src="/js/auth.js"></script>
  </body>
</html>
