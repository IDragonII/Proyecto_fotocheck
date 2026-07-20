import { useEffect, useState } from 'react';
import { api } from '../services/api';
import { hasPermission } from '../services/authService';
import { useToast } from '../components/Toast';
import { FaPlus, FaEdit, FaTrash } from 'react-icons/fa';
import './CrudPage.css';

export default function Roles() {
  const { toast, confirm } = useToast();
  const [items, setItems] = useState([]);
  const [permisos, setPermisos] = useState([]);
  const [form, setForm] = useState({ nombre: '', descripcion: '', nivel: 50, estado: 'ACTIVO', permisos: [] });
  const [editing, setEditing] = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [error, setError] = useState('');

  const canCreate = hasPermission('roles_crear');
  const canEdit = hasPermission('roles_editar');
  const canDelete = hasPermission('roles_eliminar');

  const load = () => {
    api.get('/roles').then(setItems);
  };

  useEffect(() => {
    load();
    api.get('/permisos').then(setPermisos);
  }, []);

  const openNew = () => { setForm({ nombre: '', descripcion: '', nivel: 50, estado: 'ACTIVO', permisos: [] }); setEditing(null); setShowModal(true); setError(''); };
  const openEdit = (item) => { setForm({ ...item, permisos: item.permisos?.map((p) => p.id) || [] }); setEditing(item.id); setShowModal(true); setError(''); };

  const handleSave = async (e) => {
    e.preventDefault();
    setError('');
    try {
      if (editing) {
        await api.put(`/roles/${editing}`, form);
      } else {
        await api.post('/roles', form);
      }
      setShowModal(false);
      load();
    } catch (err) {
      setError(err.message);
    }
  };

  const handleDelete = async (id) => {
    if (!await confirm('Eliminar este rol?')) return;
    try {
      await api.delete(`/roles/${id}`);
      toast.success('Rol eliminado');
      load();
    } catch (err) {
      toast.error(err.message);
    }
  };

  const togglePermiso = (permId) => {
    const permisos = form.permisos.includes(permId) ? form.permisos.filter((p) => p !== permId) : [...form.permisos, permId];
    setForm({ ...form, permisos });
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

  const grupos = agruparPermisos(permisos);

  return (
    <div className="crud-page">
      <div className="page-header">
        <h1>Roles</h1>
        {canCreate && <button className="btn-primary" onClick={openNew}><FaPlus /> Nuevo</button>}
      </div>

      <div className="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Descripcion</th>
              <th>Nivel</th>
              <th>Permisos</th>
              <th>Estado</th>
              {(canEdit || canDelete) && <th>Acciones</th>}
            </tr>
          </thead>
          <tbody>
            {items.map((r) => (
              <tr key={r.id}>
                <td data-label="Nombre">{r.nombre}</td>
                <td data-label="Descripcion">{r.descripcion || '-'}</td>
                <td data-label="Nivel">{r.nivel}</td>
                <td data-label="Permisos">{r.permisos?.length || 0}</td>
                <td data-label="Estado"><span className={`badge badge-${r.estado.toLowerCase()}`}>{r.estado}</span></td>
                {(canEdit || canDelete) && (
                  <td data-label="Acciones" className="actions">
                    {canEdit && <button className="btn-icon" onClick={() => openEdit(r)}><FaEdit /></button>}
                    {canDelete && <button className="btn-icon btn-danger" onClick={() => handleDelete(r.id)}><FaTrash /></button>}
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {showModal && (
        <div className="modal-overlay" onClick={() => setShowModal(false)}>
          <div className="modal modal-lg" onClick={(e) => e.stopPropagation()}>
            <h2>{editing ? 'Editar' : 'Nuevo'} Rol</h2>
            {error && <div className="form-error">{error}</div>}
            <form onSubmit={handleSave}>
              <div className="form-grid">
                <label>Nombre<input value={form.nombre} onChange={(e) => setForm({ ...form, nombre: e.target.value })} required /></label>
                <label>Descripcion<input value={form.descripcion || ''} onChange={(e) => setForm({ ...form, descripcion: e.target.value })} /></label>
                <label>Nivel<input type="number" min="1" max="100" value={form.nivel} onChange={(e) => setForm({ ...form, nivel: parseInt(e.target.value) })} required /></label>
                <label>Estado<select value={form.estado} onChange={(e) => setForm({ ...form, estado: e.target.value })}><option>ACTIVO</option><option>INACTIVO</option></select></label>
              </div>
              <div className="permisos-section">
                <span className="roles-label">Permisos:</span>
                {Object.entries(grupos).map(([grupo, perms]) => (
                  <div key={grupo} className="permiso-grupo">
                    <strong>{grupo}</strong>
                    <div className="roles-list">
                      {perms.map((p) => (
                        <label key={p.id} className="role-check">
                          <input type="checkbox" checked={form.permisos.includes(p.id)} onChange={() => togglePermiso(p.id)} />
                          {p.nombre}
                        </label>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
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
