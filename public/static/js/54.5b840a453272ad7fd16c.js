(window.webpackJsonp=window.webpackJsonp||[]).push([[54],{"0owY":function(e,t,a){},"1iK8":function(e,t,a){"use strict";a.r(t);var r=a("4lso"),s=a("NY7+");for(var o in s)"default"!==o&&function(e){a.d(t,e,(function(){return s[e]}))}(o);var n=a("KHd+"),l=Object(n.a)(s.default,r.a,r.b,!1,null,null,null);t.default=l.exports},"4lso":function(e,t,a){"use strict";var r=function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("div",{staticClass:"user-details-box"},[a("div",{staticClass:"details-wallet-header"},[a("p",{staticClass:"details-wallet-header__name"},[e._v(e._s(this.userInfo.username)+"（UID："+e._s(this.userInfo.id)+"）")]),e._v(" "),a("i",{staticClass:"details-wallet-header__i"}),e._v(" "),a("span",{staticClass:"details-wallet-header__details"},[e._v("详情")]),e._v(" "),a("span",{on:{click:function(t){return e.$router.push({path:"/admin/wallet",query:e.query})}}},[e._v("钱包")])]),e._v(" "),a("Card",[a("el-upload",{staticClass:"avatar-uploader",attrs:{action:"","http-request":e.uploaderLogo,"show-file-list":!1,"on-success":e.handleAvatarSuccess,"before-upload":e.beforeAvatarUpload},on:{change:e.handleFile}},[e.imageUrl?a("img",{staticClass:"avatar",attrs:{src:e.imageUrl}}):a("i",{staticClass:"el-icon-plus avatar-uploader-icon"})]),e._v(" "),a("el-button",{attrs:{type:"text"},on:{click:e.deleteImage}},[e._v("删除")])],1),e._v(" "),a("Card",{attrs:{header:"新密码："}},[a("CardRow",{attrs:{description:"如果不更改密码此处请留空"}},[a("el-input",{attrs:{clearable:""},model:{value:e.newPassword,callback:function(t){e.newPassword=t},expression:"newPassword"}})],1)],1),e._v(" "),a("Card",{attrs:{header:"手机号："}},[a("CardRow",[a("el-input",{model:{value:e.userInfo.originalMobile,callback:function(t){e.$set(e.userInfo,"originalMobile",t)},expression:"userInfo.originalMobile"}})],1)],1),e._v(" "),a("Card",{attrs:{header:"用户角色："}},[a("CardRow",{attrs:{description:"设置允许参与搜索的用户组，可多选"}},[a("el-select",{attrs:{multiple:"",placeholder:"请选择"},model:{value:e.userRole,callback:function(t){e.userRole=t},expression:"userRole"}},e._l(e.options,(function(e){return a("el-option",{key:e.value,attrs:{disabled:"6"===e.value||"7"===e.value,label:e.label,value:e.value}})})),1)],1)],1),e._v(" "),a("Card",{attrs:{header:"状态："}},[a("CardRow",[a("el-select",{attrs:{placeholder:"请选择"},model:{value:e.userInfo.status,callback:function(t){e.$set(e.userInfo,"status",t)},expression:"userInfo.status"}},e._l(e.optionsStatus,(function(e){return a("el-option",{key:e.value,attrs:{label:e.label,value:e.value}})})),1)],1)],1),e._v(" "),a("Card",{attrs:{header:"注册时间："}},[a("p",[e._v(e._s(e.$moment(e.userInfo.createdAt).format("YYYY-MM-DD HH:mm")))])]),e._v(" "),a("Card",{attrs:{header:"注册IP："}},[a("p",[e._v(e._s(e.userInfo.registerIp))])]),e._v(" "),a("Card",{attrs:{header:"最后登录时间："}},[a("p",[e._v(e._s(e.$moment(e.userInfo.updatedAt).format("YYYY-MM-DD HH:mm")))])]),e._v(" "),a("Card",{attrs:{header:"最后登录IP："}},[a("p",[e._v(e._s(e.userInfo.lastLoginIp))])]),e._v(" "),e.wechatNickName?a("Card",{attrs:{header:"微信昵称："}},[a("p",[e._v(e._s(e.wechatNickName))])]):e._e(),e._v(" "),e.sex?a("Card",{attrs:{header:"性别："}},[a("p",[e._v(e._s(0===e.sex?"未知":1===e.sex?"男":"女"))])]):e._e(),e._v(" "),a("Card",{staticClass:"footer-btn"},[a("el-button",{attrs:{type:"primary",size:"medium"},on:{click:e.submission}},[e._v("提交")])],1)],1)},s=[];a.d(t,"a",(function(){return r})),a.d(t,"b",(function(){return s}))},"88qf":function(e,t,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var r=o(a("QbLZ"));a("0owY");var s=o(a("lgPP"));function o(e){return e&&e.__esModule?e:{default:e}}t.default=(0,r.default)({name:"user-details-view"},s.default)},"NY7+":function(e,t,a){"use strict";a.r(t);var r=a("88qf"),s=a.n(r);for(var o in r)"default"!==o&&function(e){a.d(t,e,(function(){return r[e]}))}(o);t.default=s.a},lgPP:function(e,t,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var r=l(a("14Xm")),s=l(a("D3Ub")),o=l(a("4gYi")),n=l(a("pNQN"));l(a("VVfg"));function l(e){return e&&e.__esModule?e:{default:e}}t.default={data:function(){return{fileList:[],options:[],optionsList:[],imageUrl:"",userRole:[],userInfo:{},newPassword:"",wechatNickName:"",sex:"",optionsStatus:[{value:0,label:"正常"},{value:1,label:"禁用"}],value:"",query:{}}},created:function(){this.query=this.$route.query,this.getUserDetail(),this.getUserList()},methods:{getUserDetail:function(){var e=this;return(0,s.default)(r.default.mark((function t(){var a;return r.default.wrap((function(t){for(;;)switch(t.prev=t.next){case 0:return t.prev=0,t.next=3,e.appFetch({method:"get",url:"users",splice:"/"+e.query.id,data:{include:"wechat,groups"}});case 3:(a=t.sent).errors?e.$message.error(a.errors[0].code):(console.log(a,"response"),e.userInfo=a.readdata._data,e.imageUrl=e.userInfo.avatarUrl,e.userRole=a.readdata.groups.map((function(e){return e._data.id})),console.log(e.userRole,"是我啊啊啊啊啊"),console.log(e.options,"option"),a.readdata.wechat&&(e.wechatNickName=a.readdata.wechat._data.nickname,e.sex=a.readdata.wechat._data.sex),console.log()),t.next=10;break;case 7:t.prev=7,t.t0=t.catch(0),console.error(t.t0,"getUserDetail");case 10:case"end":return t.stop()}}),t,e,[[0,7]])})))()},handleRemove:function(e,t){console.log(e,t)},deleteImage:function(){this.imageUrl="",this.appFetch({url:"deleteAvatar",method:"delete",splice:"/"+this.query.id+"/avatar",data:{}})},handlePreview:function(e){console.log(e)},handleExceed:function(e,t){this.$message.warning("当前限制选择 3 个文件，本次选择了 "+e.length+" 个文件，共选择了 "+(e.length+t.length)+" 个文件")},beforeRemove:function(e,t){return this.MessageBox.confirm("确定移除 "+e.name+"？")},handleAvatarSuccess:function(e,t){},handleFile:function(){},beforeAvatarUpload:function(e){var t="image/jpeg"===e.type,a=e.size/1024/1024<2;return t||this.$message.error("上传头像图片只能是 JPG 格式!"),a||this.$message.error("上传头像图片大小不能超过 2MB!"),t&&a},uploaderLogo:function(e){var t=this;console.log(e,"000000000000000");var a=new FormData;a.append("avatar",e.file),console.log(a),this.appFetch({url:"upload",method:"post",splice:this.query.id+"/avatar",data:a}).then((function(e){data.errors?t.$message.error(data.errors[0].code):t.imageUrl=e.readdata._data.avatarUrl}))},submission:function(){var e=this,t=this.userInfo.originalMobile;if(""==t);else if(!/^((13|14|15|17|18)[0-9]{1}\d{8})$/.test(t))return this.$toast("您输入的手机号码不合法，请重新输入");this.appFetch({url:"users",method:"patch",splice:"/"+this.query.id,data:{data:{attributes:{newPassword:this.newPassword,mobile:t,userRole:this.userRole,status:this.userInfo.status}}}}).then((function(t){t.errors?e.$message.error(t.errors[0].code):(console.log(t),e.$message({message:"提交成功",type:"success"}))}))},getUserList:function(){var e=this;return(0,s.default)(r.default.mark((function t(){var a,s;return r.default.wrap((function(t){for(;;)switch(t.prev=t.next){case 0:return t.prev=0,t.next=3,e.appFetch({method:"get",url:"groups"});case 3:a=t.sent,s=a.data,console.log(s,"8888"),e.options=s.map((function(e){return{value:e.id,label:e.attributes.name}})),t.next=12;break;case 9:t.prev=9,t.t0=t.catch(0),console.error(t.t0,"getUserList");case 12:case"end":return t.stop()}}),t,e,[[0,9]])})))()}},components:{Card:o.default,CardRow:n.default}}}}]);