(window.webpackJsonp=window.webpackJsonp||[]).push([[32],{"3AWV":function(e,t,i){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var n=a(i("QbLZ"));i("iUmJ");var s=a(i("zkMY"));function a(e){return e&&e.__esModule?e:{default:e}}t.default=(0,n.default)({name:"login-sign-up-footer"},s.default)},"7Ths":function(e,t,i){"use strict";i.r(t);var n=i("8FiW"),s=i("U+hY");for(var a in s)"default"!==a&&function(e){i.d(t,e,(function(){return s[e]}))}(a);var r=i("KHd+"),o=Object(r.a)(s.default,n.a,n.b,!1,null,null,null);t.default=o.exports},"8FiW":function(e,t,i){"use strict";var n=function(){var e=this,t=e.$createElement,i=e._self._c||t;return i("div",{staticClass:"retrieve-password-box"},[i("retrievePWDHeader"),e._v(" "),i("main",{staticClass:"retrieve-password-main"},[e._m(0),e._v(" "),i("div",{staticClass:"login-module-form"},[i("van-cell-group",[i("van-field",{attrs:{label:"手机号",placeholder:"请输入您的手机号"},model:{value:e.phoneNum,callback:function(t){e.phoneNum=t},expression:"phoneNum"}}),e._v(" "),i("van-field",{attrs:{center:"",clearable:"",label:"验证码",placeholder:"请输入验证码"},model:{value:e.verifyNum,callback:function(t){e.verifyNum=t},expression:"verifyNum"}},[i("van-button",{class:{grayBg:e.isGray},attrs:{slot:"button",size:"small",type:"default"},on:{click:e.forgetSendSmsCode},slot:"button"},[e._v(e._s(e.btnContent))])],1),e._v(" "),i("van-field",{attrs:{label:"新密码",placeholder:"请输入新密码"},model:{value:e.newpwd,callback:function(t){e.newpwd=t},expression:"newpwd"}})],1)],1),e._v(" "),i("div",{staticClass:"retrieve-password-btn"},[i("van-button",{attrs:{type:"primary"},on:{click:e.submissionPassword}},[e._v("提交")])],1)])],1)},s=[function(){var e=this.$createElement,t=this._self._c||e;return t("div",{staticClass:"login-module-title-box"},[t("h2",{staticClass:"login-module-title"},[this._v("忘记密码")])])}];i.d(t,"a",(function(){return n})),i.d(t,"b",(function(){return s}))},NdMT:function(e,t,i){},Ra63:function(e,t,i){"use strict";var n=function(){var e=this,t=e.$createElement,i=e._self._c||t;return i("footer",{staticClass:"login-user-footer"},["login-user"===e.pageName||"login-phone"===e.pageName?[e.qcloudSms?i("span",{on:{click:e.retrieveClick}},[e._v("忘记密码？找回")]):e._e(),e._v(" "),e.registerClose&&e.qcloudSms?i("i"):e._e(),e._v(" "),e.registerClose?i("span",{on:{click:e.signUpClick}},[e._v("注册")]):e._e()]:"wx-login-bd"===e.pageName?[i("span",{on:{click:e.wxSignUpBdClick}},[e._v("没有账号？注册，绑定微信新账号")])]:"wx-sign-up-bd"===e.pageName?[i("span",{on:{click:e.wxLoginBdClick}},[e._v("已有账号？登录，微信绑定账号")])]:"sign-up"===e.pageName?[i("span",{on:{click:e.loginClick}},[e._v("已有账号立即登录")])]:"bind-phone"===e.pageName?[i("span",{on:{click:e.homeClick}},[e._v(e._s("pay"===e.siteMode?"跳过，进入支付费用":"跳过，进入首页"))])]:(e.pageName,[i("span")])],2)},s=[];i.d(t,"a",(function(){return n})),i.d(t,"b",(function(){return s}))},"U+hY":function(e,t,i){"use strict";i.r(t);var n=i("jJ/q"),s=i.n(n);for(var a in n)"default"!==a&&function(e){i.d(t,e,(function(){return n[e]}))}(a);t.default=s.a},UjaL:function(e,t,i){"use strict";i.r(t);var n=i("Ra63"),s=i("pz4+");for(var a in s)"default"!==a&&function(e){i.d(t,e,(function(){return s[e]}))}(a);var r=i("KHd+"),o=Object(r.a)(s.default,n.a,n.b,!1,null,null,null);t.default=o.exports},ZKVN:function(e,t,i){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var n=a(i("JZuw")),s=a(i("UjaL"));function a(e){return e&&e.__esModule?e:{default:e}}t.default={data:function(){return{newpwd:"",verifyNum:"",phoneNum:"",lostpwd:"lostpwd",btnContent:"获取验证码",time:1,disabled:!1,insterVal:"",isGray:!1}},components:{retrievePWDHeader:n.default,retrievePWDFooter:s.default},methods:{forgetSendSmsCode:function(){var e=this,t=this.phoneNum;t?(/^((13|14|15|17|18)[0-9]{1}\d{8})$/.test(t)||this.$toast("您输入的手机号码不合法，请重新输入"),this.appFetch({url:"sendSms",method:"post",data:{data:{attributes:{mobile:this.phoneNum,type:this.lostpwd}}}}).then((function(t){t.errors?e.$toast.fail(t.errors[0].code):(e.insterVal=t.data.attributes.interval,e.time=e.insterVal,e.timer())}))):this.$toast("请输入手机号码")},timer:function(){if(this.time>1){this.time--,this.btnContent=this.time+"s后重新获取",this.disabled=!0;var e=setTimeout(this.timer,1e3);this.isGray=!0}else 1==this.time&&(this.btnContent="获取验证码",clearTimeout(e),this.disabled=!1,this.isGray=!1)},submissionPassword:function(){var e=this;this.appFetch({url:"smsVerify",method:"post",data:{data:{attributes:{mobile:this.phoneNum,code:this.verifyNum,type:this.lostpwd,password:this.newpwd}}}}).then((function(t){t.errors?e.$toast.fail(t.errors[0].code):e.$router.push({path:"login-user"})}))}}}},"jJ/q":function(e,t,i){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var n=a(i("QbLZ"));i("NdMT"),i("iUmJ");var s=a(i("ZKVN"));function a(e){return e&&e.__esModule?e:{default:e}}t.default=(0,n.default)({name:"retrieve-password-view"},s.default)},"pz4+":function(e,t,i){"use strict";i.r(t);var n=i("3AWV"),s=i.n(n);for(var a in n)"default"!==a&&function(e){i.d(t,e,(function(){return n[e]}))}(a);t.default=s.a},zkMY:function(e,t,i){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var n,s=i("VVfg"),a=(n=s)&&n.__esModule?n:{default:n};t.default={data:function(){return{pageName:"login",siteMode:"",registerClose:!0,qcloudSms:!0}},methods:{retrieveClick:function(){this.$router.push("retrieve-pwd")},signUpClick:function(){this.$router.push("sign-up")},wxSignUpBdClick:function(){this.$router.push("/wx-sign-up-bd")},wxLoginBdClick:function(){this.$router.push("/wx-login-bd")},loginClick:function(){this.$router.push("/login-user")},homeClick:function(){switch(this.siteMode){case"pay":this.$router.push({path:"pay-the-fee"});break;case"public":this.$router.push({path:"/"});break;default:console.log("参数错误，请重新刷新页面")}},getForum:function(){var e=this;this.appFetch({url:"forum",method:"get",data:{}}).then((function(t){console.log(t),e.siteMode=t.readdata._data.set_site.site_mode,e.registerClose=t.readdata._data.set_reg.register_close,e.qcloudSms=t.readdata._data.qcloud.qcloud_sms,a.default.setLItem("siteInfo",t.readdata)})).catch((function(e){console.log(e)}))}},created:function(){this.pageName=this.$router.history.current.name,this.getForum()}}}}]);