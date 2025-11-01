class AuthService {
    constructor() {
        this.api = apiService;
    }

    async login(cedula, password) {
        try {
            const response = await this.api.post(API_CONFIG.ENDPOINTS.LOGIN, {
                cedula,
                password
            });

            if (response.success) {
                this.api.setToken(response.token);
                this.api.setCurrentUser(response.user);
            }

            return response;
        } catch (error) {
            throw new Error('Error al iniciar sesión: ' + error.message);
        }
    }

    async logout() {
        try {
            await this.api.post(API_CONFIG.ENDPOINTS.LOGOUT);
        } catch (error) {
            console.error('Error al cerrar sesión:', error);
        } finally {
            this.api.removeToken();
            this.api.removeCurrentUser();
            window.location.href = '/index.html';
        }
    }

    async verifySession() {
        try {
            const response = await this.api.get(API_CONFIG.ENDPOINTS.VERIFY_SESSION);
            return response.success;
        } catch (error) {
            return false;
        }
    }

    isAuthenticated() {
        return !!this.api.getToken();
    }

    getCurrentUser() {
        return this.api.getCurrentUser();
    }

    isAdmin() {
        const user = this.getCurrentUser();
        return user && user.rol === 'Administrador';
    }
}

const authService = new AuthService();
