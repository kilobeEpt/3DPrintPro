// Testimonials Module
class TestimonialsModule {
    constructor() { this.items = []; }
    async init() {
        console.log('💬 Loading testimonials...');
        const btn = document.getElementById('addTestimonialBtn');
        if (btn) btn.addEventListener('click', () => AdminMain.prototype.showToast('Функция в разработке', 'info'));
        await this.loadTestimonials();
    }
    async loadTestimonials() {
        const container = document.getElementById('testimonialsContainer');
        if (!container) return;
        try {
            AdminMain.prototype.showLoading(container);
            this.items = await adminApi.getTestimonials();
            if (this.items.length === 0) { AdminMain.prototype.showEmpty(container, 'Нет отзывов'); return; }
            container.innerHTML = this.items.map(item => `<div class="testimonial-card"><h4>${item.name}</h4><p>${item.text}</p></div>`).join('');
            console.log(`✅ Loaded ${this.items.length} testimonials`);
        } catch (error) { console.error('❌ Failed to load testimonials:', error); AdminMain.prototype.showError(container); }
    }
}
if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', () => { window.testimonialsModule = new TestimonialsModule(); window.testimonialsModule.init(); }); } else { window.testimonialsModule = new TestimonialsModule(); window.testimonialsModule.init(); }
