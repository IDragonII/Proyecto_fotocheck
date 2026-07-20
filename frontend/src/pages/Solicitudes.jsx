import { useEffect, useState } from 'react';
import { api } from '../services/api';
import { hasPermission } from '../services/authService';
import { useToast } from '../components/Toast';
import { FaEye, FaCodeBranch, FaCheck, FaTimes, FaFileDownload, FaEdit } from 'react-icons/fa';
import './CrudPage.css';

const ESTADOS = ['PENDIENTE', 'EN_PROCESO', 'RESUELTO', 'RECHAZADO'];

const ESTADO_COLORS = {
  PENDIENTE: '#f59e0b',
  EN_PROCESO: '#3b82f6',
  RESUELTO: '#10b981',
  RECHAZADO: '#ef4444',
};

export default function Solicitudes() {
  const { toast } = useToast();
  const [items, setItems] = useState([]);
  const [tipos, setTipos] = useState([]);
  const [oficinas, setOficinas] = useState([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [buscar, setBuscar] = useState('');
  const [filtroEstado, setFiltroEstado] = useState('');
  const [filtroTipo, setFiltroTipo] = useState('');
  const [showDetail, setShowDetail] = useState(false);
  const [selected, setSelected] = useState(null);
  const [showDerivar, setShowDerivar] = useState(false);
  const [derivarItem, setDerivarItem] = useState(null);
  const [derivarForm, setDerivarForm] = useState({ oficina_destino_id: '', motivo: '' });
  const [showResolver, setShowResolver] = useState(false);
  const [resolverItem, setResolverItem] = useState(null);
  const [resolverForm, setResolverForm] = useState({ respuesta: '' });
  const [showRechazar, setShowRechazar] = useState(false);
  const [rechazarItem, setRechazarItem] = useState(null);
  const [rechazarForm, setRechazarForm] = useState({ respuesta: '' });
  const [error, setError] = useState('');
  const [attModal, setAttModal] = useState({ show: false, url: '', type: '', filename: '' });
  const [editMotivo, setEditMotivo] = useState(null);
  const [nuevoMotivo, setNuevoMotivo] = useState('');

  const canEdit = hasPermission('solicitudes_editar');

  const load = (p = 1) => {
    let url = `/solicitudes?page=${p}&buscar=${buscar}`;
    if (filtroEstado) url += `&estado=${filtroEstado}`;
    if (filtroTipo) url += `&tipo_solicitud_id=${filtroTipo}`;
    api.get(url).then((res) => {
      setItems(res.data);
      setPage(res.current_page);
      setLastPage(res.last_page);
    });
  };

  useEffect(() => {
    load();
    api.get('/tipo-solicitudes?buscar=').then((res) => setTipos(res.data || res));
    api.get('/oficinas?buscar=').then((res) => setOficinas(res.data || res));
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const handleSearch = (e) => {
    e.preventDefault();
    load();
  };

  const handleFilterChange = (setter) => (e) => {
    setter(e.target.value);
    setTimeout(() => load(), 0);
  };

  const openDetail = async (item) => {
    const data = await api.get(`/solicitudes/${item.id}`);
    setSelected(data);
    setShowDetail(true);
    setError('');
  };

  const openDerivar = (item) => {
    setDerivarItem(item);
    setDerivarForm({ oficina_destino_id: '', motivo: '' });
    setShowDerivar(true);
    setError('');
  };

  const handleDerivar = async () => {
    if (!derivarForm.oficina_destino_id) {
      setError('Seleccione una oficina destino');
      return;
    }
    try {
      await api.post(`/solicitudes/${derivarItem.id}/derivar`, derivarForm);
      setShowDerivar(false);
      setDerivarItem(null);
      setDerivarForm({ oficina_destino_id: '', motivo: '' });
      toast.success('Ticket derivado');
      load(page);
    } catch (err) {
      setError(err.message);
    }
  };

  const openResolver = (item) => {
    setResolverItem(item);
    setResolverForm({ respuesta: '' });
    setShowResolver(true);
    setError('');
  };

  const handleResolverSubmit = async () => {
    if (!resolverForm.respuesta.trim()) {
      setError('Ingrese una respuesta');
      return;
    }
    try {
      await api.post(`/solicitudes/${resolverItem.id}/resolver`, resolverForm);
      setShowResolver(false);
      setResolverItem(null);
      setResolverForm({ respuesta: '' });
      toast.success('Ticket resuelto con respuesta');
      load(page);
    } catch (err) {
      setError(err.message);
    }
  };

  const openRechazar = (item) => {
    setRechazarItem(item);
    setRechazarForm({ respuesta: '' });
    setShowRechazar(true);
    setError('');
  };

  const handleRechazarSubmit = async () => {
    if (!rechazarForm.respuesta.trim()) {
      setError('Ingrese un motivo de rechazo');
      return;
    }
    try {
      await api.post(`/solicitudes/${rechazarItem.id}/rechazar`, rechazarForm);
      setShowRechazar(false);
      setRechazarItem(null);
      setRechazarForm({ respuesta: '' });
      toast.success('Ticket rechazado con motivo');
      load(page);
    } catch (err) {
      setError(err.message);
    }
  };

  const openAttachment = async (solicitudId, filename) => {
    try {
      const { url, type } = await api.getFile(`/solicitudes/${solicitudId}/adjuntos/${filename}`);
      setAttModal({ show: true, url, type, filename });
    } catch (err) {
      toast.error(err.message);
    }
  };

  const closeAttachment = () => {
    if (attModal.url) URL.revokeObjectURL(attModal.url);
    setAttModal({ show: false, url: '', type: '', filename: '' });
  };

  const formatearFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
  };

  const openEditarMotivo = (s) => {
    setEditMotivo(s);
    setNuevoMotivo(s.motivo_solicitud || '');
  };

  const handleGuardarMotivo = async () => {
    try {
      await api.put(`/solicitudes/${editMotivo.id}`, { motivo_solicitud: nuevoMotivo });
      toast.success('Motivo actualizado');
      setEditMotivo(null);
      setNuevoMotivo('');
      load(page);
    } catch (err) {
      toast.error(err.message);
    }
  };

  const esTipoCuenta = (tipoNombre) => {
    return tipoNombre === 'SOLICITUD DE ALTA Y BAJA';
  };

  const esTipoCorreo = (tipoNombre) => {
    return tipoNombre === 'SOLICITUD DE CORREO';
  };

  const esTipoSoporte = (tipoNombre) => {
    return tipoNombre === 'SOPORTE TECNICO';
  };

  return (
    <div className="crud-page">
      <div className="page-header">
        <h1>Tickets</h1>
      </div>

      <form className="search-bar" onSubmit={handleSearch}>
        <input placeholder="Buscar por codigo o DNI..." value={buscar} onChange={(e) => setBuscar(e.target.value)} />
        <select value={filtroEstado} onChange={handleFilterChange(setFiltroEstado)}>
          <option value="">Todos los estados</option>
          {ESTADOS.map((e) => <option key={e} value={e}>{e}</option>)}
        </select>
        <select value={filtroTipo} onChange={handleFilterChange(setFiltroTipo)}>
          <option value="">Todos los tipos</option>
          {tipos.map((t) => <option key={t.id} value={t.id}>{t.nombre}</option>)}
        </select>
        <button type="submit">Buscar</button>
      </form>

      <div className="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Codigo</th>
              <th>Persona</th>
              <th>Motivo</th>
              <th>Correo Personal</th>
              <th>Correo Institucional</th>
              <th>Oficina</th>
              <th>Estado</th>
              <th>Vinculo</th>
              <th>Fecha</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            {items.map((s) => (
              <tr key={s.id}>
                <td data-label="Codigo"><code>{s.codigo}</code></td>
                <td data-label="Persona">{s.persona?.nombres} {s.persona?.apellidos}</td>
                <td data-label="Motivo">{s.motivo_solicitud ? `${s.tipo_solicitud?.nombre || ''} - ${s.motivo_solicitud}` : s.tipo_solicitud?.nombre || '-'}</td>
                <td data-label="Correo Personal">{s.persona?.correos?.find(c => c.tipo === 'PERSONAL')?.correo || '-'}</td>
                <td data-label="Correo Institucional">{s.persona?.correos?.find(c => c.tipo === 'INSTITUCIONAL')?.correo || '-'}</td>
                <td data-label="Oficina">{s.oficina_actual?.nombre || '-'}</td>
                <td data-label="Estado">
                  <span className="badge" style={{ background: ESTADO_COLORS[s.estado], color: '#fff', padding: '2px 8px', borderRadius: 4, fontSize: '0.8em' }}>
                    {s.estado}
                  </span>
                </td>
                <td data-label="Vinculo">{s.vinculo || '-'}</td>
                <td data-label="Fecha">{formatearFecha(s.fecha_solicitud)}</td>
                <td data-label="Acciones" className="actions">
                  <button className="btn-icon" title="Ver detalle" onClick={() => openDetail(s)}><FaEye /></button>
                  {canEdit && s.estado !== 'RESUELTO' && s.estado !== 'RECHAZADO' && (
                    <>
                      <button className="btn-icon" title="Editar motivo" style={{ color: '#8b5cf6', borderColor: '#8b5cf6' }} onClick={() => openEditarMotivo(s)}><FaEdit /></button>
                      <button className="btn-icon" title="Derivar" style={{ color: '#3b82f6', borderColor: '#3b82f6' }} onClick={() => openDerivar(s)}><FaCodeBranch /></button>
                      <button className="btn-icon" title="Resolver" style={{ color: '#10b981', borderColor: '#10b981' }} onClick={() => openResolver(s)}><FaCheck /></button>
                      <button className="btn-icon btn-danger" title="Rechazar" onClick={() => openRechazar(s)}><FaTimes /></button>
                    </>
                  )}
                </td>
              </tr>
            ))}
            {items.length === 0 && <tr><td colSpan="10" className="empty">No se encontraron tickets</td></tr>}
          </tbody>
        </table>
      </div>

      <div className="pagination">
        <button disabled={page <= 1} onClick={() => load(page - 1)}>Anterior</button>
        <span>Pagina {page} de {lastPage}</span>
        <button disabled={page >= lastPage} onClick={() => load(page + 1)}>Siguiente</button>
      </div>

      {showDetail && selected && (
        <div className="modal-overlay" onClick={() => { setShowDetail(false); }}>
          <div className="modal modal-lg" onClick={(e) => e.stopPropagation()}>
            <h2>Detalle del Ticket</h2>

            <div className="form-grid" style={{ marginBottom: 16 }}>
              <label>Codigo<code>{selected.codigo}</code></label>
              <label>Estado<span className="badge" style={{ background: ESTADO_COLORS[selected.estado], color: '#fff', padding: '2px 8px', borderRadius: 4, marginLeft: 8 }}>{selected.estado}</span></label>
              <label>Vinculo<div>{selected.vinculo || '-'}</div></label>
              <label>Tipo de Solicitud<div>{selected.tipo_solicitud?.nombre}</div></label>
              <label>Oficina Actual<div>{selected.oficina_actual?.nombre || '-'}</div></label>
              <label>Persona<div>{selected.persona?.nombres} {selected.persona?.apellidos} (DNI: {selected.persona?.dni})</div></label>
              <label>Correo Personal<div>{selected.correo_personal || '-'}</div></label>
              <label>Fecha Solicitud<div>{formatearFecha(selected.fecha_solicitud)}</div></label>
              <label>Fecha Atencion<div>{formatearFecha(selected.fecha_atencion)}</div></label>
              <label>Atendido por<div>{selected.atendido_por?.nombres || '-'}</div></label>
              <label style={{ gridColumn: '1 / -1' }}>Observaciones<div>{selected.observaciones || '-'}</div></label>
              {esTipoCuenta(selected.tipo_solicitud?.nombre) && (
                <>
                  <label>Motivo<div>{selected.motivo_solicitud || '-'}</div></label>
                  <label>Tipo de Cuenta<div>{selected.tipo_cuenta || '-'}</div></label>
                  <label>Sistema Específico<div>{selected.sistema_especifico || '-'}</div></label>
                  <label>Usuario Creado<div>{selected.usuario_creado ? 'Sí' : selected.usuario_creado === false ? 'No' : '-'}</div></label>
                </>
              )}
              {esTipoCorreo(selected.tipo_solicitud?.nombre) && (
                <>
                  <label>Correo Personal<div>{selected.correo_personal || '-'}</div></label>
                  <label>Motivo<div>{selected.motivo_solicitud || '-'}</div></label>
                </>
              )}
              {esTipoSoporte(selected.tipo_solicitud?.nombre) && (
                <>
                  <label>Oficina Soporte<div>{selected.oficina_sopporte || '-'}</div></label>
                  <label>Dificultad<div>{selected.dificultad || '-'}</div></label>
                  <label>Mensaje<div>{selected.observaciones || '-'}</div></label>
                </>
              )}
              {selected.adjuntos && Array.isArray(selected.adjuntos) && selected.adjuntos.length > 0 && (
                <label style={{ gridColumn: '1 / -1' }}>
                  Adjuntos
                  <div style={{ display: 'flex', flexDirection: 'column', gap: 6, marginTop: 4 }}>
                    {selected.adjuntos.map((archivo, idx) => {
                      const nombre = archivo.split('/').pop();
                      return (
                        <button
                          key={idx}
                          onClick={() => openAttachment(selected.id, nombre)}
                          style={{ display: 'flex', alignItems: 'center', gap: 6, color: '#3b82f6', background: 'none', border: 'none', cursor: 'pointer', fontSize: '0.9em', padding: 0, textAlign: 'left' }}
                        >
                          <FaFileDownload /> {nombre}
                        </button>
                      );
                    })}
                  </div>
                </label>
              )}
              {selected.adjuntos && typeof selected.adjuntos === 'string' && (
                <label style={{ gridColumn: '1 / -1' }}>Adjuntos<div style={{ wordBreak: 'break-all' }}>{selected.adjuntos}</div></label>
              )}
            </div>

            {selected.derivaciones?.length > 0 && (
              <div style={{ marginBottom: 16 }}>
                <strong>Historial de Derivaciones</strong>
                <table style={{ width: '100%', marginTop: 8, fontSize: '0.85em' }}>
                  <thead>
                    <tr>
                      <th>Fecha</th>
                      <th>Origen</th>
                      <th>Destino</th>
                      <th>Derivado por</th>
              <th>Motivo</th>
              <th>Correo Personal</th>
              <th>Correo Institucional</th>
                    </tr>
                  </thead>
                  <tbody>
                    {selected.derivaciones.map((d) => (
                      <tr key={d.id}>
                        <td>{formatearFecha(d.fecha)}</td>
                        <td>{d.oficina_origen?.nombre || '-'}</td>
                        <td>{d.oficina_destino?.nombre}</td>
                        <td>{d.derivado_por?.nombres || '-'}</td>
                        <td>{d.motivo || '-'}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}

            <div className="form-actions">
              <button className="btn-secondary" onClick={() => { setShowDetail(false); }}>Cerrar</button>
            </div>
          </div>
        </div>
      )}

      {showDerivar && derivarItem && (
        <div className="modal-overlay" onClick={() => { setShowDerivar(false); setDerivarItem(null); }}>
          <div className="modal" onClick={(e) => e.stopPropagation()}>
            <h2>Derivar Ticket</h2>
            <p style={{ color: 'var(--text-muted, #666)', marginBottom: 12, fontSize: '0.9em' }}>
              Ticket: <code>{derivarItem.codigo}</code> — Oficina actual: {derivarItem.oficina_actual?.nombre || '-'}
            </p>
            {error && <div className="form-error">{error}</div>}
            <div className="form-grid">
              <label>
                Oficina destino
                <select value={derivarForm.oficina_destino_id} onChange={(e) => setDerivarForm({ ...derivarForm, oficina_destino_id: e.target.value })} required>
                  <option value="">Seleccionar...</option>
                  {oficinas.filter((o) => o.id !== derivarItem.oficina_actual_id).map((o) => (
                    <option key={o.id} value={o.id}>{o.nombre}</option>
                  ))}
                </select>
              </label>
              <label style={{ gridColumn: '1 / -1' }}>
                Motivo
                <textarea value={derivarForm.motivo} onChange={(e) => setDerivarForm({ ...derivarForm, motivo: e.target.value })} rows={2} placeholder="Motivo de la derivacion..." />
              </label>
            </div>
            <div className="form-actions">
              <button className="btn-secondary" onClick={() => { setShowDerivar(false); setDerivarItem(null); setError(''); }}>Cancelar</button>
              <button className="btn-primary" onClick={handleDerivar}>Derivar</button>
            </div>
          </div>
        </div>
      )}

      {showResolver && resolverItem && (
        <div className="modal-overlay" onClick={() => { setShowResolver(false); setResolverItem(null); setResolverForm({ respuesta: '' }); }}>
          <div className="modal" onClick={(e) => e.stopPropagation()}>
            <h2>Resolver Ticket</h2>
            <p style={{ color: 'var(--text-muted, #666)', marginBottom: 12, fontSize: '0.9em' }}>
              Ticket: <code>{resolverItem.codigo}</code>
            </p>
            {error && <div className="form-error">{error}</div>}
            <div className="form-grid">
              <label style={{ gridColumn: '1 / -1' }}>
                Respuesta / Comentario
                <textarea value={resolverForm.respuesta} onChange={(e) => setResolverForm({ ...resolverForm, respuesta: e.target.value })} rows={4} required placeholder="Escriba la respuesta para el solicitante..." />
              </label>
            </div>
            <div className="form-actions">
              <button className="btn-secondary" onClick={() => { setShowResolver(false); setResolverItem(null); setResolverForm({ respuesta: '' }); setError(''); }}>Cancelar</button>
              <button className="btn-primary" style={{ background: '#10b981', borderColor: '#10b981' }} onClick={handleResolverSubmit}>Resolver</button>
            </div>
          </div>
        </div>
      )}

      {showRechazar && rechazarItem && (
        <div className="modal-overlay" onClick={() => { setShowRechazar(false); setRechazarItem(null); setRechazarForm({ respuesta: '' }); }}>
          <div className="modal" onClick={(e) => e.stopPropagation()}>
            <h2>Rechazar Ticket</h2>
            <p style={{ color: 'var(--text-muted, #666)', marginBottom: 12, fontSize: '0.9em' }}>
              Ticket: <code>{rechazarItem.codigo}</code>
            </p>
            {error && <div className="form-error">{error}</div>}
            <div className="form-grid">
              <label style={{ gridColumn: '1 / -1' }}>
                Motivo del rechazo
                <textarea value={rechazarForm.respuesta} onChange={(e) => setRechazarForm({ ...rechazarForm, respuesta: e.target.value })} rows={4} required placeholder="Explique el motivo del rechazo..." />
              </label>
            </div>
            <div className="form-actions">
              <button className="btn-secondary" onClick={() => { setShowRechazar(false); setRechazarItem(null); setRechazarForm({ respuesta: '' }); setError(''); }}>Cancelar</button>
              <button className="btn-danger" onClick={handleRechazarSubmit}>Rechazar</button>
            </div>
          </div>
        </div>
      )}

      {editMotivo && (() => {
        const MOTIVOS_POR_TIPO = {
          'SOLICITUD DE CORREO': ['CREACION', 'RESTABLECIMIENTO', 'ACTIVACION', 'OTRO'],
          'SOLICITUD DE ALTA Y BAJA': ['CREACION', 'RENOVACION', 'MODIFICACION', 'BAJA'],
        };
        const tipo = editMotivo.tipo_solicitud?.nombre || '';
        const opciones = MOTIVOS_POR_TIPO[tipo] || [];
        return (
          <div className="modal-overlay" onClick={() => { setEditMotivo(null); setNuevoMotivo(''); }}>
            <div className="modal" onClick={(e) => e.stopPropagation()}>
              <h2>Editar Motivo</h2>
              <p style={{ color: 'var(--text-muted, #666)', marginBottom: 12, fontSize: '0.9em' }}>
                Ticket: <code>{editMotivo.codigo}</code> — {tipo}
              </p>
              {opciones.length > 0 ? (
                <div className="form-grid">
                  <label style={{ gridColumn: '1 / -1' }}>
                    Motivo de solicitud
                    <select value={nuevoMotivo} onChange={(e) => setNuevoMotivo(e.target.value)}>
                      <option value="">Sin motivo</option>
                      {opciones.map((o) => <option key={o} value={o}>{o.charAt(0) + o.slice(1).toLowerCase()}</option>)}
                    </select>
                  </label>
                </div>
              ) : (
                <p style={{ color: 'var(--text-muted, #666)' }}>Este tipo de solicitud no tiene motivos editables.</p>
              )}
              <div className="form-actions">
                <button className="btn-secondary" onClick={() => { setEditMotivo(null); setNuevoMotivo(''); }}>Cancelar</button>
                {opciones.length > 0 && <button className="btn-primary" onClick={handleGuardarMotivo}>Guardar</button>}
              </div>
            </div>
          </div>
        );
      })()}

      {attModal.show && (
        <div className="modal-overlay" onClick={closeAttachment}>
          <div className="modal att-modal" onClick={(e) => e.stopPropagation()}>
            <h2>{attModal.filename}</h2>
            <div className="att-preview">
              {attModal.type?.startsWith('image/') ? (
                <img src={attModal.url} alt={attModal.filename} />
              ) : attModal.type === 'application/pdf' ? (
                <iframe src={attModal.url} title={attModal.filename} />
              ) : (
                <div className="att-no-preview">
                  <FaFileDownload size={40} />
                  <p>Vista previa no disponible para este tipo de archivo</p>
                  <a href={attModal.url} download={attModal.filename} className="btn-primary" style={{ textDecoration: 'none' }}>
                    <FaFileDownload /> Descargar
                  </a>
                </div>
              )}
            </div>
            <div className="form-actions">
              <button className="btn-secondary" onClick={closeAttachment}>Cerrar</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
