(function(t){function e(e){for(var a,l,n=e[0],o=e[1],c=e[2],d=0,h=[];d<n.length;d++)l=n[d],Object.prototype.hasOwnProperty.call(r,l)&&r[l]&&h.push(r[l][0]),r[l]=0;for(a in o)Object.prototype.hasOwnProperty.call(o,a)&&(t[a]=o[a]);u&&u(e);while(h.length)h.shift()();return i.push.apply(i,c||[]),s()}function s(){for(var t,e=0;e<i.length;e++){for(var s=i[e],a=!0,l=1;l<s.length;l++){var o=s[l];0!==r[o]&&(a=!1)}a&&(i.splice(e--,1),t=n(n.s=s[0]))}return t}var a={},r={app:0},i=[];function l(t){return n.p+"hw30assets/js/"+({about:"about"}[t]||t)+"."+{about:"b4538c67"}[t]+".js"}function n(e){if(a[e])return a[e].exports;var s=a[e]={i:e,l:!1,exports:{}};return t[e].call(s.exports,s,s.exports,n),s.l=!0,s.exports}n.e=function(t){var e=[],s=r[t];if(0!==s)if(s)e.push(s[2]);else{var a=new Promise((function(e,a){s=r[t]=[e,a]}));e.push(s[2]=a);var i,o=document.createElement("script");o.charset="utf-8",o.timeout=120,n.nc&&o.setAttribute("nonce",n.nc),o.src=l(t);var c=new Error;i=function(e){o.onerror=o.onload=null,clearTimeout(d);var s=r[t];if(0!==s){if(s){var a=e&&("load"===e.type?"missing":e.type),i=e&&e.target&&e.target.src;c.message="Loading chunk "+t+" failed.\n("+a+": "+i+")",c.name="ChunkLoadError",c.type=a,c.request=i,s[1](c)}r[t]=void 0}};var d=setTimeout((function(){i({type:"timeout",target:o})}),12e4);o.onerror=o.onload=i,document.head.appendChild(o)}return Promise.all(e)},n.m=t,n.c=a,n.d=function(t,e,s){n.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:s})},n.r=function(t){"undefined"!==typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},n.t=function(t,e){if(1&e&&(t=n(t)),8&e)return t;if(4&e&&"object"===typeof t&&t&&t.__esModule)return t;var s=Object.create(null);if(n.r(s),Object.defineProperty(s,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var a in t)n.d(s,a,function(e){return t[e]}.bind(null,a));return s},n.n=function(t){var e=t&&t.__esModule?function(){return t["default"]}:function(){return t};return n.d(e,"a",e),e},n.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},n.p="/",n.oe=function(t){throw console.error(t),t};var o=window["webpackJsonp"]=window["webpackJsonp"]||[],c=o.push.bind(o);o.push=e,o=o.slice();for(var d=0;d<o.length;d++)e(o[d]);var u=c;i.push([0,"chunk-vendors"]),s()})({0:function(t,e,s){t.exports=s("56d7")},"56d7":function(t,e,s){"use strict";s.r(e);var a=s("2b0e"),r=function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",{staticClass:"bg-black text-white"},[s("router-view")],1)},i=[],l=s("2877"),n={},o=Object(l["a"])(n,r,i,!1,null,null,null),c=o.exports,d=s("8c4f"),u=function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",{staticClass:"home"},[s("div",{staticClass:"fixed z-20 w-screen",class:{modalToggle:!t.isModalToggled},attrs:{id:"contentModal"}},[s("div",{staticClass:"grid grid-cols-12"},[s("div",{staticClass:"hidden lg:block lg:col-span-2"}),s("div",{staticClass:"col-span-12 lg:col-span-10 h-screen bg-white text-black overflow-y-scroll"},[s("div",{staticClass:"grid grid-cols-12"},[s("div",{staticClass:"col-span-1"}),s("div",{staticClass:"col-span-10"},[s("div",{staticClass:"flex items-center h-32 border-b border-black mb-10"},[s("div",{staticClass:"flex items-center w-full pt-5"},[s("p",{staticClass:"text-3xl font-medium"},[t._v("1992")]),s("div",{staticClass:"flex mx-auto md:ml-24"},[s("svg",{staticClass:"h-6 w-6 mr-2 cursor-pointer text-gray-300",attrs:{xmlns:"http://www.w3.org/2000/svg",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor"}},[s("path",{attrs:{"stroke-linecap":"round","stroke-linejoin":"round","stroke-width":"2",d:"M15 19l-7-7 7-7"}})]),s("span",{staticClass:"hidden md:inline text-gray-300 cursor-pointer"},[t._v("Previous")]),s("span",{staticClass:"mx-6"},[t._v("1 / 4")]),s("span",{staticClass:"hidden md:inline cursor-pointer"},[t._v("Next")]),s("svg",{staticClass:"h-6 w-6 ml-2 cursor-pointer",attrs:{xmlns:"http://www.w3.org/2000/svg",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor"}},[s("path",{attrs:{"stroke-linecap":"round","stroke-linejoin":"round","stroke-width":"2",d:"M9 5l7 7-7 7"}})])]),s("a",{staticClass:"flex ml-auto cursor-pointer",on:{click:function(e){return t.toggleModal()}}},[t._v(" Close "),s("svg",{staticClass:"h-6 w-6 ml-2",attrs:{xmlns:"http://www.w3.org/2000/svg",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor"}},[s("path",{attrs:{"stroke-linecap":"round","stroke-linejoin":"round","stroke-width":"2",d:"M6 18L18 6M6 6l12 12"}})])])])]),t._m(0)]),s("div",{staticClass:"col-span-1"})])])])]),t._m(1),t._m(2),s("div",{staticClass:"col-span-12 sticky top-0 pt-6 bg-black z-10"},[s("ul",{staticClass:"flex md:hidden px-4 h-8"},[s("li",{staticClass:"w-1/2 flex"},[t._v(" 1993 "),s("svg",{staticClass:"h-6 w-6 ml-2",attrs:{xmlns:"http://www.w3.org/2000/svg",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor"}},[s("path",{attrs:{"stroke-linecap":"round","stroke-linejoin":"round","stroke-width":"2",d:"M19 9l-7 7-7-7"}})])]),s("li",{staticClass:"w-1/2 text-right"},[t._v("Filter")])]),s("ul",{staticClass:"hidden md:flex justify-center"},[t._m(3),t._m(4),t._m(5),t._m(6),s("li",[s("a",{staticClass:"flex h-8 hover:border-b-4 border-white",attrs:{href:"#"}},[t._v("By Locations "),s("svg",{staticClass:"h-6 w-6 ml-2",attrs:{xmlns:"http://www.w3.org/2000/svg",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor"}},[s("path",{attrs:{"stroke-linecap":"round","stroke-linejoin":"round","stroke-width":"2",d:"M19 9l-7 7-7-7"}})])])])])]),s("div",{staticStyle:{background:"linear-gradient(177.84deg, #110223 0%, #73173e 100%)"}},[s("div",{staticClass:"grid grid-cols-12"},[s("div",{staticClass:"hidden md:block md:col-span-2"}),s("div",{staticClass:"col-span-12 md:col-span-8"},[s("div",{staticClass:"flex flex-col grid grid-cols-9 mx-auto text-white"},[t._m(7),s("div",{staticClass:"flex flex-row-reverse contents"},[t._m(8),t._m(9),s("div",{staticClass:"col-start-6 col-end-9"},[s("a",{staticClass:"cursor-pointer",on:{click:function(e){return t.toggleModal()}}},[s("img",{staticClass:"mb-20",attrs:{src:"/hw30img/img_2.png"}})])])]),s("div",{staticClass:"flex flex-row-reverse contents mt-4"},[t._m(10),t._m(11),s("div",{staticClass:"col-start-6 col-end-9"},[s("a",{staticClass:"cursor-pointer",on:{click:function(e){return t.toggleModal()}}},[s("img",{staticClass:"mb-20",attrs:{src:"/hw30img/img_4.png"}})])])]),s("div",{staticClass:"flex contents"},[s("div",{staticClass:"col-start-1 col-end-5"},[s("a",{staticClass:"cursor-pointer",on:{click:function(e){return t.toggleModal()}}},[s("img",{staticClass:"pl-4 ml-auto mb-10",attrs:{src:"/hw30img/img_3.png"}})])]),t._m(12),t._m(13)]),s("div",{staticClass:"flex flex-row-reverse contents"},[t._m(14),s("div",{staticClass:"col-start-5 col-end-6 mx-auto relative"},[t._m(15),s("div",{staticClass:"w-10 h-10 absolute top-1/2 -mt-5 -ml-1 rounded-full bg-white text-black flex items-center justify-center"},[s("svg",{staticClass:"h-6 w-6",attrs:{xmlns:"http://www.w3.org/2000/svg",fill:"none",viewBox:"0 0 24 24",stroke:"currentColor"}},[s("path",{attrs:{"stroke-linecap":"round","stroke-linejoin":"round","stroke-width":"2",d:"M12 4v16m8-8H4"}})])])])])])])])])])},h=[function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",{staticClass:"grid grid-cols-5 gap-8 mb-20"},[s("div",{staticClass:"col-span-5 md:col-span-2"},[s("img",{attrs:{src:"/hw30img/place.png"}}),s("p",{staticClass:"text-sm mt-2"},[t._v(" Exterior view of the gallery on Sonneggstrasse in Zurich, 1992 ")])]),s("div",{staticClass:"col-span-5 md:col-span-3"},[s("h1",{staticClass:"text-5xl font-medium mb-6"},[t._v(" Ursula Hauser & Iwan Wirth open their first gallery together ")]),s("p",{staticClass:"font-bold mb-6"},[t._v(" In 1992, Ursula Hauser and Iwan Wirth open a gallery together in the first-floor apartment of an Art Deco villa on Sonneggstrasse, a quiet, residential street in Zurich, where Iwan Wirth has been living for a year. ")]),s("p",[t._v(" ‘In the late 1980s I lived in St. Gallen, and as a teenager I ran a gallery there. Beginning in 1985 artists I had only heard of, like Martin Disler, Klaudia Schifferle, Fischli & Weiss, Urs Lüthi, or Josef Felix Müller, were exhibited by curators like Bice Curiger, Bernhard Mendes Bürgi and Harald Szeemann. The important galleries in Switzerland at the time were Thomas Ammann, Pablo Stähli and Stampa in Basel. And of course, Ernst Beyeler! The Zurich institutions in those years that I still remember well are the Shedhalle in the Rote Fabrik and of course the Kunsthalle. I moved to Zurich in 1991 to Sonneggstrasse 84. The art market was stuck in a deep recession, and commercial interest in contemporary art was at a low point. So I lived in that apartment and also ran an art dealership there, but didn’t actually represent any artists in the usual sense’. ")])])])},function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",{staticClass:"bg-white text-black"},[s("div",{staticClass:"container mx-auto px-4"},[s("div",{staticClass:"grid grid-cols-8 py-4"},[s("div",{staticClass:"col-span-2"},[s("img",{staticClass:"max-w-full",attrs:{src:"/hw30img/logo.svg"}})]),s("div",{staticClass:"col-span-4"},[s("div",{staticClass:"flex h-full items-center"},[s("img",{staticClass:"mx-auto px-4 object-scale-down",attrs:{src:"/hw30img/hwlogo.svg"}})])]),s("div",{staticClass:"hidden lg:block"},[s("div",{staticClass:"flex h-full items-center"},[s("ul",{staticClass:"flex"},[s("li",{staticClass:"mr-6"},[s("a",{staticClass:"hover:underline",attrs:{href:"#"}},[t._v("Timeline")])]),s("li",{staticClass:"mr-6"},[s("a",{staticClass:"hover:underline",attrs:{href:"#"}},[t._v("Index")])]),s("li",[s("a",{staticClass:"hover:underline",attrs:{href:"#"}},[t._v("About")])])])])]),s("div",{staticClass:"hidden lg:block"},[s("div",{staticClass:"flex h-full items-center justify-end"},[s("ul",{staticClass:"flex"},[s("li",{staticClass:"mr-4"},[t._v("ENG")]),s("li",[t._v("*!#")])])])]),s("div",{staticClass:"col-span-2 lg:hidden flex"},[s("img",{staticClass:"ml-auto w-10",attrs:{src:"/hw30img/menu.svg"}})])])])])},function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",{staticClass:"container mx-auto grid grid-cols-12 px-5"},[s("div",{staticClass:"col-span-12 md:col-span-10 lg:col-span-6 md:col-start-2 lg:col-start-4 text-center pt-10 pb-5"},[s("h1",{staticClass:"text-4xl md:text-6xl font-medium mb-4"},[t._v("The timeline")]),s("p",[t._v(" On the occasion of its 30th anniversary, Hauser & Wirth presents 30 Years, an interactive, digital chronology that traces the journey of Hauser & Wirth and the artists who have shaped it through personal stories and a wealth of never-before-seen archival and visual material. ")])])])},function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("li",{staticClass:"mr-10"},[s("a",{staticClass:"flex h-8 hover:border-b-4 border-white",attrs:{href:"#"}},[t._v("View All")])])},function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("li",{staticClass:"mr-10"},[s("a",{staticClass:"flex h-8 hover:border-b-4 border-white",attrs:{href:"#"}},[t._v("Key Milestones")])])},function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("li",{staticClass:"mr-10"},[s("a",{staticClass:"flex h-8 hover:border-b-4 border-white",attrs:{href:"#"}},[t._v("Historical Exhbitions")])])},function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("li",{staticClass:"mr-10"},[s("a",{staticClass:"flex h-8 hover:border-b-4 border-white",attrs:{href:"#"}},[t._v("By Artists & Estates")])])},function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",{staticClass:"flex contents"},[s("div",{staticClass:"col-start-5 col-end-6 mx-auto relative"},[s("div",{staticClass:"h-64 w-8 flex items-center justify-center"},[s("div",{staticClass:"h-full w-px bg-gray-100 pointer-events-none"})]),s("div",{staticClass:"w-64 top-0 text-center absolute left-4 -ml-32"},[s("div",{staticClass:"pt-12"},[s("p",{staticClass:"text-7xl font-medium mb-2"},[t._v("1993")]),s("p",{staticClass:"text-3xl font-medium"},[t._v("The Beginning")])])])])])},function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",{staticClass:"col-start-1 col-end-5 rounded-xl ml-auto"},[s("div",{staticClass:"h-full flex items-start"},[s("div",{staticClass:"text-right ml-4"},[s("h3",{staticClass:"text-sm mb-1"},[t._v("Zurich")]),s("p",{staticClass:"font-medium text-3xl"},[t._v(" Ursula Hauser & Iwan Wirth open their first gallery together ")])])])])},function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",{staticClass:"col-start-5 col-end-6 mx-auto relative"},[s("div",{staticClass:"h-full w-8 flex items-center justify-center"},[s("div",{staticClass:"h-full w-px bg-gray-100 pointer-events-none"})]),s("div",{staticClass:"w-8 h-8 absolute top-0 -mt-2 rounded-full bg-white"})])},function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",{staticClass:"col-start-1 col-end-5 rounded-xl ml-auto"},[s("div",{staticClass:"h-full flex items-start"},[s("div",{staticClass:"text-right pl-4"},[s("h3",{staticClass:"text-sm mb-1"},[t._v("Zurich")]),s("p",{staticClass:"font-medium text-3xl"},[t._v(" Ursula Hauser & Iwan Wirth open their first gallery together ")])])])])},function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",{staticClass:"col-start-5 col-end-6 mx-auto relative"},[s("div",{staticClass:"h-full w-8 flex items-center justify-center"},[s("div",{staticClass:"h-full w-px bg-gray-100 pointer-events-none"})]),s("div",{staticClass:"w-8 h-8 absolute top-0 -mt-2 rounded-full bg-white"})])},function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",{staticClass:"col-start-5 col-end-6 mx-auto relative"},[s("div",{staticClass:"h-full w-8 flex items-center justify-center"},[s("div",{staticClass:"h-full w-px bg-gray-100 pointer-events-none"})]),s("div",{staticClass:"w-8 h-8 absolute top-0 -mt-2 rounded-full bg-white"})])},function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",{staticClass:"col-start-6 col-end-9 rounded-xl ml-auto"},[s("div",{staticClass:"h-full flex items-start"},[s("div",{staticClass:"text-left"},[s("h3",{staticClass:"text-sm mb-1"},[t._v("Publishers")]),s("p",{staticClass:"font-medium text-3xl"},[t._v(" Hauser & Wirth publishes its first books ")])])])])},function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",{staticClass:"col-start-1 col-end-5 p-4 rounded-xl my-4 ml-auto"},[s("h3",{staticClass:"mb-1"},[t._v(" Discover More "),s("span",{staticClass:"font-semibold"},[t._v("(3)")])])])},function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("div",{staticClass:"h-full w-8 flex items-center justify-center"},[s("div",{staticClass:"h-full w-px bg-gray-100 pointer-events-none"})])}],m={name:"Home",components:{},data(){return{isModalToggled:!0}},methods:{toggleModal(){this.isModalToggled=!this.isModalToggled,this.isModalToggled&&(document.body.style.overflow="scroll"),this.isModalToggled||(document.body.style.overflow="hidden")}}},v=m,f=(s("cccb"),Object(l["a"])(v,u,h,!1,null,null,null)),p=f.exports;a["a"].use(d["a"]);const g=[{path:"/",name:"Home",component:p},{path:"/about",name:"About",component:function(){return s.e("about").then(s.bind(null,"f820"))}}],C=new d["a"]({mode:"history",base:"/",routes:g});var w=C,x=s("2f62");a["a"].use(x["a"]);var b=new x["a"].Store({state:{},mutations:{},actions:{},modules:{}});s("ba8c");a["a"].config.productionTip=!1,new a["a"]({router:w,store:b,render:function(t){return t(c)}}).$mount("#app")},"5ced":function(t,e,s){},ba8c:function(t,e,s){},cccb:function(t,e,s){"use strict";s("5ced")}});
//# sourceMappingURL=app.619b21a0.js.map