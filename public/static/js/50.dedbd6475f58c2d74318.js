(window.webpackJsonp=window.webpackJsonp||[]).push([[50,110],{"+fa4":function(t,e,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var i,s=a("VVfg"),o=(i=s)&&i.__esModule?i:{default:i};e.default={data:function(){return{isfixNav:!1,siteInfo:!1,username:"",limitList:!1,allowRegister:""}},created:function(){this.loadSite();o.default.getLItem("tokenId")},methods:{loadSite:function(){var t=this,e=o.default.getLItem("tokenId");this.appFetch({url:"users",method:"get",splice:"/"+e,data:{include:"groups"}}).then((function(e){if(e.errors)throw t.$toast.fail(e.errors[0].code),new Error(e.error);t.roleList=e.readdata.groups,""==e.readdata._data.joinedAt||null==e.readdata._data.joinedAt?t.joinedAt=e.readdata._data.createdAt:t.joinedAt=e.readdata._data.joinedAt})),this.appFetch({url:"forum",method:"get",data:{include:["users"]}}).then((function(e){if(e.errors)throw t.$toast.fail(e.errors[0].code),new Error(e.error);t.siteInfo=e.readdata,t.allowRegister=e.readdata._data.set_reg.register_close})),this.appFetch({url:"groups",method:"get",data:{include:["permission"],"filter[isDefault]":1}}).then((function(e){if(e.errors)throw t.$toast.fail(e.errors[0].code),new Error(e.error);t.limitList=e.readdata[0]}))},moreCilrcleMembers:function(){this.$router.push({path:"circle-members"})},membersJump:function(t){this.$router.push({path:"/home-page/"+t})},loginJump:function(){this.$router.push({path:"/login-user"})},registerJump:function(){this.$router.push({path:"/sign-up"})}},mounted:function(){window.addEventListener("scroll",this.handleTabFix,!0)},beforeRouteLeave:function(t,e,a){window.removeEventListener("scroll",this.handleTabFix,!0),a()}}},"53J7":function(t,e,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var i=n(a("QbLZ")),s=n(a("+fa4")),o=n(a("QiNT")),r=n(a("omtG"));function n(t){return t&&t.__esModule?t:{default:t}}e.default=(0,i.default)({name:"circleInviteView",components:{Header:r.default}},o.default,s.default)},I0Z1:function(t,e,a){"use strict";var i=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("section",[a("van-popup",{staticClass:"sidebarWrap",style:{height:"100%",right:t.isPhone||t.isWeixin?"0":(t.viewportWidth-640)/2+"px"},attrs:{position:"right"},model:{value:t.popupShow,callback:function(e){t.popupShow=e},expression:"popupShow"}},[a("sidebar",{attrs:{isPayVal:t.isPayVal}})],1),t._v(" "),t.$route.meta.oneHeader?a("div",{staticClass:"headerBox"},[a("div",{directives:[{name:"show",rawName:"v-show",value:t.invitePerDet,expression:"invitePerDet"}],staticClass:"invitePerDet aaa"},[t.userInfoAvatarUrl?a("img",{staticClass:"inviteHead",attrs:{src:t.userInfoAvatarUrl,alt:""}}):a("img",{staticClass:"inviteHead",attrs:{src:t.appConfig.staticBaseUrl+"/images/noavatar.gif",alt:"ssss"}}),t._v(" "),t.invitePerDet&&t.userInfoName?a("div",{staticClass:"inviteName",model:{value:t.userInfoName,callback:function(e){t.userInfoName=e},expression:"userInfoName"}},[t._v(t._s(t.userInfoName))]):a("div",{staticClass:"inviteName"},[t._v("该用户已被删除")]),t._v(" "),a("p",{directives:[{name:"show",rawName:"v-show",value:t.invitationShow,expression:"invitationShow"}],staticClass:"inviteWo"},[t._v("邀请您加入")]),t._v(" "),t.followShow?a("div",{staticClass:"followBox"},[a("span",[t._v("关注："+t._s(t.followDet._data.followCount))]),t._v(" "),a("span",[t._v("被关注："+t._s(t.followDet._data.fansCount))]),t._v(" "),t.equalId?t._e():a("div",{staticClass:"followStatus",attrs:{href:"javascript:;"}},["0"==t.intiFollowVal?a("a",{attrs:{href:"javascript:;"},on:{click:function(e){return t.followCli(t.intiFollowVal)}}},[t._v("关注TA")]):"2"==t.intiFollowVal?a("a",{attrs:{href:"javascript:;"},on:{click:function(e){return t.followCli(t.intiFollowVal)}}},[t._v("相互关注")]):"1"==t.intiFollowVal?a("a",{attrs:{href:"javascript:;"},on:{click:function(e){return t.followCli(t.intiFollowVal)}}},[t._v("已关注")]):t._e()])]):t._e()]),t._v(" "),t.searchIconShow||t.menuIconShow?t._e():a("div",{staticClass:"headeGap"}),t._v(" "),t.searchIconShow||t.menuIconShow?a("div",{staticClass:"headOpe"},[a("span",{directives:[{name:"show",rawName:"v-show",value:t.searchIconShow,expression:"searchIconShow"}],staticClass:"icon iconfont icon-search",on:{click:t.searchJump}}),t._v(" "),a("span",{directives:[{name:"show",rawName:"v-show",value:t.menuIconShow,expression:"menuIconShow"}],staticClass:"icon iconfont icon-Shape relative",attrs:{"is-link":""},on:{click:t.showPopup}},[t.noticeSum>0?a("i",{staticClass:"noticeNew"}):t._e()])]):t._e(),t._v(" "),a("div",{directives:[{name:"show",rawName:"v-show",value:t.logoShow,expression:"logoShow"}],staticClass:"logoBox"},[t.logo?a("img",{staticClass:"logo",attrs:{src:t.logo}}):a("img",{staticClass:"logo",attrs:{src:t.appConfig.staticBaseUrl+"/images/logo.png"}})]),t._v(" "),t.siteInfo?a("div",{directives:[{name:"show",rawName:"v-show",value:t.perDetShow,expression:"perDetShow"}],staticClass:"circleDet"},[a("span",[t._v("主题："+t._s(t.siteInfo._data.other.count_threads))]),t._v(" "),a("span",[t._v("成员："+t._s(t.siteInfo._data.other.count_users))]),t._v(" "),t.siteInfo._data.set_site.site_author?a("span",[t._v("站长："+t._s(t.siteInfo._data.set_site.site_author.username))]):a("span",[t._v("站长：无")])]):t._e(),t._v(" "),a("div",{directives:[{name:"show",rawName:"v-show",value:t.navShow,expression:"navShow"}],staticClass:"navBox",class:{fixedNavBar:t.isfixNav},attrs:{id:"testNavBar"}},[a("van-tabs",{model:{value:t.navActi,callback:function(e){t.navActi=e},expression:"navActi"}},[a("van-tab",[a("span",{attrs:{slot:"title"},on:{click:function(e){return t.categoriesCho(0)}},slot:"title"},[t._v("\n              全部\n          ")])]),t._v(" "),t._l(t.categories,(function(e,i){return a("van-tab",{key:i},[a("span",{attrs:{slot:"title"},on:{click:function(a){return t.categoriesCho(e._data.id)}},slot:"title"},[t._v("\n              "+t._s(e._data.name)+"\n          ")])])}))],2)],1)]):t._e()],1)},s=[];a.d(e,"a",(function(){return i})),a.d(e,"b",(function(){return s}))},IBtZU:function(t,e,a){"use strict";a.r(e);var i=a("53J7"),s=a.n(i);for(var o in i)"default"!==o&&function(t){a.d(e,t,(function(){return i[t]}))}(o);e.default=s.a},Jgvg:function(t,e,a){"use strict";a.r(e);var i=a("pvnC"),s=a.n(i);for(var o in i)"default"!==o&&function(t){a.d(e,t,(function(){return i[t]}))}(o);e.default=s.a},QiNT:function(t,e,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var i,s=n(a("YEIV")),o=(a("ULRk"),n(a("VVfg"))),r=n(a("6NK7"));function n(t){return t&&t.__esModule?t:{default:t}}e.default={data:function(){var t;return t={headBackShow:!1,oneHeader:!1,twoHeader:!1,threeHeader:!1,fourHeader:!1,isfixNav:!1,isShow:!1,isHeadShow:!1,showHeader:!1,showMask:!1,title:"",navActi:0,perDet:{themeNum:"1222",memberNum:"1222",circleLeader:"站长名称"},avatarUrl:"",mobile:""},(0,s.default)(t,"isfixNav",!1),(0,s.default)(t,"popupShow",!1),(0,s.default)(t,"current",0),(0,s.default)(t,"userDet",[]),(0,s.default)(t,"categories",[]),(0,s.default)(t,"siteInfo",!1),(0,s.default)(t,"username",""),(0,s.default)(t,"isPayVal",""),(0,s.default)(t,"isWeixin",!1),(0,s.default)(t,"isPhone",!1),(0,s.default)(t,"firstCategoriesId",""),(0,s.default)(t,"logo",!1),(0,s.default)(t,"viewportWidth",""),(0,s.default)(t,"userId",""),(0,s.default)(t,"followDet",""),(0,s.default)(t,"followFlag",""),(0,s.default)(t,"intiFollowVal","0"),(0,s.default)(t,"noticeSum",0),(0,s.default)(t,"intiFollowChangeVal","0"),(0,s.default)(t,"oldFollow",!1),(0,s.default)(t,"equalId",!1),t},props:{userInfoAvatarUrl:{type:String},userInfoName:{type:String},headFixed:{headFixed:!1},invitePerDet:{invitePerDet:!1},searchIconShow:{searchIconShow:!1},menuIconShow:{menuIconShow:!1},navShow:{navShow:!1},invitationShow:{invitationShow:!1},perDetShow:{perDet:!1},logoShow:{logoShow:!1},followShow:{logoShow:!1}},computed:{personUserId:function(){return this.$route.params.userId}},created:function(){this.userId=o.default.getLItem("tokenId"),this.userId==this.personUserId?this.equalId=!0:this.equalId=!1,this.viewportWidth=window.innerWidth,this.isWeixin=r.default.isWeixin().isWeixin,this.isPhone=r.default.isWeixin().isPhone,this.loadCategories(),this.followShow&&this.userId&&this.loadUserFollowInfo(),this.userId&&this.loadUserInfo()},watch:{isfixNav:function(t,e){this.isfixNav=t}},methods:(i={limitWidth:function(){document.getElementById("testNavBar").style.width="640px";var t=window.innerWidth;document.getElementById("testNavBar").style.marginLeft=(t-640)/2+"px"},loadCategories:function(){var t=this;this.appFetch({url:"forum",method:"get",data:{include:["users"]}}).then((function(e){t.siteInfo=e.readdata,e.readdata._data.set_site.site_logo&&(t.logo=e.readdata._data.set_site.site_logo),t.isPayVal=e.readdata._data.set_site.site_mode})),this.navShow&&this.appFetch({url:"categories",method:"get",data:{include:[]}}).then((function(e){t.categories=e.readdata,t.firstCategoriesId=e.readdata[0]._data.id,t.$emit("update",t.firstCategoriesId)}))},loadUserFollowInfo:function(){var t=this;if(!this.userId)return!1;this.appFetch({url:"users",method:"get",splice:"/"+this.personUserId,data:{}}).then((function(e){t.followDet=e.readdata,"1"==e.readdata._data.follow?t.followFlag="已关注":"0"==e.readdata._data.follow?t.followFlag="关注TA":t.followFlag="相互关注",t.intiFollowVal=e.readdata._data.follow}))},loadUserInfo:function(){var t=this;if(!this.userId)return!1;this.appFetch({url:"users",method:"get",splice:"/"+this.userId,data:{}}).then((function(e){e.data.attributes.typeUnreadNotifications.liked||(e.data.attributes.typeUnreadNotifications.liked=0),e.data.attributes.typeUnreadNotifications.replied||(e.data.attributes.typeUnreadNotifications.replied=0),e.data.attributes.typeUnreadNotifications.rewarded||(e.data.attributes.typeUnreadNotifications.rewarded=0),e.data.attributes.typeUnreadNotifications.system||(e.data.attributes.typeUnreadNotifications.system=0),t.noticeSum=e.data.attributes.typeUnreadNotifications.liked+e.data.attributes.typeUnreadNotifications.replied+e.data.attributes.typeUnreadNotifications.rewarded+e.data.attributes.typeUnreadNotifications.system}))},followCli:function(t){if(o.default.getLItem("Authorization")){var e=new Object,a="";"1"==t||"2"==t?(e.to_user_id=this.personUserId,a="delete",this.oldFollow=t):(e.to_user_id=this.personUserId,a="post"),this.followRequest(a,e,t)}else o.default.setSItem("beforeVisiting",this.$route.path),this.$router.push({path:"/login-user"})},followRequest:function(t,e,a){var i=this;this.appFetch({url:"follow",method:t,data:{data:{type:"user_follow",attributes:e}}}).then((function(e){if(e.errors)throw i.$toast.fail(e.errors[0].code),new Error(e.error);"delete"==t?i.intiFollowVal="0":"1"==i.oldFollow||"0"==i.oldFollow?i.intiFollowVal="1":i.intiFollowVal="2"}))},backUrl:function(){window.history.go(-1)},showPopup:function(){this.popupShow=!0},categoriesCho:function(t){this.$emit("categoriesChoice",t)},searchJump:function(){this.$router.push({path:"/search"})},handleTabFix:function(){if(this.headFixed)if((window.pageYOffset||document.documentElement.scrollTop||document.body.scrollTop)>document.querySelector("#testNavBar").offsetTop)this.showHeader=!0,this.isfixNav=!0,1!=this.isWeixin&&1!=this.isPhone&&this.limitWidth();else{this.showHeader=!1,this.isfixNav=!1;window.innerWidth;document.getElementById("testNavBar").style.marginLeft="0px"}}},(0,s.default)(i,"backUrl",(function(){window.history.go(-1)})),(0,s.default)(i,"LogOut",(function(){})),(0,s.default)(i,"bindEvent",(function(t){1==t&&this.LogOut()})),i),mounted:function(){window.addEventListener("scroll",this.handleTabFix,!0)},beforeDestroy:function(){window.removeEventListener("scroll",this.handleTabFix)},destroyed:function(){window.removeEventListener("scroll",this.handleTabFix)},beforeRouteLeave:function(t,e,a){window.removeEventListener("scroll",this.handleTabFix),a()}}},"hl++":function(t,e,a){"use strict";var i=function(){var t=this,e=t.$createElement,a=t._self._c||e;return t.siteInfo?a("div",{staticClass:"circleCon"},[a("Header",{attrs:{searchIconShow:!1,perDetShow:!0,logoShow:!0,menuIconShow:!1,navShow:!1,invitePerDet:!0}}),t._v(" "),a("div",{staticClass:"gap"}),t._v(" "),a("div",{staticClass:"circleInfo padB0 lastBorNone"},[a("h1",{staticClass:"cirInfoTit"},[t._v("站点简介")]),t._v(" "),a("p",{staticClass:"cirInfoWord"},[t._v(t._s(t.siteInfo._data.set_site.site_introduction))])]),t._v(" "),a("div",{staticClass:"gap"}),t._v(" "),t.limitList?a("div",{staticClass:"powerListBox"},[a("div",{staticClass:"powerTit"},[t._v("作为"+t._s(t.limitList._data.name)+"，您将获得以下权限")]),t._v(" "),a("div",{staticClass:"powerList"},[a("div",{staticClass:"powerClassify"},[t._v("权限列表")]),t._v(" "),t._l(t.limitList.permission,(function(e,i){return a("div",{key:i},[e._data.permission&&"viewThreads"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("查看主题列表")]):t._e(),t._v(" "),e._data.permission&&"thread.viewPosts"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("查看主题")]):t._e(),t._v(" "),e._data.permission&&"createThread"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("发表主题")]):t._e(),t._v(" "),e._data.permission&&"thread.reply"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("回复主题")]):t._e(),t._v(" "),e._data.permission&&"attachment.create.0"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("上传附件")]):t._e(),t._v(" "),e._data.permission&&"attachment.create.1"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("上传图片")]):t._e(),t._v(" "),e._data.permission&&"attachment.view.0"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("查看附件")]):t._e(),t._v(" "),e._data.permission&&"attachment.view.1"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("查看图片")]):t._e(),t._v(" "),e._data.permission&&"viewUserList"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("站点会员列表")]):t._e(),t._v(" "),e._data.permission&&"attachment.delete"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("删除附件")]):t._e(),t._v(" "),e._data.permission&&"cash.create"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("申请提现")]):t._e(),t._v(" "),e._data.permission&&"order.create"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("创建订单")]):t._e(),t._v(" "),e._data.permission&&"thread.hide"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("删除主题")]):t._e(),t._v(" "),e._data.permission&&"thread.hidePosts"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("删除回复")]):t._e(),t._v(" "),e._data.permission&&"thread.favorite"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("帖子收藏")]):t._e(),t._v(" "),e._data.permission&&"thread.likePosts"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("帖子点赞")]):t._e(),t._v(" "),e._data.permission&&"user.view"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("查看某个用户信息权限")]):t._e(),t._v(" "),e._data.permission&&"viewSiteInfo"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("站点信息")]):t._e(),t._v(" "),e._data.permission&&"user.edit"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("编辑用户状态")]):t._e(),t._v(" "),e._data.permission&&"group.edit"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("编辑用户组")]):t._e(),t._v(" "),e._data.permission&&"createInvite"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("管理-邀请加入")]):t._e(),t._v(" "),e._data.permission&&"thread.batchEdit"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("批量管理主题")]):t._e(),t._v(" "),e._data.permission&&"thread.editPosts"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("编辑")]):t._e(),t._v(" "),e._data.permission&&"thread.essence"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("加精")]):t._e(),t._v(" "),e._data.permission&&"thread.sticky"==e._data.permission?a("p",{staticClass:"powerChi"},[t._v("置顶")]):t._e()])}))],2)]):t._e(),t._v(" "),a("div",{staticClass:"gap"}),t._v(" "),a("div",{staticClass:"loginOpera"},[a("a",{staticClass:"mustLogin",attrs:{href:"javascript:;"},on:{click:t.loginJump}},[t._v("已注册，登录")]),t._v(" "),t.allowRegister?a("a",{staticClass:"regiJoin",attrs:{href:"javascript:;"},on:{click:t.registerJump}},[t._v("接受邀请，注册")]):t._e(),t._v(" "),a("p",{staticClass:"payMoney"},[t._v("￥"+t._s(t.siteInfo._data.set_site.site_price)+" / 永久有效")])])],1):t._e()},s=[];a.d(e,"a",(function(){return i})),a.d(e,"b",(function(){return s}))},j7rN:function(t,e,a){"use strict";a.r(e);var i=a("hl++"),s=a("IBtZU");for(var o in s)"default"!==o&&function(t){a.d(e,t,(function(){return s[t]}))}(o);var r=a("KHd+"),n=Object(r.a)(s.default,i.a,i.b,!1,null,null,null);e.default=n.exports},omtG:function(t,e,a){"use strict";a.r(e);var i=a("I0Z1"),s=a("Jgvg");for(var o in s)"default"!==o&&function(t){a.d(e,t,(function(){return s[t]}))}(o);var r=a("KHd+"),n=Object(r.a)(s.default,i.a,i.b,!1,null,null,null);e.default=n.exports},pvnC:function(t,e,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var i=r(a("QbLZ")),s=r(a("QiNT")),o=r(a("IsPG"));function r(t){return t&&t.__esModule?t:{default:t}}a("iUmJ"),e.default=(0,i.default)({name:"headerView",components:{Sidebar:o.default}},s.default)}}]);