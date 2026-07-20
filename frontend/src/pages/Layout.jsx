import { NavLink, Outlet, useNavigate, useLocation } from 'react-router-dom';
import { getUsuario, hasPermission } from '../services/authService';
import { FaHome, FaUsers, FaIdCard, FaUserShield, FaClipboardList, FaTicketAlt, FaSignOutAlt, FaBars, FaTimes, FaChevronDown } from 'react-icons/fa';
import { useState } from 'react';
import './Layout.css';

const navStructure = [
  { type: 'link', to: '/', icon: <FaHome />, label: 'Dashboard', permission: 'dashboard_ver' },
  {
    type: 'category', label: 'Personas', icon: <FaUsers />,
    children: [
      { to: '/trabajadores', label: 'Trabajadores', permission: 'trabajadores_ver' },
      { to: '/estudiantes', label: 'Estudiantes', permission: 'estudiantes_ver' },
    ],
  },
  {
    type: 'category', label: 'Credenciales', icon: <FaIdCard />,
    children: [
      { to: '/fotochecks', label: 'Credenciales', permission: 'fotochecks_ver' },
      { to: '/accesos-qr', label: 'Accesos QR', permission: 'fotochecks_ver' },
    ],
  },
  {
    type: 'category', label: 'Solicitudes', icon: <FaTicketAlt />,
    children: [
      { to: '/solicitudes', label: 'Tickets', permission: 'solicitudes_ver' },
      { to: '/tipo-solicitudes', label: 'Tipos de Ticket', permission: 'tipo_solicitudes_ver' },
    ],
  },
  {
    type: 'category', label: 'Administracion', icon: <FaUserShield />,
    children: [
      { to: '/usuarios', label: 'Usuarios', permission: 'usuarios_ver' },
      { to: '/roles', label: 'Roles', permission: 'roles_ver' },
      { to: '/oficinas', label: 'Oficinas', permission: 'oficinas_ver' },
      { to: '/api-keys', label: 'Claves API', permission: 'api_keys_ver' },
    ],
  },
  { type: 'link', to: '/logs', icon: <FaClipboardList />, label: 'Logs', permission: 'logs_ver' },
];

export default function Layout({ onLogout }) {
  const usuario = getUsuario();
  const navigate = useNavigate();
  const location = useLocation();
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [openCategories, setOpenCategories] = useState(() => {
    const initial = {};
    navStructure.forEach((item) => {
      if (item.type === 'category') {
        initial[item.label] = false;
      }
    });
    return initial;
  });

  const toggleCategory = (label) => {
    setOpenCategories((prev) => ({ ...prev, [label]: !prev[label] }));
  };

  const handleLogout = () => {
    onLogout();
    navigate('/login');
  };

  const getIniciales = () => {
    if (!usuario) return '?';
    const n = usuario.nombres?.[0] || '';
    const a = usuario.apellidos?.[0] || '';
    return (n + a).toUpperCase();
  };

  const getRol = () => {
    if (!usuario?.roles) return '';
    const roles = Object.values(usuario.roles);
    return roles[0] || '';
  };

  const isActiveCategory = (children) => {
    return children.some((child) => location.pathname === child.to);
  };

  const filteredNav = navStructure.filter((item) => {
    if (item.type === 'link') return hasPermission(item.permission);
    const visibleChildren = item.children.filter((c) => hasPermission(c.permission));
    return visibleChildren.length > 0;
  });

  return (
    <div className="layout">
      <button className="sidebar-toggle" onClick={() => setSidebarOpen(!sidebarOpen)}>
        {sidebarOpen ? <FaTimes /> : <FaBars />}
      </button>

      <aside className={`sidebar ${sidebarOpen ? 'open' : ''}`}>
        <div className="sidebar-header">
          <div className="user-avatar">{getIniciales()}</div>
          <div className="user-meta">
            <span className="user-name">{usuario?.nombres} {usuario?.apellidos}</span>
            <span className="user-role">{getRol()}</span>
          </div>
        </div>
        <nav className="sidebar-nav">
          {filteredNav.map((item) => {
            if (item.type === 'link') {
              return (
                <NavLink
                  key={item.to}
                  to={item.to}
                  end={item.to === '/'}
                  className={({ isActive }) => `nav-link ${isActive ? 'active' : ''}`}
                  onClick={() => setSidebarOpen(false)}
                >
                  {item.icon}
                  <span>{item.label}</span>
                </NavLink>
              );
            }

            const visibleChildren = item.children.filter((c) => hasPermission(c.permission));
            const isOpen = openCategories[item.label] || isActiveCategory(visibleChildren);

            return (
              <div key={item.label} className={`nav-category ${isOpen ? 'open' : ''}`}>
                <button className="nav-link nav-category-btn" onClick={() => toggleCategory(item.label)}>
                  {item.icon}
                  <span>{item.label}</span>
                  <FaChevronDown className={`nav-chevron ${isOpen ? 'rotated' : ''}`} />
                </button>
                {isOpen && (
                  <div className="nav-subitems">
                    {visibleChildren.map((child) => (
                      <NavLink
                        key={child.to}
                        to={child.to}
                        className={({ isActive }) => `nav-link nav-sublink ${isActive ? 'active' : ''}`}
                        onClick={() => setSidebarOpen(false)}
                      >
                        <span>{child.label}</span>
                      </NavLink>
                    ))}
                  </div>
                )}
              </div>
            );
          })}
        </nav>
        <div className="sidebar-footer">
          <button className="logout-btn" onClick={handleLogout}>
            <FaSignOutAlt />
            <span>Cerrar Sesion</span>
          </button>
        </div>
      </aside>

      {sidebarOpen && <div className="sidebar-overlay" onClick={() => setSidebarOpen(false)} />}

      <main className="main-content">
        <header className="topbar">
          <span className="topbar-title">Sistema de Gestion</span>
        </header>
        <div className="page-content">
          <Outlet />
        </div>
      </main>
    </div>
  );
}
