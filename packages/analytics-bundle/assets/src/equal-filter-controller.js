import { Controller } from '@hotwired/stimulus'
import TomSelect from 'tom-select'

export default class extends Controller {
    connect() {
        this.element.data = this.getData()

        this.tomSelect = new TomSelect(this.element, {
            maxItems: 500,
            allowEmptyOption: true,
            plugins: {
                remove_button: {}
            },
        })

        this.element.addEventListener('change', () => {
            this.element.data = this.getData()
            this.dispatch('change', {})
        })
    }

    getData() {
        const values = Array.from(this.element.selectedOptions).map(({ value }) => value)

        return {
            dimension: this.element.dataset.dimension,
            values: values
        }
    }

    disconnect() {
        this.tomSelect.destroy()
    }
}
