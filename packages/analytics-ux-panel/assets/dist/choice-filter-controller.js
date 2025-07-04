function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == typeof e || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inheritsLoose(t, o) { t.prototype = Object.create(o.prototype), t.prototype.constructor = t, _setPrototypeOf(t, o); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';
var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    return _callSuper(this, _default, arguments);
  }
  _inheritsLoose(_default, _Controller);
  var _proto = _default.prototype;
  _proto.connect = function connect() {
    var _this = this;
    if (!this.element instanceof HTMLSelectElement) {
      throw new Error('Element is not a select element');
    }
    this.element.labelEmpty = this.element.dataset.labelEmpty;
    this.element.labelSelected = this.element.dataset.labelSelected;
    this.element.data = this.getData();
    this.tomSelect = new TomSelect(this.element, {
      maxItems: 500,
      allowEmptyOption: true,
      plugins: {
        remove_button: {},
        clear_button: {}
      },
      render: {
        option: function option(data, escape) {
          return "<div>" + data.html + "</div>";
        },
        item: function item(_item, escape) {
          return "<div>" + _item.html + "</div>";
        }
      }
    });
    this.element.addEventListener('change', function () {
      // change placeholder text based on selected options
      _this.updatePlaceholder();

      // dispatch change event
      _this.element.data = _this.getData();
      _this.dispatch('change', {});
    });
    this.updatePlaceholder();
  };
  _proto.updatePlaceholder = function updatePlaceholder() {
    if (this.element.selectedOptions.length === 0) {
      if (this.element.labelEmpty) {
        this.tomSelect.settings.placeholder = this.element.labelEmpty;
        this.tomSelect.inputState();
      }
    } else {
      if (this.element.labelSelected) {
        this.tomSelect.settings.placeholder = this.element.labelSelected;
        this.tomSelect.inputState();
      }
    }
  };
  _proto.getData = function getData() {
    var values = Array.from(this.element.selectedOptions).map(function (_ref) {
      var value = _ref.value;
      return value;
    });
    return {
      dimension: this.element.dataset.dimension,
      values: values
    };
  };
  _proto.disconnect = function disconnect() {
    this.tomSelect.destroy();
  };
  return _default;
}(Controller);
export { _default as default };