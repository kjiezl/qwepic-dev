import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['track', 'slide', 'indicators', 'container'];

    connect() {
        this.currentSlide = 0;
        this.totalSlides = this.slideTargets.length;
        this.updateCarousel();
    }

    next() {
        this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
        this.updateCarousel();
    }

    prev() {
        this.currentSlide = this.currentSlide === 0 ? this.totalSlides - 1 : this.currentSlide - 1;
        this.updateCarousel();
    }

    goToSlide(event) {
        const slideIndex = parseInt(event.currentTarget.dataset.slideIndex);
        this.currentSlide = slideIndex;
        this.updateCarousel();
    }

    updateCarousel() {
        this.slideTargets.forEach((slide, index) => {
            slide.classList.remove('active');
            slide.classList.remove('opacity-100');
            slide.classList.add('opacity-0');
            slide.style.position = 'absolute';
            slide.style.zIndex = '0';
        });

        if (this.slideTargets[this.currentSlide]) {
            const activeSlide = this.slideTargets[this.currentSlide];
            activeSlide.classList.add('active');
            activeSlide.classList.remove('opacity-0');
            activeSlide.classList.add('opacity-100');
            activeSlide.style.position = 'relative';
            activeSlide.style.zIndex = '1';

            activeSlide.offsetHeight;

            if (this.hasContainerTarget) {
                const activeSlideHeight = activeSlide.offsetHeight;
                if (activeSlideHeight > 0) {
                    this.containerTarget.style.height = activeSlideHeight + 'px';
                }
            }
        }

        if (this.hasIndicatorsTarget) {
            const indicators = this.indicatorsTarget.querySelectorAll('.carousel-indicator');
            indicators.forEach((indicator, index) => {
                if (index === this.currentSlide) {
                    indicator.classList.add('active');
                } else {
                    indicator.classList.remove('active');
                }
            });
        }
    }
}
