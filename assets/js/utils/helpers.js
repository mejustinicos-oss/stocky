const Helpers = {
    // Formatear moneda
    formatCurrency(value) {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            minimumFractionDigits: 0
        }).format(value);
    },

    // Formatear fecha
    formatDate(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            ...options
        };
        return new Date(date).toLocaleDateString('es-CO', defaultOptions);
    },

    // Formatear fecha y hora
    formatDateTime(date) {
        return new Date(date).toLocaleString('es-CO', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    // Debounce para búsquedas
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Mostrar notificación
    showNotification(message, type = 'info') {
        // Implementar con una librería como Toastify o custom
        alert(`${type.toUpperCase()}: ${message}`);
    },

    // Confirmar acción
    async confirmAction(message) {
        return confirm(message);
    },

    // Calcular IVA
    calcularIVA(subtotal, porcentaje = 19) {
        return (subtotal * porcentaje) / 100;
    },

    // Generar código único
    generateUniqueCode(prefix = 'PROD') {
        const timestamp = Date.now();
        const random = Math.random().toString(36).substring(2, 9);
        return `${prefix}-${timestamp}-${random}`.toUpperCase();
    }
};