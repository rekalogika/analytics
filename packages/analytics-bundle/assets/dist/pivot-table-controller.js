function _createForOfIteratorHelperLoose(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (t) return (t = t.call(r)).next.bind(t); if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var o = 0; return function () { return o >= r.length ? { done: !0 } : { done: !1, value: r[o++] }; }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
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
import { visit } from '@hotwired/turbo';
import Sortable from 'sortablejs';

/* stimulusFetch: 'lazy' */
var _animation = /*#__PURE__*/_classPrivateFieldLooseKey("animation");
var _group = /*#__PURE__*/_classPrivateFieldLooseKey("group");
var _onEnd = /*#__PURE__*/_classPrivateFieldLooseKey("onEnd");
var _onMove = /*#__PURE__*/_classPrivateFieldLooseKey("onMove");
var _submit = /*#__PURE__*/_classPrivateFieldLooseKey("submit");
var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    var _this;
    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }
    _this = _callSuper(this, _default, [].concat(args));
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
  var data = {};
  var uls = this.element.querySelectorAll('ul');
  for (var _iterator = _createForOfIteratorHelperLoose(uls), _step; !(_step = _iterator()).done;) {
    var ul = _step.value;
    var type = ul.dataset.type;
    if (!['rows', 'columns', 'values', 'filters'].includes(type)) {
      continue;
    }
    var lis = ul.querySelectorAll('li');
    for (var _iterator2 = _createForOfIteratorHelperLoose(lis.entries()), _step2; !(_step2 = _iterator2()).done;) {
      var _step2$value = _step2.value,
        index = _step2$value[0],
        li = _step2$value[1];
      var value = li.dataset.value;
      var select = li.querySelector('select');
      if (select) {
        value += '.' + select.value;
      }

      // data[type + '[' + index + ']'] = value

      if (!data[type]) {
        data[type] = [];
      }
      data[type][index] = value;
    }
  }
  if (this.urlParameterValue) {
    var url = new URL(window.location);
    url.searchParams.set(this.urlParameterValue, JSON.stringify(data));
    // window.location.href = url
    visit(url.toString(), {
      'frame': 'pivottable'
    });
  }
}
_default.values = {
  urlParameter: String
};
export { _default as default };