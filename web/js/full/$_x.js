var $_x;
(function ($_x) {
    /* подгрузить файлы в head */
    var load = (function () {
        function load() {
        }
        load.endsWith = function (str, search, position) {
            if (str === void 0) { str = ''; }
            if (search === void 0) { search = ''; }
            if (position === void 0) { position = null; }
            var subjectString = str.toString();
            if (typeof position !== 'number' || !isFinite(position) ||
                Math.floor(position) !== position ||
                position > subjectString.length)
                position = subjectString.length;
            position -= search.length;
            var lastIndex = subjectString.lastIndexOf(search, position);
            return lastIndex !== -1 && lastIndex === position;
        };
        load.startsWith = function (str, search, position) {
            if (str === void 0) { str = ''; }
            if (search === void 0) { search = ''; }
            if (position === void 0) { position = null; }
            position = position || 0;
            return str.substr(position, search.length) === search;
        };
        load.includes = function (str, search, start) {
            'use strict';
            if (str === void 0) { str = ''; }
            if (search === void 0) { search = ''; }
            if (start === void 0) { start = null; }
            if (typeof start !== 'number')
                start = 0;
            return (start + search.length > this.length) ? false : str.indexOf(search, start) !== -1;
        };
        load.getObj = function (url, type, callback, parent, endCallback, uid) {
            if (uid === void 0) { uid = null; }
            //определяем обьект
            if (type && !this.obj[type]) {
                return {};
            }
            else {
                var t = {};
                if (type && !this.arr[type])
                    this.arr[type] = [];
                for (var key in this.obj) {
                    if (this.endsWith(url.trim(), "." + key) || this.includes(url, "." + key + "?")) {
                        t = key;
                        break;
                    }
                }
                if (typeof t === "object")
                    return t;
                else
                    type = t;
            }
            //получаем последовательность объекта
            uid = uid === null ? (this.arr[type] ? this.arr[type].length : 0) : uid;
            if (uid)
                this.arr[type][uid] = this.obj[type];
            else
                this.arr[type] = [this.obj[type]];
            if (type == 'ico')
                endCallback.obj--;
            //возвращаем готовый объект
            return this.arr[type][uid] = this.obj[type].url ?
                this.createObj(url.trim(), this.arr[type][uid], callback && typeof callback === "function" ? callback : function () {
                }, parent && typeof parent === "object" ? parent : {}, endCallback) : {};
        };
        load.createObj = function (url, obj, callback, parent, endCallback) {
            var elem = document.createElement(obj.element);
            for (var key in obj.parent)
                elem[key] = obj.parent[key];
            elem[obj.url] = url;
            for (var key in parent)
                elem[key] = parent[key];
            elem.onload = function () {
                elem.onload = null;
                callback();
                if (endCallback !== null && endCallback.obj && endCallback.callback) {
                    endCallback.obj--;
                    if (!endCallback.obj)
                        endCallback.callback();
                }
            };
            document.head.appendChild(elem);
            return elem;
        };
        load.condom = function (arr, callback) {
            if (callback === void 0) { callback = {}; }
            return arr.url ? this.getObj(arr.url, typeof arr.type === "string" ? arr.type : '', typeof arr.callback === "function" ? arr.callback : function () {
            }, typeof arr.parent === "object" ? arr.parent : {}, callback) : this.getObj(arr[0], typeof arr[1] === "string" ? arr[1] : '', typeof arr[2] === "function" ? arr[2] : function () {
            }, typeof arr[3] === "object" ? arr[3] : {}, callback);
        };
        load.files = function (type) {
            if (type === void 0) { type = ''; }
            var list = {};
            for (var key in this.obj) {
                if (type == key)
                    return this.arr[key];
                else
                    list[key] = this.arr[key];
            }
            return list;
        };
        load.remove = function (type, uid) {
            if (type === void 0) { type = ''; }
            if (uid === void 0) { uid = null; }
            if (!type || !uid)
                return null;
            for (var key in this.obj) {
                if (type == key && this.arr[key][uid]) {
                    this.arr[key][uid] = null;
                    return true;
                }
            }
            return false;
        };
        load.get = function (type, uid) {
            if (type === void 0) { type = ''; }
            if (uid === void 0) { uid = null; }
            if (!type || !uid)
                return null;
            for (var key in this.obj) {
                if (type == key && this.arr[key][uid]) {
                    return this.arr[key][uid];
                }
            }
            return false;
        };
        load.add = function () {
            var files = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                files[_i] = arguments[_i];
            }
            if (typeof files === "object" && files[0]) {
                var callback = files[1] && typeof files[1] === "function" ? {
                    obj: this.loadList[files[0]] = files[0].length,
                    callback: files[1]
                } : null;
                if (typeof files[0] === "string")
                    this.condom(files);
                else if (files[0].url)
                    this.condom(files[0]);
                else if (typeof files[0][0] === "string") {
                    if (files[0][1] && !this[files[0][1]] && files[0][1].length > 3)
                        for (var key in files[0])
                            this.condom([files[0][key]], callback);
                    else if (!files[0][1] && files[1])
                        this.condom([files[0][0]], callback);
                    else
                        this.condom(files[0]);
                }
                else if (files[0][0] && (files[0][0].url || typeof files[0][0][0] === "string"))
                    for (var key in files[0])
                        this.condom(files[0][key], callback);
            }
            return this;
        };
        return load;
    }());
    load.arr = {};
    load.loadList = [];
    load.obj = {
        css: {
            element: 'link',
            parent: { rel: 'stylesheet', type: "text/css" },
            url: 'href'
        },
        js: {
            element: 'script',
            parent: { charset: "utf-8", async: null },
            url: 'src'
        },
        png: {
            element: 'link',
            parent: { rel: "icon", type: 'image/png' },
            url: 'href'
        },
        ico: {
            element: 'link',
            parent: { rel: "shortcut icon", type: 'image/x-icon' },
            url: 'href'
        }
    };
    $_x.load = load;
    /* создать новый объект */
    var createElements = (function () {
        function createElements(pars, obj) {
            if (typeof pars === "string") {
                var tagName = pars.replace(/![a-zA-Z0-9-_]/gim, '');
                this.element = document.createElement(tagName ? tagName : 'div');
            }
            else {
                this.createHtml(pars, obj);
                this.element = obj;
            }
        }
        createElements.prototype.set = function () {
            var attr = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                attr[_i] = arguments[_i];
            }
            if (attr[0] != undefined && attr[0]) {
                if (attr.length > 1 && attr[0])
                    this.element[attr[0]] = attr[1];
                else
                    for (var key in attr[0])
                        this.element[key] = attr[0][key];
            }
            return this;
        };
        createElements.prototype.attr = function () {
            var attr = [];
            for (var _i = 0; _i < arguments.length; _i++) {
                attr[_i] = arguments[_i];
            }
            if (attr[0] != undefined && attr[0]) {
                if (attr.length > 1 && attr[0])
                    this.element.setAttribute(attr[0], attr[1]);
                else
                    for (var key in attr[0])
                        this.element.setAttribute(key, attr[0][key]);
            }
            return this;
        };
        createElements.prototype.get = function (parent) {
            if (parent === void 0) { parent = ''; }
            if (parent && this.element[parent] != undefined)
                return this.element[parent];
            return this.element;
        };
        createElements.prototype.reload = function (parent) {
            if (parent === void 0) { parent = null; }
            this.element = parent;
            return this;
        };
        createElements.prototype.getAttr = function (parent) {
            if (parent === void 0) { parent = ''; }
            if (parent && this.element.getAttribute(parent) != undefined)
                return this.element.getAttribute(parent);
            return "";
        };
        createElements.prototype.createHtml = function (dom, obj) {
            if (typeof dom === "object" && dom[0]) {
                for (var key in dom) {
                    this.createHtml(dom[key], obj);
                }
            }
            else if (typeof dom === "object" && dom.name) {
                var element = $_x.create(dom.name);
                if (dom.attr) {
                    var attrObj = {};
                    for (var attr in dom.attr)
                        attrObj[attr] = dom.attr[attr].toString();
                    element.attr(attrObj);
                }
                if (dom.html) {
                    element.set({
                        'innerHTML': dom.html
                    });
                }
                if (dom.find && dom.find.length && dom.find[0]) {
                    for (var key in dom.find) {
                        this.createHtml(dom.find[key], element.get());
                    }
                }
                obj.appendChild(element.get());
            }
        };
        return createElements;
    }());
    /* функция для создания обьекта */
    function create(str, obj) {
        if (str === void 0) { str = null; }
        if (obj === void 0) { obj = null; }
        if (str === null || str !== null && typeof str !== "string" && obj === null)
            return null;
        return new createElements(str, obj);
    }
    $_x.create = create;
    /* объект для отправки запросов */
    var http = (function () {
        function http() {
        }
        http.request = function (method, url, parent, callback) {
            var formData = new FormData();
            for (var key in parent)
                formData.append(key, parent[key]);
            var request = typeof XMLHttpRequest != 'undefined'
                ? new XMLHttpRequest()
                : new ActiveXObject('Microsoft.XMLHTTP');
            request.open(method, url, true);
            request.onreadystatechange = function () {
                if (request.readyState == 4) {
                    callback({
                        status: request.status,
                        data: request.responseText
                    });
                }
            };
            request.send(formData);
        };
        http.get = function (url) {
            if (url === void 0) { url = null; }
            var pars = [];
            for (var _i = 1; _i < arguments.length; _i++) {
                pars[_i - 1] = arguments[_i];
            }
            if (url === null)
                return false;
            var parent = {}, i = true;
            var callback = function () {
            };
            for (var key in pars) {
                if (typeof pars[key] === "function")
                    callback = pars[key];
                else if (typeof pars[key] === "object")
                    parent = pars[key];
            }
            for (var key in parent) {
                url += "" + (i ? '?' : '&') + key + "=" + parent[key];
                i = false;
            }
            this.request('GET', url, {}, callback);
            return true;
        };
        http.post = function (url) {
            if (url === void 0) { url = null; }
            var pars = [];
            for (var _i = 1; _i < arguments.length; _i++) {
                pars[_i - 1] = arguments[_i];
            }
            if (url === null)
                return false;
            var parent = {};
            var callback = function () {
            };
            for (var key in pars) {
                if (typeof pars[key] === "function")
                    callback = pars[key];
                else if (typeof pars[key] === "object")
                    parent = pars[key];
            }
            this.request('POST', url, parent, callback);
            return true;
        };
        return http;
    }());
    $_x.http = http;
    /* объект для жестов рукой */
    var touchElement = (function () {
        function touchElement(element) {
            this.event = {
                start: { x: 0, y: 0 },
                end: { x: 0, y: 0 },
                sum: { x: 0, y: 0 },
                module: { x: 0, y: 0 }
            };
            this.px = 100;
            this.callbackList = {
                lr: function () {
                },
                rl: function () {
                },
                tb: function () {
                },
                bt: function () {
                }
            };
            var te = this;
            //стартовые точки
            element.addEventListener("touchstart", function (e) {
                // e.preventDefault();
                te.event.start = {
                    x: e.changedTouches[0].clientX,
                    y: e.changedTouches[0].clientY
                };
            }, false);
            //конечные точки
            element.addEventListener("touchend", function (e) {
                // e.preventDefault();
                te.event.end = {
                    x: e.changedTouches[0].clientX,
                    y: e.changedTouches[0].clientY
                };
                te.handleSum(te);
            }, false);
        }
        //расчеты
        touchElement.prototype.handleSum = function (te) {
            //разность точек
            te.event.sum = {
                x: te.event.start.x - te.event.end.x,
                y: te.event.start.y - te.event.end.y
            };
            //модуль разности
            te.event.module = {
                x: Math.abs(te.event.sum.x),
                y: Math.abs(te.event.sum.y)
            };
            //проверка точек
            if (te.event.module.x > (te.event.module.y * 2)
                && te.event.sum.x < 0
                && te.event.module.x > te.px)
                te.callbackList.lr();
            else if (te.event.module.x > (te.event.module.y * 2)
                && te.event.sum.x > 0
                && te.event.module.x > te.px)
                te.callbackList.rl();
            else if (te.event.module.y > (te.event.module.x * 2)
                && te.event.sum.y < 0
                && te.event.module.y > te.px)
                te.callbackList.tb();
            else if (te.event.module.y > (te.event.module.x * 2)
                && te.event.sum.y > 0
                && te.event.module.y > te.px)
                te.callbackList.bt();
        };
        //замена стандартных действия
        touchElement.prototype.callback = function (obj) {
            if (obj === void 0) { obj = {}; }
            for (var key in obj)
                if (this.callbackList[key] && typeof obj[key] === "function")
                    this.callbackList[key] = obj[key];
            return this;
        };
        return touchElement;
    }());
    $_x.touchElement = touchElement;
    /* функция для создания обьекта */
    function touch(obj) {
        if (obj === void 0) { obj = document.body; }
        return new touchElement(obj);
    }
    $_x.touch = touch;
})($_x || ($_x = {}));
