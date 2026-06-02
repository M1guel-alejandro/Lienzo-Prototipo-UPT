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
  const response = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
    body: JSON.stringify(payload),
  });

  const data = await response.json().catch(() => ({ error: 'Error de comunicación' }));
  if (!response.ok) {
    throw new Error(data.error || 'Error en la solicitud');
  }
  return data;
}

if (loginForm) {
  loginForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    loginError.textContent = '';
    loginSuccess.textContent = '';

    const email = loginEmail.value.trim();
    const password = loginPassword.value.trim();

    if (!email || !password) {
      loginError.textContent = 'Completa todos los campos para iniciar sesión.';
      return;
    }
    if (email.length > 50 || password.length > 20) {
      loginError.textContent = 'Los campos no pueden exceder el límite de caracteres.';
      return;
    }
    if (!validateEmail(email)) {
      loginError.textContent = 'Ingresa un correo válido.';
      return;
    }

    try {
      const data = await postJson('/api/auth/login', { email, password });
      loginSuccess.textContent = data.message;
        window.location.href = '/dashboard';
    } catch (error) {
      loginError.textContent = error.message;
    }
  });
}

if (signupForm) {
  signupForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    signupError.textContent = '';
    signupSuccess.textContent = '';

    const name = signupName.value.trim();
    const email = signupEmail.value.trim();
    const password = signupPassword.value.trim();
    const confirm = signupConfirm.value.trim();

    if (!name || !email || !password || !confirm) {
      signupError.textContent = 'Llena todos los campos para crear la cuenta.';
      return;
    }
    if (name.length > 50 || email.length > 50 || password.length > 20 || confirm.length > 20) {
      signupError.textContent = 'Los campos exceden el máximo de caracteres permitidos.';
      return;
    }
    if (!validateEmail(email)) {
      signupError.textContent = 'Ingresa un correo válido.';
      return;
    }
    const passwordError = validatePassword(password);
    if (passwordError) {
      signupError.textContent = passwordError;
      return;
    }
    if (password !== confirm) {
      signupError.textContent = 'Las contraseñas no coinciden.';
      return;
    }

    try {
      const data = await postJson('/api/auth/signup', { name, email, password, confirm });
      signupSuccess.textContent = data.message;
      window.location.href = '/login';
    } catch (error) {
      signupError.textContent = error.message;
    }
  });
}
