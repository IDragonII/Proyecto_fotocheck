import { useEffect, useState } from 'react';
import { api } from '../services/api';
import './CrudPage.css';

export default function Logs() {
  const [items, setItems] = useState([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);

  const load = (p = 1) => {
    api.get(`/logs?page=${p}`).then((res) => {
      setItems(res.data);
      setPage(res.current_page);
      setLastPage(res.last_page);
    });
  };

  useEffect(() => { load(); }, []);

  return (
    <div className="crud-page">
      <div className="page-header">
        <h1>Logs de Auditoria</h1>
      </div>

      <div className="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Usuario</th>
              <th>Accion</th>
              <th>Tabla</th>
              <th>Detalle</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody>
            {items.map((l) => (
              <tr key={l.id}>
                <td data-label="Fecha">{new Date(l.fecha).toLocaleString()}</td>
                <td data-label="Usuario">{l.usuario?.usuario || '-'}</td>
                <td data-label="Accion">{l.accion || '-'}</td>
                <td data-label="Tabla">{l.tabla_afectada || '-'}</td>
                <td data-label="Detalle" className="log-detalle">{l.detalle || '-'}</td>
                <td data-label="IP">{l.ip || '-'}</td>
              </tr>
            ))}
            {items.length === 0 && <tr><td colSpan="6" className="empty">No hay registros</td></tr>}
          </tbody>
        </table>
      </div>

      <div className="pagination">
        <button disabled={page <= 1} onClick={() => load(page - 1)}>Anterior</button>
        <span>Pagina {page} de {lastPage}</span>
        <button disabled={page >= lastPage} onClick={() => load(page + 1)}>Siguiente</button>
      </div>
    </div>
  );
}
