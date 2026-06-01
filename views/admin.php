<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Lienzo</title>
    <link rel="stylesheet" href="/css/styles.css">
  </head>
  <body>
    <div class="topbar">
      <a class="brand" href="/">
        <div class="brand-icon">🖼️</div>
        <div class="brand-title">
          <strong>Admin</strong>
          <span>Panel de administración</span>
        </div>
      </a>
      <div class="topbar-actions" id="topbarActions"></div>
    </div>

    <main class="layout">
      <section class="view active">
        <h1>Panel de administración</h1>
        <div class="grid-2">
          <div>
            <h2>Sesiones activas</h2>
            <div id="sessionsList"></div>
          </div>

          <div>
            <h2>Usuarios registrados</h2>
            <div id="usersList"></div>
          </div>
        </div>

        <section class="card" id="selectedUserSection" style="display:none; margin-top: 1rem;">
          <h2>Detalle del usuario</h2>
          <div id="selectedUserInfo"></div>
          <div class="form-row" style="margin-top:1rem; gap:1rem; align-items:center;">
            <label style="flex:1;">
              Créditos disponibles
              <input type="number" id="userCreditsInput" min="0" style="width:100%;" />
            </label>
            <button class="button" id="saveUserCredits">Actualizar créditos</button>
          </div>
          <h3>Historial</h3>
          <div id="selectedUserHistory"></div>
        </section>
      </section>
    </main>

    <script>
      async function renderSessions(sessions) {
        const container = document.getElementById('sessionsList');
        container.innerHTML = Object.keys(sessions).length
          ? Object.entries(sessions).map(([id, item]) => `<div class="card"><strong>${item.email}</strong> — ${item.login_time} — <small>${id}</small></div>`).join('')
          : '<p>No hay sesiones activas.</p>';
      }

      async function renderUsers(users) {
        const container = document.getElementById('usersList');
        if (!Object.keys(users).length) {
          container.innerHTML = '<p>No hay usuarios registrados.</p>';
          return;
        }

        container.innerHTML = Object.entries(users).map(([email, user]) => {
          return `<div class="card"><strong>${user.name}</strong><div>${email}</div><div>Rol: ${user.role} · Créditos: ${user.credits} · Usos hoy: ${user.daily_count}</div><button class="button-alt" data-user="${email}">Ver usuario</button></div>`;
        }).join('');

        container.querySelectorAll('button[data-user]').forEach((btn) => {
          btn.addEventListener('click', () => loadUserDetail(btn.dataset.user));
        });
      }

      async function loadUserDetail(email) {
        const resp = await fetch(`/api/admin/users/${encodeURIComponent(email)}`);
        const data = await resp.json();
        if (resp.status !== 200) {
          alert(data.error || 'No se pudo cargar el usuario.');
          return;
        }

        document.getElementById('selectedUserSection').style.display = 'block';
        document.getElementById('selectedUserInfo').innerHTML = `
          <p><strong>${data.name}</strong> (${data.email})</p>
          <p>Rol: ${data.role}</p>
          <p>Usos hoy: ${data.daily_count} · Fecha: ${data.daily_count_date || 'N/A'}</p>
        `;
        document.getElementById('userCreditsInput').value = data.credits;

        const historyElement = document.getElementById('selectedUserHistory');
        historyElement.innerHTML = data.history.length
          ? data.history.map(item => `<div class="card"><a href="${item.result_url}" target="_blank">${item.result_url}</a><div class="muted">${item.timestamp}</div></div>`).join('')
          : '<p>Sin historial.</p>';

        document.getElementById('saveUserCredits').onclick = async () => {
          const credits = Number(document.getElementById('userCreditsInput').value);
          const updateResp = await fetch('/api/admin/users/update', {
            method: 'PATCH',
            headers: {'Content-Type': 'application/json', 'X-HTTP-Method-Override': 'PATCH'},
            body: JSON.stringify({email: data.email, credits}),
          });
          const updateData = await updateResp.json();
          if (!updateResp.ok) {
            alert(updateData.error || 'No se pudo actualizar créditos.');
            return;
          }
          alert('Créditos actualizados.');
          await loadAdminData();
        };
      }

      async function loadAdminData() {
        const sresp = await fetch('/api/admin/sessions');
        const sdata = await sresp.json();
        await renderSessions(sdata.sessions || {});

        const uresp = await fetch('/api/admin/users');
        const udata = await uresp.json();
        if (uresp.ok) {
          await renderUsers(udata.users || {});
        }
      }

      async function initAdmin() {
        try {
          const resp = await fetch('/api/auth/current_user');
          const me = await resp.json();
          if (!me.logged_in || me.role !== 'admin') {
            alert('Acceso no autorizado');
            window.location.href = '/';
            return;
          }

          document.getElementById('topbarActions').innerHTML = `<span>Hola, ${me.name}</span> <button class="button-alt" id="logoutBtn">Cerrar sesión</button>`;
          document.getElementById('logoutBtn').addEventListener('click', async () => { await fetch('/api/auth/logout', { method: 'POST' }); location.href = '/'; });

          await loadAdminData();
        } catch (err) {
          console.error(err);
          alert('Error cargando admin.');
          window.location.href = '/';
        }
      }
      initAdmin();
    </script>
  </body>
</html>
