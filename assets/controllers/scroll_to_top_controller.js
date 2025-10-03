import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.element.classList.add('opacity-0', 'translate-y-5', 'pointer-events-none', 'transition-all', 'duration-300');
        window.addEventListener('scroll', this.toggle.bind(this));
    }

    disconnect() {
        window.removeEventListener('scroll', this.toggle.bind(this));
    }

    toggle() {
        if (window.scrollY > 300) {
            this.element.classList.remove('opacity-0', 'translate-y-5', 'pointer-events-none');
            this.element.classList.add('opacity-100', 'translate-y-0', 'pointer-events-auto');
        } else {
            this.element.classList.remove('opacity-100', 'translate-y-0', 'pointer-events-auto');
            this.element.classList.add('opacity-0', 'translate-y-5', 'pointer-events-none');
        }
    }

    top() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
}