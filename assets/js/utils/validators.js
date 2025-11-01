const Validators = {
    // Validar email
    isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    },

    // Validar teléfono
    isValidPhone(phone) {
        const regex = /^[0-9]{3}-[0-9]{3}-[0-9]{4}$/;
        return regex.test(phone);
    },

    // Validar cédula
    isValidCedula(cedula) {
        return cedula && cedula.length >= 7 && cedula.length <= 10;
    },

    // Validar NIT
    isValidNIT(nit) {
        return nit && nit.length >= 9;
    },

    // Validar contraseña
    isValidPassword(password) {
        return password && password.length >= 6;
    },

    // Validar campo requerido
    isRequired(value) {
        return value !== null && value !== undefined && value.toString().trim() !== '';
    },

    // Validar número positivo
    isPositiveNumber(value) {
        return !isNaN(value) && Number(value) > 0;
    }
};