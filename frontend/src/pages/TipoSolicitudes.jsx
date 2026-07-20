import { useEffect, useState } from 'react';
import { api } from '../services/api';
import { hasPermission } from '../services/authService';
import { useToast } from '../components/Toast';
import { FaPlus, FaEdit, FaTrash } from 'react-icons/fa';
import './CrudPage.css';

export default function TipoSolicitudes() {
  const { toast, confirm } = useToast();
  const [items, setItems] = useState([]);
  const [oficinas, setOficinas] = useState([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [buscar, setBuscar] = useState('');
  const [form, setForm] = useState({ nombre: '', descripcion: '', oficina_id: '', estado: 'ACTIVO' });
  const [editing, setEditing] = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [error, setError] = useState('');

  const canCreate = hasPermission('tipo_solicitudes_crear');
  const canEdit = hasPermission('tipo_solicitudes_editar');
  const canDelete = hasPermission('tipo_solicitudes_eliminar');

  const load = (p = 1) => {
    api.get(`/tipo-solicitudes?page=${p}&buscar=${buscar}`).then((res) => {
      setItems(res.data);
      setPage(res.current_page);
      setLastPage(res.last_page);
    });
  };

  useEffect(() => {
    load();
    api.get('/oficinas?per_page=100').then((res) => setOficinas(res.data || res));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const handleSearch = (e) => {
    e.preventDefault();
    load();
  };

  const openNew = () => {
    setForm({ nombre: '', descripcion: '', oficina_id: oficinas[0]?.id || '', estado: 'ACTIVO' });
    setEditing(null);
    setShowModal(true);
    setError('');
  };

  const openEdit = (item) => {
    setForm({
      nombre: item.nombre,
      descripcion: item.descripcion || '',
      oficina_id: item.oficina_id,
      estado: item.estado,
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
        await api.put(`/tipo-solicitudes/${editing}`, form);
      } else {
        await api.post('/tipo-solicitudes', form);
      }
      setShowModal(false);
      load(page);
    } catch (err) {
      setError(err.message);
    }
  };

  const handleDelete = async (id) => {
    if (!await confirm('Eliminar este tipo de solicitud?')) return;
    try {
      await api.delete(`/tipo-solicitudes/${id}`);
      toast.success('Tipo de solicitud eliminado');
      load(page);
    } catch (err) {
      toast.error(err.message);
    }
  };

  return (
    <div className="crud-page">
      <div className="page-header">
        <h1>Tipos de Ticket</h1>
        {canCreate && <button className="btn-primary" onClick={openNew}><FaPlus /> Nuevo</button>}
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
              <th>Descripcion</th>
              <th>Oficina Destino</th>
              <th>Estado</th>
              {(canEdit || canDelete) && <th>Acciones</th>}
            </tr>
          </thead>
          <tbody>
            {items.map((t) => (
              <tr key={t.id}>
                <td data-label="Nombre">{t.nombre}</td>
                <td data-label="Descripcion">{t.descripcion || '-'}</td>
                <td data-label="Oficina Destino"><span className="badge badge-activo">{t.oficina?.nombre || '-'}</span></td>
                <td data-label="Estado"><span className={`badge badge-${t.estado.toLowerCase()}`}>{t.estado}</span></td>
                {(canEdit || canDelete) && (
                  <td data-label="Acciones" className="actions">
                    {canEdit && <button className="btn-icon" onClick={() => openEdit(t)}><FaEdit /></button>}
                    {canDelete && <button className="btn-icon btn-danger" onClick={() => handleDelete(t.id)}><FaTrash /></button>}
                  </td>
                )}
              </tr>
            ))}
            {items.length === 0 && <tr><td colSpan="5" className="empty">No se encontraron tipos de solicitud</td></tr>}
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
          <div className="modal" onClick={(e) => e.stopPropagation()}>
            <h2>{editing ? 'Editar' : 'Nuevo'} Tipo de Ticket</h2>
            {error && <div className="form-error">{error}</div>}
            <form onSubmit={handleSave}>
              <div className="form-grid">
                <label>
                  Nombre
                  <input value={form.nombre} onChange={(e) => setForm({ ...form, nombre: e.target.value })} required />
                </label>
                <label>
                  Oficina Destino
                  <select value={form.oficina_id} onChange={(e) => setForm({ ...form, oficina_id: e.target.value })} required>
                    <option value="">Seleccionar oficina...</option>
                    {oficinas.map((o) => (
                      <option key={o.id} value={o.id}>{o.nombre}</option>
                    ))}
                  </select>
                </label>
                <label style={{ gridColumn: '1 / -1' }}>
                  Descripcion
                  <textarea value={form.descripcion} onChange={(e) => setForm({ ...form, descripcion: e.target.value })} rows={2} />
                </label>
                <label>
                  Estado
                  <select value={form.estado} onChange={(e) => setForm({ ...form, estado: e.target.value })}>
                    <option value="ACTIVO">ACTIVO</option>
                    <option value="INACTIVO">INACTIVO</option>
                  </select>
                </label>
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
