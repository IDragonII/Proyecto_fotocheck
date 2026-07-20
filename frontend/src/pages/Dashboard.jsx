import { useEffect, useState } from 'react';
import { api } from '../services/api';
import { FaUsers, FaIdCard, FaUserShield, FaQrcode } from 'react-icons/fa';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, PointElement, LineElement, ArcElement, Tooltip, Legend, Filler } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import { Bar, Doughnut } from 'react-chartjs-2';
import './Dashboard.css';

ChartJS.register(CategoryScale, LinearScale, BarElement, PointElement, LineElement, ArcElement, Tooltip, Legend, Filler, ChartDataLabels);

const COLORS = {
  primary: '#3a6fa0',
  accent: '#c9a85a',
  green: '#10b981',
  red: '#ef4444',
  yellow: '#f59e0b',
  purple: '#8b5cf6',
  gray: '#6b7280',
  blue: '#3b82f6',
  teal: '#14b8a6',
  orange: '#f97316',
};

const n = (v) => Number(v) || 0;

const chartDefaults = {
  responsive: true,
  maintainAspectRatio: false,
  animation: { duration: 600 },
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#1a3a5a',
      titleColor: '#ffffff',
      bodyColor: '#cfe0ff',
      borderColor: 'rgba(201,168,90,0.3)',
      borderWidth: 1,
      padding: 12,
      cornerRadius: 8,
    },
    datalabels: {
      color: '#ffffff',
      font: { weight: 'bold', size: 13 },
      formatter: (value) => {
        const ds = value.chart?.data?.datasets?.[0]?.data;
        if (!ds) return '';
        const total = ds.reduce((a, b) => a + n(b), 0);
        if (total === 0) return '';
        const pct = ((n(value) / total) * 100).toFixed(1);
        return pct >= 5 ? `${pct}%` : '';
      },
      display: (ctx) => {
        const ds = ctx.chart?.data?.datasets?.[0]?.data;
        if (!ds) return false;
        const total = ds.reduce((a, b) => a + n(b), 0);
        return total > 0 && (n(ds[ctx.dataIndex]) / total) >= 0.05;
      },
    },
  },
};

const legendOpts = {
  display: true,
  position: 'bottom',
  labels: {
    padding: 16,
    usePointStyle: true,
    pointStyle: 'circle',
    generateLabels: (chart) => {
      const data = chart.data;
      if (!data.labels || !data.datasets.length) return [];
      const total = data.datasets[0].data.reduce((a, b) => a + n(b), 0);
      return data.labels.map((label, i) => {
        const value = n(data.datasets[0].data[i]);
        const pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
        return { text: `${label} (${value} - ${pct}%)`, fillStyle: data.datasets[0].backgroundColor[i], strokeStyle: data.datasets[0].backgroundColor[i], lineWidth: 0, pointStyle: 'circle', index: i };
      });
    },
  },
};

const barDatalabels = {
  color: '#1a3a5a',
  anchor: 'end',
  align: 'end',
  font: { weight: 'bold', size: 12 },
  formatter: (value) => n(value),
};

export default function Dashboard() {
  const [stats, setStats] = useState(null);

  useEffect(() => {
    api.get('/dashboard').then(setStats);
  }, []);

  if (!stats) return <p>Cargando...</p>;

  const cards = [
    { label: 'Trabajadores', value: stats.totalTrabajadores, sub: `${stats.trabajadoresActivos} activos`, icon: <FaUsers />, color: COLORS.primary },
    { label: 'Credenciales', value: stats.totalFotochecks, sub: `${stats.fotochecksVigentes} vigentes`, icon: <FaIdCard />, color: COLORS.green },
    { label: 'Usuarios', value: stats.totalUsuarios, sub: 'Registrados', icon: <FaUserShield />, color: COLORS.purple },
    { label: 'Accesos QR', value: stats.totalAccesos, sub: 'Ultimos 30 dias', icon: <FaQrcode />, color: COLORS.accent },
  ];

  const personalTipoData = {
    labels: stats.personalPorTipo.map((d) => d.tipo),
    datasets: [{ data: stats.personalPorTipo.map((d) => n(d.total)), backgroundColor: [COLORS.primary, COLORS.accent], borderWidth: 0, hoverOffset: 8 }],
  };

  const fotosTipoData = {
    labels: ['Presencial', 'Digital', 'Sin Foto'],
    datasets: [{ data: [n(stats.fotosPorTipo.presencial), n(stats.fotosPorTipo.digital), n(stats.fotosPorTipo.sin_foto)], backgroundColor: [COLORS.green, COLORS.blue, COLORS.gray], borderWidth: 0, hoverOffset: 8 }],
  };

  const disponibilidadData = {
    labels: stats.disponibilidadFoto.map((d) => d.tipo),
    datasets: [{ data: stats.disponibilidadFoto.map((d) => n(d.total)), backgroundColor: [COLORS.green, COLORS.primary, COLORS.teal, COLORS.gray], borderWidth: 0, hoverOffset: 8 }],
  };

  const cargoData = {
    labels: stats.distribucionCargo.map((d) => d.cargo),
    datasets: [{ label: 'Cantidad', data: stats.distribucionCargo.map((d) => n(d.total)), backgroundColor: [COLORS.primary, COLORS.accent, COLORS.green, COLORS.purple, COLORS.teal, COLORS.orange, COLORS.red, COLORS.yellow, COLORS.blue, COLORS.gray], borderRadius: 6, borderSkipped: false }],
  };

  const integridadData = {
    labels: stats.integridadContacto.map((d) => d.estado),
    datasets: [{ data: stats.integridadContacto.map((d) => n(d.total)), backgroundColor: stats.integridadContacto.map((d) => { if (d.estado === 'Completo') return COLORS.green; if (d.estado === 'Sin Contacto') return COLORS.red; return COLORS.yellow; }), borderWidth: 0, hoverOffset: 8 }],
  };

  return (
    <div className="dashboard">
      <h1>Dashboard</h1>

      <div className="stats-grid">
        {cards.map((c) => (
          <div key={c.label} className="stat-card" style={{ borderLeftColor: c.color }}>
            <div className="stat-icon" style={{ background: `${c.color}15`, color: c.color }}>{c.icon}</div>
            <div className="stat-info">
              <span className="stat-value">{c.value.toLocaleString()}</span>
              <span className="stat-label">{c.label}</span>
              <span className="stat-sub">{c.sub}</span>
            </div>
          </div>
        ))}
      </div>

      <div className="charts-grid">
        <div className="chart-card">
          <h3>Personal: Administrativos vs Docentes</h3>
          <div className="chart-box chart-box-doughnut">
            <Doughnut data={personalTipoData} options={{ ...chartDefaults, plugins: { ...chartDefaults.plugins, legend: legendOpts }, cutout: '55%' }} />
          </div>
        </div>

        <div className="chart-card">
          <h3>Fotos: Presencial vs Digital</h3>
          <div className="chart-box chart-box-doughnut">
            <Doughnut data={fotosTipoData} options={{ ...chartDefaults, plugins: { ...chartDefaults.plugins, legend: legendOpts }, cutout: '55%' }} />
          </div>
        </div>

        <div className="chart-card">
          <h3>Disponibilidad de Fotografia</h3>
          <div className="chart-box chart-box-doughnut">
            <Doughnut data={disponibilidadData} options={{ ...chartDefaults, plugins: { ...chartDefaults.plugins, legend: legendOpts }, cutout: '55%' }} />
          </div>
        </div>

        <div className="chart-card">
          <h3>Integridad de Contacto</h3>
          <div className="chart-box chart-box-doughnut">
            <Doughnut data={integridadData} options={{ ...chartDefaults, plugins: { ...chartDefaults.plugins, legend: legendOpts }, cutout: '55%' }} />
          </div>
        </div>
      </div>

      <div className="charts-grid">
        <div className="chart-card chart-wide">
          <h3>Distribucion por Condicion Laboral</h3>
          <div className="chart-box">
            <Bar data={cargoData} options={{ ...chartDefaults, plugins: { ...chartDefaults.plugins, legend: { display: false }, datalabels: barDatalabels }, scales: { x: { grid: { display: false }, ticks: { maxRotation: 45 } }, y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } } } }} />
          </div>
        </div>
      </div>
    </div>
  );
}
