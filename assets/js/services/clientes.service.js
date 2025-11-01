class ClientesService {
    constructor() {
        this.api = apiService;
    }

    async getAll(params = {}) {
        return this.api.get(API_CONFIG.ENDPOINTS.CLIENTES, params);
    }

    async getById(cedula) {
        const endpoint = API_CONFIG.ENDPOINTS.CLIENTE_BY_ID.replace(':id', cedula);
        return this.api.get(endpoint);
    }

    async create(cliente) {
        return this.api.post(API_CONFIG.ENDPOINTS.CLIENTES, cliente);
    }

    async update(cedula, cliente) {
        const endpoint = API_CONFIG.ENDPOINTS.CLIENTE_BY_ID.replace(':id', cedula);
        return this.api.put(endpoint, cliente);
    }

    async delete(cedula) {
        const endpoint = API_CONFIG.ENDPOINTS.CLIENTE_BY_ID.replace(':id', cedula);
        return this.api.delete(endpoint);
    }

    async getHistorialCompras(cedula) {
        const endpoint = API_CONFIG.ENDPOINTS.CLIENTE_HISTORIAL.replace(':id', cedula);
        return this.api.get(endpoint);
    }

    async search(query) {
        return this.api.get(API_CONFIG.ENDPOINTS.CLIENTES, { search: query });
    }
}

const clientesService = new ClientesService();
