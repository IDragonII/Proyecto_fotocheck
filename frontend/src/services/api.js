import { getToken, logout } from './authService';

const API_URL = import.meta.env.VITE_API_URL;

async function request(url, options = {}) {
  const token = getToken();
  const headers = { ...options.headers };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const isFormData = options.body instanceof FormData;
  if (!isFormData) {
    headers['Content-Type'] = 'application/json';
  }

  const res = await fetch(`${API_URL}${url}`, {
    ...options,
    headers,
  });

  if (res.status === 401) {
    logout();
    window.location.href = '/login';
    throw new Error('Sesion expirada');
  }

  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error(data.message || 'Error en la peticion');
  }
  return res.json();
}

export const api = {
  get: (url) => request(url),
  post: (url, body, isFormData = false) =>
    request(url, { method: 'POST', body: isFormData ? body : JSON.stringify(body) }),
  put: (url, body) => request(url, { method: 'PUT', body: JSON.stringify(body) }),
  delete: (url) => request(url, { method: 'DELETE' }),
  download: async (url, filename) => {
    const token = getToken();
    const headers = {};
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }
    const res = await fetch(`${API_URL}${url}`, { headers });
    if (!res.ok) throw new Error('Error al descargar');
    const blob = await res.blob();
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
    URL.revokeObjectURL(link.href);
  },
  getFile: async (url) => {
    const token = getToken();
    const headers = {};
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }
    const res = await fetch(`${API_URL}${url}`, { headers });
    if (!res.ok) throw new Error('Error al cargar archivo');
    const blob = await res.blob();
    const type = res.headers.get('Content-Type') || blob.type;
    return { url: URL.createObjectURL(blob), type };
  },
};

export function proxyImageUrl(url) {
  if (!url) return null;
  const encoded = btoa(url).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
  return `${API_URL}/proxy/image/${encoded}`;
}
