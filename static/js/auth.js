const loginForm = document.getElementById('loginForm');
const loginEmail = document.getElementById('loginEmail');
const loginPassword = document.getElementById('loginPassword');
const loginError = document.getElementById('loginError');
const loginSuccess = document.getElementById('loginSuccess');

const signupForm = document.getElementById('signupForm');
const signupName = document.getElementById('signupName');
const signupEmail = document.getElementById('signupEmail');
const signupPassword = document.getElementById('signupPassword');
const signupConfirm = document.getElementById('signupConfirm');
const signupError = document.getElementById('signupError');
const signupSuccess = document.getElementById('signupSuccess');

// Si tu backend está en otro dominio (Aiven), configura `window.API_BASE` en el HTML
// por ejemplo: <script>window.API_BASE = 'https://api.tu-dominio.com';</script>
const API_BASE = window.API_BASE || '';

function validateEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function validatePassword(password) {
  if (password.length < 8) {
    return "La contraseña debe tener al menos 8 caracteres.";
  }
  if (!/[A-Z]/.test(password)) {
    return "La contraseña debe contener al menos una letra mayúscula.";
  }
  if (!/[a-z]/.test(password)) {
    return "La contraseña debe contener al menos una letra minúscula.";
  }
  if (!/\d/.test(password)) {
    return "La contraseña debe contener al menos un número.";
  }
  return "";
}

async function postJson(url, payload) {
  const fullUrl = url.match(/^https?:\/\//) ? url : (API_BASE.replace(/\/$/, '') + url);
  const response = await fetch(fullUrl, {
    method: 'POST',
    mode: 'cors',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });

  const data = await response.json().catch(() => ({ error: 'Error de comunicación' }));
  if (!response.ok) {
    throw new Error(data.error || 'Error en la solicitud');
  }
  return data;
}

document.addEventListener('DOMContentLoaded', () => {
  const loginFormEl = document.getElementById('loginForm');
  const loginEmailEl = document.getElementById('loginEmail');
  const loginPasswordEl = document.getElementById('loginPassword');
  const loginErrorEl = document.getElementById('loginError');
  const loginSuccessEl = document.getElementById('loginSuccess');

  if (loginFormEl) {
    loginFormEl.addEventListener('submit', async (event) => {
      event.preventDefault();
      loginErrorEl.textContent = '';
      loginSuccessEl.textContent = '';

      const email = loginEmailEl.value.trim();
      const password = loginPasswordEl.value.trim();

      if (!email || !password) {
        loginErrorEl.textContent = 'Completa todos los campos para iniciar sesión.';
        return;
      }
      if (email.length > 50 || password.length > 20) {
        loginErrorEl.textContent = 'Los campos no pueden exceder el límite de caracteres.';
        return;
      }
      if (!validateEmail(email)) {
        loginErrorEl.textContent = 'Ingresa un correo válido.';
        return;
      }

      try {
        const data = await postJson('/api/auth/login', { email, password });
        loginSuccessEl.textContent = data.message;
        window.location.href = '/';
      } catch (error) {
        loginErrorEl.textContent = error.message;
      }
    });
  }

  const signupFormEl = document.getElementById('signupForm');
  const signupNameEl = document.getElementById('signupName');
  const signupEmailEl = document.getElementById('signupEmail');
  const signupPasswordEl = document.getElementById('signupPassword');
  const signupConfirmEl = document.getElementById('signupConfirm');
  const signupErrorEl = document.getElementById('signupError');
  const signupSuccessEl = document.getElementById('signupSuccess');

  if (signupFormEl) {
    signupFormEl.addEventListener('submit', async (event) => {
      event.preventDefault();
      signupErrorEl.textContent = '';
      signupSuccessEl.textContent = '';

      const name = signupNameEl.value.trim();
      const email = signupEmailEl.value.trim();
      const password = signupPasswordEl.value.trim();
      const confirm = signupConfirmEl.value.trim();

      if (!name || !email || !password || !confirm) {
        signupErrorEl.textContent = 'Llena todos los campos para crear la cuenta.';
        return;
      }
      if (name.length > 50 || email.length > 50 || password.length > 20 || confirm.length > 20) {
        signupErrorEl.textContent = 'Los campos exceden el máximo de caracteres permitidos.';
        return;
      }
      if (!validateEmail(email)) {
        signupErrorEl.textContent = 'Ingresa un correo válido.';
        return;
      }
      const passwordError = validatePassword(password);
      if (passwordError) {
        signupErrorEl.textContent = passwordError;
        return;
      }
      if (password !== confirm) {
        signupErrorEl.textContent = 'Las contraseñas no coinciden.';
        return;
      }

      try {
        const data = await postJson('/api/auth/signup', { name, email, password, confirm });
        signupSuccessEl.textContent = data.message;
        window.location.href = '/login';
      } catch (error) {
        signupErrorEl.textContent = error.message;
      }
    });
  }
});
