// ========================================
// MAIN APPLICATION
// ========================================

class MainApp {
    constructor() {
        this.currentTestimonial = 0;
        this.currentFilter = 'all';
        this.autoSlideInterval = null;
        this.dataLoaded = false;
    }

    async init() {
        this.initPreloader();
        this.initNavigation();
        this.initThemeToggle();
        this.initPhoneMasks();
        
        // Wait for CONFIG to load from database
        await this.waitForConfigLoad();
        
        await this.loadContent();
        this.initStats();
        await this.loadServices();
        await this.loadPortfolio();
        await this.loadTestimonials();
        await this.loadFAQ();
        this.initForms();
        this.renderDynamicFormFields();
        this.initScrollAnimations();
        this.initCalculator();
        this.dataLoaded = true;
    }
    
    async waitForConfigLoad() {
        return new Promise((resolve) => {
            if (window.CONFIG && window.CONFIG._loaded) {
                resolve();
                return;
            }
            
            const checkConfig = () => {
                if (window.CONFIG && window.CONFIG._loaded) {
                    resolve();
                } else {
                    setTimeout(checkConfig, 50);
                }
            };
            
            window.addEventListener('configLoaded', () => {
                window.CONFIG._loaded = true;
                resolve();
            }, { once: true });
            
            // Start checking in case event already fired
            setTimeout(checkConfig, 100);
        });
    }
    
    async reloadData() {
        console.log('🔄 Reloading all data from API...');
        try {
            await this.loadContent();
            await this.loadServices();
            await this.loadPortfolio();
            await this.loadTestimonials();
            await this.loadFAQ();
            console.log('✅ Data reloaded successfully');
            this.showNotification('✅ Данные обновлены', 'success');
        } catch (error) {
            console.error('❌ Failed to reload data:', error);
            this.showNotification('❌ Не удалось обновить данные', 'error');
        }
    }

    initPreloader() {
        setTimeout(() => {
            const preloader = document.getElementById('preloader');
            if (preloader) {
                preloader.classList.add('hidden');
            }
        }, 800);
    }

    initPhoneMasks() {
        const phoneInputs = document.querySelectorAll('input[type="tel"]');
        phoneInputs.forEach(input => {
            if (typeof Utils !== 'undefined') {
                Utils.initPhoneMask(input);
            } else {
                input.addEventListener('input', (e) => this.formatPhone(e.target));
                input.addEventListener('focus', (e) => {
                    if (!e.target.value) {
                        e.target.value = '+7 ';
                    }
                });
                input.addEventListener('blur', (e) => {
                    if (e.target.value === '+7 ') {
                        e.target.value = '';
                    }
                });
            }
        });
    }

    formatPhone(input) {
        if (typeof Utils !== 'undefined') {
            Utils.formatPhone(input);
            return;
        }
        
        let value = input.value.replace(/\D/g, '');

        if (value.length > 0 && value[0] === '8') {
            value = '7' + value.slice(1);
        }

        if (value.length > 0 && value[0] !== '7') {
            value = '7' + value;
        }

        let formatted = '';
        if (value.length > 0) {
            formatted = '+7';
            if (value.length > 1) {
                formatted += ' (' + value.slice(1, 4);
            }
            if (value.length >= 5) {
                formatted += ') ' + value.slice(4, 7);
            }
            if (value.length >= 8) {
                formatted += '-' + value.slice(7, 9);
            }
            if (value.length >= 10) {
                formatted += '-' + value.slice(9, 11);
            }
        }

        input.value = formatted;
    }

    initNavigation() {
        const header = document.getElementById('header');
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('navMenu');
        const navLinks = document.querySelectorAll('.nav-link');

        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        hamburger?.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
            
            if (navMenu.classList.contains('active')) {
                document.body.classList.add('nav-open');
            } else {
                document.body.classList.remove('nav-open');
            }
        });

        const currentPath = window.location.pathname.split('/').pop() || 'index.html';
        const isHomePage = currentPath === 'index.html' || currentPath === '';
        
        navLinks.forEach(link => {
            const linkHref = link.getAttribute('href');
            const linkPage = link.getAttribute('data-page');
            
            if (linkPage) {
                const isCurrentPage = 
                    (isHomePage && linkPage === 'home') ||
                    (currentPath === linkPage + '.html');
                
                if (isCurrentPage && !linkHref.startsWith('#')) {
                    link.classList.add('active');
                }
            }
        });

        const sections = document.querySelectorAll('section[id]');
        if (sections.length > 0 && isHomePage) {
            window.addEventListener('scroll', () => {
                let current = '';

                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.clientHeight;
                    if (scrollY >= (sectionTop - 200)) {
                        current = section.getAttribute('id');
                    }
                });

                navLinks.forEach(link => {
                    const linkHref = link.getAttribute('href');
                    if (linkHref.startsWith('#')) {
                        if (linkHref === '#' + current) {
                            link.classList.add('active');
                        } else {
                            link.classList.remove('active');
                        }
                    }
                });
            });
        }

        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const targetHref = link.getAttribute('href');
                
                if (targetHref.startsWith('#')) {
                    const targetId = targetHref;
                    const targetSection = document.querySelector(targetId);

                    if (targetSection) {
                        e.preventDefault();
                        
                        navMenu.classList.remove('active');
                        hamburger.classList.remove('active');
                        document.body.classList.remove('nav-open');

                        window.scrollTo({
                            top: targetSection.offsetTop - 80,
                            behavior: 'smooth'
                        });
                    }
                } else {
                    navMenu.classList.remove('active');
                    hamburger.classList.remove('active');
                    document.body.classList.remove('nav-open');
                }
            });
        });
    }

    initThemeToggle() {
        const themeToggle = document.getElementById('themeToggle');
        const savedTheme = localStorage.getItem('theme') || 'light';

        if (savedTheme === 'dark') {
            document.body.classList.add('dark-theme');
            themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        }

        themeToggle?.addEventListener('click', () => {
            document.body.classList.toggle('dark-theme');

            if (document.body.classList.contains('dark-theme')) {
                themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                localStorage.setItem('theme', 'dark');
            } else {
                themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                localStorage.setItem('theme', 'light');
            }
        });
    }

    async loadContent() {
        try {
            const content = db.getDefaultContent();
            const settings = await db.getOrCreateSettings() || db.getDefaultSettings();
            const stats = db.getDefaultStats();

            if (content.hero) {
                const heroTitle = document.getElementById('heroTitle');
                if (heroTitle) heroTitle.textContent = content.hero.title || 'идеи в реальность';

                const heroDescription = document.getElementById('heroDescription');
                if (heroDescription) heroDescription.textContent = content.hero.subtitle || '';
            }

            const contactAddress = document.getElementById('contactAddress');
            if (contactAddress) contactAddress.textContent = settings.address || settings.company_address || '';

            const contactPhone = document.getElementById('contactPhone');
            if (contactPhone) contactPhone.textContent = settings.contactPhone || settings.company_phone || '';

            const contactEmail = document.getElementById('contactEmail');
            if (contactEmail) contactEmail.textContent = settings.contactEmail || settings.company_email || '';

            const contactHours = document.getElementById('contactHours');
            if (contactHours) contactHours.innerHTML = (settings.workingHours || settings.company_hours || '').replace(/\n/g, '<br>');

            const siteName = document.getElementById('siteName');
            if (siteName && (settings.siteName || settings.site_name)) {
                const name = settings.siteName || settings.site_name;
                siteName.innerHTML = name.replace('Pro', '<strong>Pro</strong>');
            }

            this.loadSocialLinks(settings.socialLinks || {});
            this.updateStatsTargets(stats);
        } catch (error) {
            console.error('❌ Failed to load content:', error);
            const settings = db.getDefaultSettings();
            const stats = db.getDefaultStats();
            this.loadSocialLinks(settings.socialLinks || {});
            this.updateStatsTargets(stats);
        }
    }

    loadSocialLinks(links) {
        const container = document.getElementById('socialLinks');
        if (!container) return;

        const socialIcons = {
            vk: 'fab fa-vk',
            telegram: 'fab fa-telegram',
            whatsapp: 'fab fa-whatsapp',
            youtube: 'fab fa-youtube'
        };

        if (!links.telegram) {
            links.telegram = CONFIG.telegram.contactUrl;
        }

        let html = '';
        Object.entries(links).forEach(([key, url]) => {
            if (url) {
                html += `<a href="${url}" class="social-link" target="_blank" rel="noopener">
                    <i class="${socialIcons[key]}"></i>
                </a>`;
            }
        });

        container.innerHTML = html;
    }

    updateStatsTargets(stats) {
        const statNumbers = document.querySelectorAll('.stat-number');
        if (statNumbers[0]) statNumbers[0].setAttribute('data-target', stats.totalProjects || 1500);
        if (statNumbers[1]) statNumbers[1].setAttribute('data-target', stats.happyClients || 850);
        if (statNumbers[2]) statNumbers[2].setAttribute('data-target', stats.yearsExperience || 12);
        if (statNumbers[3]) statNumbers[3].setAttribute('data-target', stats.awards || 25);
    }

    initStats() {
        const statNumbers = document.querySelectorAll('.stat-number');
        let animated = false;

        const animateStats = () => {
            if (animated) return;

            const statsSection = document.querySelector('.stats');
            if (!statsSection) return;

            const rect = statsSection.getBoundingClientRect();

            if (rect.top < window.innerHeight && rect.bottom > 0) {
                animated = true;

                statNumbers.forEach(stat => {
                    const target = parseInt(stat.getAttribute('data-target'));
                    const duration = 2000;
                    const increment = target / (duration / 16);
                    let current = 0;

                    const updateCounter = () => {
                        current += increment;
                        if (current < target) {
                            stat.textContent = Math.floor(current);
                            requestAnimationFrame(updateCounter);
                        } else {
                            stat.textContent = target;
                        }
                    };

                    updateCounter();
                });
            }
        };

        window.addEventListener('scroll', animateStats);
        animateStats();
    }

    async loadServices() {
        try {
            let services = await db.getServices();
            services = services.filter(s => s.active !== false);
            
            const grid = document.getElementById('servicesGrid');
            if (!grid) return;

            const isHomepage = document.body.getAttribute('data-page') === 'home';
            if (isHomepage && services.length > 4) {
                services = services.slice(0, 4);
            }

            grid.innerHTML = services.map(service => `
                <a href="index.html#calculator" class="service-card ${service.featured ? 'featured' : ''}" style="text-decoration: none; color: inherit; display: block;">
                    ${service.featured ? '<div class="featured-badge">Популярное</div>' : ''}
                    <div class="service-icon">
                        <i class="fas ${service.icon}"></i>
                    </div>
                    <h3>${service.name}</h3>
                    <p>${service.description}</p>
                    <ul class="service-features">
                        ${(service.features || []).map(f => `
                            <li><i class="fas fa-check"></i> ${f}</i>
                        `).join('')}
                    </ul>
                </a>
            `).join('');
            
            const syncInfo = db.getSyncInfo('services');
            if (syncInfo.source === 'cache' && this.dataLoaded) {
                console.log('⚠️ Services loaded from cache');
            }
        } catch (error) {
            console.error('❌ Failed to load services:', error);
            if (this.dataLoaded) {
                this.showNotification('⚠️ Не удалось загрузить услуги. Используются сохранённые данные.', 'warning');
            }
        }
    }

    async openServiceModal(slug) {
        try {
            const services = await db.getServices();
            const service = services.find(s => s.slug === slug);
            if (!service) return;

            const modal = document.getElementById('serviceModal');
            const content = document.getElementById('serviceModalContent');

            content.innerHTML = `
                <h2>${service.name}</h2>
                <p style="color: var(--text-secondary); margin: 20px 0;">${service.description}</p>
                <h3 style="margin: 25px 0 15px;">Преимущества:</h3>
                <ul style="list-style: none; padding: 0;">
                    ${(service.features || []).map(f => `
                        <li style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <i class="fas fa-check-circle" style="color: var(--success);"></i>
                            <span>${f}</span>
                        </li>
                    `).join('')}
                </ul>
                <div style="margin-top: 30px; padding: 20px; background: var(--bg-secondary); border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 18px; font-weight: 600;">Стоимость:</span>
                    <span style="font-size: 24px; color: var(--primary); font-weight: 700;">${service.price}</span>
                </div>
                <div style="display: flex; gap: 15px; margin-top: 25px; flex-wrap: wrap;">
                    <button class="btn btn-primary" onclick="window.location.href='#calculator'">
                        <i class="fas fa-calculator"></i>
                        Рассчитать стоимость
                    </button>
                    <a href="${CONFIG.telegram.contactUrl}" target="_blank" class="btn btn-outline" style="text-decoration: none;">
                        <i class="fab fa-telegram"></i>
                        Написать в Telegram
                    </a>
                </div>
            `;

            modal.classList.add('active');
        } catch (error) {
            console.error('❌ Failed to open service modal:', error);
        }
    }

    async loadPortfolio() {
        try {
            const items = await db.getPortfolio();
            this.portfolioItems = items;
            this.renderPortfolio(items);
            this.initPortfolioFilters();
        } catch (error) {
            console.error('❌ Failed to load portfolio:', error);
        }
    }

    renderPortfolio(items = null) {
        if (!items) {
            items = this.portfolioItems || [];
        }

        const grid = document.getElementById('portfolioGrid');
        if (!grid) return;

        let filtered = this.currentFilter === 'all'
            ? items
            : items.filter(item => item.category === this.currentFilter);

        const isHomepage = document.body.getAttribute('data-page') === 'home';
        if (isHomepage && filtered.length > 6) {
            filtered = filtered.slice(0, 6);
        }

        if (filtered.length === 0) {
            grid.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--text-secondary); grid-column: 1/-1;">Работ не найдено</p>';
            return;
        }

        grid.innerHTML = filtered.map(item => `
            <div class="portfolio-item" data-category="${item.category}" onclick="app.openPortfolioModal('${item.id}')">
                <img src="${item.image_url || item.image}" alt="${item.title}" class="portfolio-image" loading="lazy">
                <span class="portfolio-category">${this.getCategoryName(item.category)}</span>
                <div class="portfolio-overlay">
                    <h3>${item.title}</h3>
                    <p>${item.description}</p>
                </div>
            </div>
        `).join('');
    }

    initPortfolioFilters() {
        const filterBtns = document.querySelectorAll('.filter-btn');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                this.currentFilter = btn.getAttribute('data-filter');
                this.renderPortfolio();
            });
        });
    }

    getCategoryName(category) {
        const names = {
            'prototype': 'Прототипы',
            'functional': 'Функциональные',
            'art': 'Художественные',
            'industrial': 'Промышленные'
        };
        return names[category] || category;
    }

    openPortfolioModal(id) {
        const item = (this.portfolioItems || []).find(i => i.id == id);
        if (!item) return;

        const modal = document.getElementById('portfolioModal');
        const content = document.getElementById('portfolioModalContent');

        content.innerHTML = `
            <img src="${item.image_url || item.image}" alt="${item.title}" style="width: 100%; border-radius: 12px; margin-bottom: 20px;">
            <h2>${item.title}</h2>
            <p style="color: var(--text-secondary); margin: 15px 0;">${item.description}</p>
            ${item.details ? `
            <div style="padding: 20px; background: var(--bg-secondary); border-radius: 12px; margin-top: 20px;">
                <h3 style="margin-bottom: 10px;">Детали проекта:</h3>
                <p style="color: var(--text-secondary);">${item.details}</p>
            </div>
            ` : ''}
            <div style="display: flex; gap: 15px; margin-top: 20px; flex-wrap: wrap;">
                <button class="btn btn-primary" onclick="window.location.href='#calculator'">
                    <i class="fas fa-calculator"></i>
                    Заказать похожее
                </button>
                <a href="${CONFIG.telegram.contactUrl}" target="_blank" class="btn btn-outline" style="text-decoration: none;">
                    <i class="fab fa-telegram"></i>
                    Написать в Telegram
                </a>
            </div>
        `;

        modal.classList.add('active');
    }

    async loadTestimonials() {
        try {
            let testimonials = await db.getTestimonials();
            testimonials = testimonials.filter(t => t.approved !== false);
            
            const slider = document.getElementById('testimonialsSlider');
            if (!slider) return;

            const isHomepage = document.body.getAttribute('data-page') === 'home';
            if (isHomepage && testimonials.length > 3) {
                testimonials = testimonials.slice(0, 3);
            }

            if (testimonials.length === 0) {
                slider.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--text-secondary);">Отзывов пока нет</p>';
                return;
            }

            slider.innerHTML = testimonials.map((item, index) => `
                <div class="testimonial-card ${index === 0 ? 'active' : ''}">
                    <img src="${item.avatar}" alt="${item.name}" class="testimonial-avatar">
                    <div class="testimonial-rating">
                        ${'★'.repeat(item.rating || 5)}
                    </div>
                    <p class="testimonial-text">"${item.text}"</p>
                    <h4 class="testimonial-author">${item.name}</h4>
                    <p class="testimonial-position">${item.position}</p>
                </div>
            `).join('');

            this.initTestimonialsSlider(testimonials.length);
        } catch (error) {
            console.error('❌ Failed to load testimonials:', error);
        }
    }

    initTestimonialsSlider(count) {
        const prevBtn = document.getElementById('prevTestimonial');
        const nextBtn = document.getElementById('nextTestimonial');

        prevBtn?.addEventListener('click', () => {
            this.currentTestimonial = (this.currentTestimonial - 1 + count) % count;
            this.updateTestimonials();
        });

        nextBtn?.addEventListener('click', () => {
            this.currentTestimonial = (this.currentTestimonial + 1) % count;
            this.updateTestimonials();
        });

        this.autoSlideInterval = setInterval(() => {
            this.currentTestimonial = (this.currentTestimonial + 1) % count;
            this.updateTestimonials();
        }, 5000);
    }

    updateTestimonials() {
        const cards = document.querySelectorAll('.testimonial-card');
        cards.forEach((card, index) => {
            card.classList.toggle('active', index === this.currentTestimonial);
        });
    }

    async loadFAQ() {
        try {
            let faqs = await db.getFAQ();
            faqs = faqs.filter(f => f.active !== false);
            
            const list = document.getElementById('faqList');
            if (!list) return;

            const isHomepage = document.body.getAttribute('data-page') === 'home';
            if (isHomepage && faqs.length > 5) {
                faqs = faqs.slice(0, 5);
            }

            list.innerHTML = faqs.map((faq, index) => `
                <div class="faq-item" id="faq-${index}">
                    <button class="faq-question" onclick="app.toggleFAQ(${index})">
                        <span>${faq.question}</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-content">${faq.answer}</div>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            console.error('❌ Failed to load FAQ:', error);
        }
    }

    toggleFAQ(index) {
        const item = document.getElementById(`faq-${index}`);
        const answer = item.querySelector('.faq-answer');
        const isActive = item.classList.contains('active');

        document.querySelectorAll('.faq-item').forEach(faq => {
            faq.classList.remove('active');
            faq.querySelector('.faq-answer').style.maxHeight = null;
        });

        if (!isActive) {
            item.classList.add('active');
            answer.style.maxHeight = answer.scrollHeight + 'px';
        }
    }

    renderDynamicFormFields() {
        const container = document.getElementById('dynamicFormFields');
        if (!container) return;
        
        // Show loading placeholder initially
        container.innerHTML = '<div class="form-loading"><i class="fas fa-spinner fa-spin"></i> Загрузка формы...</div>';
        
        const fields = CONFIG.formFields.contact || [];
        const activeFields = fields.filter(f => f.enabled !== false);
        
        if (activeFields.length === 0) {
            console.warn('⚠️ No active form fields configured');
            return;
        }
        
        // Sort by order
        activeFields.sort((a, b) => (a.order || 0) - (b.order || 0));
        
        const html = activeFields.map(field => this.renderFormField(field)).join('');
        container.innerHTML = html;
        
        // Re-init phone masks for dynamically created fields
        this.initPhoneMasks();
        
        console.log('✅ Динамические поля формы отрисованы:', activeFields.length);
    }
    
    renderFormField(field) {
        const requiredAttr = field.required ? 'required' : '';
        const requiredStar = field.required ? '<span style="color: var(--danger);">*</span>' : '';
        
        let fieldHtml = '';
        
        switch (field.type) {
            case 'text':
            case 'email':
            case 'tel':
                fieldHtml = `
                    <div class="form-group">
                        <label for="${field.name}">
                            ${field.label} ${requiredStar}
                        </label>
                        <input 
                            type="${field.type}" 
                            id="${field.name}" 
                            name="${field.name}" 
                            class="form-control" 
                            placeholder="${field.placeholder || ''}"
                            ${requiredAttr}
                        >
                        ${field.helpText ? `<small class="form-help">${field.helpText}</small>` : ''}
                        <div class="field-error"></div>
                    </div>
                `;
                break;
                
            case 'textarea':
                fieldHtml = `
                    <div class="form-group">
                        <label for="${field.name}">
                            ${field.label} ${requiredStar}
                        </label>
                        <textarea 
                            id="${field.name}" 
                            name="${field.name}" 
                            class="form-control" 
                            rows="4"
                            placeholder="${field.placeholder || ''}"
                            ${requiredAttr}
                        ></textarea>
                        ${field.helpText ? `<small class="form-help">${field.helpText}</small>` : ''}
                        <div class="field-error"></div>
                    </div>
                `;
                break;
                
            case 'select':
                const options = field.options || [];
                fieldHtml = `
                    <div class="form-group">
                        <label for="${field.name}">
                            ${field.label} ${requiredStar}
                        </label>
                        <select 
                            id="${field.name}" 
                            name="${field.name}" 
                            class="form-control"
                            ${requiredAttr}
                        >
                            <option value="">${field.placeholder || 'Выберите...'}</option>
                            ${options.map(opt => {
                                const value = typeof opt === 'string' ? opt : opt.value;
                                const label = typeof opt === 'string' ? opt : opt.label;
                                return `<option value="${value}">${label}</option>`;
                            }).join('')}
                        </select>
                        ${field.helpText ? `<small class="form-help">${field.helpText}</small>` : ''}
                        <div class="field-error"></div>
                    </div>
                `;
                break;
                
            default:
                console.warn('Unknown field type:', field.type);
                return '';
        }
        
        return fieldHtml;
    }
    
    initForms() {
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleFormSubmit(e.target, 'contact');
            });
        }

        const subscribeForm = document.getElementById('subscribeForm');
        if (subscribeForm) {
            subscribeForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSubscribe(e.target);
            });
        }
    }

    async handleUniversalForm(form) {
        const validator = new Validator();
        const formData = new FormData(form);

        // ИСПРАВЛЕНО: Валидируем только активные поля из CONFIG
        const activeFields = CONFIG.formFields.contact.filter(f => f.enabled);

        let isValid = true;

        // Валидация активных полей
        activeFields.forEach(field => {
            const value = formData.get(field.name);

            // Проверка обязательных полей
            if (field.required) {
                if (!validator.required(value, field.label)) {
                    isValid = false;
                }
            }

            // Проверка типов (только если поле заполнено)
            if (value && value.trim() !== '') {
                switch (field.type) {
                    case 'email':
                        if (!validator.email(value, field.label)) {
                            isValid = false;
                        }
                        break;
                    case 'tel':
                        if (!validator.phone(value, field.label)) {
                            isValid = false;
                        }
                        break;
                }
            }
        });

        if (!isValid) {
            validator.showErrors(form);
            this.showNotification('Пожалуйста, исправьте ошибки в форме', 'error');
            return;
        }

        // Disable submit button and show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
        }

        // Получаем данные калькулятора
        const calculatorData = window.calculator && window.calculator.calculation ? window.calculator.calculation : null;

        // Формируем заказ
        const order = {
            type: calculatorData ? 'order' : 'contact',
            clientName: formData.get('name') || '',
            name: formData.get('name') || '',
            email: formData.get('email') || '',
            clientEmail: formData.get('email') || '',
            phone: formData.get('phone') || '',
            clientPhone: formData.get('phone') || '',
            telegram: formData.get('telegram') || '',
            subject: formData.get('subject') || (calculatorData ? 'Заказ из калькулятора' : 'Обращение с сайта'),
            message: formData.get('message') || '',
            details: formData.get('message') || '',
            service: calculatorData ? calculatorData.service : (formData.get('subject') || 'Обращение'),
            amount: calculatorData ? calculatorData.total : 0,
            calculatorData: calculatorData,
            status: 'new',
            orderNumber: this.generateOrderNumber(),
            telegramSent: false
        };

        try {
            console.log('📤 Отправка заявки через API...');
            
            if (typeof apiClient === 'undefined') {
                throw {
                    message: 'API клиент недоступен',
                    isNetworkError: true
                };
            }
            
            const apiStatus = apiClient.getStatus();
            if (!apiStatus.isOnline) {
                throw {
                    message: 'API недоступен',
                    isNetworkError: true
                };
            }
            
            const result = await apiClient.createOrder(order);
            
            console.log('✅ Заявка успешно сохранена в БД. Order ID:', result.id);
            console.log('📬 Telegram отправлен:', result.telegram_sent);
            
            try {
                await db.getOrders();
                console.log('💾 Кеш заказов обновлен');
            } catch (e) {
                console.log('⚠️ Не удалось обновить кеш заказов');
            }
            
            if (result.telegram_sent) {
                this.showNotification('✅ Спасибо! Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.', 'success');
            } else {
                this.showNotification('✅ Спасибо! Ваша заявка сохранена. Мы свяжемся с вами в ближайшее время.', 'success');
                if (result.telegram_error) {
                    console.warn('⚠️ Telegram ошибка:', result.telegram_error);
                }
            }
            
            form.reset();
            const calcInfo = document.getElementById('calculationInfo');
            if (calcInfo) calcInfo.style.display = 'none';
            const formTitle = document.getElementById('formTitle');
            if (formTitle) {
                formTitle.innerHTML = '<i class="fas fa-envelope"></i> Свяжитесь с нами';
            }
            
        } catch (error) {
            console.error('❌ Ошибка отправки формы:', error);
            
            const isNetworkError = error.isNetworkError || error.name === 'TypeError' || 
                                 error.message.includes('Failed to fetch') || 
                                 error.message.includes('Network');
            
            try {
                const orderToSave = {
                    ...order,
                    id: Date.now() + Math.random().toString(36).substr(2, 9),
                    createdAt: new Date().toISOString(),
                    pendingSync: true
                };
                
                const allData = JSON.parse(localStorage.getItem('3dprintpro_data') || '{}');
                const orders = allData.orders || [];
                orders.push(orderToSave);
                allData.orders = orders;
                localStorage.setItem('3dprintpro_data', JSON.stringify(allData));
                
                console.log('💾 Заявка сохранена в localStorage для последующей синхронизации');
                
                if (isNetworkError) {
                    this.showNotification(
                        '⚠️ Нет подключения к серверу. Ваша заявка сохранена локально и будет отправлена при восстановлении связи. ' +
                        'Или свяжитесь с нами напрямую по телефону.',
                        'warning'
                    );
                } else {
                    this.showNotification(
                        '⚠️ Ваша заявка сохранена локально. Пожалуйста, попробуйте повторить отправку позже или свяжитесь с нами по телефону.',
                        'warning'
                    );
                }
            } catch (e) {
                this.showNotification(
                    '❌ Не удалось отправить заявку. Пожалуйста, попробуйте позже или свяжитесь с нами по телефону: ' + 
                    (typeof CONFIG !== 'undefined' && CONFIG.phone ? CONFIG.phone : '+7 (999) 123-45-67'),
                    'error'
                );
            }
        } finally {
            // Re-enable submit button
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }
    }
    
    async handleFormSubmit(form, formSlug = 'contact') {
        // Clear previous errors
        this.clearFormErrors(form);
        
        const formData = new FormData(form);
        const activeFields = CONFIG.formFields[formSlug]?.filter(f => f.enabled !== false) || [];
        
        // Client-side validation
        const validation = this.validateFormData(formData, activeFields);
        if (!validation.valid) {
            this.displayFormErrors(form, validation.errors);
            this.showNotification('Пожалуйста, исправьте ошибки в форме', 'error');
            return;
        }
        
        // Disable submit button and show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
        }
        
        // Build submission data object
        const submissionData = {};
        for (const [key, value] of formData.entries()) {
            if (key !== 'privacy') {
                submissionData[key] = value;
            }
        }
        
        // Add calculator data if available
        const calculatorData = window.calculator?.calculation;
        if (calculatorData) {
            submissionData.calculator_data = calculatorData;
            submissionData.amount = calculatorData.total;
        }
        
        try {
            console.log('📤 Отправка формы через новый Forms API...');
            
            if (typeof apiClient === 'undefined') {
                throw {
                    message: 'API клиент недоступен',
                    isNetworkError: true
                };
            }
            
            const result = await apiClient.submitForm(formSlug, submissionData);
            
            console.log('✅ Форма успешно отправлена. Submission ID:', result.submissionId);
            
            if (result.orderNumber) {
                console.log('📦 Order Number:', result.orderNumber);
            }
            
            // Show success message
            const successMessage = result.message || 'Спасибо! Ваше сообщение отправлено. Мы свяжемся с вами в ближайшее время.';
            this.showNotification('✅ ' + successMessage, 'success');
            
            // Reset form
            form.reset();
            
            // Hide calculator info if present
            const calcInfo = document.getElementById('calculationInfo');
            if (calcInfo) calcInfo.style.display = 'none';
            
            const formTitle = document.getElementById('formTitle');
            if (formTitle) {
                formTitle.innerHTML = '<i class="fas fa-envelope"></i> Свяжитесь с нами';
            }
            
            // Redirect if specified
            if (result.redirectUrl) {
                setTimeout(() => {
                    window.location.href = result.redirectUrl;
                }, 2000);
            }
            
        } catch (error) {
            console.error('❌ Ошибка отправки формы:', error);
            
            // Handle validation errors from server
            if (error.errors && Object.keys(error.errors).length > 0) {
                this.displayFormErrors(form, error.errors);
                this.showNotification('Пожалуйста, исправьте ошибки в форме', 'error');
            } else {
                // Generic error message
                const errorMessage = error.message || 'Произошла ошибка при отправке формы';
                this.showNotification('❌ ' + errorMessage + '. Пожалуйста, попробуйте позже или свяжитесь с нами по телефону.', 'error');
            }
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }
    }
    
    validateFormData(formData, fields) {
        const errors = {};
        let valid = true;
        
        fields.forEach(field => {
            const value = formData.get(field.name);
            const trimmedValue = value ? value.trim() : '';
            
            // Required validation
            if (field.required && !trimmedValue) {
                errors[field.name] = `${field.label} обязательно для заполнения`;
                valid = false;
                return;
            }
            
            // Skip further validation if field is empty and not required
            if (!trimmedValue) return;
            
            // Type-specific validation
            switch (field.type) {
                case 'email':
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(trimmedValue)) {
                        errors[field.name] = 'Введите корректный email адрес';
                        valid = false;
                    }
                    break;
                    
                case 'tel':
                    const phoneRegex = /^[\+]?[(]?[0-9]{1,3}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,9}$/;
                    if (!phoneRegex.test(trimmedValue.replace(/[\s\(\)\-]/g, ''))) {
                        errors[field.name] = 'Введите корректный номер телефона';
                        valid = false;
                    }
                    break;
            }
            
            // Custom validation rules from config
            if (field.validation) {
                if (field.validation.minLength && trimmedValue.length < field.validation.minLength) {
                    errors[field.name] = `Минимальная длина: ${field.validation.minLength} символов`;
                    valid = false;
                }
                if (field.validation.maxLength && trimmedValue.length > field.validation.maxLength) {
                    errors[field.name] = `Максимальная длина: ${field.validation.maxLength} символов`;
                    valid = false;
                }
                if (field.validation.pattern) {
                    const regex = new RegExp(field.validation.pattern);
                    if (!regex.test(trimmedValue)) {
                        errors[field.name] = field.validation.message || 'Неверный формат';
                        valid = false;
                    }
                }
            }
        });
        
        return { valid, errors };
    }
    
    clearFormErrors(form) {
        form.querySelectorAll('.field-error').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
        form.querySelectorAll('.form-control').forEach(el => {
            el.classList.remove('error');
        });
    }
    
    displayFormErrors(form, errors) {
        Object.entries(errors).forEach(([fieldName, errorMessage]) => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.classList.add('error');
                const errorDiv = field.parentElement.querySelector('.field-error');
                if (errorDiv) {
                    errorDiv.textContent = errorMessage;
                    errorDiv.style.display = 'block';
                    errorDiv.style.color = 'var(--danger, #e74c3c)';
                    errorDiv.style.fontSize = '0.875rem';
                    errorDiv.style.marginTop = '0.5rem';
                }
            }
        });
    }

    handleSubscribe(form) {
        const validator = new Validator();
        const email = new FormData(form).get('email');

        if (!validator.email(email, 'Email')) {
            this.showNotification('Пожалуйста, введите корректный email', 'error');
            return;
        }

        this.showNotification('Спасибо за подписку!', 'success');
        form.reset();
    }

    async generateOrderNumber() {
        if (typeof Utils !== 'undefined' && Utils.generateOrderNumber) {
            return await Utils.generateOrderNumber();
        }
        
        try {
            const orders = await db.getOrders();
            const maxNumber = orders.reduce((max, o) => {
                const num = parseInt(o.order_number || o.orderNumber) || 0;
                return num > max ? num : max;
            }, 1000);
            return (maxNumber + 1).toString();
        } catch (error) {
            console.error('❌ Failed to generate order number:', error);
            return Date.now().toString();
        }
    }

    initCalculator() {
        // Calculator инициализируется автоматически
    }

    initScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        const animatedElements = document.querySelectorAll('.service-card, .portfolio-item, .stat-card, .about-feature');
        animatedElements.forEach(el => {
            el.style.opacity = '0';
            observer.observe(el);
        });
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    }

    showNotification(message, type = 'info', duration = 5000) {
        if (typeof Utils !== 'undefined' && Utils.showNotification) {
            Utils.showNotification(message, type, duration);
            return;
        }
        
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#6366f1'
        };

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 20px 30px;
            background: ${colors[type]};
            color: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: slideInRight 0.3s ease;
            max-width: 400px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        `;

        notification.innerHTML = `
            <i class="fas ${icons[type]}" style="font-size: 20px;"></i>
            <span>${message}</span>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }
}

// ========================================
// GLOBAL APP INSTANCE
// ========================================

const app = new MainApp();

// ========================================
// GLOBAL FUNCTIONS
// ========================================

function scrollToContactForm() {
    const calc = window.calculator && window.calculator.calculation ? window.calculator.calculation : null;

    const calcInfo = document.getElementById('calculationInfo');
    const formTitle = document.getElementById('formTitle');
    const subjectSelect = document.getElementById('formSubject'); // ИСПРАВЛЕНО: было subjectInput

    if (calc) {
        // Показываем расчёт
        document.getElementById('calcService').textContent = calc.service || '-';
        document.getElementById('calcMaterial').textContent = calc.material || '-';
        document.getElementById('calcWeight').textContent = (calc.weight || 0) + 'г';
        document.getElementById('calcQuantity').textContent = (calc.quantity || 0) + ' шт';
        document.getElementById('calcTotal').textContent = (calc.total || 0).toLocaleString('ru-RU') + '₽';

        if (calcInfo) calcInfo.style.display = 'block';
        if (formTitle) formTitle.innerHTML = '<i class="fas fa-shopping-cart"></i> Оформление заказа';
        if (subjectSelect) subjectSelect.value = 'Расчет стоимости';

        app.showNotification('📝 Заполните форму для оформления заказа', 'info');
    } else {
        // Обычная форма
        if (calcInfo) calcInfo.style.display = 'none';
        if (formTitle) formTitle.innerHTML = '<i class="fas fa-envelope"></i> Запрос на 3D печать';
        if (subjectSelect) subjectSelect.value = '';

        app.showNotification('💡 Укажите детали заказа в форме', 'info');
    }

    const contactSection = document.getElementById('contact');
    if (contactSection) {
        contactSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

        const form = document.getElementById('contactForm');
        if (form) {
            form.style.animation = 'pulse 0.5s ease 2';
            setTimeout(() => {
                form.style.animation = '';
            }, 1000);
        }
    }
}

function closeModal(modalId) {
    app.closeModal(modalId);
}

function toggleFAQ(index) {
    app.toggleFAQ(index);
}

// ========================================
// INITIALIZATION
// ========================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Инициализация приложения...');
    app.init();
    console.log('✅ Приложение запущено');
    console.log('✅ scrollToContactForm доступна:', typeof scrollToContactForm === 'function');
});

window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.active').forEach(modal => {
            modal.classList.remove('active');
        });
    }
});

window.reloadForm = function () {
    if (typeof app !== 'undefined' && app.renderDynamicFormFields) {
        CONFIG.loadFromDatabase();
        app.renderDynamicFormFields();
        app.showNotification('✅ Форма обновлена из настроек', 'success');
    }
}