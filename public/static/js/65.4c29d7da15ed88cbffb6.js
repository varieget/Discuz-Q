(window.webpackJsonp=window.webpackJsonp||[]).push([[65],{"3eyw":function(t,a,e){"use strict";var r=function(){var t=this,a=t.$createElement,e=t._self._c||a;return e("div",{staticClass:"withdrawal-setting-box"},[e("Card",{attrs:{header:"提现间隔时间（天）"}},[e("CardRow",{attrs:{description:"用户每次提现需间隔的时间，1天为24小时，0或不填则不限制"}},[e("el-input",{attrs:{clearable:""},model:{value:t.withdrawalInterval,callback:function(a){t.withdrawalInterval=a},expression:"withdrawalInterval"}})],1)],1),t._v(" "),e("Card",{attrs:{header:"提现手续费率（百分之）："}},[e("CardRow",{attrs:{description:"用户每次提现平台收取的手续费"}},[e("el-input",{attrs:{clearable:""},model:{value:t.withdrawalFee,callback:function(a){t.withdrawalFee=a},expression:"withdrawalFee"}})],1)],1),t._v(" "),e("Card",{attrs:{header:"单次提现最小金额（元）："}},[e("CardRow",{attrs:{description:"用户每次提现的最小金额"}},[e("el-input",{attrs:{clearable:""},model:{value:t.minAmount,callback:function(a){t.minAmount=a},expression:"minAmount"}})],1)],1),t._v(" "),e("Card",{attrs:{header:"单次提现最大金额（元）："}},[e("CardRow",{attrs:{description:"用户每次提现的最大金额"}},[e("el-input",{attrs:{clearable:""},model:{value:t.maxAmount,callback:function(a){t.maxAmount=a},expression:"maxAmount"}})],1)],1),t._v(" "),e("Card",{attrs:{header:"每日提现总金额上限（元）："}},[e("CardRow",{attrs:{description:"所有用户提现的每日上限总金额"}},[e("el-input",{attrs:{clearable:""},model:{value:t.amountCap,callback:function(a){t.amountCap=a},expression:"amountCap"}})],1)],1),t._v(" "),e("Card",{staticClass:"footer-btn"},[e("el-button",{attrs:{type:"primary",size:"medium"},on:{click:t.submitClick}},[t._v("提交")])],1)],1)},n=[];e.d(a,"a",(function(){return r})),e.d(a,"b",(function(){return n}))},RNFI:function(t,a,e){"use strict";e.r(a);var r=e("RPHq"),n=e.n(r);for(var i in r)"default"!==i&&function(t){e.d(a,t,(function(){return r[t]}))}(i);a.default=n.a},RPHq:function(t,a,e){"use strict";Object.defineProperty(a,"__esModule",{value:!0});var r=i(e("QbLZ"));e("rLWY");var n=i(e("sPLB"));function i(t){return t&&t.__esModule?t:{default:t}}a.default=(0,r.default)({name:"withdrawal-setting-view"},n.default)},sPLB:function(t,a,e){"use strict";Object.defineProperty(a,"__esModule",{value:!0});var r=i(e("4gYi")),n=i(e("pNQN"));function i(t){return t&&t.__esModule?t:{default:t}}a.default={data:function(){return{withdrawalInterval:"",withdrawalFee:"",minAmount:"",maxAmount:"",amountCap:""}},methods:{submitClick:function(){this.postWithdrawalSettings()},postWithdrawalSettings:function(){var t=this;this.appFetch({url:"settings",method:"post",data:{data:[{attributes:{key:"cash_interval_time",value:this.withdrawalInterval,tag:"cash"}},{attributes:{key:"cash_rate",value:this.withdrawalFee,tag:"cash"}},{attributes:{key:"cash_min_sum",value:this.minAmount,tag:"cash"}},{attributes:{key:"cash_max_sum",value:this.maxAmount,tag:"cash"}},{attributes:{key:"cash_sum_limit",value:this.amountCap,tag:"cash"}}]}}).then((function(a){t.$message({message:"提交成功",type:"success"}),t.getForum()})).catch((function(a){console.log(a),t.$message.error("操作失败！")}))},getForum:function(){var t=this;this.appFetch({url:"forum",method:"get",data:{}}).then((function(a){var e=a.data.attributes.setcash;t.withdrawalInterval=e.cash_interval_time,t.withdrawalFee=e.cash_rate,t.minAmount=e.cash_min_sum,t.maxAmount=e.cash_max_sum,t.amountCap=e.cash_sum_limit})).catch((function(a){console.log(a),t.$message.error("初始化失败！请重新刷新页面（F5）")}))}},created:function(){this.getForum()},components:{Card:r.default,CardRow:n.default}}},vIWR:function(t,a,e){"use strict";e.r(a);var r=e("3eyw"),n=e("RNFI");for(var i in n)"default"!==i&&function(t){e.d(a,t,(function(){return n[t]}))}(i);var s=e("KHd+"),u=Object(s.a)(n.default,r.a,r.b,!1,null,null,null);a.default=u.exports}}]);