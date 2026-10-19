import { session } from './storage';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Atendimento: balão de boas-vindas e convite de ajuda
    Alpine.data('care', (config) => ({
        init() {
            const ui = Alpine.store('ui');
            const bag = Alpine.store('bag');

            // Balão: uma vez por visita. No computador, 5 s após abrir; no celular, só depois de rolar a página.
            if (!session.has('orvalha.bubble')) {
                const show = () => {
                    if (!ui.busy) ui.bubbleOn = true;
                };

                if (window.matchMedia('(max-width: 520px)').matches) {
                    const onScroll = () => {
                        if (window.scrollY > 700) {
                            window.removeEventListener('scroll', onScroll);
                            setTimeout(show, 1200);
                        }
                    };
                    window.addEventListener('scroll', onScroll, { passive: true });
                } else {
                    setTimeout(show, 5000);
                }
            }

            // Convite: depois de N minutos no site com a sacola vazia, uma vez por visita, nunca sobre outro painel.
            if (config.helpInvite?.enabled && !session.has('orvalha.help')) {
                const timer = setInterval(() => {
                    if (session.has('orvalha.help') || bag.count > 0) {
                        clearInterval(timer);
                        return;
                    }
                    if (ui.busy) return;

                    session.flag('orvalha.help');
                    ui.hideBubble();
                    ui.helpOpen = true;
                    clearInterval(timer);
                    this.$nextTick(() => document.getElementById('helpBox')?.focus());
                }, Math.max(1, config.helpInvite.minutes) * 60000);
            }
        },

        close() {
            Alpine.store('ui').careOpen = false;
        },
    }));

    // Página da peça: dados vêm do servidor
    Alpine.data('productModal', () => ({
        product: null,
        view: 0,
        loading: false,
        error: false,
        _id: null,

        async load(id) {
            if (id === null || id === undefined) return;
            if (id === this._id && this.product) return;

            this._id = id;
            this.product = null;
            this.view = 0;
            this.error = false;
            this.loading = true;

            try {
                const response = await fetch(`/pecas/${id}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('product');
                const data = await response.json();
                if (this._id === id) this.product = data;
            } catch {
                this.error = true;
            } finally {
                this.loading = false;
            }
        },
    }));

    // Guia de cuidados: ao abrir por uma dica, rola até o assunto
    Alpine.data('guide', () => ({
        focusSection(open, section) {
            if (!open) return;

            const guide = this.$refs.guide;
            guide.scrollTop = 0;
            guide.focus({ preventScroll: true });

            if (!section) return;
            const target = document.getElementById(section.startsWith('g-') ? section : `g-${section}`);
            if (target) setTimeout(() => guide.scrollTo({ top: target.offsetTop - 16, behavior: 'smooth' }), 250);
        },
    }));
});
