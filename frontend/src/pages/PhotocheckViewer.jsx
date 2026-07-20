import { useEffect, useState, useRef } from 'react';
import { useParams } from 'react-router-dom';
import { FaSyncAlt, FaWhatsapp, FaPhone, /* FaSms, */ FaEnvelope, FaAddressCard, /* FaMapMarkerAlt, FaGlobe, */ FaShareAlt } from 'react-icons/fa';
import logoUrl from '../assets/logo.png';
import firmaUrl from '../assets/firma.png';
import marcaAguaUrl from '../assets/marca_agua.png';
import { proxyImageUrl } from '../services/api';
import JsBarcode from 'jsbarcode';
import './PhotocheckViewer.css';

const API_URL = import.meta.env.VITE_API_URL;

const isMobileDevice = () => /Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

function loadImage(src) {
  return new Promise((resolve) => {
    if (!src) return resolve(null);
    const img = new Image();
    img.onload = () => resolve(img);
    img.onerror = () => resolve(null);
    img.src = src;
  });
}

const openWhatsApp = (telefono) => {
  const clean = telefono.replace(/^51/, '').replace(/\D/g, '');
  const url = `https://wa.me/51${clean}`;
  window.open(url, '_blank', 'noopener,noreferrer');
};

const openCall = (telefono) => {
  const clean = telefono.replace(/\D/g, '');
  window.location.href = `tel:+${clean.startsWith('51') ? clean : '51' + clean}`;
};

// const openSms = (telefono) => {
//   const clean = telefono.replace(/\D/g, '');
//   window.location.href = `sms:+${clean.startsWith('51') ? clean : '51' + clean}`;
// };

const openEmail = (correo) => {
  if (!correo) return;
  window.location.href = `mailto:${correo}`;
};

const downloadContact = (t) => {
  const nombre = t.nombre_completo || `${t.nombres} ${t.apellidos}`;
  const phone = (t.telefono || '').replace(/\D/g, '');
  const phoneFormat = phone.startsWith('51') ? phone : '51' + phone;
  const vcard = [
    'BEGIN:VCARD',
    'VERSION:3.0',
    `FN:${nombre}`,
    `N:${t.apellidos || ''};${t.nombres || ''};;;`,
    phoneFormat ? `TEL;TYPE=CELL:+${phoneFormat}` : '',
    t.correo ? `EMAIL:${t.correo}` : '',
    t.direccion ? `ADR;TYPE=WORK:;;${t.direccion};;;;` : '',
    t.empresa ? `ORG:${t.empresa}` : '',
    t.cargo ? `TITLE:${t.cargo}` : '',
    'END:VCARD',
  ].filter(Boolean).join('\r\n');
  const blob = new Blob([vcard], { type: 'text/vcard;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `${nombre.replace(/\s+/g, '_')}.vcf`;
  a.click();
  URL.revokeObjectURL(url);
};

// const openLocation = () => {
//   if (!navigator.geolocation) {
//     alert('La geolocalización no está soportada en este navegador.');
//     return;
//   }
//   navigator.geolocation.getCurrentPosition(
//     (pos) => {
//       const { latitude, longitude } = pos.coords;
//       window.open(`https://www.google.com/maps?q=${latitude},${longitude}`, '_blank', 'noopener,noreferrer');
//     },
//     () => {
//       alert('No se pudo obtener la ubicación. Verifique los permisos del navegador.');
//     }
//   );
// };

// const openWebsite = () => {
//   window.open('https://oti.unap.edu.pe/', '_blank', 'noopener,noreferrer');
// };

const sharePhotocheck = async (t) => {
  const nombre = t.nombre_completo || `${t.nombres} ${t.apellidos}`;
  const url = window.location.href;
  if (navigator.share) {
    try {
      await navigator.share({ title: `Credencial - ${nombre}`, text: `Credencial de ${nombre}`, url });
    } catch { /* usuario canceló */ }
  } else {
    try {
      await navigator.clipboard.writeText(url);
      alert('Enlace copiado al portapapeles');
    } catch {
      prompt('Copia el enlace:', url);
    }
  }
};

export default function PhotocheckViewer() {
  const { codigo } = useParams();
  const [flipped, setFlipped] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [data, setData] = useState(null);
  const [fotoUrl, setFotoUrl] = useState(null);
  const [qrUrl, setQrUrl] = useState(null); // eslint-disable-line no-unused-vars
  const [firmaImg, setFirmaImg] = useState(null);
  const [isMobile] = useState(isMobileDevice);
  const barcodeCanvasRef = useRef(null);

  const generateBarcode = (dni) => {
    if (barcodeCanvasRef.current && dni) {
      try {
        const container = barcodeCanvasRef.current;
        container.innerHTML = '';
        const canvas = document.createElement('canvas');
        canvas.width = 200;
        canvas.height = 40;
        container.appendChild(canvas);
        JsBarcode(canvas, dni, {
          format: 'CODE128',
          displayValue: false,
          height: 32,
          width: 1.5,
          margin: 0,
        });
      } catch (e) {
        console.error('Error generating barcode:', e);
      }
    }
  };

  useEffect(() => {
    if (data?.trabajador?.dni) {
      requestAnimationFrame(() => generateBarcode(data.trabajador.dni));
    }
  }, [data]);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const res = await fetch(`${API_URL}/public/fotocheck/${codigo}`, { cache: 'no-store' });
        if (!res.ok)         throw new Error('Credencial no encontrada');
        const json = await res.json();
        if (cancelled) return;
        setData(json);
        const fotoSrc = json.trabajador.url_foto_presencial || json.trabajador.url_foto_virtual;
        const [foto, firma] = await Promise.all([
          loadImage(fotoSrc ? proxyImageUrl(fotoSrc) : null),
          loadImage(firmaUrl),
        ]);
        if (!cancelled) {
          setFotoUrl(foto);
          setQrUrl(json.fotocheck.url_qr || null);
          setFirmaImg(firma);
          setLoading(false);
        }
      } catch (err) {
        if (!cancelled) { setError(err.message); setLoading(false); }
      }
    })();
    return () => { cancelled = true; };
  }, [codigo]);

  if (loading && !error) {
    return (
      <div className="pcv-container">
        <div className="pcv-overlay"><div className="pcv-spinner" /><p>Cargando credencial...</p></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="pcv-container">
        <div className="pcv-overlay">
          <div className="pcv-error"><h2>No se encontro la credencial</h2><p>{error}</p></div>
        </div>
      </div>
    );
  }

  const t = data.trabajador;
  const f = data.fotocheck;
  const nombre = t.nombre_completo || `${t.nombres} ${t.apellidos}`;
  const phone = (t.telefono || '').replace(/^51/, '');

  return (
    <div className="pcv-container">
      <div className="pcv-main">
        <div className={`pcv-card ${flipped ? 'pcv-flipped' : ''}`}>
          <div className="pcv-face pcv-front">
            <img src={marcaAguaUrl} alt="" className="pcv-watermark" />
            <div className="pcv-blue-strip" />
            <div className="pcv-barcode-top" ref={barcodeCanvasRef} />
            <div className="pcv-front-header">
              <img src={logoUrl} alt="UNA" className="pcv-logo" />
              <div className="pcv-header-right">
                <div className="pcv-university">
                  <span>UNIVERSIDAD</span>
                  <span>NACIONAL DEL</span>
                  <span>ALTIPLANO</span>
                  <small>PUNO - PERÚ</small>
                </div>
              </div>
            </div>
            <div className="pcv-front-body">
              <div className="pcv-photo-frame">
                {fotoUrl ? (
                  <img src={fotoUrl.src || fotoUrl} alt={nombre} className="pcv-photo" />
                ) : (
                  <div className="pcv-photo-placeholder">{nombre.split(' ').filter(Boolean).slice(0, 2).map(s => s[0]).join('')}</div>
                )}
              </div>
            </div>
            <div className="pcv-front-footer">
              <div className="pcv-divider" />
              <h2 className="pcv-name">{nombre}</h2>
              <div className="pcv-divider" />
              <p className="pcv-cargo">{t.cargo || t.area || 'DOCENTE'}</p>
            </div>
            <div className="pcv-front-bottom-bar">
              <span className="pcv-code">{t.codigo_universitario || '0000000'}</span>
            </div>
          </div>

          <div className="pcv-face pcv-back">
            <div className="pcv-back-header">
              <div className="pcv-dni-tag">DNI</div>
              <span className="pcv-dni-number">{t.dni || '--------'}</span>
            </div>
            <div className="pcv-back-body">
              <div className="pcv-back-blue-strip" />
              <div className="pcv-back-content">
                <section className="pcv-section">
                  <h4>Contacto</h4>
                  <div className="pcv-info-row"><span>Email</span><span>{t.correo || '-'}</span></div>
                  <div className="pcv-info-row"><span>Teléfono</span><span>{phone || '-'}</span></div>
                </section>
                <section className="pcv-section">
                  <h4>Información Laboral</h4>
                  <div className="pcv-info-row"><span>Régimen</span><span>{t.regimen || 'Ley Nro. 30057 - Nombrado'}</span></div>
                  {t.cargo && t.cargo.toLowerCase().includes('docente') ? (
                    <>
                      <div className="pcv-info-row"><span>Facultad</span><span>{t.facultad || '-'}</span></div>
                      <div className="pcv-info-row"><span>E.P.</span><span>{t.escuela_profesional || '-'}</span></div>
                    </>
                  ) : (
                    <div className="pcv-info-row"><span>Dependencia</span><span>{t.dependencia || '-'}</span></div>
                  )}
                  <div className="pcv-info-row"><span>Cargo</span><span>{t.cargo || '-'}</span></div>
                  <div className="pcv-info-row"><span>F. Ingreso</span><span>{t.fecha_ingreso ? new Date(t.fecha_ingreso).toLocaleDateString() : '-'}</span></div>
                  <div className="pcv-info-row"><span>R.R.</span><span>{t.resolucion_rectoral || '-'}</span></div>
                  <div className="pcv-info-row"><span>Vigencia</span><span>{t.vigencia || '-'}</span></div>
                  <div className="pcv-info-row"><span>F. Emisión</span><span>{t.fecha_emision ? new Date(t.fecha_emision).toLocaleDateString() : '-'}</span></div>
                </section>
                <div className="pcv-firma-section">
                  {firmaImg && (
                    <div className="pcv-firma-img-wrap">
                      <img src={firmaUrl} alt="Firma" className="pcv-firma-img" />
                    </div>
                  )}
                  <span className="pcv-firma-nombre">{f?.firmante_nombre || 'Dr. Paulino Machaca Ari'}</span>
                  <span className="pcv-firma-cargo">{f?.firmante_cargo || 'RECTOR'}</span>
                </div>
              </div>
            </div>
            <div className="pcv-back-footer">
              www.unap.edu.pe
            </div>
          </div>
        </div>

        <button className="pcv-toggle" onClick={() => setFlipped(!flipped)}>
          <FaSyncAlt /> {flipped ? 'Ver Anverso' : 'Ver Reverso'}
        </button>
      </div>

      <div className="pcv-actions">
        <button className="pcv-action-btn pcv-btn-whatsapp" onClick={() => openWhatsApp(t.telefono || '')} title="WhatsApp" aria-label="Abrir WhatsApp">
          <FaWhatsapp />
        </button>
        {isMobile && (
          <>
            <button className="pcv-action-btn pcv-btn-call" onClick={() => openCall(t.telefono || '')} title="Llamar" aria-label="Llamar">
              <FaPhone className="pcv-icon-rotate" />
            </button>
            <button className="pcv-action-btn pcv-btn-contact" onClick={() => downloadContact(t)} title="Agregar contacto" aria-label="Agregar contacto">
              <FaAddressCard />
            </button>
          </>
        )}
        <button className="pcv-action-btn pcv-btn-email" onClick={() => openEmail(t.correo)} title="Enviar Email" aria-label="Enviar Email">
          <FaEnvelope />
        </button>
        <button className="pcv-action-btn pcv-btn-share" onClick={() => sharePhotocheck(t)} title="Compartir" aria-label="Compartir">
          <FaShareAlt />
        </button>
      </div>
    </div>
  );
}