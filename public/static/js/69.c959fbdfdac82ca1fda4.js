(window.webpackJsonp=window.webpackJsonp||[]).push([[69],{"3OiF":function(e,t,a){"use strict";var i=function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("div",{staticClass:"recycle-bin-box"},[a("div",{staticClass:"recycle-bin-header"},[a("div",{staticClass:"recycle-bin-header__section"},[a("div",{staticClass:"section-top"},[a("span",{staticClass:"cont-review-header__lf-title"},[e._v("作者：")]),e._v(" "),a("el-input",{attrs:{size:"medium",clearable:"",placeholder:"搜索作者"},model:{value:e.searchUserName,callback:function(t){e.searchUserName=t},expression:"searchUserName"}})],1),e._v(" "),a("div",[a("span",{staticClass:"cont-review-header__lf-title"},[e._v("搜索范围：")]),e._v(" "),a("el-select",{attrs:{clearable:"",size:"medium",placeholder:"选择主题分类"},model:{value:e.categoriesListSelect,callback:function(t){e.categoriesListSelect=t},expression:"categoriesListSelect"}},e._l(e.categoriesList,(function(e){return a("el-option",{key:e.id,attrs:{label:e.name,value:e.id}})})),1)],1)]),e._v(" "),a("div",{staticClass:"recycle-bin-header__section"},[a("div",{staticClass:"section-top"},[a("span",{staticClass:"cont-review-header__lf-title"},[e._v("内容包含：")]),e._v(" "),a("el-input",{attrs:{size:"medium",clearable:"",placeholder:"搜索内容包含"},model:{value:e.keyWords,callback:function(t){e.keyWords=t},expression:"keyWords"}})],1),e._v(" "),a("div",[a("span",{staticClass:"cont-review-header__lf-title"},[e._v("操作人：")]),e._v(" "),a("el-input",{attrs:{size:"medium",clearable:"",placeholder:"搜索操作人"},model:{value:e.operator,callback:function(t){e.operator=t},expression:"operator"}})],1)]),e._v(" "),a("div",{staticClass:"recycle-bin-header__section"},[a("div",{staticClass:"section-top"},[a("span",{staticClass:"cont-review-header__lf-title time-title"},[e._v("发布时间范围：")]),e._v(" "),a("el-date-picker",{attrs:{"value-format":"yyyy-MM-dd",type:"daterange",align:"right","unlink-panels":"",size:"medium","range-separator":"至","start-placeholder":"开始日期","end-placeholder":"结束日期","picker-options":e.pickerOptions},model:{value:e.releaseTime,callback:function(t){e.releaseTime=t},expression:"releaseTime"}})],1),e._v(" "),a("div",[a("span",{staticClass:"cont-review-header__lf-title time-title"},[e._v("删除时间范围：")]),e._v(" "),a("el-date-picker",{attrs:{"value-format":"yyyy-MM-dd",type:"daterange",align:"right","unlink-panels":"",size:"medium","range-separator":"至","start-placeholder":"开始日期","end-placeholder":"结束日期","picker-options":e.pickerOptions},model:{value:e.deleteTime,callback:function(t){e.deleteTime=t},expression:"deleteTime"}})],1)]),e._v(" "),a("div",{staticClass:"recycle-bin-header__section"},[a("el-button",{attrs:{size:"small",type:"primary"},on:{click:e.searchClick}},[e._v("搜索")])],1)]),e._v(" "),a("div",{staticClass:"recycle-bin-table"},[e._l(e.themeList,(function(t,i){return a("ContArrange",{key:t._data.id,attrs:{author:t.user?t.user._data.username:"该用户被删除",theme:t.category._data.name,finalPost:e.formatDate(t._data.createdAt),deleTime:e.formatDate(t._data.deletedAt),userId:t.user?t.user._data.id:"该用户被删除"}},[a("div",{staticClass:"recycle-bin-table__side",attrs:{slot:"side"},slot:"side"},[a("el-radio-group",{on:{change:function(t){return e.radioChange(t,i)}},model:{value:e.submitForm[i].radio,callback:function(t){e.$set(e.submitForm[i],"radio",t)},expression:"submitForm[index].radio"}},[a("el-radio",{attrs:{label:"还原"}}),e._v(" "),a("el-radio",{attrs:{label:"删除"}})],1)],1),e._v(" "),a("div",{staticClass:"recycle-bin-table__main",attrs:{slot:"main"},slot:"main"},[a("a",{staticClass:"recycle-bin-table__main__cont-text",attrs:{href:"/details/"+t._data.id,target:"_blank"},domProps:{innerHTML:e._s(t.firstPost._data.contentHtml)}}),e._v(" "),a("div",{staticClass:"recycle-bin-table__main__cont-imgs"},e._l(t.firstPost.images,(function(i,s){return a("p",{key:i._data.thumbUrl,staticClass:"recycle-bin-table__main__cont-imgs-p"},[a("img",{directives:[{name:"lazy",rawName:"v-lazy",value:i._data.thumbUrl,expression:"item._data.thumbUrl"}],attrs:{alt:i._data.fileName},on:{click:function(a){return e.imgShowClick(t.firstPost.images,s)}}})])})),0),e._v(" "),a("div",{directives:[{name:"show",rawName:"v-show",value:t.firstPost.attachments.length>0,expression:"items.firstPost.attachments.length > 0"}],staticClass:"recycle-bin-table__main__cont-annex"},[a("span",[e._v("附件：")]),e._v(" "),e._l(t.firstPost.attachments,(function(t,i){return a("p",{key:i},[a("a",{attrs:{href:t._data.url,target:"_blank"}},[e._v(e._s(t._data.fileName))])])}))],2)]),e._v(" "),a("div",{staticClass:"recycle-bin-table__footer",attrs:{slot:"footer"},slot:"footer"},[a("div",{staticClass:"recycle-bin-table__footer-operator"},[a("span",[e._v("操作者：")]),e._v(" "),a("span",[e._v(e._s(t.user?t.deletedUser._data.username:"操作者被禁止或删除"))])]),e._v(" "),t.lastDeletedLog._data.message.length>0?a("div",{staticClass:"recycle-bin-table__footer-reason"},[a("span",[e._v("原因：")]),e._v(" "),a("span",[e._v(e._s(t.user?t.lastDeletedLog._data.message:"操作者被禁止或删除"))])]):e._e()])])})),e._v(" "),e.showViewer?a("el-image-viewer",{attrs:{"on-close":e.closeViewer,"url-list":e.url}}):e._e(),e._v(" "),a("tableNoList",{directives:[{name:"show",rawName:"v-show",value:e.themeList.length<1,expression:"themeList.length < 1"}]}),e._v(" "),e.pageCount>1?a("Page",{attrs:{"current-page":e.currentPaga,"page-size":10,total:e.total},on:{"current-change":e.handleCurrentChange}}):e._e()],2),e._v(" "),a("div",{staticClass:"recycle-bin-footer footer-btn"},[a("el-button",{attrs:{size:"small",type:"primary"},on:{click:e.submitClick}},[e._v("提交")]),e._v(" "),a("el-button",{attrs:{type:"text"},on:{click:function(t){return e.allOperationsSubmit(1)}}},[e._v("全部还原")]),e._v(" "),a("el-button",{attrs:{type:"text"},on:{click:function(t){return e.allOperationsSubmit(2)}}},[e._v("全部删除")])],1)])},s=[];a.d(t,"a",(function(){return i})),a.d(t,"b",(function(){return s}))},CohS:function(e,t,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var i=o(a("QbLZ"));a("lL+3");var s=o(a("aNwV")),r=o(a("Ozmy"));function o(e){return e&&e.__esModule?e:{default:e}}t.default=(0,i.default)({components:{ElRadio:r.default},name:"recycle-bin-view"},s.default)},Ozmy:function(e,t,a){"use strict";a.r(t);function i(e,t,a){this.$children.forEach(s=>{s.$options.componentName===e?s.$emit.apply(s,[t].concat(a)):i.apply(s,[e,t].concat([a]))})}var s={name:"ElRadio",mixins:[{methods:{dispatch(e,t,a){for(var i=this.$parent||this.$root,s=i.$options.componentName;i&&(!s||s!==e);)(i=i.$parent)&&(s=i.$options.componentName);i&&i.$emit.apply(i,[t].concat(a))},broadcast(e,t,a){i.call(this,e,t,a)}}}],inject:{elForm:{default:""},elFormItem:{default:""}},componentName:"ElRadio",props:{value:{},label:{},disabled:Boolean,name:String,border:Boolean,size:String},data:()=>({focus:!1}),computed:{isGroup(){let e=this.$parent;for(;e;){if("ElRadioGroup"===e.$options.componentName)return this._radioGroup=e,!0;e=e.$parent}return!1},model:{get(){return this.isGroup?this._radioGroup.value:this.value},set(e){this.isGroup?this.dispatch("ElRadioGroup","input",[e]):this.$emit("input",e),this.$refs.radio&&(this.$refs.radio.checked=this.model===this.label)}},_elFormItemSize(){return(this.elFormItem||{}).elFormItemSize},radioSize(){const e=this.size||this._elFormItemSize||(this.$ELEMENT||{}).size;return this.isGroup&&this._radioGroup.radioGroupSize||e},isDisabled(){return this.isGroup?this._radioGroup.disabled||this.disabled||(this.elForm||{}).disabled:this.disabled||(this.elForm||{}).disabled},tabIndex(){return this.isDisabled||this.isGroup&&this.model!==this.label?-1:0}},methods:{handleChange(){this.$nextTick(()=>{this.$emit("change",this.model),this.isGroup&&this.dispatch("ElRadioGroup","handleChange",this.model)})}}},r=a("KHd+"),o=Object(r.a)(s,(function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("label",{staticClass:"el-radio",class:[e.border&&e.radioSize?"el-radio--"+e.radioSize:"",{"is-disabled":e.isDisabled},{"is-focus":e.focus},{"is-bordered":e.border},{"is-checked":e.model===e.label}],attrs:{role:"radio","aria-checked":e.model===e.label,"aria-disabled":e.isDisabled,tabindex:e.tabIndex},on:{keydown:function(t){if(!t.type.indexOf("key")&&e._k(t.keyCode,"space",32,t.key,[" ","Spacebar"]))return null;t.stopPropagation(),t.preventDefault(),e.model=e.isDisabled?e.model:e.label}}},[a("span",{staticClass:"el-radio__input",class:{"is-disabled":e.isDisabled,"is-checked":e.model===e.label}},[a("span",{staticClass:"el-radio__inner"}),e._v(" "),a("input",{directives:[{name:"model",rawName:"v-model",value:e.model,expression:"model"}],ref:"radio",staticClass:"el-radio__original",attrs:{type:"radio","aria-hidden":"true",name:e.name,disabled:e.isDisabled,tabindex:"-1"},domProps:{value:e.label,checked:e._q(e.model,e.label)},on:{focus:function(t){e.focus=!0},blur:function(t){e.focus=!1},change:[function(t){e.model=e.label},e.handleChange]}})]),e._v(" "),a("span",{staticClass:"el-radio__label",on:{keydown:function(e){e.stopPropagation()}}},[e._t("default"),e._v(" "),e.$slots.default?e._e():[e._v(e._s(e.label))]],2)])}),[],!1,null,null,null);t.default=o.exports},aNwV:function(e,t,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var i=d(a("4gYi")),s=d(a("Dt3C")),r=d(a("rWG0")),o=d(a("7qpD")),l=d(a("VVfg")),n=d(a("wd/R")),c=d(a("CKnL"));function d(e){return e&&e.__esModule?e:{default:e}}t.default={data:function(){return{searchUserName:"",keyWords:"",operator:"",categoriesList:[],categoriesListSelect:"",pickerOptions:{shortcuts:[{text:"最近一周",onClick:function(e){var t=new Date,a=new Date;a.setTime(a.getTime()-6048e5),e.$emit("pick",[a,t])}},{text:"最近一个月",onClick:function(e){var t=new Date,a=new Date;a.setTime(a.getTime()-2592e6),e.$emit("pick",[a,t])}},{text:"最近三个月",onClick:function(e){var t=new Date,a=new Date;a.setTime(a.getTime()-7776e6),e.$emit("pick",[a,t])}}]},releaseTime:["",""],deleteTime:["",""],radioList:"",deleteStatusList:[],appleAll:!1,themeList:[],currentPaga:1,total:0,pageCount:1,submitForm:[],showViewer:!1,url:[]}},methods:{imgShowClick:function(e,t){var a=this;console.log(e),this.url=[];var i=[];e.forEach((function(e){i.push(e._data.url)})),this.url.push(i[t]),i.forEach((function(e,i){i>t&&a.url.push(e)})),i.forEach((function(e,i){i<t&&a.url.push(e)})),this.showViewer=!0},closeViewer:function(){this.showViewer=!1},radioChange:function(e,t){switch(e){case"还原":this.submitForm[t].attributes.isDeleted=!1,this.submitForm[t].hardDelete=!1;break;case"删除":this.submitForm[t].hardDelete=!0;break;default:console.log("左侧操作错误，请刷新页面!")}},searchClick:function(){console.log(this.releaseTime),this.currentPaga=1,this.getThemeList(1)},handleCurrentChange:function(e){document.getElementsByClassName("index-main-con__main")[0].scrollTop=0,l.default.setLItem("currentPag",e),this.currentPaga=e,this.getThemeList(e)},submitClick:function(){var e=this;console.log(this.submitForm),this.deleteStatusList=[];var t=[];this.submitForm.forEach((function(a,i){a.hardDelete&&e.deleteStatusList.push(a.id),a.attributes.isDeleted||t.push(a.id)})),this.deleteStatusList.length>0&&this.deleteThreadsBatch(this.deleteStatusList.join(",")),t.length>0&&this.patchThreadsBatch(this.submitForm)},allOperationsSubmit:function(e){var t=this,a="";switch(e){case 1:this.submitForm.forEach((function(e,a){t.submitForm[a].attributes.isDeleted=!1})),this.patchThreadsBatch(this.submitForm);break;case 2:this.submitForm.forEach((function(e,i){i<t.submitForm.length-1?a=a+e.id+",":a+=e.id})),this.deleteThreadsBatch(a);break;default:console.log("全部还原或全部删除操作错误,请刷新页面!")}},formatDate:function(e){return(0,n.default)(e).format("YYYY-MM-DD HH:mm")},getThemeList:function(e){var t=this;this.releaseTime=null==this.releaseTime?["",""]:this.releaseTime,this.radioList=null==this.radioList?["",""]:this.radioList,this.appFetch({url:"threads",method:"get",data:{include:["user","firstPost","category","deletedUser","lastDeletedLog","firstPost.images","firstPost.attachments"],"filter[isDeleted]":"yes","filter[username]":this.searchUserName,"page[number]":e,"page[size]":10,"filter[q]":this.keyWords,"filter[categoryId]":this.categoriesListSelect,"filter[deletedUsername]":this.operator,"filter[createdAtBegin]":this.releaseTime[0],"filter[createdAtEnd]":this.releaseTime[1],"filter[deletedAtBegin]":this.deleteTime[0],"filter[deletedAtEnd]":this.deleteTime[1],sort:"-deletedAt"}}).then((function(e){console.log(e),e.errors?t.$message.error(e.errors[0].code):(t.themeList=e.readdata,t.total=e.meta.threadCount,t.pageCount=e.meta.pageCount,t.submitForm=[],t.themeList.forEach((function(e,a){t.submitForm.push({radio:"",hardDelete:!1,type:"threads",id:e._data.id,attributes:{isDeleted:!0},relationships:{category:{data:{type:"categories",id:e.category._data.id}}}})})))})).catch((function(e){console.log(e)}))},getCategories:function(){var e=this;this.appFetch({url:"categories",method:"get",data:{}}).then((function(t){t.errors?e.$message.error(t.errors[0].code):(e.categoriesList=[],t.data.forEach((function(t,a){e.categoriesList.push({name:t.attributes.name,id:t.id})})))})).catch((function(e){console.log(e)}))},patchThreadsBatch:function(e){var t=this;this.appFetch({url:"threadsBatch",method:"patch",data:{data:e}}).then((function(e){e.errors?t.$message.error(e.errors[0].code):(e.meta&&e.data?t.$message.error("操作失败！"):(t.getThemeList(Number(l.default.getLItem("currentPag"))||1),t.$message({message:"操作成功",type:"success"})),console.log(e))})).catch((function(e){}))},deleteThreadsBatch:function(e){var t=this;this.appFetch({url:"threadsBatch",method:"delete",splice:"/"+e}).then((function(e){console.log(e),e.meta?e.meta.forEach((function(e,a){setTimeout((function(){t.$message.error(e.code)}),500*(a+1))})):(t.getThemeList(Number(l.default.getLItem("currentPag"))||1),t.$message({message:"操作成功",type:"success"}))})).catch((function(e){console.log(e)}))},getCreated:function(e){e?(console.log(e),this.getThemeList(1)):(console.log(e),this.getThemeList(Number(l.default.getLItem("currentPag"))||1))}},created:function(){this.getCategories()},beforeRouteEnter:function(e,t,a){a((function(a){e.name!==t.name&&null!==t.name?(console.log("执行"),a.getCreated(!0)):a.getCreated(!1)}))},components:{Card:i.default,ContArrange:s.default,Page:r.default,tableNoList:o.default,ElImageViewer:c.default}}},wqDz:function(e,t,a){"use strict";a.r(t);var i=a("3OiF"),s=a("zmsP");for(var r in s)"default"!==r&&function(e){a.d(t,e,(function(){return s[e]}))}(r);var o=a("KHd+"),l=Object(o.a)(s.default,i.a,i.b,!1,null,null,null);t.default=l.exports},zmsP:function(e,t,a){"use strict";a.r(t);var i=a("CohS"),s=a.n(i);for(var r in i)"default"!==r&&function(e){a.d(t,e,(function(){return i[e]}))}(r);t.default=s.a}}]);