(window.webpackJsonp=window.webpackJsonp||[]).push([[85],{"5JD/":function(t,e,o){"use strict";o.r(e);var s=o("HE2v"),i=o.n(s);for(var n in s)"default"!==n&&function(t){o.d(e,t,(function(){return s[t]}))}(n);e.default=i.a},HE2v:function(t,e,o){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var s=n(o("QbLZ"));o("XCsR"),o("i4TU");var i=n(o("SHGB"));function n(t){return t&&t.__esModule?t:{default:t}}e.default=(0,s.default)({name:"login-view"},i.default)},SHGB:function(t,e,o){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var s=l(o("QbLZ")),i=l(o("JZuw")),n=l(o("UjaL")),a=l(o("VVfg")),r=o("L2JU");function l(t){return t&&t.__esModule?t:{default:t}}e.default={data:function(){return{userName:"",password:"",userId:"2",btnLoading:!1,wxLoginShow:!0,isOne:!1,siteMode:"",phoneStatus:"",wxHref:"",isPC:!1,isCodeState:0,wxStatus:""}},computed:(0,r.mapState)({status:function(t){return t.appSiteModule.status}}),mounted:function(){},methods:(0,s.default)({},(0,r.mapMutations)({setStatus:"appSiteModule/SET_STATUS",setOpenId:"appSiteModule/SET_OPENID"}),{loginClick:function(){var t=this;this.setStatus("啊啦啦啦"),console.log(this.status),this.appFetch({url:"login",method:"post",data:{data:{attributes:{username:this.userName,password:this.password}}}}).then((function(e){if(console.log(e),e.errors)t.$toast.fail(e.errors[0].code);else{t.$toast.success("登录成功");var o=e.data.attributes.access_token,s=e.data.id;a.default.setLItem("Authorization",o),a.default.setLItem("tokenId",s),t.getUsers(s).then((function(e){e.readdata._data.paid?t.$router.push({path:"/"}):"pay"===t.siteMode?t.$router.push({path:"pay-circle-login"}):"public"===t.siteMode?t.$router.push({path:"/"}):console.log("缺少参数，请刷新页面")}))}})).catch((function(t){console.log(t)}))},loginWxClick:function(){this.isPC&&this.$message({message:"PC端暂不支持微信登录，请在微信客户端打开",type:"warning"})},loginPhoneClick:function(){this.$router.push({path:"/login-phone"})},getForum:function(){var t=this;this.appFetch({url:"forum",method:"get",data:{}}).then((function(e){console.log(e),t.phoneStatus=e.readdata._data.qcloud.qcloud_sms,t.siteMode=e.readdata._data.setsite.site_mode,a.default.setLItem("siteInfo",e.readdata)})).catch((function(t){console.log(t)}))},getUsers:function(t){return this.appFetch({url:"users",method:"get",splice:"/"+t,headers:{Authorization:"Bearer "+a.default.getLItem("Authorization")},data:{include:["groups"]}}).then((function(t){return console.log(t),t})).catch((function(t){console.log(t)}))}}),created:function(){localStorage.clear();var t=this.appCommonH.isWeixin().isWeixin,e=this.appCommonH.isWeixin().isPhone;console.log(this.$router.history),console.log(this.$router.history.current.query.code),console.log(this.$router.history.current.query.state),!0===t?console.log("微信登录"):!0===e?(console.log("手机浏览器登录"),this.wxLoginShow=!1,this.isOne=!0):(console.log("pc登录"),this.isPC=!0),this.getForum()},components:{LoginHeader:i.default,LoginFooter:n.default}}},YDYI:function(t,e,o){"use strict";var s=function(){var t=this,e=t.$createElement,o=t._self._c||e;return o("div",{staticClass:"login-user-box"},[o("LoginHeader"),t._v(" "),o("main",{staticClass:"login-user-box-main"},[t._m(0),t._v(" "),o("form",{staticClass:"user-login-form login-module-form"},[o("van-cell-group",[o("van-field",{attrs:{clearable:"",label:"用户名",placeholder:"请输入您的用户名"},model:{value:t.userName,callback:function(e){t.userName=e},expression:"userName"}}),t._v(" "),o("van-field",{attrs:{type:"password",clearable:"",label:"密码",placeholder:"请填写密码"},model:{value:t.password,callback:function(e){t.password=e},expression:"password"}})],1)],1),t._v(" "),o("div",{staticClass:"login-user-btn"},[o("van-button",{attrs:{type:"primary",loading:t.btnLoading,"loading-text":"登录中..."},on:{click:t.loginClick}},[t._v("登录")])],1),t._v(" "),o("div",{staticClass:"login-user-method"},[o("div",{staticClass:"login-user-method-box"},[o("van-divider",{directives:[{name:"show",rawName:"v-show",value:t.phoneStatus||t.wxLoginShow,expression:"phoneStatus ||  wxLoginShow"}]},[t._v("其他登录方式")])],1),t._v(" "),o("div",{staticClass:"login-user-method-icon"},[o("div",{staticClass:"login-user-method-icon-box",class:{justifyCenter:t.isOne}},[t.phoneStatus?o("i",{staticClass:"login-user-method-icon-ring iconfont",on:{click:t.loginPhoneClick}},[o("span",{staticClass:"icon iconfont icon-shouji",staticStyle:{color:"rgba(136, 136, 136, 1)"}})]):t._e(),t._v(" "),o("i",{directives:[{name:"show",rawName:"v-show",value:t.wxLoginShow,expression:"wxLoginShow"}],staticClass:"login-user-method-icon-ring iconfont",on:{click:t.loginWxClick}},[o("span",{staticClass:"icon iconfont icon-weixin",staticStyle:{color:"rgba(136, 136, 136, 1)"}})])])])])]),t._v(" "),o("LoginFooter")],1)},i=[function(){var t=this.$createElement,e=this._self._c||t;return e("div",{staticClass:"login-user-title-box login-module-title-box"},[e("p",{staticClass:"login-user-title-p login-module-title"},[this._v("用户名登录")])])}];o.d(e,"a",(function(){return s})),o.d(e,"b",(function(){return i}))},lEHL:function(t,e,o){"use strict";o.r(e);var s=o("YDYI"),i=o("5JD/");for(var n in i)"default"!==n&&function(t){o.d(e,t,(function(){return i[t]}))}(n);var a=o("KHd+"),r=Object(a.a)(i.default,s.a,s.b,!1,null,null,null);e.default=r.exports}}]);