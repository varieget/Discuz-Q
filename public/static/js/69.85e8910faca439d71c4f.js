(window.webpackJsonp=window.webpackJsonp||[]).push([[69],{"1muQ":function(t,e,a){"use strict";var s=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",[a("Card",{attrs:{header:"SDK AppID:"}},[a("CardRow",{attrs:{description:"SDK AppID是短信应用的唯一标识，调用短信API接口时，需要提供该参数"}},[a("el-input",{attrs:{clearable:""},model:{value:t.sdkAppId,callback:function(e){t.sdkAppId=e},expression:"sdkAppId"}})],1)],1),t._v(" "),a("Card",{attrs:{header:"App Key:"}},[a("CardRow",{attrs:{description:"App Key：App Key是用来校验短信发送合法性的密码，与SDK AppID对应"}},[a("el-input",{attrs:{clearable:""},model:{value:t.appKey,callback:function(e){t.appKey=e},expression:"appKey"}})],1)],1),t._v(" "),a("Card",{attrs:{header:"短信验证码使用模板ID："}},[a("CardRow",{attrs:{description:"填写在腾讯云已配置并审核通过的短信验证码的模板的ID"},scopedSlots:t._u([{key:"tail",fn:function(){return[a("a",{staticStyle:{"margin-left":"15px"},attrs:{href:"https://cloud.tencent.com/product/sms",target:"_blank"}},[t._v("未申请？点此申请")])]},proxy:!0}])},[a("el-input",{attrs:{clearable:""},model:{value:t.smsId,callback:function(e){t.smsId=e},expression:"smsId"}})],1)],1),t._v(" "),a("Card",{attrs:{header:"短信签名："}},[a("CardRow",{attrs:{description:"腾讯云账户 - 访问管理 - 访问密钥 - API密钥的SecretId"}},[a("el-input",{attrs:{clearable:""},model:{value:t.smsSignature,callback:function(e){t.smsSignature=e},expression:"smsSignature"}})],1)],1),t._v(" "),a("Card",{staticClass:"footer-btn"},[a("el-button",{attrs:{type:"primary",size:"medium"},on:{click:t.Submission}},[t._v("提交")])],1)],1)},n=[];a.d(e,"a",(function(){return s})),a.d(e,"b",(function(){return n}))},H7jw:function(t,e,a){"use strict";a.r(e);var s=a("1muQ"),n=a("hgJm");for(var u in n)"default"!==u&&function(t){a.d(e,t,(function(){return n[t]}))}(u);var r=a("KHd+"),d=Object(r.a)(n.default,s.a,s.b,!1,null,null,null);e.default=d.exports},hgJm:function(t,e,a){"use strict";a.r(e);var s=a("kl0r"),n=a.n(s);for(var u in s)"default"!==u&&function(t){a.d(e,t,(function(){return s[t]}))}(u);e.default=n.a},kl0r:function(t,e,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var s=u(a("QbLZ"));a("zt69");var n=u(a("z5fL"));function u(t){return t&&t.__esModule?t:{default:t}}e.default=(0,s.default)({name:"tencent-cloud-config-sms-view"},n.default)},z5fL:function(t,e,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var s=u(a("4gYi")),n=u(a("pNQN"));function u(t){return t&&t.__esModule?t:{default:t}}e.default={data:function(){return{sdkAppId:"",appKey:"",smsId:"",smsSignature:""}},created:function(){var t=this.$route.query.type;this.type=t,this.tencentCloudSms()},methods:{configClick:function(t){},tencentCloudSms:function(){var t=this;this.appFetch({url:"forum",method:"get",data:{}}).then((function(e){console.log(e),t.sdkAppId=e.readdata._data.qcloud.qcloud_sms_app_id,t.appKey=e.readdata._data.qcloud.qcloud_sms_app_key,t.smsId=e.readdata._data.qcloud.qcloud_sms_template_id,t.smsSignature=e.readdata._data.qcloud.qcloud_sms_sign}))},Submission:function(){var t=this;this.appFetch({url:"settings",method:"post",data:{data:[{attributes:{key:"qcloud_sms_app_id",value:this.sdkAppId,tag:"qcloud"}},{attributes:{key:"qcloud_sms_app_key",value:this.appKey,tag:"qcloud"}},{attributes:{key:"qcloud_sms_template_id",value:this.smsId,tag:"qcloud"}},{attributes:{key:"qcloud_sms_sign",value:this.smsSignature,tag:"qcloud"}}]}}).then((function(e){t.$message({message:"提交成功",type:"success"})}))}},components:{Card:s.default,CardRow:n.default}}}}]);