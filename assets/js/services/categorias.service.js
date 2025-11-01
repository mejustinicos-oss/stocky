class CategoriasService {
    constructor() {
        this.api = apiService;
    }

    async getAll() {
        return this.api.get(API_CONFIG.ENDPOINTS.CATEGORIAS);
    }

    async getById(id) {
        const endpoint = API_CONFIG.ENDPOINTS.CATEGORIA_BY_ID.replace(':id', id);
        return this.api.get(endpoint);
    }

    async create(categoria) {
        return this.api.post(API_CONFIG.ENDPOINTS.CATEGORIAS, categoria);
    }

    async update(id, categoria) {
        const endpoint = API_CONFIG.ENDPOINTS.CATEGORIA_BY_ID.replace(':id', id);
        return this.api.put(endpoint, categoria);
    }

    async delete(id) {
        const endpoint = API_CONFIG.ENDPOINTS.CATEGORIA_BY_ID.replace(':id', id);
        return this.api.delete(endpoint);
    }
}

const categoriasService = new CategoriasService();
