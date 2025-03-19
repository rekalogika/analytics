import { Controller } from '@hotwired/stimulus'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.css'

export default class extends Controller {
    connect() {
        this.element.data = this.getData()
        this.lang = this.element.dataset.lang

        // ensure lang only contains alphanumeric or underscore
        this.lang = this.lang
            .toLowerCase()
            .replace(/-/g, '_')
            .replace(/[^a-zA-Z0-9_]/g, '')

        if (this.lang) {
            import("flatpickr/dist/l10n/" + this.lang + ".js")
                .catch((error) => {
                    this.initialize(null)
                })
                .then((module) => {
                    const lang = module.default.default[this.lang]

                    this.initialize(lang)
                })
        } else {
            this.initialize(null)
        }
    }

    initialize(lang) {
        let options = {
            mode: 'range',
            allowInput: true,
        }

        if (lang) {
            options.locale = lang
            console.log(lang)
        } else {
            console.log('flatpickr locale: default')
        }

        this.flatpickr = flatpickr(this.element, options)

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
