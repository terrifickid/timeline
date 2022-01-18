(function(t){function e(e){for(var n,i,s=e[0],l=e[1],c=e[2],u=0,d=[];u<s.length;u++)i=s[u],Object.prototype.hasOwnProperty.call(o,i)&&o[i]&&d.push(o[i][0]),o[i]=0;for(n in l)Object.prototype.hasOwnProperty.call(l,n)&&(t[n]=l[n]);f&&f(e);while(d.length)d.shift()();return a.push.apply(a,c||[]),r()}function r(){for(var t,e=0;e<a.length;e++){for(var r=a[e],n=!0,i=1;i<r.length;i++){var s=r[i];0!==o[s]&&(n=!1)}n&&(a.splice(e--,1),t=l(l.s=r[0]))}return t}var n={},i={app:0},o={app:0},a=[];function s(t){return l.p+"js/"+({timeline:"timeline"}[t]||t)+"."+{timeline:"8df57491"}[t]+".js"}function l(e){if(n[e])return n[e].exports;var r=n[e]={i:e,l:!1,exports:{}};return t[e].call(r.exports,r,r.exports,l),r.l=!0,r.exports}l.e=function(t){var e=[],r={timeline:1};i[t]?e.push(i[t]):0!==i[t]&&r[t]&&e.push(i[t]=new Promise((function(e,r){for(var n="css/"+({timeline:"timeline"}[t]||t)+"."+{timeline:"b14115b7"}[t]+".css",o=l.p+n,a=document.getElementsByTagName("link"),s=0;s<a.length;s++){var c=a[s],u=c.getAttribute("data-href")||c.getAttribute("href");if("stylesheet"===c.rel&&(u===n||u===o))return e()}var d=document.getElementsByTagName("style");for(s=0;s<d.length;s++){c=d[s],u=c.getAttribute("data-href");if(u===n||u===o)return e()}var f=document.createElement("link");f.rel="stylesheet",f.type="text/css",f.onload=e,f.onerror=function(e){var n=e&&e.target&&e.target.src||o,a=new Error("Loading CSS chunk "+t+" failed.\n("+n+")");a.code="CSS_CHUNK_LOAD_FAILED",a.request=n,delete i[t],f.parentNode.removeChild(f),r(a)},f.href=o;var p=document.getElementsByTagName("head")[0];p.appendChild(f)})).then((function(){i[t]=0})));var n=o[t];if(0!==n)if(n)e.push(n[2]);else{var a=new Promise((function(e,r){n=o[t]=[e,r]}));e.push(n[2]=a);var c,u=document.createElement("script");u.charset="utf-8",u.timeout=120,l.nc&&u.setAttribute("nonce",l.nc),u.src=s(t);var d=new Error;c=function(e){u.onerror=u.onload=null,clearTimeout(f);var r=o[t];if(0!==r){if(r){var n=e&&("load"===e.type?"missing":e.type),i=e&&e.target&&e.target.src;d.message="Loading chunk "+t+" failed.\n("+n+": "+i+")",d.name="ChunkLoadError",d.type=n,d.request=i,r[1](d)}o[t]=void 0}};var f=setTimeout((function(){c({type:"timeout",target:u})}),12e4);u.onerror=u.onload=c,document.head.appendChild(u)}return Promise.all(e)},l.m=t,l.c=n,l.d=function(t,e,r){l.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:r})},l.r=function(t){"undefined"!==typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},l.t=function(t,e){if(1&e&&(t=l(t)),8&e)return t;if(4&e&&"object"===typeof t&&t&&t.__esModule)return t;var r=Object.create(null);if(l.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var n in t)l.d(r,n,function(e){return t[e]}.bind(null,n));return r},l.n=function(t){var e=t&&t.__esModule?function(){return t["default"]}:function(){return t};return l.d(e,"a",e),e},l.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},l.p="/",l.oe=function(t){throw console.error(t),t};var c=window["webpackJsonp"]=window["webpackJsonp"]||[],u=c.push.bind(c);c.push=e,c=c.slice();for(var d=0;d<c.length;d++)e(c[d]);var f=u;a.push([0,"chunk-vendors"]),r()})({0:function(t,e,r){t.exports=r("56d7")},"56d7":function(t,e,r){"use strict";r.r(e);var n=r("7a23");function i(t,e,r,i,o,a){const s=Object(n["z"])("router-view");return Object(n["t"])(),Object(n["d"])(s)}var o={mounted(){this.$store.dispatch("loadData")}},a=r("6b0d"),s=r.n(a);const l=s()(o,[["render",i]]);var c=l,u=r("6c02");const d=[{path:"/",name:"Timeline",component:function(){return r.e("timeline").then(r.bind(null,"f67a"))}}],f=Object(u["a"])({history:Object(u["b"])("/"),routes:d});var p=f,m=r("5502"),h=r("bc3a"),v=r.n(h),g=Object(m["a"])({state:{timeline:[],isModalActive:!1,activeEntry:{},artists:[],artistsFilter:[],locations:[],locationsFilter:[]},getters:{timestream:t=>{var e=t.timeline.filter(e=>!(t.artistsFilter.length&&!t.artistsFilter.includes(e.artists[0].slug))),r={};return e.forEach(t=>{r[t.date[2]]||(r[t.date[2]]={showCount:2,entries:[]}),r[t.date[2]].entries.push(t)}),console.log(r),r},isModalActive:t=>t.isModalActive,activeEntry:t=>t.activeEntry,artists:t=>t.artists,artistsFilter:t=>t.artistsFilter,locations:t=>t.locations,locationsFilter:t=>t.locationsFilter},mutations:{setTimeline(t,e){t.timeline=e},setModal(t,e){t.isModalActive=e},setActiveEntry(t,e){t.activeEntry=e},setArtists(t,e){t.artists=e},setArtistsFilter(t,e){if(t.artistsFilter.includes(e.slug)){var r=t.artistsFilter.indexOf(e.slug);-1!==r&&t.artistsFilter.splice(r,1)}else t.artistsFilter.push(e.slug)},setLocations(t,e){t.locations=e},setLocationsFilter(t,e){t.locationsFilter=e}},actions:{async loadData({commit:t}){console.log("Loading..");var e=await v.a.get("https://hw30secure1.wpengine.com/wp-json/wp/v2/timeline/");console.log("loaded!");var r=e.data.map(t=>{var e=t.acf;return e.date=t.acf.date.split(","),e.title=t.title.rendered,e.artists=t.artists,e.locations=t.locations,e}),n=[],i=[];r.forEach(t=>{t.artists.forEach(t=>{n.some(e=>e.term_id===t.term_id)||n.push(t)}),t.locations.forEach(t=>{i.some(e=>e.term_id===t.term_id)||i.push(t)})}),t("setArtists",n),t("setLocations",i),t("setTimeline",r),t("setActiveEntry",r[0])}},modules:{}});r("a589");Object(n["c"])(c).use(g).use(p).mount("#app")},a589:function(t,e,r){}});
//# sourceMappingURL=app.65fbeab0.js.map