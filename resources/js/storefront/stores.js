import { local, session } from './storage';

const BAG_KEY = 'orvalha.bag';
const FAV_KEY = 'orvalha.favs';

function cleanBag(raw) {
    const items = {};
    if (raw && typeof raw === 'object') {
        for (const [id, q] of Object.entries(raw)) {
            const quantity = Number.parseInt(q, 10);
            if (/^\d+$/.test(id) && quantity > 0) items[id] = Math.min(quantity, 99);
        }
    }
    return items;
}

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.store('ui', {
        bagOpen: false,
        productId: null,
        careOpen: false,
        guideOpen: false,
        guideSection: null,
        helpOpen: false,
        bubbleOn: false,
        toastMsg: '',
        toastOn: false,
        _toastTimer: null,

        get scrim() {
            return this.bagOpen || this.productId !== null;
        },

        toast(message) {
            this.toastMsg = message;
            this.toastOn = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => (this.toastOn = false), 2000);
        },

        openBag() {
            this.closeAll();
            this.careOpen = false;
            this.bagOpen = true;
            Alpine.store('bag').refresh();
        },

        openProduct(id) {
            this.bagOpen = false;
            this.productId = id;
        },

        closeAll() {
            this.bagOpen = false;
            this.productId = null;
        },

        openCare() {
            this.careOpen = true;
            this.hideBubble();
        },

        hideBubble() {
            this.bubbleOn = false;
            session.flag('orvalha.bubble');
        },

        openGuide(section = null) {
            this.careOpen = false;
            this.guideSection = section;
            this.guideOpen = true;
        },

        closeGuide() {
            this.guideOpen = false;
        },

        /** Há algo aberto que não deve ser coberto por balão ou convite. */
        get busy() {
            return this.bagOpen || this.productId !== null || this.careOpen || this.guideOpen || this.helpOpen;
        },
    });

    Alpine.store('bag', {
        items: cleanBag(local.get(BAG_KEY, {})),
        summary: null,
        bumping: false,
        _request: 0,

        get count() {
            return Object.values(this.items).reduce((total, q) => total + q, 0);
        },

        _save() {
            local.set(BAG_KEY, this.items);
        },

        _set(items) {
            this.items = items;
            this._save();
            this.refresh();
        },

        add(id, name = null) {
            const key = String(id);
            this._set({ ...this.items, [key]: Math.min((this.items[key] || 0) + 1, 99) });
            this.bumping = false;
            requestAnimationFrame(() => (this.bumping = true));
            setTimeout(() => (this.bumping = false), 500);
            Alpine.store('ui').toast(name ? `${name} na sacola` : 'Peça na sacola');
        },

        inc(id) {
            this.add(id);
        },

        dec(id) {
            const key = String(id);
            const next = { ...this.items };
            if ((next[key] || 0) <= 1) delete next[key];
            else next[key] -= 1;
            this._set(next);
        },

        remove(id) {
            const next = { ...this.items };
            delete next[String(id)];
            this._set(next);
        },

        /** Os preços e a disponibilidade vêm sempre do servidor; a sacola guarda só ids e quantidades. */
        async refresh() {
            if (this.count === 0) {
                this.summary = null;
                return;
            }

            const request = ++this._request;
            const query = Object.entries(this.items).map(([id, q]) => `${id}:${q}`).join(',');

            try {
                const response = await fetch(`/sacola/resumo?i=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('bag');
                const data = await response.json();
                if (request !== this._request) return;

                if (data.removed?.length) {
                    const next = { ...this.items };
                    data.removed.forEach((id) => delete next[String(id)]);
                    this.items = next;
                    this._save();
                    Alpine.store('ui').toast('Algumas peças não estão mais disponíveis e saíram da sacola');
                }

                this.summary = this.count === 0 ? null : data;
            } catch {
                // sem rede: mantém o último resumo
            }
        },
    });

    Alpine.store('favs', {
        ids: [...new Set((local.get(FAV_KEY, []) || []).map((id) => Number.parseInt(id, 10)).filter((id) => id > 0))],

        get count() {
            return this.ids.length;
        },

        has(id) {
            return this.ids.includes(Number(id));
        },

        toggle(id) {
            const value = Number(id);
            const saved = !this.has(value);
            this.ids = saved ? [...this.ids, value] : this.ids.filter((x) => x !== value);
            local.set(FAV_KEY, this.ids);
            Alpine.store('ui').toast(saved ? 'Salvo nos favoritos' : 'Removido dos favoritos');
        },
    });

    // sacola persistida: atualiza o resumo ao abrir a página
    if (Alpine.store('bag').count > 0) Alpine.store('bag').refresh();
});
