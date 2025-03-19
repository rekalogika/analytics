import { Controller } from '@hotwired/stimulus'
import { visit } from '@hotwired/turbo'
import Sortable from 'sortablejs'

export default class extends Controller {
    static values = {
        urlParameter: String,
        frame: String,
    }

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
                if (
                    select.closest('.filters')
                    || select.closest('.rows')
                    || select.closest('.columns')
                ) {
                    this.filterChanged = true
                }

                this.#submit()
            })
        })

        document.addEventListener('turbo:before-frame-render', this.beforeFrameRender.bind(this))
    }

    beforeFrameRender(event) {
        console.log(event)
        if (this.filterChanged) {
            event.detail.render = (currentElement, newElement) => {
                currentElement.replaceWith(newElement)
            }

            this.filterChanged = false
        }
    }

    disconnect() {
        this.sortableItems.destroy()
        this.sortableRows.destroy()
        this.sortableColumns.destroy()
        this.sortableValues.destroy()
        this.sortableFilters.destroy()

        document.removeEventListener('turbo:before-frame-render', this.beforeFrameRender.bind(this))
    }

    getData() {
        let data = {}

        const uls = this.element.querySelectorAll('ul')

        for (const ul of uls) {
            let type = ul.dataset.type

            if (!['rows', 'columns', 'values', 'filters'].includes(type)) {
                continue
            }

            let lis = ul.querySelectorAll('li')

            for (const [index, li] of lis.entries()) {
                let value = li.dataset.value
                let select = li.querySelector('select')

                if (select) {
                    value += '.' + select.value
                }

                // data[type + '[' + index + ']'] = value

                if (!data[type]) {
                    data[type] = []
                }

                data[type][index] = value
            }
        }

        // initialize filterexpressions
        let filterExpressions = {};

        // filters
        const filterElements = this.element.querySelectorAll('.filterelement')

        for (const filterElement of filterElements) {
            let data = filterElement.data

            if (!data) {
                continue
            }

            let dimension = data.dimension

            filterExpressions[dimension] = data
        }

        // finishing
        data['filterExpressions'] = filterExpressions

        return data
    }

    filter() {
        this.#submit()
    }

    #onEnd(event) {
        let sourceType = event.from.dataset.type
        let targetType = event.to.dataset.type

        if (
            targetType === 'filters' || sourceType === 'filters'
            || targetType === 'rows' || sourceType === 'rows'
            || targetType === 'columns' || sourceType === 'columns'
        ) {
            this.filterChanged = true
        }

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
        if (this.urlParameterValue && this.frameValue) {
            const url = new URL(window.location)
            url.searchParams.set(this.urlParameterValue, JSON.stringify(this.getData()))

            if (this.filterChanged) {
                visit(url.toString(), { 'frame': '__filters', 'action': 'replace' })
                this.filterChanged = false
            }

            visit(url.toString(), { 'frame': this.frameValue, 'action': 'advance' })
        }
    }
}
