import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['item'];

    connect() {
        this.itemTargets.forEach(item => {
            item.classList.add('opacity-0', 'translate-y-5', 'transition-all', 'duration-700');
        });

        const observer = new IntersectionObserver(this.onIntersection.bind(this), {
            rootMargin: '0px 0px -100px 0px',
        });

        this.itemTargets.forEach(item => {
            observer.observe(item);
        });
    }

    onIntersection(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.remove('opacity-0', 'translate-y-5');
                entry.target.classList.add('opacity-100', 'translate-y-0');
            }
        });
    }
}