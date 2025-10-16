import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['item'];
    static values = {
        stagger: { type: Number, default: 0 },
        threshold: { type: Number, default: 0.1 },
        rootMargin: { type: String, default: '0px 0px -100px 0px' },
        animation: { type: String, default: 'slideUp' }
    };

    connect() {
        this.setupAnimations();
        this.createObserver();
        this.observeElements();
    }

    disconnect() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    setupAnimations() {
        this.itemTargets.forEach((item, index) => {
            // Set initial state based on animation type
            item.style.opacity = '0';

            switch (this.animationValue) {
                case 'slideUp':
                    item.style.transform = 'translateY(30px)';
                    break;
                case 'slideInLeft':
                    item.style.transform = 'translateX(-30px)';
                    break;
                case 'slideInRight':
                    item.style.transform = 'translateX(30px)';
                    break;
                case 'scaleIn':
                    item.style.transform = 'scale(0.9)';
                    break;
                case 'fadeIn':
                default:
                    // Just opacity for fade in
                    break;
            }

            // Add staggered delay if specified
            if (this.staggerValue > 0) {
                item.style.transitionDelay = `${index * this.staggerValue}ms`;
            }

            item.classList.add('transition-all', 'duration-700', 'ease-out');
        });
    }

    createObserver() {
        const options = {
            threshold: this.thresholdValue,
            rootMargin: this.rootMarginValue
        };

        this.observer = new IntersectionObserver(this.onIntersection.bind(this), options);
    }

    observeElements() {
        this.itemTargets.forEach(item => {
            this.observer.observe(item);
        });
    }

    onIntersection(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                this.animateElement(entry.target);
                // Unobserve after animation to improve performance
                this.observer.unobserve(entry.target);
            }
        });
    }

    animateElement(element) {
        // Remove initial state and add final state
        element.style.opacity = '1';

        switch (this.animationValue) {
            case 'slideUp':
                element.style.transform = 'translateY(0)';
                break;
            case 'slideInLeft':
                element.style.transform = 'translateX(0)';
                break;
            case 'slideInRight':
                element.style.transform = 'translateX(0)';
                break;
            case 'scaleIn':
                element.style.transform = 'scale(1)';
                break;
            case 'fadeIn':
            default:
                // Already handled opacity
                break;
        }

        // Add visible class for CSS-based animations
        element.classList.add('animate-visible');
    }

    // Method to manually trigger animations (useful for testing or dynamic content)
    animateAll() {
        this.itemTargets.forEach(item => {
            this.animateElement(item);
        });
    }

    // Method to reset animations
    resetAnimations() {
        this.itemTargets.forEach(item => {
            item.style.opacity = '0';
            item.classList.remove('animate-visible');

            switch (this.animationValue) {
                case 'slideUp':
                    item.style.transform = 'translateY(30px)';
                    break;
                case 'slideInLeft':
                    item.style.transform = 'translateX(-30px)';
                    break;
                case 'slideInRight':
                    item.style.transform = 'translateX(30px)';
                    break;
                case 'scaleIn':
                    item.style.transform = 'scale(0.9)';
                    break;
            }
        });
    }
}