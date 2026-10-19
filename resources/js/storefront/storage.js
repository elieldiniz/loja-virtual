// localStorage / sessionStorage podem não existir (aba privada, bloqueio de cookies): nunca quebrar a página.
export const local = {
    get(key, fallback) {
        try {
            const raw = window.localStorage.getItem(key);
            return raw === null ? fallback : JSON.parse(raw);
        } catch {
            return fallback;
        }
    },
    set(key, value) {
        try {
            window.localStorage.setItem(key, JSON.stringify(value));
        } catch {
            /* sem armazenamento: a sacola continua funcionando só durante a visita */
        }
    },
};

export const session = {
    has(key) {
        try {
            return window.sessionStorage.getItem(key) === '1';
        } catch {
            return false;
        }
    },
    flag(key) {
        try {
            window.sessionStorage.setItem(key, '1');
        } catch {
            /* ignora */
        }
    },
};
