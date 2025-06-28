import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    connect() {
        if (!this.element instanceof HTMLInputElement) {
            throw new Error('Element is not a select element')
        }

        this.element.data = this.getData()

        this.element.addEventListener('change', () => {
            this.element.data = this.getData()
            this.dispatch('change', {})
        })
    }

    getData() {
        return {
            dimension: this.element.dataset.dimension,
            value: this.element.value
        }
    }
}
