// ========================================
// SIMPLIFIED MAIN APPLICATION
// Static content-driven with UI interactions only
// ========================================

class StaticApp {
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
        this.initStats();
        this.initPortfolioFilters();
        this.initTestimonials();
        this.initForms();
        this.initScrollAnimations();
        this.initSmoothScroll();
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

        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (navMenu.classList.contains('active')) {
                    hamburger.classList.remove('active');
                    navMenu.classList.remove('active');
                    document.body.classList.remove('nav-open');
                }
            });
        });

        const currentPath = window.location.pathname.split('/').pop() || 'index.php';
        const isHomePage = currentPath === 'index.php' || currentPath === '' || currentPath === 'index.html';
        
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
                        link.classList.remove('active');
                        if (linkHref === '#' + current) {
                            link.classList.add('active');
                        }
                    }
                });
            });
        }
    }

    initThemeToggle() {
        const themeToggle = document.getElementById('themeToggle');
        const savedTheme = localStorage.getItem('theme') || 'light';
        
        document.body.setAttribute('data-theme', savedTheme);
        this.updateThemeIcon(savedTheme);

        themeToggle?.addEventListener('click', () => {
            const currentTheme = document.body.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            this.updateThemeIcon(newTheme);
        });
    }

    updateThemeIcon(theme) {
        const themeToggle = document.getElementById('themeToggle');
        if (!themeToggle) return;
        
        const icon = themeToggle.querySelector('i');
        if (theme === 'dark') {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        } else {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
    }

    initStats() {
        const statsGrid = document.getElementById('statsGrid');
        if (!statsGrid) return;

        let animated = false;

        const animateStats = () => {
            if (animated) return;

            const statNumbers = statsGrid.querySelectorAll('.stat-number');
            const rect = statsGrid.getBoundingClientRect();

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

    initPortfolioFilters() {
        const filterBtns = document.querySelectorAll('.filter-btn');
        const portfolioItems = document.querySelectorAll('.portfolio-item');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const filter = btn.getAttribute('data-filter');

                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                portfolioItems.forEach(item => {
                    const category = item.getAttribute('data-category');
                    if (filter === 'all' || category === filter) {
                        item.style.display = 'block';
                        setTimeout(() => item.classList.add('visible'), 10);
                    } else {
                        item.classList.remove('visible');
                        setTimeout(() => item.style.display = 'none', 300);
                    }
                });
            });
        });

        portfolioItems.forEach(item => {
            item.classList.add('visible');
        });
    }

    initTestimonials() {
        const container = document.getElementById('testimonialsContainer');
        if (!container) return;

        const testimonials = container.querySelectorAll('.testimonial-card');
        if (testimonials.length <= 1) return;

        this.autoSlideInterval = setInterval(() => {
            this.changeTestimonial(1);
        }, 5000);

        container.addEventListener('mouseenter', () => {
            clearInterval(this.autoSlideInterval);
        });

        container.addEventListener('mouseleave', () => {
            this.autoSlideInterval = setInterval(() => {
                this.changeTestimonial(1);
            }, 5000);
        });
    }

    changeTestimonial(direction) {
        const container = document.getElementById('testimonialsContainer');
        if (!container) return;

        const testimonials = container.querySelectorAll('.testimonial-card');
        if (testimonials.length === 0) return;

        testimonials[this.currentTestimonial].classList.remove('active');
        
        this.currentTestimonial += direction;
        
        if (this.currentTestimonial >= testimonials.length) {
            this.currentTestimonial = 0;
        } else if (this.currentTestimonial < 0) {
            this.currentTestimonial = testimonials.length - 1;
        }
        
        testimonials[this.currentTestimonial].classList.add('active');
    }

    initForms() {
        const contactForm = document.getElementById('contactForm');
        const subscribeForm = document.getElementById('subscribeForm');

        if (contactForm) {
            contactForm.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }

        if (subscribeForm) {
            subscribeForm.addEventListener('submit', (e) => this.handleSubscribeSubmit(e));
        }

        const dynamicFields = document.getElementById('dynamicFormFields');
        if (dynamicFields) {
            dynamicFields.innerHTML = '';
        }
    }

    handleFormSubmit(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        console.log('Form submitted:', data);
        
        if (typeof TelegramIntegration !== 'undefined') {
            TelegramIntegration.sendMessage('contact', data);
        }
        
        this.showNotification('✅ Спасибо! Мы свяжемся с вами в ближайшее время.', 'success');
        e.target.reset();
    }

    handleSubscribeSubmit(e) {
        e.preventDefault();
        
        const email = e.target.querySelector('input[name="email"]').value;
        console.log('Subscribe email:', email);
        
        this.showNotification('✅ Спасибо за подписку!', 'success');
        e.target.reset();
    }

    initScrollAnimations() {
        const animatedElements = document.querySelectorAll('.service-card, .portfolio-item, .testimonial-card, .faq-item, .stat-card');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });

        animatedElements.forEach(el => observer.observe(el));
    }

    initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href === '#') return;
                
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    const headerOffset = 80;
                    const elementPosition = target.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
}

// Global functions for inline event handlers
function toggleFAQ(element) {
    const faqItem = element.parentElement;
    const answer = faqItem.querySelector('.faq-answer');
    const icon = element.querySelector('i');
    
    const isActive = faqItem.classList.contains('active');
    
    document.querySelectorAll('.faq-item').forEach(item => {
        item.classList.remove('active');
        item.querySelector('.faq-answer').style.display = 'none';
        item.querySelector('.faq-question i').style.transform = 'rotate(0deg)';
    });
    
    if (!isActive) {
        faqItem.classList.add('active');
        answer.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    }
}

function changeTestimonial(direction) {
    if (window.app) {
        window.app.changeTestimonial(direction);
    }
}

function scrollToContactForm() {
    // Try order form first (preferred), fallback to contact form
    const orderForm = document.getElementById('order-form-section');
    const contactForm = document.getElementById('contact-form');
    
    if (orderForm) {
        orderForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else if (contactForm) {
        contactForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function handleFormSubmit(e) {
    if (window.app) {
        window.app.handleFormSubmit(e);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new StaticApp();
    window.app.init();
    console.log('✅ Static app initialized');
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = StaticApp;
}
