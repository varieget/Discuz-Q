(window.webpackJsonp=window.webpackJsonp||[]).push([[29],{"5W5W":function(e,t,a){"use strict";var s=function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("div",{staticClass:"cont-arrange-box"},[a("div",{staticClass:"cont-arrange-main"},[a("div",{staticClass:"cont-arrange__lf-side"},[e._t("side")],2),e._v(" "),a("main",{staticClass:"cont-arrange__rt-main"},[a("div",{staticClass:"cont-arrange__rt-main-header"},[a("div",{staticClass:"cont-arrange__rt-main-header__release"},[e.$attrs.author?a("p",{ref:"userName"},[e._v(e._s(e.$attrs.author))]):e._e(),e._v(" "),e.$attrs.replyBy?a("p",{ref:"userName"},[e._v(e._s(e.$attrs.replyBy))]):e._e(),e._v(" "),e.$attrs.author?a("span",[e._v("发布于")]):e._e(),e._v(" "),e.$attrs.replyBy?a("span",[e._v("回复主题")]):e._e(),e._v(" "),e.$attrs.theme?a("p",[e._v(e._s(e.$attrs.theme))]):e._e(),e._v(" "),e.$attrs.themeName?a("p",{ref:"themeName",class:e.$attrs.themeName?"themeName":"",style:e.themeNameStyle},[e._v("123"+e._s(e.$attrs.themeName))]):e._e()]),e._v(" "),e.$attrs.prply>=0&&e.$attrs.browse>=0?a("div",{staticClass:"cont-arrange__rt-main-header__reply-view rt-box"},[a("span",[e._v("回复/查看：")]),e._v(" "),a("span",[e._v(e._s(e.$attrs.prply)+"/"+e._s(e.$attrs.browse))])]):e._e(),e._v(" "),e.$attrs.last?a("div",{staticClass:"cont-arrange__rt-main-header__last-reply rt-box"},[a("span",[e._v("最后回复：")]),e._v(" "),a("span",[e._v(e._s(e.$attrs.last))])]):e._e(),e._v(" "),e.$attrs.ip?a("div",{staticClass:" rt-box"},[a("span",[e._v("IP：")]),e._v(" "),a("span",[e._v(e._s(e.$attrs.ip))])]):e._e(),e._v(" "),e.$attrs.finalPost?a("div",{staticClass:"cont-arrange__rt-main-header__release-time rt-box"},[a("span",[e._v("发布时间：")]),e._v(" "),a("span",[e._v(e._s(e.$attrs.finalPost))])]):e._e(),e._v(" "),e.$attrs.deleTime?a("div",{staticClass:" rt-box"},[a("span",[e._v("删除时间：")]),e._v(" "),a("span",[e._v(e._s(e.$attrs.deleTime))])]):e._e(),e._v(" "),e._t("header")],2),e._v(" "),a("div",{ref:"contMain",staticClass:"cont-arrange__rt-main-box",style:{height:e.showContStatus?e.mainHeight+30+"px":e.mainHeight>78?"78PX":""}},[e._t("main")],2),e._v(" "),e.mainHeight>78?a("div",{ref:"contControl",staticClass:"cont-block-control",class:e.showBottomStatus?"is-bottom-out":"",on:{click:e.showCont}},[a("p",[a("span",{staticClass:"iconfont icondown-menu",class:e.showContStatus?"show-down":""}),e._v("\n          "+e._s(e.showContStatus?"收起详情":"展开详情")+"\n        ")])]):e._e(),e._v(" "),e.$slots.footer?a("div",{staticClass:"cont-arrange__rt-main-footer"},[e._t("footer")],2):e._e()])])])},i=[];a.d(t,"a",(function(){return s})),a.d(t,"b",(function(){return i}))},"9Zrv":function(e,t,a){"use strict";var s=function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("div",{staticClass:"cont-review-box"},[a("Card",{attrs:{header:"搜索"}}),e._v(" "),a("div",{staticClass:"cont-review-header"},[a("div",{staticClass:"cont-review-header__lf"},[a("div",[a("span",{staticClass:"cont-review-header__lf-title"},[e._v("用户名：")]),e._v(" "),a("el-input",{attrs:{size:"medium",placeholder:"搜索用户名",clearable:""},model:{value:e.searchUserName,callback:function(t){e.searchUserName=t},expression:"searchUserName"}})],1),e._v(" "),a("div",[a("span",{staticClass:"cont-review-header__lf-title"},[e._v("每页显示：")]),e._v(" "),a("el-select",{attrs:{size:"medium",placeholder:"选择每页显示"},model:{value:e.pageSelect,callback:function(t){e.pageSelect=t},expression:"pageSelect"}},e._l(e.pageOptions,(function(e){return a("el-option",{key:e.value,attrs:{label:e.label,value:e.value}})})),1)],1)]),e._v(" "),a("div",{staticClass:"cont-review-header__rt"},[a("div",[a("span",{staticClass:"cont-review-header__lf-title"},[e._v("内容包含：")]),e._v(" "),a("el-input",{staticClass:"content-contains-input",attrs:{size:"medium",clearable:"",placeholder:"搜索关键词"},model:{value:e.keyWords,callback:function(t){e.keyWords=t},expression:"keyWords"}}),e._v(" "),a("el-checkbox",{model:{value:e.showSensitiveWords,callback:function(t){e.showSensitiveWords=t},expression:"showSensitiveWords"}},[e._v("显示敏感词")])],1),e._v(" "),a("div",{staticClass:"cont-review-header__rt-search"},[a("span",{staticClass:"cont-review-header__lf-title"},[e._v("搜索范围：")]),e._v(" "),a("el-select",{attrs:{size:"medium",placeholder:"选择审核状态"},model:{value:e.searchReviewSelect,callback:function(t){e.searchReviewSelect=t},expression:"searchReviewSelect"}},e._l(e.searchReview,(function(e){return a("el-option",{key:e.value,attrs:{label:e.label,value:e.value}})})),1),e._v(" "),a("el-select",{attrs:{size:"medium",clearable:"",placeholder:"选择搜索分类"},model:{value:e.categoriesListSelect,callback:function(t){e.categoriesListSelect=t},expression:"categoriesListSelect"}},e._l(e.categoriesList,(function(e){return a("el-option",{key:e.id,attrs:{label:e.name,value:e.id}})})),1),e._v(" "),a("el-select",{attrs:{size:"medium",placeholder:"选择搜索时间"},on:{change:e.searchTimeChange},model:{value:e.searchTimeSelect,callback:function(t){e.searchTimeSelect=t},expression:"searchTimeSelect"}},e._l(e.searchTime,(function(e){return a("el-option",{key:e.value,attrs:{label:e.label,value:e.value}})})),1),e._v(" "),a("el-button",{attrs:{size:"small",type:"primary"},on:{click:e.themeSearch}},[e._v("搜索")])],1)])]),e._v(" "),a("div",{staticClass:"cont-review-table"},[e._l(e.themeList,(function(t,s){return a("ContArrange",{key:t._data.id,attrs:{author:t.user?t.user._data.username:"该用户被删除",theme:t.category._data.name,prply:t._data.postCount,browse:t._data.viewCount,last:t.lastPostedUser?t.lastPostedUser._data.username:"该用户被删除",finalPost:e.formatDate(t._data.createdAt)}},[a("div",{staticClass:"cont-review-table__side",attrs:{slot:"side"},slot:"side"},[a("el-radio-group",{on:{change:function(t){return e.radioChange(t,s)}},model:{value:e.submitForm[s].radio,callback:function(t){e.$set(e.submitForm[s],"radio",t)},expression:"submitForm[index].radio"}},[a("el-radio",{attrs:{label:0}},[e._v("通过")]),e._v(" "),a("el-radio",{attrs:{label:1}},[e._v("删除")]),e._v(" "),2!==t._data.isApproved?a("el-radio",{attrs:{label:2,disabled:2===t._data.isApproved}},[e._v("忽略")]):e._e()],1)],1),e._v(" "),a("div",{staticClass:"cont-review-table__main",attrs:{slot:"main"},slot:"main"},[e._v("\n        "+e._s(t.firstPost._data.content)+"\n      ")]),e._v(" "),a("div",{staticClass:"cont-review-table__footer",attrs:{slot:"footer"},slot:"footer"},[a("div",{staticClass:"cont-review-table__footer__lf"},[a("el-button",{attrs:{type:"text"},on:{click:function(a){return e.singleOperationSubmit(1,t.category._data.id,t._data.id)}}},[e._v("通过")]),e._v(" "),a("i"),e._v(" "),a("el-button",{attrs:{type:"text"},on:{click:function(a){return e.singleOperationSubmit(2,t.category._data.id,t._data.id,s)}}},[e._v("删除")]),e._v(" "),a("i"),e._v(" "),2!==t._data.isApproved?a("el-button",{attrs:{type:"text"},on:{click:function(a){return e.singleOperationSubmit(3,t.category._data.id,t._data.id)}}},[e._v("忽略")]):e._e()],1),e._v(" "),a("div",{staticClass:"cont-review-table__footer__rt"},[a("span",[e._v("操作理由：")]),e._v(" "),a("el-input",{attrs:{size:"medium",clearable:""},model:{value:e.submitForm[s].attributes.message,callback:function(t){e.$set(e.submitForm[s].attributes,"message",t)},expression:"submitForm[index].attributes.message"}}),e._v(" "),a("el-select",{attrs:{size:"medium",placeholder:"选择操作理由"},on:{change:function(t){return e.reasonForOperationChange(t,s)}},model:{value:e.submitForm[s].Select,callback:function(t){e.$set(e.submitForm[s],"Select",t)},expression:"submitForm[index].Select"}},e._l(e.reasonForOperation,(function(e){return a("el-option",{key:e.value,attrs:{label:e.label,value:e.value}})})),1)],1),e._v(" "),a("div",{staticClass:"cont-review-table__footer__bottom"},[a("el-button",{attrs:{type:"text"},on:{click:function(a){return e.viewClick(t._data.id)}}},[e._v("查看")]),e._v(" "),a("el-button",{attrs:{type:"text"},on:{click:function(a){return e.editClick(t._data.id)}}},[e._v("编辑")])],1)])])})),e._v(" "),e.pageCount>1?a("Page",{attrs:{"current-page":e.currentPaga,"page-size":10,total:e.total},on:{"current-change":e.handleCurrentChange}}):e._e()],2),e._v(" "),a("div",{staticClass:"cont-review-footer footer-btn"},[a("el-button",{attrs:{size:"small",type:"primary"},on:{click:e.submitClick}},[e._v("提交")]),e._v(" "),a("el-button",{attrs:{type:"text"},on:{click:function(t){return e.allOperationsSubmit(1)}}},[e._v("全部通过")]),e._v(" "),a("el-button",{attrs:{type:"text"},on:{click:function(t){return e.allOperationsSubmit(2)}}},[e._v("全部删除")]),e._v(" "),a("el-button",{directives:[{name:"show",rawName:"v-show",value:e.ignoreStatus,expression:"ignoreStatus"}],attrs:{type:"text"},on:{click:function(t){return e.allOperationsSubmit(3)}}},[e._v("全部忽略")]),e._v(" "),a("el-checkbox",{model:{value:e.appleAll,callback:function(t){e.appleAll=t},expression:"appleAll"}},[e._v("将操作应用到其他所有页面")])],1)],1)},i=[];a.d(t,"a",(function(){return s})),a.d(t,"b",(function(){return i}))},CD7n:function(e,t,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var s=r(a("QbLZ"));a("cajz");var i=r(a("ItsC"));function r(e){return e&&e.__esModule?e:{default:e}}t.default=(0,s.default)({name:"cont-review-view"},i.default)},Dt3C:function(e,t,a){"use strict";a.r(t);var s=a("5W5W"),i=a("aoOm");for(var r in i)"default"!==r&&function(e){a.d(t,e,(function(){return i[e]}))}(r);var o=a("KHd+"),n=Object(o.a)(i.default,s.a,s.b,!1,null,null,null);t.default=n.exports},ItsC:function(e,t,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var s=l(a("4gYi")),i=l(a("Dt3C")),r=l(a("rWG0")),o=l(a("VVfg")),n=l(a("wd/R"));function l(e){return e&&e.__esModule?e:{default:e}}t.default={data:function(){return{searchUserName:"",keyWords:"",showSensitiveWords:!1,pageOptions:[{value:10,label:"每页显示10条"},{value:20,label:"每页显示20条"},{value:30,label:"每页显示30条"}],pageSelect:10,searchReview:[{value:0,label:"未审核"},{value:2,label:"已忽略"}],searchReviewSelect:0,categoriesList:[],categoriesListSelect:"",searchTime:[{value:1,label:"全部"},{value:2,label:"最近一周"},{value:3,label:"最近一个月"},{value:4,label:"最近三个月"}],searchTimeSelect:1,relativeTime:["",""],submitForm:[],reasonForOperation:[{value:"无",label:"无"},{value:"广告/SPAM",label:"广告/SPAM"},{value:"恶意灌水",label:"恶意灌水"},{value:"违规内容",label:"违规内容"},{value:"文不对题",label:"文不对题"},{value:"重复发帖",label:"重复发帖"},{value:"我很赞同",label:"我很赞同"},{value:"精品文章",label:"精品文章"},{value:"原创内容",label:"原创内容"},{value:"其他",label:"其他"}],reasonForOperationSelect:1,appleAll:!1,themeList:[],currentPaga:1,total:0,pageCount:1,ignoreStatus:!0}},methods:{reasonForOperationChange:function(e,t){this.submitForm[t].attributes.message=e,console.log(this.submitForm[t])},handleCurrentChange:function(e){this.isIndeterminate=!1,this.checkAll=!1,this.getThemeList(e)},themeSearch:function(){this.ignoreStatus=2!==this.searchReviewSelect,this.getThemeList()},searchTimeChange:function(e){var t=new Date,a=new Date;switch(this.relativeTime=[],e){case 1:this.relativeTime.push("","");break;case 2:a.setTime(a.getTime()-6048e5),this.relativeTime.push(this.formatDate(t),this.formatDate(a));break;case 3:a.setTime(a.getTime()-2592e6),this.relativeTime.push(this.formatDate(t),this.formatDate(a));break;case 4:a.setTime(a.getTime()-7776e6),this.relativeTime.push(this.formatDate(t),this.formatDate(a));break;default:this.$message.error("搜索日期选择错误，请重新选择！或 刷新页面（F5）")}console.log("相对时间："+this.relativeTime)},submitClick:function(){console.log(this.submitForm),this.patchThreadsBatch(this.submitForm)},radioChange:function(e,t){switch(e){case 0:this.submitForm[t].attributes.isApproved=1;break;case 1:this.submitForm[t].attributes.isDeleted=!0;break;case 2:this.submitForm[t].attributes.isApproved=2}},allOperationsSubmit:function(e){var t=this;switch(e){case 1:this.submitForm.forEach((function(e,a){t.submitForm[a].attributes.isApproved=1}));break;case 2:this.submitForm.forEach((function(e,a){t.submitForm[a].attributes.isDeleted=!0}));break;case 3:this.submitForm.forEach((function(e,a){t.submitForm[a].attributes.isApproved=2}))}this.patchThreadsBatch(this.submitForm)},singleOperationSubmit:function(e,t,a,s){var i={type:"threads",attributes:{isApproved:0,isDeleted:!1},relationships:{category:{data:{type:"categories",id:t}}}};switch(e){case 1:i.attributes.isApproved=1,this.patchThreads(i,a);break;case 2:i.attributes.isDeleted=!0,i.attributes.message=this.submitForm[s].attributes.message,this.patchThreads(i,a);break;case 3:i.attributes.isApproved=2,this.patchThreads(i,a);break;default:console.log("系统错误，请刷新页面")}},viewClick:function(e){var t=this.$router.resolve({path:"/details/"+e});window.open(t.href,"_blank")},editClick:function(e,t){console.log(e);var a=this.$router.resolve({name:"reply-to-topic",query:{themeId:e}});window.open(a.href,"_blank")},formatDate:function(e){return(0,n.default)(e).format("YYYY-MM-DD HH:mm")},getThemeList:function(e){var t=this;this.appFetch({url:"threads",method:"get",data:{"filter[isDeleted]":"no","filter[username]":this.searchUserName,"page[number]":e,"page[size]":this.pageSelect,"filter[q]":this.keyWords,"filter[isApproved]":this.searchReviewSelect,"filter[createdAtBegin]":this.relativeTime[1],"filter[createdAtEnd]":this.relativeTime[0],"filter[categoryId]":this.categoriesListSelect}}).then((function(e){console.log(e),t.themeList=[],t.submitForm=[],t.themeList=e.readdata,t.total=e.meta.threadCount,t.pageCount=e.meta.pageCount,t.themeList.forEach((function(e,a){t.submitForm.push({Select:"无",radio:"",type:"threads",id:e._data.id,attributes:{isApproved:0,isDeleted:!1,message:""},relationships:{category:{data:{type:"categories",id:e.category._data.id}}}})}))})).catch((function(e){console.log(e)}))},getCategories:function(){var e=this;this.appFetch({url:"categories",method:"get",data:{}}).then((function(t){e.categoriesList=[],t.data.forEach((function(t,a){e.categoriesList.push({name:t.attributes.name,id:t.id})}))})).catch((function(e){console.log(e)}))},patchThreadsBatch:function(e){var t=this;this.appFetch({url:"threadsBatch",method:"patch",data:{data:e}}).then((function(e){e.meta&&e.data?t.$message.error("操作失败！"):(t.getThemeList(Number(o.default.getLItem("currentPag"))||1),t.$message({message:"操作成功",type:"success"})),console.log(e)})).catch((function(e){}))},patchThreads:function(e,t){var a=this;this.appFetch({url:"threads",method:"patch",splice:"/"+t,data:{data:e}}).then((function(e){e.meta&&e.data?(a.checkedTheme=[],a.$message.error("操作失败！")):(a.getThemeList(Number(o.default.getLItem("currentPag"))||1),a.$message({message:"操作成功",type:"success"}))})).catch((function(e){console.log(e)}))}},created:function(){this.getCategories(),this.getThemeList(Number(o.default.getLItem("currentPag"))||1)},components:{Card:s.default,ContArrange:i.default,Page:r.default}}},Oi5V:function(e,t,a){"use strict";a.r(t);var s=a("9Zrv"),i=a("fHKr");for(var r in i)"default"!==r&&function(e){a.d(t,e,(function(){return i[e]}))}(r);var o=a("KHd+"),n=Object(o.a)(i.default,s.a,s.b,!1,null,null,null);t.default=n.exports},Q86h:function(e,t,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var s=r(a("QbLZ"));a("uwep");var i=r(a("u8Dz"));function r(e){return e&&e.__esModule?e:{default:e}}t.default=(0,s.default)({name:"cont-arrange-view"},i.default)},aoOm:function(e,t,a){"use strict";a.r(t);var s=a("Q86h"),i=a.n(s);for(var r in s)"default"!==r&&function(e){a.d(t,e,(function(){return s[e]}))}(r);t.default=i.a},fHKr:function(e,t,a){"use strict";a.r(t);var s=a("CD7n"),i=a.n(s);for(var r in s)"default"!==r&&function(e){a.d(t,e,(function(){return s[e]}))}(r);t.default=i.a},u8Dz:function(e,t,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.default={data:function(){return{showContStatus:!1,showBottomStatus:!1,mainHeight:0,windowWidth:0,themeNameLeft:70,themeNameStyle:{left:"70",width:"calc(100% - "},styleobj:{color:"red",background:"red"}}},props:{},methods:{showCont:function(){this.mainHeight=this.$slots.main[0].elm.offsetHeight,this.showContStatus=!this.showContStatus;var e=this.$slots.main[0].elm.getBoundingClientRect().width;this.$slots.main[0].elm.offsetHeight+this.$slots.main[0].elm.getBoundingClientRect().top>window.innerHeight&&(this.showBottomStatus=!0,this.$refs.contControl.style.width=e+40+"PX"),this.showContStatus||(this.showBottomStatus=!1,this.$refs.contControl.style.width="100%")},handleScroll:function(){this.$refs.contControl&&(this.$refs.contControl.style.width=this.$slots.main[0].elm.getBoundingClientRect().width+40+"PX"),this.$slots.main[0].elm.offsetHeight+this.$slots.main[0].elm.getBoundingClientRect().top<window.innerHeight?this.showBottomStatus=!1:this.showContStatus&&(this.showBottomStatus=!0)},browserSize:function(){if(this.$refs.contControl){var e=this.$slots.main[0].elm.getBoundingClientRect(),t=e.width,a=e.top,s=this.$refs.contControl.style;this.showContStatus?(this.$slots.main[0].elm.offsetHeight+a>window.innerHeight?s.width=t+40+"PX":s.width="100%",this.$refs.contMain.style.height=this.$slots.main[0].elm.offsetHeight+30+"PX"):s.width="100%"}},removeScrollHandler:function(){window.removeEventListener("scroll",this.handleScroll,!0),window.removeEventListener("resize",this.browserSize,!0)},themeStyle:function(){this.themeNameStyle.left="70",this.themeNameStyle.width="calc(100% - ",this.themeNameStyle.left=70+this.$refs.userName.clientWidth+"px",this.themeNameStyle.width=this.themeNameStyle.width+(100+this.$refs.userName.clientWidth)+"px)"}},mounted:function(){this.mainHeight=this.$slots.main[0].elm.offsetHeight,window.addEventListener("scroll",this.handleScroll,!0),window.addEventListener("resize",this.browserSize,!0),this.windowWidth=window.innerWidth,this.themeStyle()},beforeDestroy:function(){this.removeScrollHandler()}}},uwep:function(e,t,a){}}]);