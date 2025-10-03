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
            slide.classList.remove('block', 'opacity-100', 'relative', 'z-10');
            slide.classList.add('hidden', 'opacity-0', 'absolute', 'z-0');
        });

        if (this.slideTargets[this.currentSlide]) {
            const activeSlide = this.slideTargets[this.currentSlide];
            activeSlide.classList.remove('hidden', 'opacity-0', 'absolute', 'z-0');
            activeSlide.classList.add('block', 'opacity-100', 'relative', 'z-10');

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
                indicator.classList.toggle('bg-vivid-sky-blue', index === this.currentSlide);
                indicator.classList.toggle('opacity-100', index === this.currentSlide);
                indicator.classList.toggle('opacity-50', index !== this.currentSlide);
            });
        }
    }
}