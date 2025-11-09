// ========================================
// MAIN APPLICATION
// ========================================

class MainApp {
    constructor() {
        this.currentTestimonial = 0;
        this.currentFilter = 'all';
        this.autoSlideInterval = null;
    }

    init() {
        this.initPreloader();
        this.initNavigation();
        this.initThemeToggle();
        this.initPhoneMasks();
        this.loadContent();
        this.initStats();
        this.loadServices();
        this.loadPortfolio();
        this.loadTestimonials();
        this.loadFAQ();
        this.initForms();
        this.initScrollAnimations();
        this.initCalculator();
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
        });
    }

    formatPhone(input) {
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
        });

        window.addEventListener('scroll', () => {
            let current = '';
            const sections = document.querySelectorAll('section[id]');

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (scrollY >= (sectionTop - 200)) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href').includes(current)) {
                    link.classList.add('active');
                }
            });
        });

        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('href');
                const targetSection = document.querySelector(targetId);

                if (targetSection) {
                    navMenu.classList.remove('active');
                    hamburger.classList.remove('active');

                    window.scrollTo({
                        top: targetSection.offsetTop - 80,
                        behavior: 'smooth'
                    });
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

    loadContent() {
        const content = db.getData('content')[0] || db.getDefaultContent();
        const settings = db.getData('settings')[0] || db.getDefaultSettings();
        const stats = db.getData('stats')[0] || db.getDefaultStats();

        if (content.hero) {
            const heroTitle = document.getElementById('heroTitle');
            if (heroTitle) heroTitle.textContent = content.hero.title || 'идеи в реальность';

            const heroDescription = document.getElementById('heroDescription');
            if (heroDescription) heroDescription.textContent = content.hero.subtitle || '';
        }

        const contactAddress = document.getElementById('contactAddress');
        if (contactAddress) contactAddress.textContent = settings.address || '';

        const contactPhone = document.getElementById('contactPhone');
        if (contactPhone) contactPhone.textContent = settings.contactPhone || '';

        const contactEmail = document.getElementById('contactEmail');
        if (contactEmail) contactEmail.textContent = settings.contactEmail || '';

        const contactHours = document.getElementById('contactHours');
        if (contactHours) contactHours.innerHTML = (settings.workingHours || '').replace(/\n/g, '<br>');

        const siteName = document.getElementById('siteName');
        if (siteName && settings.siteName) {
            siteName.innerHTML = settings.siteName.replace('Pro', '<strong>Pro</strong>');
        }

        this.loadSocialLinks(settings.socialLinks || {});
        this.updateStatsTargets(stats);
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

    loadServices() {
        const services = db.getData('services').filter(s => s.active);
        const grid = document.getElementById('servicesGrid');
        if (!grid) return;

        grid.innerHTML = services.map(service => `
            <div class="service-card ${service.featured ? 'featured' : ''}">
                ${service.featured ? '<div class="featured-badge">Популярное</div>' : ''}
                <div class="service-icon">
                    <i class="fas ${service.icon}"></i>
                </div>
                <h3>${service.name}</h3>
                <p>${service.description}</p>
                <ul class="service-features">
                    ${(service.features || []).map(f => `
                        <li><i class="fas fa-check"></i> ${f}</li>
                    `).join('')}
                </ul>
                <button class="btn btn-sm ${service.featured ? 'btn-primary' : ''}" onclick="app.openServiceModal('${service.slug}')">
                    Подробнее
                </button>
            </div>
        `).join('');
    }

    openServiceModal(slug) {
        const service = db.getData('services').find(s => s.slug === slug);
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
                <button class="btn btn-outline" onclick="window.location.href='#contact'">
                    <i class="fas fa-phone"></i>
                    Консультация
                </button>
                <a href="${CONFIG.telegram.contactUrl}" target="_blank" class="btn btn-outline" style="text-decoration: none;">
                    <i class="fab fa-telegram"></i>
                    Написать в Telegram
                </a>
            </div>
        `;

        modal.classList.add('active');
    }

    loadPortfolio() {
        const items = db.getData('portfolio');
        this.renderPortfolio(items);
        this.initPortfolioFilters();
    }

    renderPortfolio(items = null) {
        if (!items) {
            items = db.getData('portfolio');
        }

        const grid = document.getElementById('portfolioGrid');
        if (!grid) return;

        const filtered = this.currentFilter === 'all'
            ? items
            : items.filter(item => item.category === this.currentFilter);

        if (filtered.length === 0) {
            grid.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--text-secondary); grid-column: 1/-1;">Работ не найдено</p>';
            return;
        }

        grid.innerHTML = filtered.map(item => `
            <div class="portfolio-item" data-category="${item.category}" onclick="app.openPortfolioModal('${item.id}')">
                <img src="${item.image}" alt="${item.title}" class="portfolio-image" loading="lazy">
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
        const item = db.getData('portfolio').find(i => i.id === id);
        if (!item) return;

        const modal = document.getElementById('portfolioModal');
        const content = document.getElementById('portfolioModalContent');

        content.innerHTML = `
            <img src="${item.image}" alt="${item.title}" style="width: 100%; border-radius: 12px; margin-bottom: 20px;">
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

    loadTestimonials() {
        const testimonials = db.getData('testimonials').filter(t => t.approved);
        const slider = document.getElementById('testimonialsSlider');
        if (!slider) return;

        if (testimonials.length === 0) {
            slider.innerHTML = '<p style="text-align: center; padding: 40px; color: var(--text-secondary);">Отзывов пока нет</p>';
            return;
        }

        slider.innerHTML = testimonials.map((item, index) => `
            <div class="testimonial-card ${index === 0 ? 'active' : ''}">
                <img src="${item.avatar}" alt="${item.name}" class="testimonial-avatar">
                <div class="testimonial-rating">
                    ${'★'.repeat(item.rating)}
                </div>
                <p class="testimonial-text">"${item.text}"</p>
                <h4 class="testimonial-author">${item.name}</h4>
                <p class="testimonial-position">${item.position}</p>
            </div>
        `).join('');

        this.initTestimonialsSlider(testimonials.length);
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

    loadFAQ() {
        const faqs = db.getData('faq').filter(f => f.active);
        const list = document.getElementById('faqList');
        if (!list) return;

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

    initForms() {
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleUniversalForm(e.target);
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

    handleUniversalForm(form) {
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

        // Сохраняем в БД
        const savedOrder = db.addItem('orders', order);

        // Отправка в Telegram
        if (CONFIG.features.telegramNotifications && CONFIG.telegram.chatId) {
            const sendMethod = calculatorData ? 'sendOrderNotification' : 'sendContactNotification';
            telegramBot[sendMethod](savedOrder).then(result => {
                if (result.success) {
                    db.updateItem('orders', savedOrder.id, { telegramSent: true });
                }
            });
        }

        this.showNotification('✅ Спасибо! Ваше сообщение отправлено. Мы свяжемся с вами в ближайшее время.', 'success');

        // Очистка формы
        form.reset();

        const calcInfo = document.getElementById('calculationInfo');
        if (calcInfo) calcInfo.style.display = 'none';

        const formTitle = document.getElementById('formTitle');
        if (formTitle) {
            formTitle.innerHTML = '<i class="fas fa-envelope"></i> Свяжитесь с нами';
        }
    }

    hhandleUniversalForm(form) {
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

        // Сохраняем в БД
        const savedOrder = db.addItem('orders', order);

        // Отправка в Telegram
        if (CONFIG.features.telegramNotifications && CONFIG.telegram.chatId) {
            const sendMethod = calculatorData ? 'sendOrderNotification' : 'sendContactNotification';
            telegramBot[sendMethod](savedOrder).then(result => {
                if (result.success) {
                    db.updateItem('orders', savedOrder.id, { telegramSent: true });
                }
            });
        }

        this.showNotification('✅ Спасибо! Ваше сообщение отправлено. Мы свяжемся с вами в ближайшее время.', 'success');

        // Очистка формы
        form.reset();

        const calcInfo = document.getElementById('calculationInfo');
        if (calcInfo) calcInfo.style.display = 'none';

        const formTitle = document.getElementById('formTitle');
        if (formTitle) {
            formTitle.innerHTML = '<i class="fas fa-envelope"></i> Свяжитесь с нами';
        }
    }

    renderDynamicFormFields() {
    const container = document.getElementById('dynamicFormFields');
    if (!container) return;
    
    // Фильтруем только активные поля и сортируем по order
    const fields = CONFIG.formFields.contact
        .filter(f => f.enabled)
        .sort((a, b) => (a.order || 0) - (b.order || 0));
    
    container.innerHTML = fields.map(field => {
        let inputHTML = '';
        
        switch(field.type) {
            case 'text':
            case 'email':
            case 'tel':
                inputHTML = `<input 
                    type="${field.type}" 
                    name="${field.name}" 
                    class="form-control" 
                    placeholder="${field.placeholder || field.label}${field.required ? ' *' : ''}" 
                    ${field.required ? 'required' : ''}>`;
                break;
            
            case 'textarea':
                inputHTML = `<textarea 
                    name="${field.name}" 
                    class="form-control" 
                    rows="5" 
                    placeholder="${field.placeholder || field.label}${field.required ? ' *' : ''}" 
                    ${field.required ? 'required' : ''}></textarea>`;
                break;
            
            case 'select':
                const options = field.options || [];
                inputHTML = `
                    <select 
                        name="${field.name}" 
                        class="form-control" 
                        ${field.name === 'subject' ? 'id="formSubject"' : ''}
                        ${field.required ? 'required' : ''}>
                        <option value="">${field.placeholder || field.label}${field.required ? ' *' : ''}</option>
                        ${options.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                    </select>
                `;
                break;
            
            case 'checkbox':
                inputHTML = `
                    <label class="checkbox-label">
                        <input type="checkbox" name="${field.name}" ${field.required ? 'required' : ''}>
                        <span>${field.placeholder || field.label}</span>
                    </label>
                `;
                break;
            
            default:
                inputHTML = `<input 
                    type="text" 
                    name="${field.name}" 
                    class="form-control" 
                    placeholder="${field.placeholder || field.label}${field.required ? ' *' : ''}"
                    ${field.required ? 'required' : ''}>`;
        }
        
        return `
            <div class="form-group">
                ${inputHTML}
            </div>
        `;
    }).join('');
    
    console.log('✅ Динамические поля формы обновлены. Активных полей:', fields.length);
    console.log('📋 Обязательные поля:', fields.filter(f => f.required).map(f => f.name).join(', '));
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

    generateOrderNumber() {
        const orders = db.getData('orders');
        const maxNumber = orders.reduce((max, o) => {
            const num = parseInt(o.orderNumber) || 0;
            return num > max ? num : max;
        }, 1000);
        return (maxNumber + 1).toString();
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

    showNotification(message, type = 'info') {
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
        }, 3000);
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