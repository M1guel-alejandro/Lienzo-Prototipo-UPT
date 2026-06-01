<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Historial - Lienzo</title>
    <link rel="stylesheet" href="/css/styles.css">
  </head>
  <body>
    <div class="topbar">
      <a class="brand" href="/">
        <div class="brand-icon">🖼️</div>
        <div class="brand-title">
          <strong>Historial</strong>
          <span>Tu historial de imágenes procesadas</span>
        </div>
      </a>
      <div class="topbar-actions" id="topbarActions"></div>
    </div>

    <main class="layout">
      <section class="view active">
        <h1>Historial de imágenes</h1>
        <div id="historyList"></div>
      </section>
    </main>

    <script>
      async function initHistory() {
        try {
          const resp = await fetch('/api/auth/current_user');
          const me = await resp.json();
          if (!me.logged_in) {
            window.location.href = '/signup';
            return;
          }
          document.getElementById('topbarActions').innerHTML = `<span>Hola, ${me.name}</span> <button class="button-alt" id="logoutBtn">Cerrar sesión</button> <a class="button-alt" href="/">Volver</a>`;
          document.getElementById('logoutBtn').addEventListener('click', async () => { await fetch('/api/auth/logout', { method: 'POST' }); location.href = '/'; });

          const hresp = await fetch('/api/images/history');
          const hdata = await hresp.json();
          if (hresp.status !== 200) {
            document.getElementById('historyList').textContent = hdata.error || 'No hay historial.';
            return;
          }
          const list = hdata.history || [];
          document.getElementById('historyList').innerHTML = list.length ? list.map(item => `<div class="card"><a href="${item.result_url}" target="_blank">${item.result_url}</a><div class="muted">${item.timestamp}</div></div>`).join('') : '<p>Aún no has procesado imágenes.</p>';
        } catch (err) {
          console.error(err);
          document.getElementById('historyList').textContent = 'Error cargando historial.';
        }
      }
      initHistory();
    </script>
  </body>
</html>
