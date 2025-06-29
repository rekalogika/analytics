import { Controller } from '@hotwired/stimulus'
import TomSelect from 'tom-select'

export default class extends Controller {
    connect() {
        if (!this.element instanceof HTMLSelectElement) {
            throw new Error('Element is not a select element')
        }

        this.element.labelEmpty = this.element.dataset.labelEmpty
        this.element.labelSelected = this.element.dataset.labelSelected
        this.element.data = this.getData()

        this.tomSelect = new TomSelect(this.element, {
            maxItems: 500,
            allowEmptyOption: true,
            plugins: {
                remove_button: {},
                clear_button: {}
            },
            render: {
                option: function (data, escape) {
                    return `<div>${data.html}</div>`;
                },
                item: function (item, escape) {
                    return `<div>${item.html}</div>`;
                }
            }
        })

        this.element.addEventListener('change', () => {
            // change placeholder text based on selected options
            this.updatePlaceholder()

            // dispatch change event
            this.element.data = this.getData()
            this.dispatch('change', {})
        })

        this.updatePlaceholder()
    }

    updatePlaceholder() {
        if (this.element.selectedOptions.length === 0) {
            if (this.element.labelEmpty) {
                this.tomSelect.settings.placeholder = this.element.labelEmpty
                this.tomSelect.inputState()
            }
        } else {
            if (this.element.labelSelected) {
                this.tomSelect.settings.placeholder = this.element.labelSelected
                this.tomSelect.inputState()
            }
        }
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
