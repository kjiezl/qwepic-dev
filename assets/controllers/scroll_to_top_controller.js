import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.element.classList.add('hidden');
        window.addEventListener('scroll', this.toggle.bind(this));
    }

    disconnect() {
        window.removeEventListener('scroll', this.toggle.bind(this));
    }

    toggle() {
        if (window.scrollY > 300) {
            this.element.classList.remove('hidden');
        } else {
            this.element.classList.add('hidden');
        }
    }

    top() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
}
