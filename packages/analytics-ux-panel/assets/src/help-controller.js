import { Controller } from '@hotwired/stimulus'
import tippy from 'tippy.js'
import 'tippy.js/dist/tippy.css'
import 'tippy.js/animations/scale-extreme.css'

export default class extends Controller {
    connect() {
        const content = this.element.dataset.help
        const delay = this.element.dataset.helpDelay

        this.tippy = tippy(this.element, {
            content: content,
            delay: [delay ? parseInt(delay) : null, null],
            animation: 'scale-extreme',
        })
    }

    disconnect() {
        if (this.tippy) {
            this.tippy.destroy()
        }
    }
}
