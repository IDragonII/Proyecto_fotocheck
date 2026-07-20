import { useState } from 'react';
import { login } from '../services/authService';
import { FaGraduationCap, FaEye, FaEyeSlash, FaUser, FaLock, FaShieldAlt, FaIdCard, FaQrcode } from 'react-icons/fa';

export default function Login({ onLogin }) {
  const [usuario, setUsuario] = useState('');
  const [clave, setClave] = useState('');
  const [showClave, setShowClave] = useState(false);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const user = await login(usuario, clave);
      onLogin(user);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="login-wrapper">
      <div className="login-branding">
        <div className="login-branding-content">
          <div className="brand-logo">
            <FaGraduationCap size={48} />
          </div>
          <h1 className="brand-title">Sistema de Gestion</h1>
          <p className="brand-subtitle">Plataforma integral de gestion documental</p>
          <p className="brand-institution">Universidad Nacional del Altiplano - Puno</p>

          <div className="brand-features">
            <div className="brand-feature">
              <div className="feature-icon">
                <FaIdCard size={20} />
              </div>
              <div className="feature-text">
                <h3>Gestion Documental</h3>
                <p>Administra credenciales y documentos de forma segura</p>
              </div>
            </div>
            <div className="brand-feature">
              <div className="feature-icon">
                <FaQrcode size={20} />
              </div>
              <div className="feature-text">
                <h3>Verificacion QR</h3>
                <p>Validacion instantanea con codigos QR</p>
              </div>
            </div>
            <div className="brand-feature">
              <div className="feature-icon">
                <FaShieldAlt size={20} />
              </div>
              <div className="feature-text">
                <h3>Seguridad Avanzada</h3>
                <p>Control de acceso y auditoria completa</p>
              </div>
            </div>
          </div>
        </div>

        <div className="brand-decoration">
          <div className="decoration-circle decoration-circle-1"></div>
          <div className="decoration-circle decoration-circle-2"></div>
          <div className="decoration-circle decoration-circle-3"></div>
        </div>
      </div>

      <div className="login-form-container">
        <form className="login-form" onSubmit={handleSubmit}>
          <div className="form-header">
            <h2>Bienvenido</h2>
            <p>Ingrese sus credenciales para acceder</p>
          </div>

          {error && (
            <div className="login-error">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
              </svg>
              <span>{error}</span>
            </div>
          )}

          <div className="form-group">
            <label htmlFor="usuario">Usuario</label>
            <div className="input-wrapper">
              <FaUser className="input-icon" size={16} />
              <input
                id="usuario"
                type="text"
                value={usuario}
                onChange={(e) => setUsuario(e.target.value)}
                placeholder="Ingrese su usuario"
                autoFocus
                autoComplete="username"
              />
            </div>
          </div>

          <div className="form-group">
            <label htmlFor="clave">Contrasena</label>
            <div className="input-wrapper">
              <FaLock className="input-icon" size={16} />
              <input
                id="clave"
                type={showClave ? 'text' : 'password'}
                value={clave}
                onChange={(e) => setClave(e.target.value)}
                placeholder="Ingrese su contrasena"
                autoComplete="current-password"
              />
              <button
                type="button"
                className="toggle-password"
                onClick={() => setShowClave(!showClave)}
                tabIndex={-1}
                aria-label={showClave ? 'Ocultar contrasena' : 'Mostrar contrasena'}
              >
                {showClave ? <FaEyeSlash size={16} /> : <FaEye size={16} />}
              </button>
            </div>
          </div>

          <button type="submit" className="submit-btn" disabled={loading}>
            {loading ? (
              <>
                <span className="spinner"></span>
                <span>Ingresando...</span>
              </>
            ) : (
              <span>Ingresar</span>
            )}
          </button>

          <div className="form-footer">
            <p>Sistema de gestion integral UNA-Puno</p>
          </div>
        </form>
      </div>
    </div>
  );
}
