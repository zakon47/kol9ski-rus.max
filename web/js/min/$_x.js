var $_x;!function(t){function e(t,e){return void 0===t&&(t=null),void 0===e&&(e=null),null===t||null!==t&&"string"!=typeof t&&null===e?null:new i(t,e)}function n(t){return void 0===t&&(t=document.body),new l(t)}var r=function(){function t(){}return t.endsWith=function(t,e,n){void 0===t&&(t=""),void 0===e&&(e=""),void 0===n&&(n=null);var r=t.toString();("number"!=typeof n||!isFinite(n)||Math.floor(n)!==n||n>r.length)&&(n=r.length),n-=e.length;var i=r.lastIndexOf(e,n);return-1!==i&&i===n},t.startsWith=function(t,e,n){return void 0===t&&(t=""),void 0===e&&(e=""),void 0===n&&(n=null),n=n||0,t.substr(n,e.length)===e},t.includes=function(t,e,n){"use strict";return void 0===t&&(t=""),void 0===e&&(e=""),void 0===n&&(n=null),"number"!=typeof n&&(n=0),!(n+e.length>this.length)&&-1!==t.indexOf(e,n)},t.getObj=function(t,e,n,r,i,o){if(void 0===o&&(o=null),e&&!this.obj[e])return{};var l={};e&&!this.arr[e]&&(this.arr[e]=[]);for(var u in this.obj)if(this.endsWith(t.trim(),"."+u)||this.includes(t,"."+u+"?")){l=u;break}return"object"==typeof l?l:(e=l,o=null===o?this.arr[e]?this.arr[e].length:0:o,o?this.arr[e][o]=this.obj[e]:this.arr[e]=[this.obj[e]],"ico"==e&&i.obj--,this.arr[e][o]=this.obj[e].url?this.createObj(t.trim(),this.arr[e][o],n&&"function"==typeof n?n:function(){},r&&"object"==typeof r?r:{},i):{})},t.createObj=function(t,e,n,r,i){var o=document.createElement(e.element);for(var l in e.parent)o[l]=e.parent[l];o[e.url]=t;for(var l in r)o[l]=r[l];return o.onload=function(){o.onload=null,n(),null!==i&&i.obj&&i.callback&&(--i.obj||i.callback())},document.head.appendChild(o),o},t.condom=function(t,e){return void 0===e&&(e={}),t.url?this.getObj(t.url,"string"==typeof t.type?t.type:"","function"==typeof t.callback?t.callback:function(){},"object"==typeof t.parent?t.parent:{},e):this.getObj(t[0],"string"==typeof t[1]?t[1]:"","function"==typeof t[2]?t[2]:function(){},"object"==typeof t[3]?t[3]:{},e)},t.files=function(t){void 0===t&&(t="");var e={};for(var n in this.obj){if(t==n)return this.arr[n];e[n]=this.arr[n]}return e},t.remove=function(t,e){if(void 0===t&&(t=""),void 0===e&&(e=null),!t||!e)return null;for(var n in this.obj)if(t==n&&this.arr[n][e])return this.arr[n][e]=null,!0;return!1},t.get=function(t,e){if(void 0===t&&(t=""),void 0===e&&(e=null),!t||!e)return null;for(var n in this.obj)if(t==n&&this.arr[n][e])return this.arr[n][e];return!1},t.add=function(){for(var t=[],e=0;e<arguments.length;e++)t[e]=arguments[e];if("object"==typeof t&&t[0]){var n=t[1]&&"function"==typeof t[1]?{obj:this.loadList[t[0]]=t[0].length,callback:t[1]}:null;if("string"==typeof t[0])this.condom(t);else if(t[0].url)this.condom(t[0]);else if("string"==typeof t[0][0])if(t[0][1]&&!this[t[0][1]]&&t[0][1].length>3)for(var r in t[0])this.condom([t[0][r]],n);else!t[0][1]&&t[1]?this.condom([t[0][0]],n):this.condom(t[0]);else if(t[0][0]&&(t[0][0].url||"string"==typeof t[0][0][0]))for(var r in t[0])this.condom(t[0][r],n)}return this},t}();r.arr={},r.loadList=[],r.obj={css:{element:"link",parent:{rel:"stylesheet",type:"text/css"},url:"href"},js:{element:"script",parent:{charset:"utf-8",async:null},url:"src"},png:{element:"link",parent:{rel:"icon",type:"image/png"},url:"href"},ico:{element:"link",parent:{rel:"shortcut icon",type:"image/x-icon"},url:"href"}},t.load=r;var i=function(){function e(t,e){if("string"==typeof t){var n=t.replace(/![a-zA-Z0-9-_]/gim,"");this.element=document.createElement(n||"div")}else this.createHtml(t,e),this.element=e}return e.prototype.set=function(){for(var t=[],e=0;e<arguments.length;e++)t[e]=arguments[e];if(void 0!=t[0]&&t[0])if(t.length>1&&t[0])this.element[t[0]]=t[1];else for(var n in t[0])this.element[n]=t[0][n];return this},e.prototype.attr=function(){for(var t=[],e=0;e<arguments.length;e++)t[e]=arguments[e];if(void 0!=t[0]&&t[0])if(t.length>1&&t[0])this.element.setAttribute(t[0],t[1]);else for(var n in t[0])this.element.setAttribute(n,t[0][n]);return this},e.prototype.get=function(t){return void 0===t&&(t=""),t&&void 0!=this.element[t]?this.element[t]:this.element},e.prototype.reload=function(t){return void 0===t&&(t=null),this.element=t,this},e.prototype.getAttr=function(t){return void 0===t&&(t=""),t&&void 0!=this.element.getAttribute(t)?this.element.getAttribute(t):""},e.prototype.createHtml=function(e,n){if("object"==typeof e&&e[0])for(var r in e)this.createHtml(e[r],n);else if("object"==typeof e&&e.name){var i=t.create(e.name);if(e.attr){var o={};for(var l in e.attr)o[l]=e.attr[l].toString();i.attr(o)}if(e.html&&i.set({innerHTML:e.html}),e.find&&e.find.length&&e.find[0])for(var r in e.find)this.createHtml(e.find[r],i.get());n.appendChild(i.get())}},e}();t.create=e;var o=function(){function t(){}return t.request=function(t,e,n,r){var i=new FormData;for(var o in n)i.append(o,n[o]);var l="undefined"!=typeof XMLHttpRequest?new XMLHttpRequest:new ActiveXObject("Microsoft.XMLHTTP");l.open(t,e,!0),l.onreadystatechange=function(){4==l.readyState&&r({status:l.status,data:l.responseText})},l.send(i)},t.get=function(t){void 0===t&&(t=null);for(var e=[],n=1;n<arguments.length;n++)e[n-1]=arguments[n];if(null===t)return!1;var r={},i=!0,o=function(){};for(var l in e)"function"==typeof e[l]?o=e[l]:"object"==typeof e[l]&&(r=e[l]);for(var l in r)t+=(i?"?":"&")+l+"="+r[l],i=!1;return this.request("GET",t,{},o),!0},t.post=function(t){void 0===t&&(t=null);for(var e=[],n=1;n<arguments.length;n++)e[n-1]=arguments[n];if(null===t)return!1;var r={},i=function(){};for(var o in e)"function"==typeof e[o]?i=e[o]:"object"==typeof e[o]&&(r=e[o]);return this.request("POST",t,r,i),!0},t}();t.http=o;var l=function(){function t(t){this.event={start:{x:0,y:0},end:{x:0,y:0},sum:{x:0,y:0},module:{x:0,y:0}},this.px=100,this.callbackList={lr:function(){},rl:function(){},tb:function(){},bt:function(){}};var e=this;t.addEventListener("touchstart",function(t){e.event.start={x:t.changedTouches[0].clientX,y:t.changedTouches[0].clientY}},!1),t.addEventListener("touchend",function(t){e.event.end={x:t.changedTouches[0].clientX,y:t.changedTouches[0].clientY},e.handleSum(e)},!1)}return t.prototype.handleSum=function(t){t.event.sum={x:t.event.start.x-t.event.end.x,y:t.event.start.y-t.event.end.y},t.event.module={x:Math.abs(t.event.sum.x),y:Math.abs(t.event.sum.y)},t.event.module.x>2*t.event.module.y&&t.event.sum.x<0&&t.event.module.x>t.px?t.callbackList.lr():t.event.module.x>2*t.event.module.y&&t.event.sum.x>0&&t.event.module.x>t.px?t.callbackList.rl():t.event.module.y>2*t.event.module.x&&t.event.sum.y<0&&t.event.module.y>t.px?t.callbackList.tb():t.event.module.y>2*t.event.module.x&&t.event.sum.y>0&&t.event.module.y>t.px&&t.callbackList.bt()},t.prototype.callback=function(t){void 0===t&&(t={});for(var e in t)this.callbackList[e]&&"function"==typeof t[e]&&(this.callbackList[e]=t[e]);return this},t}();t.touchElement=l,t.touch=n}($_x||($_x={}));