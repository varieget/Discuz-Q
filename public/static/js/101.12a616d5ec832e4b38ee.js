(window.webpackJsonp=window.webpackJsonp||[]).push([[101],{"4Vlt":function(t,e,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var n=r(a("QbLZ")),s=r(a("Mro7"));function r(t){return t&&t.__esModule?t:{default:t}}a("I1+7"),e.default=(0,n.default)({name:"userReview"},s.default)},Mro7:function(t,e,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var n=r(a("wd/R")),s=r(a("4gYi"));function r(t){return t&&t.__esModule?t:{default:t}}e.default={data:function(){return{tableData:[],multipleSelection:[]}},methods:{handleSelectionChange:function(t){this.multipleSelection=t},singleOperation:function(t,e){var a=this;"pass"===t?this.editUser(e,0):"no"===t?this.$MessageBox.prompt("","提示",{confirmButtonText:"提交",cancelButtonText:"取消",inputPlaceholder:"请输入否决原因"}).then((function(t){a.editUser(e,1,t.value)})).catch((function(t){})):"del"===t&&this.deleteUser(e)},allOperation:function(t){var e=this,a=[];"pass"===t?(this.multipleSelection.forEach((function(t){a.push({attributes:{id:t._data.id,status:"0"}})})),this.patchEditUser(a)):"no"===t?this.$MessageBox.prompt("","提示",{confirmButtonText:"提交",cancelButtonText:"取消",inputPlaceholder:"请输入否决原因"}).then((function(t){e.multipleSelection.forEach((function(e){a.push({attributes:{id:e._data.id,status:"1",refuse_message:t.value}})})),e.patchEditUser(a)})).catch((function(t){})):"del"===t&&(this.multipleSelection.forEach((function(t){a.push(t._data.id)})),this.patchDeleteUser(a))},formatDate:function(t){return(0,n.default)(t).format("YYYY-MM-DD HH:mm")},getUserList:function(){var t=this;this.appFetch({url:"users",method:"get",data:{"filter[status]":"mod"}}).then((function(e){t.tableData=e.readdata}))},editUser:function(t,e,a){var n=this;this.appFetch({url:"users",method:"PATCH",splice:"/"+t,data:{data:{attributes:{status:e,refuse_message:a}}}}).then((function(t){t.errors?n.$message.error(t.errors[0].code):(n.$message({message:"操作成功",type:"success"}),n.getUserList())})).catch((function(t){}))},patchEditUser:function(t){var e=this;this.appFetch({method:"PATCH",url:"users",data:{data:t}}).then((function(t){t.errors?e.$message.error(t.errors[0].code):(e.$message({message:"操作成功",type:"success"}),e.getUserList())})).catch((function(t){}))},patchDeleteUser:function(t){var e=this;this.appFetch({url:"users",method:"delete",data:{data:{attributes:{id:t}}}}).then((function(t){t.errors?e.$message.error(t.errors[0].code):(e.$message({message:"操作成功",type:"success"}),e.getUserList())})).catch((function(t){}))},deleteUser:function(t){var e=this;this.appFetch({url:"users",method:"delete",splice:"/"+t,data:{}}).then((function(t){t.errors?e.$message.error(t.errors[0].code):(e.$message({message:"操作成功",type:"success"}),e.getUserList())})).catch((function(t){}))}},created:function(){this.getUserList()},components:{Card:s.default}}},ONLH:function(t,e,a){"use strict";var n=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",{staticClass:"user-review-box"},[a("div",{staticClass:"user-review-table"},[a("el-table",{ref:"multipleTable",staticStyle:{width:"100%"},attrs:{data:t.tableData,"tooltip-effect":"dark"},on:{"selection-change":t.handleSelectionChange}},[a("el-table-column",{attrs:{type:"selection",width:"55"}}),t._v(" "),a("el-table-column",{attrs:{label:"编号",prop:"_data.id",width:"100"}}),t._v(" "),a("el-table-column",{attrs:{prop:"_data.username",label:"用户名",width:"200"}}),t._v(" "),a("el-table-column",{attrs:{prop:"_data.registerReason",label:"注册原因","show-overflow-tooltip":""}}),t._v(" "),a("el-table-column",{attrs:{label:"注册时间"},scopedSlots:t._u([{key:"default",fn:function(e){return[t._v(t._s(t.formatDate(e.row._data.createdAt)))]}}])}),t._v(" "),a("el-table-column",{attrs:{label:"",width:"230"},scopedSlots:t._u([{key:"default",fn:function(e){return[a("el-button",{attrs:{type:"text"},on:{click:function(a){return t.singleOperation("pass",e.row._data.id)}}},[t._v("通过")]),t._v(" "),a("el-button",{attrs:{type:"text"},on:{click:function(a){return t.singleOperation("no",e.row._data.id)}}},[t._v("否决")]),t._v(" "),a("el-popover",{ref:"popover-"+e.$index,attrs:{width:"100",placement:"top"}},[a("p",[t._v("确定删除该项吗？")]),t._v(" "),a("div",{staticStyle:{"text-align":"right",margin:"10PX 0 0 0"}},[a("el-button",{attrs:{type:"text",size:"mini"},on:{click:function(t){e._self.$refs["popover-"+e.$index].doClose()}}},[t._v("取消")]),t._v(" "),a("el-button",{attrs:{type:"danger",size:"mini"},on:{click:function(a){t.singleOperation("del",e.row._data.id),e._self.$refs["popover-"+e.$index].doClose()}}},[t._v("确定")])],1),t._v(" "),a("el-button",{attrs:{slot:"reference",type:"text"},slot:"reference"},[t._v("删除")])],1)]}}])})],1)],1),t._v(" "),a("Card",{staticClass:"footer-btn"},[a("el-button",{attrs:{type:"primary"},on:{click:function(e){return t.allOperation("pass")}}},[t._v("通过")]),t._v(" "),a("el-button",{attrs:{type:"primary",plain:""},on:{click:function(e){return t.allOperation("no")}}},[t._v("否决")]),t._v(" "),a("el-button",{attrs:{size:"medium"},on:{click:function(e){return t.allOperation("del")}}},[t._v("删除")])],1)],1)},s=[];a.d(e,"a",(function(){return n})),a.d(e,"b",(function(){return s}))},esgl:function(t,e,a){"use strict";a.r(e);var n=a("4Vlt"),s=a.n(n);for(var r in n)"default"!==r&&function(t){a.d(e,t,(function(){return n[t]}))}(r);e.default=s.a},jhKW:function(t,e,a){"use strict";a.r(e);var n=a("ONLH"),s=a("esgl");for(var r in s)"default"!==r&&function(t){a.d(e,t,(function(){return s[t]}))}(r);var i=a("KHd+"),o=Object(i.a)(s.default,n.a,n.b,!1,null,null,null);e.default=o.exports}}]);