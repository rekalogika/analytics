function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == typeof e || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inheritsLoose(t, o) { t.prototype = Object.create(o.prototype), t.prototype.constructor = t, _setPrototypeOf(t, o); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
function _classPrivateFieldLooseBase(e, t) { if (!{}.hasOwnProperty.call(e, t)) throw new TypeError("attempted to use private field on non-instance"); return e; }
var id = 0;
function _classPrivateFieldLooseKey(e) { return "__private_" + id++ + "_" + e; }
import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

/* stimulusFetch: 'lazy' */
var _animation = /*#__PURE__*/_classPrivateFieldLooseKey("animation");
var _group = /*#__PURE__*/_classPrivateFieldLooseKey("group");
var _onEnd = /*#__PURE__*/_classPrivateFieldLooseKey("onEnd");
var _onMove = /*#__PURE__*/_classPrivateFieldLooseKey("onMove");
var _submit = /*#__PURE__*/_classPrivateFieldLooseKey("submit");
var _removeAllHiddenInputs = /*#__PURE__*/_classPrivateFieldLooseKey("removeAllHiddenInputs");
var _removeAllSelectInputs = /*#__PURE__*/_classPrivateFieldLooseKey("removeAllSelectInputs");
var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    var _this;
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }
    _this = _callSuper(this, _default, [].concat(args));
    Object.defineProperty(_this, _removeAllSelectInputs, {
      value: _removeAllSelectInputs2
    });
    Object.defineProperty(_this, _removeAllHiddenInputs, {
      value: _removeAllHiddenInputs2
    });
    Object.defineProperty(_this, _submit, {
      value: _submit2
    });
    Object.defineProperty(_this, _onMove, {
      value: _onMove2
    });
    Object.defineProperty(_this, _onEnd, {
      value: _onEnd2
    });
    Object.defineProperty(_this, _animation, {
      writable: true,
      value: 150
    });
    Object.defineProperty(_this, _group, {
      writable: true,
      value: void 0
    });
    return _this;
  }
  _inheritsLoose(_default, _Controller);
  var _proto = _default.prototype;
  _proto.connect = function connect() {
    var _this2 = this;
    _classPrivateFieldLooseBase(this, _group)[_group] = 'g' + Math.random().toString(36);
    this.itemsElement = this.element.querySelector('.available');
    this.rowsElement = this.element.querySelector('.rows');
    this.columnsElement = this.element.querySelector('.columns');
    this.valuesElement = this.element.querySelector('.values');
    this.filtersElement = this.element.querySelector('.filters');
    this.sortableItems = Sortable.create(this.itemsElement, {
      group: _classPrivateFieldLooseBase(this, _group)[_group],
      animation: _classPrivateFieldLooseBase(this, _animation)[_animation],
      onMove: _classPrivateFieldLooseBase(this, _onMove)[_onMove].bind(this),
      onEnd: _classPrivateFieldLooseBase(this, _onEnd)[_onEnd].bind(this)
    });
    this.sortableRows = Sortable.create(this.rowsElement, {
      group: _classPrivateFieldLooseBase(this, _group)[_group],
      animation: _classPrivateFieldLooseBase(this, _animation)[_animation],
      onMove: _classPrivateFieldLooseBase(this, _onMove)[_onMove].bind(this),
      onEnd: _classPrivateFieldLooseBase(this, _onEnd)[_onEnd].bind(this)
    });
    this.sortableColumns = Sortable.create(this.columnsElement, {
      group: _classPrivateFieldLooseBase(this, _group)[_group],
      animation: _classPrivateFieldLooseBase(this, _animation)[_animation],
      onMove: _classPrivateFieldLooseBase(this, _onMove)[_onMove].bind(this),
      onEnd: _classPrivateFieldLooseBase(this, _onEnd)[_onEnd].bind(this)
    });
    this.sortableValues = Sortable.create(this.valuesElement, {
      group: _classPrivateFieldLooseBase(this, _group)[_group],
      animation: _classPrivateFieldLooseBase(this, _animation)[_animation],
      onMove: _classPrivateFieldLooseBase(this, _onMove)[_onMove].bind(this),
      onEnd: _classPrivateFieldLooseBase(this, _onEnd)[_onEnd].bind(this)
    });
    this.sortableFilters = Sortable.create(this.filtersElement, {
      group: _classPrivateFieldLooseBase(this, _group)[_group],
      animation: _classPrivateFieldLooseBase(this, _animation)[_animation],
      onMove: _classPrivateFieldLooseBase(this, _onMove)[_onMove].bind(this),
      onEnd: _classPrivateFieldLooseBase(this, _onEnd)[_onEnd].bind(this)
    });
    this.element.querySelectorAll('select').forEach(function (select) {
      select.addEventListener('change', function () {
        _classPrivateFieldLooseBase(_this2, _submit)[_submit]();
      });
    });

    // this.#submit()
  };
  _proto.disconnect = function disconnect() {
    this.sortableItems.destroy();
    this.sortableRows.destroy();
    this.sortableColumns.destroy();
    this.sortableValues.destroy();
    this.sortableFilters.destroy();
  };
  return _default;
}(Controller);
function _onEnd2(event) {
  _classPrivateFieldLooseBase(this, _submit)[_submit]();
}
function _onMove2(event, originalEvent) {
  var itemType = event.dragged.dataset.type;
  var targetType = event.to.dataset.type;
  if (itemType === 'values') {
    if (['rows', 'columns'].includes(targetType)) {
      return true;
    }
  }
  if (itemType === 'dimension') {
    if (['available', 'rows', 'columns', 'filters'].includes(targetType)) {
      return true;
    }
  }
  if (itemType === 'measure') {
    if (['available', 'values'].includes(targetType)) {
      return true;
    }
  }
  return false;
}
function _submit2() {
  var _this3 = this;
  _classPrivateFieldLooseBase(this, _removeAllHiddenInputs)[_removeAllHiddenInputs]();
  this.element.querySelectorAll('ul').forEach(function (ul, index) {
    var ultype = ul.dataset.type;
    if (!['rows', 'columns', 'values', 'filters'].includes(ultype)) {
      return;
    }
    ul.querySelectorAll('li').forEach(function (li, index) {
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = ultype + '[' + index + ']';
      var value = li.dataset.value;
      var select = li.querySelector('select');
      if (select) {
        value += '.' + select.value;
      }
      input.value = value;
      _this3.element.appendChild(input);
    });
  });

  // this.#removeAllSelectInputs()
  this.element.requestSubmit();
}
function _removeAllHiddenInputs2() {
  var formElements = this.element.querySelectorAll('input[type="hidden"]');
  formElements.forEach(function (formElement) {
    formElement.remove();
  });
}
function _removeAllSelectInputs2() {
  var formElements = this.element.querySelectorAll('select');
  formElements.forEach(function (formElement) {
    formElement.remove();
  });
}
export { _default as default };