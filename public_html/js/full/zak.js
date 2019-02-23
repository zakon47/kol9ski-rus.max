function findOffsetHeight(e) {
    var res = 0;
    while ((res == 0) && e.parentNode) {
        e = e.parentNode;
        res = e.offsetHeight;
    }
    return res;
}
function getOffsetHeight(e) {
    return e.offsetHeight ||
        e.style.pixelHeight ||
        findOffsetHeight(e);
}
// var Console = Class.extend({
//     construct: function() {},
//     log: function() { },
//     info: function() { },
//     warn: function() { },
//     error: function() { }
// });
if (!window.console) {
    console = new Console();
}
// var x = document.getElementById('menu');
// console.log(getOffsetHeight(document.getElementsByClassName('menu')[0]));
// console.log(getOffsetHeight(x)); 
