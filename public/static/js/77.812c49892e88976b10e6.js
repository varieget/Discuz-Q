(window.webpackJsonp=window.webpackJsonp||[]).push([[77],{"6GI9":function(t,e,n){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var c,i,a=n("YEIV"),o=(c=a)&&c.__esModule?c:{default:c};e.default=(i={data:function(){return{active:0,faceIndex:0}},props:{faceData:{type:Array}},created:function(){},computed:{faces:function(){for(var t=this.faceData,e=(this.faceIndex,t),n=0,c=[];28*n<e.length;)c.push(e.slice(28*n,28*(n+1))),n+=1;return c},scrollWidth:function(){return this.faces.length*document.body.clientWidth},scrollPosition:function(){return this.active*document.body.clientWidth}},mounted:function(){var t=this,e=this.$refs.faceContent,n=0,c=0;e.ontouchstart=function(t){n=t.targetTouches[0].pageX},e.ontouchend=function(e){(c=e.changedTouches[0].pageX)-n>50?0!==t.active&&t.active--:c-n<-50&&t.active!==t.faces.length-1&&t.active++}}},(0,o.default)(i,"created",(function(){})),(0,o.default)(i,"methods",{getUrlCode:function(){var t=this;this.code=this.$utils.getUrlKey("code"),alert(code),this.appFetch({url:"weixin",method:"get",data:{code:this.code}}).then((function(t){alert(65756765)}),(function(e){100004==e.errors[0].status&&t.$router.go(-1)}))},loginWxClick:function(){this.$router.push({path:"/wx-login-bd"})},loginPhoneClick:function(){this.$router.push({path:"/login-phone"})},onFaceClick:function(t){this.$emit("onFaceChoose",t)}}),i)},SDcr:function(t,e,n){"use strict";n.r(e);var c=n("ZeCH"),i=n("uwTP");for(var a in i)"default"!==a&&function(t){n.d(e,t,(function(){return i[t]}))}(a);var o=n("KHd+"),u=Object(o.a)(i.default,c.a,c.b,!1,null,null,null);e.default=u.exports},ZeCH:function(t,e,n){"use strict";var c=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",{staticClass:"face-container"},[n("div",{staticClass:"scroll-wrapper"},[n("div",{ref:"faceContent",staticClass:"face-content",style:{width:t.scrollWidth+"px",marginLeft:-t.scrollPosition+"px"},on:{touchmove:function(t){t.preventDefault()}}},t._l(t.faces,(function(e,c){return n("div",{key:c,staticClass:"face-page"},t._l(e,(function(e,c){return n("a",{key:c},[n("img",{staticClass:"emoji",attrs:{src:e._data.url},on:{click:function(n){return t.onFaceClick(" "+e._data.code+" ")}}})])})),0)})),0),t._v(" "),n("div",{staticClass:"page-dot"},t._l(t.faces.length,(function(e){return n("div",{key:e,staticClass:"dot-item",class:e===t.active+1?"active":"",on:{click:function(n){t.active=e-1}}})})),0)])])},i=[];n.d(e,"a",(function(){return c})),n.d(e,"b",(function(){return i}))},uwTP:function(t,e,n){"use strict";n.r(e);var c=n("yaIx"),i=n.n(c);for(var a in c)"default"!==a&&function(t){n.d(e,t,(function(){return c[t]}))}(a);e.default=i.a},yaIx:function(t,e,n){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var c=a(n("QbLZ")),i=a(n("6GI9"));function a(t){return t&&t.__esModule?t:{default:t}}n("E2jd"),e.default=(0,c.default)({name:"expressionView"},i.default)}}]);