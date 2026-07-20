import { useEffect, useState } from 'react';
import { api } from '../services/api';
import './CrudPage.css';

export default function AccesosQr() {
  const [items, setItems] = useState([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [buscar, setBuscar] = useState('');

  const load = (p = 1) => {
    api.get(`/accesos-qr?page=${p}&buscar=${buscar}`).then((res) => {
      setItems(res.data);
      setPage(res.current_page);
      setLastPage(res.last_page);
    });
  };

  useEffect(() => { load(); }, []); // eslint-disable-line react-hooks/exhaustive-deps

  const handleSearch = (e) => {
    e.preventDefault();
    load();
  };

  return (
    <div className="crud-page">
      <div className="page-header">
        <h1>Accesos QR</h1>
      </div>

      <form className="search-bar" onSubmit={handleSearch}>
        <input placeholder="Buscar por nombre o DNI del trabajador..." value={buscar} onChange={(e) => setBuscar(e.target.value)} />
        <button type="submit">Buscar</button>
      </form>

      <div className="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Trabajador</th>
              <th>DNI</th>
              <th>IP</th>
              <th>Navegador</th>
            </tr>
          </thead>
          <tbody>
            {items.map((a) => (
              <tr key={a.id}>
                <td data-label="Fecha">{new Date(a.fecha_acceso).toLocaleString()}</td>
                <td data-label="Trabajador">{a.trabajador?.persona?.nombres} {a.trabajador?.persona?.apellidos}</td>
                <td data-label="DNI">{a.trabajador?.persona?.dni}</td>
                <td data-label="IP">{a.ip || '-'}</td>
                <td data-label="Navegador" className="log-detalle">{a.navegador || '-'}</td>
              </tr>
            ))}
            {items.length === 0 && <tr><td colSpan="5" className="empty">No hay registros de accesos</td></tr>}
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
