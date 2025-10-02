import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['item'];

    connect() {
        this.itemTargets.forEach(item => {
            item.classList.add('is-visible');
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
                entry.target.classList.add('is-visible');
            }
        });
    }
}
