import { useEffect, useState, useRef } from 'react';
import { api } from '../services/api';
import { hasPermission } from '../services/authService';
import { useToast } from '../components/Toast';
import { FaPlus, FaEdit, FaTrash, FaFileImport, FaDownload, FaImage, FaTimes } from 'react-icons/fa';
import './CrudPage.css';

const initial = { dni: '', codigo_universitario: '', nombres: '', apellidos: '', telefono: '', correos: [], estado: 'ACTIVO', facultad: '', escuela_profesional: '', url_foto_presencial: '', url_foto_virtual: '' };

const determinarTipoCorreo = (correo) => {
  const domain = correo.split('@')[1]?.toLowerCase() || '';
  if (domain === 'unap.edu.pe' || domain === 'est.unap.pe') return 'INSTITUCIONAL';
  return 'PERSONAL';
};

export default function Estudiantes() {
  const { toast, confirm } = useToast();
  const [items, setItems] = useState([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [buscar, setBuscar] = useState('');
  const [form, setForm] = useState(initial);
  const [editing, setEditing] = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [error, setError] = useState('');
  const [importResult, setImportResult] = useState(null);
  const [importing, setImporting] = useState(false);
  const [nuevoCorreo, setNuevoCorreo] = useState('');
  const fileRef = useRef(null);

  const canCreate = hasPermission('estudiantes_crear');
  const canEdit = hasPermission('estudiantes_editar');
  const canDelete = hasPermission('estudiantes_eliminar');

  const flatten = (e) => ({
    ...e,
    ...e.persona,
    id: e.id,
    correos: e.persona?.correos || [],
  });

  const load = (p = 1) => {
    api.get(`/estudiantes?page=${p}&buscar=${buscar}`).then((res) => {
      setItems(res.data.map(flatten));
      setPage(res.current_page);
      setLastPage(res.last_page);
    });
  };

  useEffect(() => { load(); }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const handleSearch = (e) => {
    e.preventDefault();
    load();
  };

  const openNew = () => {
    setForm({ ...initial, correos: [] });
    setEditing(null);
    setShowModal(true);
    setError('');
    setNuevoCorreo('');
  };

  const openEdit = (item) => {
    setForm({
      ...item,
      correos: item.correos?.map((c) => ({ correo: c.correo, tipo: c.tipo || determinarTipoCorreo(c.correo), principal: c.principal })) || [],
    });
    setEditing(item.id);
    setShowModal(true);
    setError('');
    setNuevoCorreo('');
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setError('');
    try {
      if (editing) {
        await api.put(`/estudiantes/${editing}`, form);
      } else {
        await api.post('/estudiantes', form);
      }
      setShowModal(false);
      load(page);
      toast.success(editing ? 'Estudiante actualizado' : 'Estudiante registrado');
    } catch (err) {
      setError(err.response?.data?.message || err.response?.data?.error || 'Error al guardar');
    }
  };

  const handleDelete = async (id) => {
    const ok = await confirm('¿Eliminar este estudiante?');
    if (!ok) return;
    try {
      await api.delete(`/estudiantes/${id}`);
      load(page);
      toast.success('Estudiante eliminado');
    } catch {
      toast.error('Error al eliminar');
    }
  };

  const handleImport = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setImporting(true);
    setImportResult(null);
    try {
      const formData = new FormData();
      formData.append('archivo', file);
      const res = await api.post('/estudiantes/importar', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
      setImportResult(res);
      load(1);
      toast.success('Importacion completada');
    } catch (err) {
      toast.error(err.response?.data?.message || 'Error al importar');
    } finally {
      setImporting(false);
      if (fileRef.current) fileRef.current.value = '';
    }
  };

  const handleDescargarPlantilla = () => {
    api.download('/plantilla-estudiantes', 'plantilla_estudiantes.xlsx');
  };

  const addCorreo = () => {
    const correo = nuevoCorreo.trim();
    if (!correo) return;
    if (form.correos.some((c) => c.correo === correo)) {
      setError('Este correo ya esta en la lista');
      return;
    }
    setForm({
      ...form,
      correos: [...form.correos, { correo, tipo: determinarTipoCorreo(correo), principal: form.correos.length === 0 }],
    });
    setNuevoCorreo('');
    setError('');
  };

  const removeCorreo = (index) => {
    const newCorreos = form.correos.filter((_, i) => i !== index);
    if (newCorreos.length > 0 && !newCorreos.some((c) => c.principal)) {
      newCorreos[0].principal = true;
    }
    setForm({ ...form, correos: newCorreos });
  };

  const handleCorreoKeyDown = (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      addCorreo();
    }
  };

  return (
    <div className="crud-page">
      <div className="page-header">
        <h1>Estudiantes</h1>
        <div className="header-actions">
          {canCreate && (
            <>
              <button className="btn-primary" onClick={openNew}><FaPlus /> Nuevo</button>
              <label className="btn-secondary">
                <FaFileImport /> {importing ? 'Importando...' : 'Importar'}
                <input ref={fileRef} type="file" accept=".xlsx,.xls,.csv" onChange={handleImport} style={{ display: 'none' }} disabled={importing} />
              </label>
              <button className="btn-secondary" onClick={handleDescargarPlantilla}><FaDownload /> Plantilla</button>
            </>
          )}
        </div>
      </div>

      {importResult && (
        <div className="import-result">
          <strong>Importacion:</strong> Creados: {importResult.creados}, Actualizados: {importResult.actualizados}, Saltados: {importResult.saltados}
          <button className="btn-icon" onClick={() => setImportResult(null)}><FaTimes /></button>
        </div>
      )}

      <form className="search-bar" onSubmit={handleSearch}>
        <input type="text" placeholder="Buscar por nombre, apellido o DNI..." value={buscar} onChange={(e) => setBuscar(e.target.value)} />
        <button type="submit" className="btn-primary">Buscar</button>
      </form>

      <div className="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>DNI</th>
              <th>Nombres</th>
              <th>Apellidos</th>
              <th>Correos</th>
              <th>Facultad</th>
              <th>Escuela Prof.</th>
              <th>Codigo</th>
              <th>Foto</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            {items.map((item) => (
              <tr key={item.id}>
                <td>{item.dni}</td>
                <td>{item.nombres}</td>
                <td>{item.apellidos}</td>
                <td>
                  {item.correos?.map((c) => (
                    <span key={c.id} className={`badge ${c.principal ? 'badge-activo' : ''}`}>{c.correo}</span>
                  ))}
                </td>
                <td>{item.facultad}</td>
                <td>{item.escuela_profesional}</td>
                <td>{item.codigo_universitario}</td>
                <td>
                  {item.url_foto_presencial ? (
                    <a href={item.url_foto_presencial} target="_blank" rel="noopener noreferrer" className="foto-icon"><FaImage /></a>
                  ) : (
                    <span className="no-photo"><FaImage /></span>
                  )}
                </td>
                <td><span className={`badge badge-${item.estado?.toLowerCase()}`}>{item.estado}</span></td>
                <td className="actions">
                  {canEdit && <button className="btn-icon" onClick={() => openEdit(item)} title="Editar"><FaEdit /></button>}
                  {canDelete && <button className="btn-icon btn-danger" onClick={() => handleDelete(item.id)} title="Eliminar"><FaTrash /></button>}
                </td>
              </tr>
            ))}
            {items.length === 0 && (
              <tr><td colSpan="10" style={{ textAlign: 'center' }}>No se encontraron estudiantes</td></tr>
            )}
          </tbody>
        </table>
      </div>

      <div className="pagination">
        <button className="btn-secondary" disabled={page <= 1} onClick={() => load(page - 1)}>Anterior</button>
        <span>Pagina {page} de {lastPage}</span>
        <button className="btn-secondary" disabled={page >= lastPage} onClick={() => load(page + 1)}>Siguiente</button>
      </div>

      {showModal && (
        <div className="modal-overlay" onClick={() => setShowModal(false)}>
          <div className="modal" onClick={(e) => e.stopPropagation()}>
            <h2>{editing ? 'Editar' : 'Nuevo'} Estudiante</h2>
            {error && <div className="form-error">{error}</div>}
            <form onSubmit={handleSave}>
              <div className="form-grid">
                <label>DNI *<input type="text" maxLength={8} value={form.dni} onChange={(e) => setForm({ ...form, dni: e.target.value })} required /></label>
                <label>Codigo Universitario *<input type="text" maxLength={50} value={form.codigo_universitario} onChange={(e) => setForm({ ...form, codigo_universitario: e.target.value })} required /></label>
                <label>Nombres *<input type="text" maxLength={100} value={form.nombres} onChange={(e) => setForm({ ...form, nombres: e.target.value })} required /></label>
                <label>Apellidos *<input type="text" maxLength={100} value={form.apellidos} onChange={(e) => setForm({ ...form, apellidos: e.target.value })} required /></label>
                <label>Telefono<input type="text" maxLength={20} value={form.telefono || ''} onChange={(e) => setForm({ ...form, telefono: e.target.value })} /></label>
                <label>Estado
                  <select value={form.estado} onChange={(e) => setForm({ ...form, estado: e.target.value })}>
                    <option value="ACTIVO">Activo</option>
                    <option value="INACTIVO">Inactivo</option>
                  </select>
                </label>
                <label>Facultad<input type="text" maxLength={150} value={form.facultad || ''} onChange={(e) => setForm({ ...form, facultad: e.target.value })} /></label>
                <label>Escuela Profesional<input type="text" maxLength={150} value={form.escuela_profesional || ''} onChange={(e) => setForm({ ...form, escuela_profesional: e.target.value })} /></label>
              </div>

              <div className="form-section-title">Correos</div>
              <div className="correos-input-section">
                <div className="correo-add-row">
                  <input type="email" placeholder="Agregar correo..." value={nuevoCorreo} onChange={(e) => setNuevoCorreo(e.target.value)} onKeyDown={handleCorreoKeyDown} />
                  <button type="button" className="btn-primary btn-sm" onClick={addCorreo}><FaPlus /></button>
                </div>
                <div className="correos-edit-list">
                  {form.correos.map((c, i) => (
                    <div key={i} className={`correo-edit-item ${c.principal ? 'correo-principal' : ''}`}>
                      <span>{c.correo} <small>({c.tipo})</small></span>
                      {!c.principal && <button type="button" className="btn-icon btn-danger btn-sm" onClick={() => removeCorreo(i)}><FaTimes /></button>}
                    </div>
                  ))}
                </div>
              </div>

              <div className="form-section-title">Fotos</div>
              <div className="form-grid">
                <label>URL Foto Presencial<input type="url" value={form.url_foto_presencial || ''} onChange={(e) => setForm({ ...form, url_foto_presencial: e.target.value })} /></label>
                <label>URL Foto Virtual<input type="url" value={form.url_foto_virtual || ''} onChange={(e) => setForm({ ...form, url_foto_virtual: e.target.value })} /></label>
              </div>

              <div className="form-actions">
                <button type="button" className="btn-secondary" onClick={() => setShowModal(false)}>Cancelar</button>
                <button type="submit" className="btn-primary">{editing ? 'Guardar' : 'Crear'}</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
