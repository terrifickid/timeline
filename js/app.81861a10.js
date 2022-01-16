(function(e){function t(t){for(var r,o,c=t[0],l=t[1],s=t[2],u=0,d=[];u<c.length;u++)o=c[u],Object.prototype.hasOwnProperty.call(i,o)&&i[o]&&d.push(i[o][0]),i[o]=0;for(r in l)Object.prototype.hasOwnProperty.call(l,r)&&(e[r]=l[r]);p&&p(t);while(d.length)d.shift()();return a.push.apply(a,s||[]),n()}function n(){for(var e,t=0;t<a.length;t++){for(var n=a[t],r=!0,o=1;o<n.length;o++){var c=n[o];0!==i[c]&&(r=!1)}r&&(a.splice(t--,1),e=l(l.s=n[0]))}return e}var r={},o={app:0},i={app:0},a=[];function c(e){return l.p+"js/"+({timeline:"timeline"}[e]||e)+"."+{timeline:"09934885"}[e]+".js"}function l(t){if(r[t])return r[t].exports;var n=r[t]={i:t,l:!1,exports:{}};return e[t].call(n.exports,n,n.exports,l),n.l=!0,n.exports}l.e=function(e){var t=[],n={timeline:1};o[e]?t.push(o[e]):0!==o[e]&&n[e]&&t.push(o[e]=new Promise((function(t,n){for(var r="css/"+({timeline:"timeline"}[e]||e)+"."+{timeline:"b14115b7"}[e]+".css",i=l.p+r,a=document.getElementsByTagName("link"),c=0;c<a.length;c++){var s=a[c],u=s.getAttribute("data-href")||s.getAttribute("href");if("stylesheet"===s.rel&&(u===r||u===i))return t()}var d=document.getElementsByTagName("style");for(c=0;c<d.length;c++){s=d[c],u=s.getAttribute("data-href");if(u===r||u===i)return t()}var p=document.createElement("link");p.rel="stylesheet",p.type="text/css",p.onload=t,p.onerror=function(t){var r=t&&t.target&&t.target.src||i,a=new Error("Loading CSS chunk "+e+" failed.\n("+r+")");a.code="CSS_CHUNK_LOAD_FAILED",a.request=r,delete o[e],p.parentNode.removeChild(p),n(a)},p.href=i;var f=document.getElementsByTagName("head")[0];f.appendChild(p)})).then((function(){o[e]=0})));var r=i[e];if(0!==r)if(r)t.push(r[2]);else{var a=new Promise((function(t,n){r=i[e]=[t,n]}));t.push(r[2]=a);var s,u=document.createElement("script");u.charset="utf-8",u.timeout=120,l.nc&&u.setAttribute("nonce",l.nc),u.src=c(e);var d=new Error;s=function(t){u.onerror=u.onload=null,clearTimeout(p);var n=i[e];if(0!==n){if(n){var r=t&&("load"===t.type?"missing":t.type),o=t&&t.target&&t.target.src;d.message="Loading chunk "+e+" failed.\n("+r+": "+o+")",d.name="ChunkLoadError",d.type=r,d.request=o,n[1](d)}i[e]=void 0}};var p=setTimeout((function(){s({type:"timeout",target:u})}),12e4);u.onerror=u.onload=s,document.head.appendChild(u)}return Promise.all(t)},l.m=e,l.c=r,l.d=function(e,t,n){l.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:n})},l.r=function(e){"undefined"!==typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},l.t=function(e,t){if(1&t&&(e=l(e)),8&t)return e;if(4&t&&"object"===typeof e&&e&&e.__esModule)return e;var n=Object.create(null);if(l.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var r in e)l.d(n,r,function(t){return e[t]}.bind(null,r));return n},l.n=function(e){var t=e&&e.__esModule?function(){return e["default"]}:function(){return e};return l.d(t,"a",t),t},l.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},l.p="/",l.oe=function(e){throw console.error(e),e};var s=window["webpackJsonp"]=window["webpackJsonp"]||[],u=s.push.bind(s);s.push=t,s=s.slice();for(var d=0;d<s.length;d++)t(s[d]);var p=u;a.push([0,"chunk-vendors"]),n()})({0:function(e,t,n){e.exports=n("56d7")},"56d7":function(e,t,n){"use strict";n.r(t);var r=n("7a23");function o(e,t,n,o,i,a){const c=Object(r["y"])("router-view");return Object(r["t"])(),Object(r["d"])(c)}var i={mounted(){this.$store.dispatch("loadData")}},a=n("6b0d"),c=n.n(a);const l=c()(i,[["render",o]]);var s=l,u=n("6c02");const d=[{path:"/",name:"Timeline",component:function(){return n.e("timeline").then(n.bind(null,"f67a"))}}],p=Object(u["a"])({history:Object(u["b"])("/"),routes:d});var f=p,m=n("5502"),v=n("bc3a"),h=n.n(v),g=Object(m["a"])({state:{timeline:[],isModalActive:!1,activeEntry:{}},getters:{timeline:e=>e.timeline,isModalActive:e=>e.isModalActive,activeEntry:e=>e.activeEntry},mutations:{setTimeline(e,t){e.timeline=t},setModal(e,t){e.isModalActive=t},setActiveEntry(e,t){e.activeEntry=t}},actions:{async loadData({commit:e}){console.log("Loading..");var t=await h.a.get("https://hw30secure1.wpengine.com/wp-json/wp/v2/timeline/");console.log("loaded!");var n={},r=t.data.map(e=>{var t=e.acf;return t.date=e.acf.date.split(","),t.title=e.title.rendered,t});r.forEach(e=>{n[e.date[2]]||(n[e.date[2]]={showCount:2,entries:[]}),n[e.date[2]].entries.push(e)}),console.log(n),e("setTimeline",n),e("setActiveEntry",n[Object.keys(n)[0]].entries[0])}},modules:{}});n("a589");Object(r["c"])(s).use(g).use(f).mount("#app")},a589:function(e,t,n){}});
//# sourceMappingURL=app.81861a10.js.map