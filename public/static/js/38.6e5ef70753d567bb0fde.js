(window.webpackJsonp=window.webpackJsonp||[]).push([[38],{"+xxM":function(t,e,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var r=s(a("QbLZ"));a("NdMT");var i=s(a("wdQE"));function s(t){return t&&t.__esModule?t:{default:t}}e.default=(0,r.default)({name:"wx-login-bd"},i.default)},"3AWV":function(t,e,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var r=s(a("QbLZ"));a("iUmJ");var i=s(a("zkMY"));function s(t){return t&&t.__esModule?t:{default:t}}e.default=(0,r.default)({name:"login-sign-up-footer"},i.default)},GcKf:function(t,e,a){"use strict";a.r(e);var r=a("+xxM"),i=a.n(r);for(var s in r)"default"!==s&&function(t){a.d(e,t,(function(){return r[t]}))}(s);e.default=i.a},NdMT:function(t,e,a){},Ra63:function(t,e,a){"use strict";var r=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("footer",{staticClass:"login-user-footer"},["login-user"===t.pageName||"login-phone"===t.pageName?[t.qcloudSms?a("span",{on:{click:t.retrieveClick}},[t._v("忘记密码？找回")]):t._e(),t._v(" "),t.registerClose&&t.qcloudSms?a("i"):t._e(),t._v(" "),t.registerClose?a("span",{on:{click:t.signUpClick}},[t._v("注册")]):t._e()]:"wx-login-bd"===t.pageName?[a("span",{on:{click:t.wxSignUpBdClick}},[t._v("没有账号？注册，绑定微信新账号")])]:"wx-sign-up-bd"===t.pageName?[a("span",{on:{click:t.wxLoginBdClick}},[t._v("已有账号？登录，微信绑定账号")])]:"sign-up"===t.pageName?[a("span",{on:{click:t.loginClick}},[t._v("已有账号立即登录")])]:"bind-phone"===t.pageName?[a("span",{on:{click:t.homeClick}},[t._v(t._s("pay"===t.siteMode?"跳过，进入支付费用":"跳过，进入首页"))])]:(t.pageName,[a("span")])],2)},i=[];a.d(e,"a",(function(){return r})),a.d(e,"b",(function(){return i}))},UjaL:function(t,e,a){"use strict";a.r(e);var r=a("Ra63"),i=a("pz4+");for(var s in i)"default"!==s&&function(t){a.d(e,t,(function(){return i[t]}))}(s);var o=a("KHd+"),n=Object(o.a)(i.default,r.a,r.b,!1,null,null,null);e.default=n.exports},dyRi:function(t,e,a){"use strict";var r=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",{staticClass:"wx-login-bd-box"},[a("LoginHeader"),t._v(" "),a("main",{staticClass:"wx-login-bd-main"},[t._m(0),t._v(" "),a("form",{staticClass:"wx-login-bd-form"},[a("van-cell-group",[a("van-field",{attrs:{clearable:"",label:"用户名",placeholder:"请输入您的用户名"},model:{value:t.userName,callback:function(e){t.userName=e},expression:"userName"}}),t._v(" "),a("van-field",{attrs:{type:"password",label:"密码",placeholder:"请填写密码"},model:{value:t.password,callback:function(e){t.password=e},expression:"password"}})],1)],1),t._v(" "),a("div",{staticClass:"wx-login-bd-btn"},[a("van-button",{attrs:{type:"primary"},on:{click:t.loginBdClick}},[t._v("登录并绑定")])],1)]),t._v(" "),a("LoginFooter")],1)},i=[function(){var t=this.$createElement,e=this._self._c||t;return e("div",{staticClass:"wx-login-bd-title-box"},[e("h2",{staticClass:"wx-login-bd-title-h2"},[this._v("微信绑定账号")]),this._v(" "),e("div",{staticClass:"wx-login-main-desc"},[this._v("登录并绑定微信号")])])}];a.d(e,"a",(function(){return r})),a.d(e,"b",(function(){return i}))},"pz4+":function(t,e,a){"use strict";a.r(e);var r=a("3AWV"),i=a.n(r);for(var s in r)"default"!==s&&function(t){a.d(e,t,(function(){return r[t]}))}(s);e.default=i.a},sgy6:function(t,e,a){"use strict";a.r(e);var r=a("dyRi"),i=a("GcKf");for(var s in i)"default"!==s&&function(t){a.d(e,t,(function(){return i[t]}))}(s);var o=a("KHd+"),n=Object(o.a)(i.default,r.a,r.b,!1,null,null,null);e.default=n.exports},wdQE:function(t,e,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var r=n(a("JZuw")),i=n(a("UjaL")),s=n(a("VVfg")),o=n(a("6NK7"));function n(t){return t&&t.__esModule?t:{default:t}}e.default={data:function(){return{userName:"",password:"",siteMode:"",openid:"",wxurl:"",platform:""}},components:{LoginHeader:r.default,LoginFooter:i.default},methods:{loginBdClick:function(){var t=this;this.appFetch({url:"login",method:"post",data:{data:{attributes:{username:this.userName,password:this.password,openid:this.openid,platform:this.platform}}}}).then((function(e){if(e.errors)e.errors[0].detail?t.$toast.fail(e.errors[0].code+"\n"+e.errors[0].detail[0]):t.$toast.fail(e.errors[0].code);else{t.$toast.success("登录成功");var a=e.data.attributes.access_token,r=e.data.id;s.default.setLItem("Authorization",a),s.default.setLItem("tokenId",r),t.$router.push({path:s.default.getSItem("beforeVisiting")}),t.getUsers(r).then((function(e){e.readdata._data.paid?t.$router.push({path:"/"}):(s.default.setLItem("foregroundUser",e.data.attributes.username),"pay"===t.siteMode?t.$router.push({path:"pay-circle-login"}):"public"===t.siteMode&&t.$router.push({path:"/"}))}))}})).catch((function(t){}))},getWatchHref:function(t,e,a){var r=this;this.appFetch({url:"wechat",method:"get",data:{code:t,state:e,sessionId:a}}).then((function(t){if(t.errors){var e=t.errors[0].status,a=t.errors[0].user.openid;400==e&&(r.openid=a,s.default.setLItem("openid",a),r.$router.push({path:"/wx-login-bd"}))}else if(t.data.attributes.location)r.wxurl=t.data.attributes.location,window.location.href=t.data.attributes.location;else if(t.data.attributes.access_token){r.$toast.success("登录成功");var i=t.data.attributes.access_token,o=t.data.id;s.default.setLItem("Authorization",i),s.default.setLItem("tokenId",o),r.$router.push({path:"/"})}})).catch((function(t){}))},getForum:function(){var t=this;this.appFetch({url:"forum",method:"get",data:{}}).then((function(e){t.siteMode=e.readdata._data.set_site.site_mode,s.default.setLItem("siteInfo",e.readdata)})).catch((function(t){}))},getUsers:function(t){var e=this;return this.appFetch({url:"users",method:"get",splice:"/"+t,headers:{Authorization:"Bearer "+s.default.getLItem("Authorization")},data:{include:["groups"]}}).then((function(t){if(!t.errors)return t;e.$toast.fail(t.errors[0].code)})).catch((function(t){}))},getWatchHrefPC:function(t,e,a){var r=this;this.appFetch({url:"wxLogin",method:"get",data:{code:t,state:e,sessionId:a}}).then((function(t){if(t.errors){var e=t.errors[0].status,a=t.errors[0].user.openid;400==e&&(r.openid=a,s.default.setLItem("openid",a),r.$router.push({path:"/wx-login-bd"}))}else if(t.data.attributes.location)r.wxurl=t.data.attributes.location,window.location.href=t.data.attributes.location;else if(t.data.attributes.access_token){r.$toast.success("登录成功");var i=t.data.attributes.access_token,o=t.data.id;s.default.setLItem("Authorization",i),s.default.setLItem("tokenId",o);var n=s.default.getSItem("beforeVisiting");n?(r.$router.replace({path:n}),s.default.setSItem("beforeState",1)):(r.$router.push({path:"/"}),r.$router.go(0))}})).catch((function(t){}))}},created:function(){this.$router.history.current.query.code,this.$router.history.current.query.state,this.$router.history.current.query.sessionId;var t=o.default.isWeixin().isWeixin;this.openid=webDb.getLItem("openid"),t?this.platform="mp":(this.platform="dev",this.openid),this.getForum()}}},zkMY:function(t,e,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var r,i=a("VVfg"),s=(r=i)&&r.__esModule?r:{default:r};e.default={data:function(){return{pageName:"login",siteMode:"",registerClose:!0,qcloudSms:!0}},methods:{retrieveClick:function(){this.$router.push("retrieve-pwd")},signUpClick:function(){this.$router.push("sign-up")},wxSignUpBdClick:function(){this.$router.push("/wx-sign-up-bd")},wxLoginBdClick:function(){this.$router.push("/wx-login-bd")},loginClick:function(){this.$router.push("/login-user")},homeClick:function(){switch(this.siteMode){case"pay":this.$router.push({path:"pay-the-fee"});break;case"public":this.$router.push({path:"/"})}},getForum:function(){var t=this;this.appFetch({url:"forum",method:"get",data:{}}).then((function(e){t.siteMode=e.readdata._data.set_site.site_mode,t.registerClose=e.readdata._data.set_reg.register_close,t.qcloudSms=e.readdata._data.qcloud.qcloud_sms,s.default.setLItem("siteInfo",e.readdata)})).catch((function(t){}))}},created:function(){this.pageName=this.$router.history.current.name,this.getForum()}}}}]);