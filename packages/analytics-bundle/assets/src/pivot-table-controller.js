import { Controller } from '@hotwired/stimulus'
import Sortable from 'sortablejs'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    #animation = 150
    #group

    connect() {
        this.#group = 'g' + Math.random().toString(36)

        this.itemsElement = this.element.querySelector('.available')
        this.rowsElement = this.element.querySelector('.rows')
        this.columnsElement = this.element.querySelector('.columns')
        this.valuesElement = this.element.querySelector('.values')
        this.filtersElement = this.element.querySelector('.filters')

        this.sortableItems = Sortable.create(this.itemsElement, {
            group: this.#group,
            animation: this.#animation,
            onMove: this.#onMove.bind(this),
            onEnd: this.#onEnd.bind(this)
        })

        this.sortableRows = Sortable.create(this.rowsElement, {
            group: this.#group,
            animation: this.#animation,
            onMove: this.#onMove.bind(this),
            onEnd: this.#onEnd.bind(this)
        })

        this.sortableColumns = Sortable.create(this.columnsElement, {
            group: this.#group,
            animation: this.#animation,
            onMove: this.#onMove.bind(this),
            onEnd: this.#onEnd.bind(this)
        })

        this.sortableValues = Sortable.create(this.valuesElement, {
            group: this.#group,
            animation: this.#animation,
            onMove: this.#onMove.bind(this),
            onEnd: this.#onEnd.bind(this)
        })

        this.sortableFilters = Sortable.create(this.filtersElement, {
            group: this.#group,
            animation: this.#animation,
            onMove: this.#onMove.bind(this),
            onEnd: this.#onEnd.bind(this)
        })

        this.element.querySelectorAll('select').forEach((select) => {
            select.addEventListener('change', () => {
                this.#submit()
            })
        })

        // this.#submit()
    }

    disconnect() {
        this.sortableItems.destroy()
        this.sortableRows.destroy()
        this.sortableColumns.destroy()
        this.sortableValues.destroy()
        this.sortableFilters.destroy()
    }

    #onEnd(event) {
        this.#submit()
    }

    #onMove(event, originalEvent) {
        let itemType = event.dragged.dataset.type
        let targetType = event.to.dataset.type

        if (itemType === 'values') {
            if (['rows', 'columns'].includes(targetType)) {
                return true
            }
        }

        if (itemType === 'dimension') {
            if (['available', 'rows', 'columns', 'filters'].includes(targetType)) {
                return true
            }
        }

        if (itemType === 'measure') {
            if (['available', 'values'].includes(targetType)) {
                return true
            }
        }

        return false
    }

    #submit() {
        this.#removeAllHiddenInputs()

        this.element.querySelectorAll('ul').forEach((ul, index) => {
            let ultype = ul.dataset.type

            if (!['rows', 'columns', 'values', 'filters'].includes(ultype)) {
                return
            }

            ul.querySelectorAll('li').forEach((li, index) => {
                let input = document.createElement('input')
                input.type = 'hidden'
                input.name = ultype + '[' + index + ']'
                let value = li.dataset.value

                let select = li.querySelector('select')

                if (select) {
                    value += '.' + select.value
                }

                input.value = value

                this.element.appendChild(input)
            })
        })

        // this.#removeAllSelectInputs()
        this.element.requestSubmit()
    }

    #removeAllHiddenInputs() {
        let formElements = this.element.querySelectorAll('input[type="hidden"]')
        formElements.forEach((formElement) => {
            formElement.remove()
        })
    }

    #removeAllSelectInputs() {
        let formElements = this.element.querySelectorAll('select')
        formElements.forEach((formElement) => {
            formElement.remove()
        })
    }
}
