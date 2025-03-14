import { Controller } from '@hotwired/stimulus'
import TomSelect from 'tom-select'
import 'tom-select/dist/css/tom-select.default.min.css'

export default class extends Controller {
    connect() {
        this.tomSelect = new TomSelect(this.element, {
            maxItems: 50,
            allowEmptyOption: true,
            plugins: {
                remove_button: {}
            },
        })

        this.element.addEventListener('change', () => {
            this.dispatch('change', {})
        })
    }

    disconnect() {
        this.tomSelect.destroy()
    }
}
