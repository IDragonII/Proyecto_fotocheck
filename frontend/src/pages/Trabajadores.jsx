import { useEffect, useState, useRef } from 'react';
import { api } from '../services/api';
import { hasPermission } from '../services/authService';
import { useToast } from '../components/Toast';
import { FaPlus, FaEdit, FaTrash, FaFileImport, FaDownload, FaImage, FaTimes } from 'react-icons/fa';
import './CrudPage.css';

const initial = { dni: '', codigo_universitario: '', nombres: '', apellidos: '', empresa: '', area: '', dependencia: '', cargo: '', telefono: '', correos: [], estado: 'ACTIVO', fecha_ingreso: '', regimen: '', facultad: '', escuela_profesional: '', resolucion_rectoral: '', vigencia: '', fecha_emision: '', url_foto_presencial: '', url_foto_virtual: '', url_qr_image: '', url_qr: '' };

const determinarTipoCorreo = (correo) => {
  const domain = correo.split('@')[1]?.toLowerCase() || '';
  if (domain === 'unap.edu.pe' || domain === 'est.unap.pe') return 'INSTITUCIONAL';
  return 'PERSONAL';
};

export default function Trabajadores() {
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
  const [nuevoCorreoTipo, setNuevoCorreoTipo] = useState('PERSONAL');
  const fileRef = useRef(null);

  const canCreate = hasPermission('trabajadores_crear');
  const canEdit = hasPermission('trabajadores_editar');
  const canDelete = hasPermission('trabajadores_eliminar');

  const flatten = (t) => ({
    ...t,
    ...t.persona,
    id: t.id,
    correos: t.persona?.correos || [],
  });

  const load = (p = 1) => {
    api.get(`/trabajadores?page=${p}&buscar=${buscar}`).then((res) => {
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
    setNuevoCorreoTipo('PERSONAL');
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
    setNuevoCorreoTipo('PERSONAL');
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setError('');
    try {
      if (editing) {
        await api.put(`/trabajadores/${editing}`, form);
      } else {
        await api.post('/trabajadores', form);
      }
      setShowModal(false);
      load(page);
    } catch (err) {
      setError(err.message);
    }
  };

  const handleDelete = async (id) => {
    if (!await confirm('Eliminar este trabajador?')) return;
    try {
      await api.delete(`/trabajadores/${id}`);
      toast.success('Trabajador eliminado');
      load(page);
    } catch (err) {
      toast.error(err.message);
    }
  };

  const handleImport = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    setImporting(true);
    setImportResult(null);

    const formData = new FormData();
    formData.append('archivo', file);

    try {
      const res = await api.post('/trabajadores/importar', formData, true);
      setImportResult(res);
      load();
    } catch (err) {
      setImportResult({ message: err.message, errores: [] });
    } finally {
      setImporting(false);
      if (fileRef.current) fileRef.current.value = '';
    }
  };

  const handleDescargarPlantilla = () => {
    api.download('/plantilla-trabajadores', 'plantilla_trabajadores.xlsx');
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
      correos: [...form.correos, { correo, tipo: nuevoCorreoTipo, principal: form.correos.length === 0 }],
    });
    setNuevoCorreo('');
    setNuevoCorreoTipo('PERSONAL');
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
        <h1>Trabajadores</h1>
        <div className="header-actions">
          <button className="btn-secondary" onClick={handleDescargarPlantilla}>
            <FaDownload /> Descargar Plantilla
          </button>
          {canCreate && (
            <>
              <label className="btn-secondary" style={{ cursor: 'pointer' }}>
                <FaFileImport /> Importar Excel
                <input ref={fileRef} type="file" accept=".xlsx,.xls,.csv" onChange={handleImport} hidden />
              </label>
              <button className="btn-primary" onClick={openNew}><FaPlus /> Nuevo</button>
            </>
          )}
        </div>
      </div>

      {importing && <div className="import-status">Importando archivo...</div>}

      {importResult && (
        <div className={`import-result ${importResult.errores?.length ? 'with-errors' : ''}`}>
          <p>{importResult.message}</p>
          {importResult.creados !== undefined && (
            <p>Creados: {importResult.creados} | Actualizados: {importResult.actualizados} | No importados: {importResult.saltados || 0}</p>
          )}
          {importResult.errores?.length > 0 && (
            <ul>
              {importResult.errores.map((err, i) => <li key={i}>{err}</li>)}
            </ul>
          )}
          <button className="btn-close" onClick={() => setImportResult(null)}>&times;</button>
        </div>
      )}

      <form className="search-bar" onSubmit={handleSearch}>
        <input placeholder="Buscar por nombre, apellido o DNI..." value={buscar} onChange={(e) => setBuscar(e.target.value)} />
        <button type="submit">Buscar</button>
      </form>

      <div className="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>DNI</th>
              <th>Nombres</th>
              <th>Apellidos</th>
              <th>Correo Personal</th>
              <th>Correo Institucional</th>
              <th>Cargo</th>
              <th>Codigo</th>
              <th>NFS</th>
              <th>Foto</th>
              <th>Estado</th>
              {(canEdit || canDelete) && <th>Acciones</th>}
            </tr>
          </thead>
          <tbody>
            {items.map((t) => (
              <tr key={t.id}>
                <td data-label="DNI">{t.dni}</td>
                <td data-label="Nombres">{t.nombres}</td>
                <td data-label="Apellidos">{t.apellidos}</td>
                <td data-label="Correo Personal">
                  {t.correos.filter(c => c.tipo === 'PERSONAL').length > 0 ? (
                    <div className="correos-list">
                      {t.correos.filter(c => c.tipo === 'PERSONAL').map((c, i) => (
                        <span key={i} className={`correo-badge ${c.principal ? 'correo-principal' : ''}`}>
                          {c.correo}
                        </span>
                      ))}
                    </div>
                  ) : '-'}
                </td>
                <td data-label="Correo Institucional">
                  {t.correos.filter(c => c.tipo === 'INSTITUCIONAL').length > 0 ? (
                    <div className="correos-list">
                      {t.correos.filter(c => c.tipo === 'INSTITUCIONAL').map((c, i) => (
                        <span key={i} className={`correo-badge ${c.principal ? 'correo-principal' : ''}`}>
                          {c.correo}
                        </span>
                      ))}
                    </div>
                  ) : '-'}
                </td>
                <td data-label="Cargo">{t.cargo || '-'}</td>
                <td data-label="Codigo"><code>{t.codigo_unico || '-'}</code></td>
                <td data-label="NFS"><code>{t.codigo_nfs || '-'}</code></td>
                <td data-label="Foto">
                  {(t.url_foto_presencial || t.url_foto_virtual) ? (
                    <a href={t.url_foto_presencial || t.url_foto_virtual} target="_blank" rel="noopener noreferrer" className="foto-icon" title="Abrir foto">
                      <FaImage />
                    </a>
                  ) : <span className="foto-icon no-photo"><FaImage /></span>}
                </td>
                <td data-label="Estado"><span className={`badge badge-${t.estado.toLowerCase()}`}>{t.estado}</span></td>
                {(canEdit || canDelete) && (
                  <td data-label="Acciones" className="actions">
                    {canEdit && <button className="btn-icon" onClick={() => openEdit(t)}><FaEdit /></button>}
                    {canDelete && <button className="btn-icon btn-danger" onClick={() => handleDelete(t.id)}><FaTrash /></button>}
                  </td>
                )}
              </tr>
            ))}
            {items.length === 0 && <tr><td colSpan="11" className="empty">No se encontraron registros</td></tr>}
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
            <h2>{editing ? 'Editar' : 'Nuevo'} Trabajador</h2>
            {error && <div className="form-error">{error}</div>}
            <form onSubmit={handleSave}>
              <div className="form-grid">
                <label>DNI<input value={form.dni} onChange={(e) => setForm({ ...form, dni: e.target.value })} required /></label>
                <label>Código Universitario<input value={form.codigo_universitario || ''} onChange={(e) => setForm({ ...form, codigo_universitario: e.target.value })} /></label>
                <label>Nombres<input value={form.nombres} onChange={(e) => setForm({ ...form, nombres: e.target.value })} required /></label>
                <label>Apellidos<input value={form.apellidos} onChange={(e) => setForm({ ...form, apellidos: e.target.value })} required /></label>
                <label>Empresa<input value={form.empresa || ''} onChange={(e) => setForm({ ...form, empresa: e.target.value })} /></label>
                <label>Área<input value={form.area || ''} onChange={(e) => setForm({ ...form, area: e.target.value })} /></label>
                <label>Dependencia<input value={form.dependencia || ''} onChange={(e) => setForm({ ...form, dependencia: e.target.value })} /></label>
                <label>Cargo<input value={form.cargo || ''} onChange={(e) => setForm({ ...form, cargo: e.target.value })} /></label>
                <label>Teléfono<input value={form.telefono || ''} onChange={(e) => setForm({ ...form, telefono: e.target.value })} /></label>
                <label>Fecha Ingreso<input type="date" value={form.fecha_ingreso || ''} onChange={(e) => setForm({ ...form, fecha_ingreso: e.target.value })} /></label>
                <label>Régimen<input value={form.regimen || ''} onChange={(e) => setForm({ ...form, regimen: e.target.value })} /></label>
                <label>Facultad<input value={form.facultad || ''} onChange={(e) => setForm({ ...form, facultad: e.target.value })} /></label>
                <label>Escuela Profesional<input value={form.escuela_profesional || ''} onChange={(e) => setForm({ ...form, escuela_profesional: e.target.value })} /></label>
                <label>Resolución Rectoral<input value={form.resolucion_rectoral || ''} onChange={(e) => setForm({ ...form, resolucion_rectoral: e.target.value })} /></label>
                <label>Vigencia<input value={form.vigencia || ''} onChange={(e) => setForm({ ...form, vigencia: e.target.value })} /></label>
                <label>Fecha Emisión<input type="date" value={form.fecha_emision || ''} onChange={(e) => setForm({ ...form, fecha_emision: e.target.value })} /></label>
                <label>Estado<select value={form.estado} onChange={(e) => setForm({ ...form, estado: e.target.value })}><option>ACTIVO</option><option>INACTIVO</option><option>SUSPENDIDO</option></select></label>
              </div>

              <div className="form-section-title">Correos</div>
              <div className="correos-input-section">
                <div className="correo-add-row">
                  <select value={nuevoCorreoTipo} onChange={(e) => setNuevoCorreoTipo(e.target.value)}>
                    <option value="PERSONAL">Personal</option>
                    <option value="INSTITUCIONAL">Institucional</option>
                    <option value="ALTERNATIVO">Alternativo</option>
                  </select>
                  <input
                    type="email"
                    value={nuevoCorreo}
                    onChange={(e) => setNuevoCorreo(e.target.value)}
                    onKeyDown={handleCorreoKeyDown}
                    placeholder="Agregar correo electronico..."
                  />
                  <button type="button" className="btn-secondary btn-sm" onClick={addCorreo}>
                    <FaPlus /> Agregar
                  </button>
                </div>
                {form.correos.length > 0 && (
                  <div className="correos-edit-list">
                    {form.correos.map((c, i) => (
                      <div key={i} className="correo-edit-item">
                        <span className={`correo-badge ${c.principal ? 'correo-principal' : ''}`}>
                          {c.correo}
                          <small className="correo-tipo-badge">{c.tipo}</small>
                          {c.principal && <small> (Principal)</small>}
                        </span>
                        <button type="button" className="btn-icon btn-danger btn-sm" onClick={() => removeCorreo(i)}>
                          <FaTimes />
                        </button>
                      </div>
                    ))}
                  </div>
                )}
              </div>

              <div className="form-section-title">URLs</div>
              <div className="form-grid">
                <label>URL Foto Presencial<input value={form.url_foto_presencial || ''} onChange={(e) => setForm({ ...form, url_foto_presencial: e.target.value })} placeholder="https://..." /></label>
                <label>URL Foto Virtual<input value={form.url_foto_virtual || ''} onChange={(e) => setForm({ ...form, url_foto_virtual: e.target.value })} placeholder="https://..." /></label>
                <label>URL QR Image<input value={form.url_qr_image || ''} onChange={(e) => setForm({ ...form, url_qr_image: e.target.value })} placeholder="https://..." /></label>
                <label>URL QR<input value={form.url_qr || ''} onChange={(e) => setForm({ ...form, url_qr: e.target.value })} placeholder="https://..." /></label>
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
