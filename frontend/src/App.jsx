import { useState, useEffect } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { ToastProvider } from './components/Toast';
import Login from './pages/Login';
import Layout from './pages/Layout';
import Dashboard from './pages/Dashboard';
import Estudiantes from './pages/Estudiantes';
import Trabajadores from './pages/Trabajadores';
import Fotochecks from './pages/Fotochecks';
import AccesosQr from './pages/AccesosQr';
import Usuarios from './pages/Usuarios';
import Roles from './pages/Roles';
import Logs from './pages/Logs';
import ClavesApi from './pages/ClavesApi';
import Oficinas from './pages/Oficinas';
import TipoSolicitudes from './pages/TipoSolicitudes';
import Solicitudes from './pages/Solicitudes';
import PhotocheckViewer from './pages/PhotocheckViewer';
import { getUsuario, logout, isSessionExpired, hasPermission } from './services/authService';
import './App.css';

function ProtectedRoute({ children, permission }) {
  const usuario = getUsuario();
  if (!usuario) return <Navigate to="/login" />;
  if (permission && !hasPermission(permission)) return <Navigate to="/" />;
  return children;
}

function App() {
  const [usuario, setUsuario] = useState(() => getUsuario());

  useEffect(() => {
    const interval = setInterval(() => {
      if (isSessionExpired()) {
        logout();
        setUsuario(null);
      }
    }, 60000);
    return () => clearInterval(interval);
  }, []);

  return (
    <BrowserRouter>
      <ToastProvider>
        <Routes>
        <Route path="/:codigo" element={<PhotocheckViewer />} />
        <Route path="/login" element={usuario ? <Navigate to="/" /> : <Login onLogin={setUsuario} />} />
        <Route path="/" element={<ProtectedRoute><Layout onLogout={() => { logout(); setUsuario(null); }} /></ProtectedRoute>}>
          <Route index element={<ProtectedRoute permission="dashboard_ver"><Dashboard /></ProtectedRoute>} />
          <Route path="trabajadores" element={<ProtectedRoute permission="trabajadores_ver"><Trabajadores /></ProtectedRoute>} />
          <Route path="estudiantes" element={<ProtectedRoute permission="estudiantes_ver"><Estudiantes /></ProtectedRoute>} />
          <Route path="fotochecks" element={<ProtectedRoute permission="fotochecks_ver"><Fotochecks /></ProtectedRoute>} />
          <Route path="accesos-qr" element={<ProtectedRoute permission="fotochecks_ver"><AccesosQr /></ProtectedRoute>} />
          <Route path="usuarios" element={<ProtectedRoute permission="usuarios_ver"><Usuarios /></ProtectedRoute>} />
          <Route path="roles" element={<ProtectedRoute permission="roles_ver"><Roles /></ProtectedRoute>} />
          <Route path="api-keys" element={<ProtectedRoute permission="api_keys_ver"><ClavesApi /></ProtectedRoute>} />
          <Route path="oficinas" element={<ProtectedRoute permission="oficinas_ver"><Oficinas /></ProtectedRoute>} />
          <Route path="tipo-solicitudes" element={<ProtectedRoute permission="tipo_solicitudes_ver"><TipoSolicitudes /></ProtectedRoute>} />
          <Route path="solicitudes" element={<ProtectedRoute permission="solicitudes_ver"><Solicitudes /></ProtectedRoute>} />
          <Route path="logs" element={<ProtectedRoute permission="logs_ver"><Logs /></ProtectedRoute>} />
        </Route>
        <Route path="*" element={<Navigate to="/" />} />
        </Routes>
      </ToastProvider>
    </BrowserRouter>
  );
}

export default App;
