import { createContext, useContext, useState, useCallback } from 'react';
import './Toast.css';

const ToastContext = createContext(null);

let toastId = 0;

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([]);

  const removeToast = useCallback((id) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  }, []);

  const addToast = useCallback((message, type = 'info', duration = 4000) => {
    const id = ++toastId;
    setToasts((prev) => [...prev, { id, message, type }]);
    if (duration > 0) {
      setTimeout(() => removeToast(id), duration);
    }
    return id;
  }, [removeToast]);

  const toast = {
    success: (msg, duration) => addToast(msg, 'success', duration),
    error: (msg, duration) => addToast(msg, 'error', duration || 6000),
    warning: (msg, duration) => addToast(msg, 'warning', duration || 5000),
    info: (msg, duration) => addToast(msg, 'info', duration),
  };

  const confirm = (message) => {
    return new Promise((resolve) => {
      const id = ++toastId;
      setToasts((prev) => [...prev, { id, message, type: 'confirm', onConfirm: () => { removeToast(id); resolve(true); }, onCancel: () => { removeToast(id); resolve(false); } }]);
    });
  };

  return (
    <ToastContext.Provider value={{ toast, confirm }}>
      {children}
      <div className="toast-container">
        {toasts.map((t) => (
          <div key={t.id} className={`toast toast-${t.type}`}>
            <span className="toast-message">{t.message}</span>
            {t.type === 'confirm' ? (
              <div className="toast-actions">
                <button className="toast-btn toast-btn-cancel" onClick={t.onCancel}>Cancelar</button>
                <button className="toast-btn toast-btn-confirm" onClick={t.onConfirm}>Aceptar</button>
              </div>
            ) : (
              <button className="toast-close" onClick={() => removeToast(t.id)}>&times;</button>
            )}
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
}

// eslint-disable-next-line react-refresh/only-export-components
export function useToast() {
  const ctx = useContext(ToastContext);
  if (!ctx) throw new Error('useToast must be used within ToastProvider');
  return ctx;
}
