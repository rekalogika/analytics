import { Controller } from '@hotwired/stimulus'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.css'

// the wire format expected by DateRangeFilter on the server side
const FORMAT = 'Y-m-d'

/**
 * Maps a locale (e.g. 'fr_CA', 'zh-TW') to the flatpickr l10n file and the
 * key of the locale in flatpickr's l10ns object. Returns null if no locale
 * file should be loaded.
 */
function resolveLocale(locale) {
    // ensure locale only contains alphanumeric or underscore
    const normalized = (locale ?? '')
        .toLowerCase()
        .replace(/-/g, '_')
        .replace(/[^a-z0-9_]/g, '')

    // the zh_tw locale is in zh-tw.js
    if (normalized === 'zh_tw') {
        return { file: 'zh-tw', key: 'zh_tw' }
    }

    const language = normalized.replace(/_.*$/, '')

    switch (language) {
        case '':
        case 'en':
            return null
        case 'nb':
        case 'nn':
            return { file: 'no', key: 'no' }
        case 'vi':
            return { file: 'vn', key: 'vn' }
        default:
            return { file: language, key: language }
    }
}

export default class extends Controller {
    // incremented on every connect & disconnect, used to ignore locale
    // imports that resolve after the controller has been disconnected
    #connectionId = 0

    // the original placeholder, if we replace it
    #placeholder = null

    connect() {
        const connectionId = ++this.#connectionId

        this.element.data = this.getData()

        const locale = resolveLocale(this.element.dataset.lang)

        if (!locale) {
            this.#initFlatpickr(null)

            return
        }

        import("flatpickr/dist/l10n/" + locale.file + ".js")
            .then(
                (module) => {
                    // under AssetMapper (jsDelivr ESM wrapper), the default
                    // export is the CJS exports object, whose default is
                    // flatpickr's l10ns. under webpack, the default export is
                    // already the l10ns.
                    const l10ns = module.default
                    const lang = l10ns?.[locale.key] ?? l10ns?.default?.[locale.key]

                    if (!lang) {
                        console.warn(`Unable to find flatpickr locale '${locale.key}'`)
                    }

                    return lang ?? null
                },
                (error) => {
                    console.warn(`Unable to load flatpickr locale '${locale.file}'`, error)

                    return null
                }
            )
            .then((lang) => {
                if (connectionId !== this.#connectionId) {
                    return
                }

                this.#initFlatpickr(lang)
            })
    }

    #onChange = (selectedDates) => {
        // flatpickr fires a change after the first click of a range. ignore it
        // until the range is complete.
        if (selectedDates.length === 1) {
            return
        }

        this.#restorePlaceholder()
        this.element.data = this.getData()
        this.dispatch('change', {})
    }

    #restorePlaceholder() {
        if (this.#placeholder !== null) {
            this.element.placeholder = this.#placeholder
            this.#placeholder = null
        }
    }

    #initFlatpickr(lang) {
        let options = {
            mode: 'range',
            allowInput: true,
            dateFormat: FORMAT,
            onChange: this.#onChange,
        }

        if (lang) {
            options.locale = lang
        }

        const start = this.element.dataset.start
        const end = this.element.dataset.end

        if (start) {
            options.defaultDate = end ? [start, end] : [start]
        }

        this.flatpickr = flatpickr(this.element, options)

        // a range without a start cannot be represented by flatpickr, show the
        // end as the placeholder so that the user can see the filter
        if (!start && end) {
            this.#placeholder = this.element.placeholder
            this.element.placeholder = '… '
                + this.flatpickr.l10n.rangeSeparator.trim()
                + ' ' + end
        }
    }

    getData() {
        let start = ''
        let end = ''

        if (this.flatpickr) {
            const dates = this.flatpickr.selectedDates

            if (dates.length > 0) {
                start = this.flatpickr.formatDate(dates[0], FORMAT)
            }

            if (dates.length > 1) {
                end = this.flatpickr.formatDate(dates[dates.length - 1], FORMAT)
            }
        } else {
            // flatpickr is not initialized yet
            start = this.element.dataset.start ?? ''
            end = this.element.dataset.end ?? ''
        }

        return {
            dimension: this.element.dataset.dimension,
            start: start,
            end: end
        }
    }

    disconnect() {
        this.#connectionId++
        this.flatpickr?.destroy()
        this.flatpickr = null
        this.#restorePlaceholder()
    }
}
