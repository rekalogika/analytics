import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
  connect() {
    if (!(this.element instanceof HTMLInputElement)) {
      throw new Error('Element is not an input element');
    }
    this.element.data = this.getData();
    this.element.addEventListener('change', this.#onChange);
  }
  #onChange = () => {
    this.element.data = this.getData();
    this.dispatch('change', {});
  };
  disconnect() {
    this.element.removeEventListener('change', this.#onChange);
  }
  getData() {
    return {
      dimension: this.element.dataset.dimension,
      value: this.element.value
    };
  }
}