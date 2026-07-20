import { useEffect, useState } from 'react';
import { api } from '../services/api';
import { hasPermission, isSuperAdmin, getUsuario } from '../services/authService';
import { useToast } from '../components/Toast';
import { FaPlus, FaEdit, FaTrash, FaUnlock } from 'react-icons/fa';
import './CrudPage.css';

const initial = { usuario: '', clave: '', nombres: '', apellidos: '', estado: 'ACTIVO', oficina_id: '', roles: [], permisos_extras: [], permisos_negados: [] };

export default function Usuarios() {
  const { toast, confirm } = useToast();
  const [items, setItems] = useState([]);
  const [roles, setRoles] = useState([]);
  const [permisos, setPermisos] = useState([]);
  const [oficinas, setOficinas] = useState([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [buscar, setBuscar] = useState('');
  const [form, setForm] = useState(initial);
  const [editing, setEditing] = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [error, setError] = useState('');

  const canCreate = hasPermission('usuarios_crear');
  const canEdit = hasPermission('usuarios_editar');
  const canDelete = hasPermission('usuarios_eliminar');
  const currentUser = getUsuario();
  const userIsSuperAdmin = isSuperAdmin();

  const load = (p = 1) => {
    api.get(`/usuarios?page=${p}&buscar=${buscar}`).then((res) => {
      setItems(res.data);
      setPage(res.current_page);
      setLastPage(res.last_page);
    });
  };

  useEffect(() => {
    load();
    api.get('/roles').then(setRoles);
    api.get('/permisos').then(setPermisos);
    api.get('/oficinas?buscar=').then((res) => setOficinas(res.data || res));
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const handleSearch = (e) => { e.preventDefault(); load(); };

  const openNew = () => { setForm({ ...initial, roles: [], permisos_extras: [], permisos_negados: [] }); setEditing(null); setShowModal(true); setError(''); };
  const openEdit = (item) => {
    setForm({
      ...item,
      clave: '',
      oficina_id: item.oficina_id || '',
      roles: item.roles?.map((r) => r.id) || [],
      permisos_extras: item.permisos_extras?.map((p) => p.id) || [],
      permisos_negados: item.permisos_negados?.map((p) => p.id) || [],
    });
    setEditing(item.id);
    setShowModal(true);
    setError('');
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setError('');
    try {
      if (editing) {
        await api.put(`/usuarios/${editing}`, form);
      } else {
        await api.post('/usuarios', form);
      }
      setShowModal(false);
      load(page);
    } catch (err) {
      setError(err.message);
    }
  };

  const handleDelete = async (id) => {
    if (!await confirm('Eliminar este usuario?')) return;
    try {
      await api.delete(`/usuarios/${id}`);
      toast.success('Usuario eliminado');
      load(page);
    } catch (err) {
      toast.error(err.message);
    }
  };

  const handleDesbloquear = async (id) => {
    if (!await confirm('Desbloquear este usuario?')) return;
    try {
      await api.post(`/usuarios/${id}/desbloquear`);
      toast.success('Usuario desbloqueado');
      load(page);
    } catch (err) {
      toast.error(err.message);
    }
  };

  const canToggleRole = (rol) => {
    if (!userIsSuperAdmin && rol.nombre === 'SUPER_ADMIN') return false;
    if (!userIsSuperAdmin && currentUser.nivel_max && rol.nivel >= currentUser.nivel_max) return false;
    return true;
  };

  const isRoleLocked = (rolId) => {
    if (!editing) return false;
    const rol = roles.find((r) => r.id === rolId);
    if (!rol) return false;
    if (rol.nombre === 'SUPER_ADMIN' && form.roles.includes(rolId)) return true;
    return false;
  };

  const toggleRole = (rolId) => {
    const rol = roles.find((r) => r.id === rolId);
    if (!rol || !canToggleRole(rol)) return;
    if (isRoleLocked(rolId)) return;

    const rolesNew = form.roles.includes(rolId)
      ? form.roles.filter((r) => r !== rolId)
      : [...form.roles, rolId];
    setForm({ ...form, roles: rolesNew });
  };

  const togglePermiso = (permId) => {
    const vieneDelRol = permisosDelRol.has(permId);
    const esExtra = form.permisos_extras.includes(permId);
    const esNegado = form.permisos_negados.includes(permId);

    let newExtras = [...form.permisos_extras];
    let newNegados = [...form.permisos_negados];

    if (vieneDelRol) {
      if (esNegado) {
        newNegados = newNegados.filter((p) => p !== permId);
      } else {
        newNegados.push(permId);
      }
    } else {
      if (esExtra) {
        newExtras = newExtras.filter((p) => p !== permId);
      } else {
        newExtras.push(permId);
      }
    }

    setForm({ ...form, permisos_extras: newExtras, permisos_negados: newNegados });
  };

  const agruparPermisos = (lista) => {
    const grupos = {};
    lista.forEach((p) => {
      const [grupo] = p.nombre.split('_');
      if (!grupos[grupo]) grupos[grupo] = [];
      grupos[grupo].push(p);
    });
    return grupos;
  };

  const gruposPermisos = agruparPermisos(permisos);

  const permisosDelRol = (() => {
    const ids = new Set();
    form.roles.forEach((rolId) => {
      const rol = roles.find((r) => r.id === rolId);
      rol?.permisos?.forEach((p) => ids.add(p.id));
    });
    return ids;
  })();

  return (
    <div className="crud-page">
      <div className="page-header">
        <h1>Usuarios</h1>
        {canCreate && <button className="btn-primary" onClick={openNew}><FaPlus /> Nuevo</button>}
      </div>

      <form className="search-bar" onSubmit={handleSearch}>
        <input placeholder="Buscar por usuario, nombre o apellido..." value={buscar} onChange={(e) => setBuscar(e.target.value)} />
        <button type="submit">Buscar</button>
      </form>

      <div className="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Usuario</th>
              <th>Nombres</th>
              <th>Apellidos</th>
              <th>Oficina</th>
              <th>Roles</th>
              <th>Estado</th>
              {(canEdit || canDelete) && <th>Acciones</th>}
            </tr>
          </thead>
          <tbody>
            {items.map((u) => (
              <tr key={u.id}>
                <td data-label="Usuario">{u.usuario}</td>
                <td data-label="Nombres">{u.nombres}</td>
                <td data-label="Apellidos">{u.apellidos}</td>
                <td data-label="Oficina">{u.oficina?.nombre || '-'}</td>
                <td data-label="Roles">{u.roles?.map((r) => r.nombre).join(', ') || '-'}</td>
                <td data-label="Estado">
                  <span className={`badge badge-${u.estado.toLowerCase()}`}>{u.estado}</span>
                  {u.bloqueado_hasta && new Date(u.bloqueado_hasta) > new Date() && (
                    <span className="badge badge-bloqueado" style={{ marginLeft: 6 }}>BLOQUEADO</span>
                  )}
                </td>
                {(canEdit || canDelete) && (
                  <td data-label="Acciones" className="actions">
                    {canEdit && u.bloqueado_hasta && new Date(u.bloqueado_hasta) > new Date() && (
                      <button className="btn-icon" title="Desbloquear" onClick={() => handleDesbloquear(u.id)} style={{ color: '#f59e0b', borderColor: '#f59e0b' }}><FaUnlock /></button>
                    )}
                    {canEdit && <button className="btn-icon" onClick={() => openEdit(u)}><FaEdit /></button>}
                    {canDelete && <button className="btn-icon btn-danger" onClick={() => handleDelete(u.id)}><FaTrash /></button>}
                  </td>
                )}
              </tr>
            ))}
            {items.length === 0 && <tr><td colSpan="7" className="empty">No se encontraron registros</td></tr>}
          </tbody>
        </table>
      </div>

      <div className="pagination">
        <button disabled={page <= 1} onClick={() => load(page - 1)}>Anterior</button>
        <span>Pagina {page} de {lastPage}</span>
        <button disabled={page >= lastPage} onClick={() => load(page + 1)}>Siguiente</button>
      </div>

      {showModal && (
        <div className="modal-overlay" onClick={() => setShowModal(false)}>
          <div className="modal modal-lg" onClick={(e) => e.stopPropagation()}>
            <h2>{editing ? 'Editar' : 'Nuevo'} Usuario</h2>
            {error && <div className="form-error">{error}</div>}
            <form onSubmit={handleSave}>
              <div className="form-grid">
                <label>Usuario<input value={form.usuario} onChange={(e) => setForm({ ...form, usuario: e.target.value })} required /></label>
                <label>Contrasena<input type="password" value={form.clave} onChange={(e) => setForm({ ...form, clave: e.target.value })} placeholder={editing ? 'Dejar vacio para no cambiar' : ''} required={!editing} /></label>
                <label>Nombres<input value={form.nombres} onChange={(e) => setForm({ ...form, nombres: e.target.value })} required /></label>
                <label>Apellidos<input value={form.apellidos} onChange={(e) => setForm({ ...form, apellidos: e.target.value })} required /></label>
                <label>Estado<select value={form.estado} onChange={(e) => setForm({ ...form, estado: e.target.value })}><option>ACTIVO</option><option>INACTIVO</option></select></label>
                <label>Oficina<select value={form.oficina_id || ''} onChange={(e) => setForm({ ...form, oficina_id: e.target.value || null })}><option value="">Sin oficina</option>{oficinas.map((o) => <option key={o.id} value={o.id}>{o.nombre}</option>)}</select></label>
              </div>

              <div className="roles-section">
                <span className="roles-label">Roles:</span>
                <div className="roles-list">
                  {roles.map((r) => {
                    const checked = form.roles.includes(r.id);
                    const locked = isRoleLocked(r.id);
                    const allowed = canToggleRole(r);
                    return (
                      <label key={r.id} className={`role-check ${!allowed ? 'role-disabled' : ''}`}>
                        <input
                          type="checkbox"
                          checked={checked}
                          disabled={locked || !allowed}
                          onChange={() => toggleRole(r.id)}
                        />
                        {r.nombre} (Nivel {r.nivel})
                        {locked && <small className="role-locked"> (fijo)</small>}
                      </label>
                    );
                  })}
                </div>
              </div>

              {userIsSuperAdmin && (
                <div className="permisos-section">
                  <span className="roles-label">Permisos del usuario:</span>
                  <small style={{ display: 'block', marginBottom: 10, color: 'var(--text-muted, #999)', fontSize: 12 }}>
                    Por rol: puede quitar. Extras: puede agregar.
                  </small>
                  {Object.entries(gruposPermisos).map(([grupo, perms]) => (
                    <div key={grupo} className="permiso-grupo">
                      <strong>{grupo}</strong>
                      <div className="roles-list">
                        {perms.map((p) => {
                          const vieneDelRol = permisosDelRol.has(p.id);
                          const esExtra = form.permisos_extras.includes(p.id);
                          const esNegado = form.permisos_negados.includes(p.id);
                          const checked = vieneDelRol ? !esNegado : esExtra;
                          return (
                            <label key={p.id} className={`role-check ${vieneDelRol && !esNegado ? 'role-locked-inline' : ''} ${vieneDelRol && esNegado ? 'role-negado' : ''}`}>
                              <input
                                type="checkbox"
                                checked={checked}
                                onChange={() => togglePermiso(p.id)}
                              />
                              {p.nombre}
                              {vieneDelRol && !esNegado && <small className="role-locked"> (por rol)</small>}
                              {vieneDelRol && esNegado && <small className="role-negado"> (negado)</small>}
                              {!vieneDelRol && esExtra && <small className="role-extra"> (extra)</small>}
                            </label>
                          );
                        })}
                      </div>
                    </div>
                  ))}
                </div>
              )}

              <div className="form-actions">
                <button type="button" className="btn-secondary" onClick={() => setShowModal(false)}>Cancelar</button>
                <button type="submit" className="btn-primary">Guardar</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
