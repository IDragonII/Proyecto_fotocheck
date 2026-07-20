const API_URL = import.meta.env.VITE_API_URL;

export async function login(usuario, clave) {
  const res = await fetch(`${API_URL}/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ usuario, clave }),
  });

  const data = await res.json();

  if (!res.ok) {
    throw new Error(data.message || 'Error al iniciar sesion');
  }

  const session = {
    usuario: data.usuario,
    token: data.token,
  };
  localStorage.setItem('usuario', JSON.stringify(session));
  return data.usuario;
}

export function getUsuario() {
  const raw = localStorage.getItem('usuario');
  if (!raw) return null;
  const session = JSON.parse(raw);
  return session.usuario || session;
}

export function hasPermission(permission) {
  const usuario = getUsuario();
  if (!usuario) return false;
  if (isSuperAdmin()) return true;
  if (!usuario.permisos) return false;
  return usuario.permisos.includes(permission);
}

export function isSuperAdmin() {
  const usuario = getUsuario();
  if (!usuario?.roles) return false;
  return Object.values(usuario.roles).includes('SUPER_ADMIN');
}

export function getToken() {
  const raw = localStorage.getItem('usuario');
  if (!raw) return null;
  const session = JSON.parse(raw);
  return session.token || null;
}

export function logout() {
  const token = getToken();
  if (token) {
    fetch(`${API_URL}/logout`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
    }).catch(() => {});
  }
  localStorage.removeItem('usuario');
}

export function isSessionExpired() {
  // Token expiry is enforced server-side by Sanctum;
  // client-side we only check if user data exists.
  return !getUsuario();
}
