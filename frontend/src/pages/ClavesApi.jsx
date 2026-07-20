import { useEffect, useState } from 'react';
import { api } from '../services/api';
import { hasPermission } from '../services/authService';
import { useToast } from '../components/Toast';
import { FaPlus, FaEdit, FaTrash, FaKey, FaToggleOn, FaToggleOff, FaCopy } from 'react-icons/fa';
import './CrudPage.css';

const PERMISOS_DISPONIBLES = [
  { value: 'tickets_crear', label: 'Crear tickets' },
  { value: 'tickets_consultar', label: 'Consultar tickets' },
  { value: 'dni_consultar', label: 'Consultar persona por DNI' },
  { value: 'tipos_solicitud_consultar', label: 'Consultar tipos de ticket' },
];

const TIEMPOS_VIDA = [
  { value: '30', label: '30 dias' },
  { value: '90', label: '90 dias' },
  { value: '365', label: '1 ano' },
  { value: 'sin_expire', label: 'Sin expiracion' },
];

export default function ClavesApi() {
  const { toast, confirm } = useToast();
  const [items, setItems] = useState([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [buscar, setBuscar] = useState('');
  const [form, setForm] = useState({ nombre: '', descripcion: '', permisos: [], rate_limit: 600, tiempo_vida: '90' });
  const [editing, setEditing] = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [error, setError] = useState('');
  const [showKeyModal, setShowKeyModal] = useState(false);
  const [generatedKey, setGeneratedKey] = useState('');

  const canCreate = hasPermission('api_keys_crear');
  const canEdit = hasPermission('api_keys_editar');
  const canDelete = hasPermission('api_keys_eliminar');

  const load = (p = 1) => {
    api.get(`/api-keys?page=${p}&buscar=${buscar}`).then((res) => {
      setItems(res.data);
      setPage(res.current_page);
      setLastPage(res.last_page);
    });
  };

  // eslint-disable-next-line react-hooks/exhaustive-deps
  useEffect(() => { load(); }, []);

  const handleSearch = (e) => {
    e.preventDefault();
    load();
  };

  const openNew = () => {
    setForm({ nombre: '', descripcion: '', permisos: [], rate_limit: 600, tiempo_vida: '90' });
    setEditing(null);
    setShowModal(true);
    setError('');
  };

  const openEdit = (item) => {
    setForm({
      nombre: item.nombre,
      descripcion: item.descripcion || '',
      permisos: item.permisos || [],
      rate_limit: item.rate_limit,
      tiempo_vida: item.expira_en ? String(Math.ceil((new Date(item.expira_en) - new Date()) / (1000 * 60 * 60 * 24))) : 'sin_expire',
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
        await api.put(`/api-keys/${editing}`, form);
        setShowModal(false);
        load(page);
      } else {
        const res = await api.post('/api-keys', form);
        setShowModal(false);
        setGeneratedKey(res.data.clave);
        setShowKeyModal(true);
        load(page);
      }
    } catch (err) {
      setError(err.message);
    }
  };

  const handleDelete = async (id) => {
    if (!await confirm('Eliminar esta clave API?')) return;
    try {
      await api.delete(`/api-keys/${id}`);
      toast.success('Clave API eliminada');
      load(page);
    } catch (err) {
      toast.error(err.message);
    }
  };

  const handleToggle = async (id) => {
    try {
      await api.post(`/api-keys/${id}/toggle-estado`);
      toast.success('Estado actualizado');
      load(page);
    } catch (err) {
      toast.error(err.message);
    }
  };

  const handleRegenerate = async (id) => {
    if (!await confirm('Regenerar esta clave API? La clave anterior dejara de funcionar.')) return;
    try {
      const res = await api.post(`/api-keys/${id}/regenerar`);
      setGeneratedKey(res.data.clave);
      setShowKeyModal(true);
      toast.success('Clave regenerada');
      load(page);
    } catch (err) {
      toast.error(err.message);
    }
  };

  const copiarClave = () => {
    navigator.clipboard.writeText(generatedKey);
  };

  const togglePermiso = (perm) => {
    const permisos = form.permisos.includes(perm)
      ? form.permisos.filter((p) => p !== perm)
      : [...form.permisos, perm];
    setForm({ ...form, permisos });
  };

  const formatearFecha = (fecha) => {
    if (!fecha) return 'Nunca';
    return new Date(fecha).toLocaleString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
  };

  return (
    <div className="crud-page">
      <div className="page-header">
        <h1>Claves API</h1>
        {canCreate && <button className="btn-primary" onClick={openNew}><FaPlus /> Nueva Clave</button>}
      </div>

      <form className="search-bar" onSubmit={handleSearch}>
        <input placeholder="Buscar por nombre..." value={buscar} onChange={(e) => setBuscar(e.target.value)} />
        <button type="submit">Buscar</button>
      </form>

      <div className="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Clave</th>
              <th>Permisos</th>
              <th>Rate Limit</th>
              <th>Estado</th>
              <th>Usos</th>
              <th>Ultimo Uso</th>
              {(canEdit || canDelete) && <th>Acciones</th>}
            </tr>
          </thead>
          <tbody>
            {items.map((k) => (
              <tr key={k.id}>
                <td data-label="Nombre">{k.nombre}</td>
                <td data-label="Clave"><code>{k.clave_prefijo}****</code></td>
                <td data-label="Permisos">
                  {(k.permisos || []).map((p) => (
                    <span key={p} className="badge badge-activo" style={{ marginRight: 4, fontSize: '0.7em' }}>
                      {PERMISOS_DISPONIBLES.find((pd) => pd.value === p)?.label || p}
                    </span>
                  ))}
                </td>
                <td data-label="Rate Limit">{k.rate_limit}/min</td>
                <td data-label="Estado">
                  <span className={`badge badge-${k.estado.toLowerCase()}`}>{k.estado}</span>
                </td>
                <td data-label="Usos">{k.total_usos}</td>
                <td data-label="Ultimo Uso">{formatearFecha(k.ultimo_uso)}</td>
                {(canEdit || canDelete) && (
                  <td data-label="Acciones" className="actions">
                    {canEdit && <button className="btn-icon" title="Editar" onClick={() => openEdit(k)}><FaEdit /></button>}
                    {canEdit && (
                      <button className="btn-icon" title={k.estado === 'ACTIVO' ? 'Desactivar' : 'Activar'} onClick={() => handleToggle(k.id)}>
                        {k.estado === 'ACTIVO' ? <FaToggleOn /> : <FaToggleOff />}
                      </button>
                    )}
                    {canEdit && <button className="btn-icon" title="Regenerar clave" onClick={() => handleRegenerate(k.id)}><FaKey /></button>}
                    {canDelete && <button className="btn-icon btn-danger" title="Eliminar" onClick={() => handleDelete(k.id)}><FaTrash /></button>}
                  </td>
                )}
              </tr>
            ))}
            {items.length === 0 && <tr><td colSpan="8" className="empty">No se encontraron claves API</td></tr>}
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
            <h2>{editing ? 'Editar' : 'Nueva'} Clave API</h2>
            {error && <div className="form-error">{error}</div>}
            <form onSubmit={handleSave}>
              <div className="form-grid">
                <label>
                  Nombre
                  <input value={form.nombre} onChange={(e) => setForm({ ...form, nombre: e.target.value })} required />
                </label>
                <label>
                  Rate Limit (req/min)
                  <input type="number" min="1" max="10000" value={form.rate_limit} onChange={(e) => setForm({ ...form, rate_limit: parseInt(e.target.value) })} required />
                </label>
                <label style={{ gridColumn: '1 / -1' }}>
                  Descripcion
                  <textarea value={form.descripcion} onChange={(e) => setForm({ ...form, descripcion: e.target.value })} rows={2} />
                </label>
                {!editing && (
                  <label>
                    Tiempo de vida
                    <select value={form.tiempo_vida} onChange={(e) => setForm({ ...form, tiempo_vida: e.target.value })}>
                      {TIEMPOS_VIDA.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}
                    </select>
                  </label>
                )}
              </div>

              <div className="permisos-section">
                <span className="roles-label">Permisos:</span>
                <div className="roles-list">
                  {PERMISOS_DISPONIBLES.map((p) => (
                    <label key={p.value} className="role-check">
                      <input type="checkbox" checked={form.permisos.includes(p.value)} onChange={() => togglePermiso(p.value)} />
                      {p.label}
                    </label>
                  ))}
                </div>
              </div>

              <div className="form-actions">
                <button type="button" className="btn-secondary" onClick={() => setShowModal(false)}>Cancelar</button>
                <button type="submit" className="btn-primary">Guardar</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {showKeyModal && (
        <div className="modal-overlay" onClick={() => setShowKeyModal(false)}>
          <div className="modal" onClick={(e) => e.stopPropagation()}>
            <h2>Clave API Generada</h2>
            <p style={{ color: 'var(--text)', marginBottom: 8 }}>
              Copia esta clave ahora. <strong>No se podra ver nuevamente.</strong>
            </p>
            <div style={{ background: 'var(--bg-secondary)', padding: 12, borderRadius: 8, marginBottom: 16, display: 'flex', alignItems: 'center', gap: 8 }}>
              <code style={{ flex: 1, wordBreak: 'break-all', fontSize: '0.85em' }}>{generatedKey}</code>
              <button className="btn-icon" title="Copiar" onClick={copiarClave}><FaCopy /></button>
            </div>
            <div className="form-actions">
              <button className="btn-primary" onClick={() => { setShowKeyModal(false); setGeneratedKey(''); }}>Cerrar</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
