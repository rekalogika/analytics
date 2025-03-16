import { Controller } from '@hotwired/stimulus'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.css'

export default class extends Controller {
    connect() {
        this.element.data = this.getData()

        this.flatpickr = flatpickr(this.element, {
            mode: 'range',
            allowInput: true,
        })

        this.element.addEventListener('change', () => {
            this.element.data = this.getData()
            this.dispatch('change', {})
        })
    }

    getData() {
        const value = this.element.value

        // get start by using regex to remove first space to the end of string
        const start = value.replace(/ .*$/, '')

        // get end by removing start to the last space
        const end = value.replace(/^.* /, '')

        return {
            dimension: this.element.dataset.dimension,
            start: start,
            end: end
        }
    }

    disconnect() {
        this.flatpickr.destroy()
    }
}
