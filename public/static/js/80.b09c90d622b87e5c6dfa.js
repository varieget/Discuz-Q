(window.webpackJsonp=window.webpackJsonp||[]).push([[80],{"/Zpk":function(t,e,s){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default={data:function(){return{id:1,checked:!0,result:[],checkBoxres:[],imageShow:!1,index:1,themeListResult:[],firstpostImageListResult:[],priview:[],showScreen:[],length:0,menuStatus:!1}},props:{themeList:{type:Array},replyTag:{replyTag:!1},isTopShow:{isTopShow:!1},isMoreShow:{isMoreShow:!1},ischeckShow:{ischeckShow:!1}},created:function(){this.loadPriviewImgList(),this.forList()},beforeDestroy:function(){},watch:{themeList:function(t,e){console.log(e),console.log(t),this.themeList=t,this.themeListResult=t,this.loadPriviewImgList(),this.$forceUpdate()},deep:!0},methods:{userArr:function(t){var e=[];return t.forEach((function(t){e.push(t._data.username)})),e.join(",")},forList:function(){for(var t=this.themeList.length,e=0;e<t;e++)this.showScreen.push(!1)},bindScreen:function(t){var e=this;console.log(t);this.showScreen.forEach((function(t){console.log(e.showScreen)})),this.showScreen.splice(t,1,!this.showScreen[t])},themeOpera:function(t,e,s){var a=new Object;2==e?(console.log(s),this.themeOpeRequest(t,a,s),a.isEssence=s):3==e?(a.isSticky=s,this.themeOpeRequest(t,a,s)):4==e?(a.isDeleted=!0,this.themeOpeRequest(t,a)):this.$router.push({path:"/edit-topic/"+this.themeId})},themeOpeRequest:function(t,e,s){var a=this;this.appFetch({url:"threads",method:"patch",splice:"/"+t,data:{data:{type:"threads",attributes:e}}}).then((function(t){console.log(t),console.log("888"),a.$emit("changeStatus",!0)}))},replyOpera:function(t,e,s,a){var i=this,r=new Object;r.isLiked=a;var n="posts/"+t;this.appFetch({url:n,method:"patch",data:{data:{type:"posts",attributes:r}}}).then((function(t){i.$message("修改成功"),i.$emit("changeStatus",!0)}))},loadPriviewImgList:function(){console.log(t);var t=this.themeListResult.length;if(""==this.themeListResult||null==this.themeListResult)return!1;for(var e=0;e<t;e++){var s=[];if(this.themeListResult[e].firstPost.images)for(var a=0;a<this.themeListResult[e].firstPost.images.length;a++)s.push(this.themeListResult[e].firstPost.images[a]._data.thumbUrl);console.log(s),this.themeListResult[e].firstPost.imageList=s}},imageSwiper:function(t){this.loadPriviewImgList(),this.imageShow=!0,console.log(this.priview)},onChange:function(t){this.index=t+1},checkAll:function(){console.log(this.$refs),this.$refs.checkboxGroup.toggleAll(!0)},signOutDele:function(){this.$refs.checkboxGroup.toggleAll()},deleteAllClick:function(){this.$emit("deleteAll",this.result)},jumpThemeDet:function(t){this.$router.push({path:"details/"+t})},jumpPerDet:function(t){this.$router.push({path:"/home-page/"+t})}},beforeRouteLeave:function(t,e,s){}}},CFQY:function(t,e,s){"use strict";s.r(e);var a=s("depY"),i=s("DhNJ");for(var r in i)"default"!==r&&function(t){s.d(e,t,(function(){return i[t]}))}(r);var n=s("KHd+"),o=Object(n.a)(i.default,a.a,a.b,!1,null,null,null);e.default=o.exports},DhNJ:function(t,e,s){"use strict";s.r(e);var a=s("xry+"),i=s.n(a);for(var r in a)"default"!==r&&function(t){s.d(e,t,(function(){return a[t]}))}(r);e.default=i.a},depY:function(t,e,s){"use strict";var a=function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("section",[s("div",[s("van-checkbox-group",{ref:"checkboxGroup",model:{value:t.result,callback:function(e){t.result=e},expression:"result"}},[t._l(t.themeList,(function(e,a){return s("div",{key:a},[s("div",{staticClass:"cirPostCon"},[s("div",{},[s("div",{staticClass:"postTop"},[s("div",{staticClass:"postPer"},[e.postHead?s("img",{staticClass:"postHead",attrs:{src:e.postHead}}):s("img",{staticClass:"postHead",attrs:{src:t.appConfig.staticBaseUrl+"/images/noavatar.gif"}}),t._v(" "),s("div",{staticClass:"perDet"},[e.user?s("div",{staticClass:"perName"},[t._v(t._s(e.user._data.username))]):s("div",{staticClass:"perName"},[t._v("该用户已被删除")]),t._v(" "),s("div",{staticClass:"postTime"},[t._v(t._s(t.$moment(e._data.createdAt).format("YYYY-MM-DD HH:mm")))])])]),t._v(" "),s("div",{staticClass:"postOpera"},[e._data.isSticky?s("span",{directives:[{name:"show",rawName:"v-show",value:t.isTopShow,expression:"isTopShow"}],staticClass:"icon iconfont icon-top"}):t._e(),t._v(" "),e._data.canEssence||e._data.canSticky||e._data.canDelete||e._data.canEdit?s("div",{staticClass:"screen",on:{click:function(e){return t.bindScreen(a)}}},[s("div",{staticClass:"moreCli"},[s("span",{staticClass:"icon iconfont icon-more"})]),t._v(" "),s("div",{directives:[{name:"show",rawName:"v-show",value:t.showScreen[a],expression:"showScreen[index]"}],staticClass:"themeList"},[e.firstPost._data.canLike&&e.firstPost._data.isLiked?s("a",{attrs:{href:"javascript:;"},on:{click:function(s){return t.replyOpera(e.firstPost._data.id,2,e.firstPost._data.isLiked,!1)}}},[t._v("取消点赞")]):t._e(),t._v(" "),e.firstPost._data.canLike&&!e.firstPost._data.isLiked?s("a",{attrs:{href:"javascript:;"},on:{click:function(s){return t.replyOpera(e.firstPost._data.id,2,e.firstPost._data.isLiked,!0)}}},[t._v("点赞")]):t._e(),t._v(" "),e._data.canEssence&&e._data.isEssence?s("a",{attrs:{href:"javascript:;"},on:{click:function(s){return t.themeOpera(e._data.id,2,!1)}}},[t._v("取消加精")]):t._e(),t._v(" "),e._data.canEssence&&!e._data.isEssence?s("a",{attrs:{href:"javascript:;"},on:{click:function(s){return t.themeOpera(e._data.id,2,!0)}}},[t._v("加精")]):t._e(),t._v(" "),e._data.canSticky&&e._data.isSticky?s("a",{attrs:{href:"javascript:;"},on:{click:function(s){return t.themeOpera(e._data.id,3,!1)}}},[t._v("取消置顶")]):t._e(),t._v(" "),e._data.canSticky&&!e._data.isSticky?s("a",{attrs:{href:"javascript:;"},on:{click:function(s){return t.themeOpera(e._data.id,3,!0)}}},[t._v("置顶")]):t._e(),t._v(" "),e._data.canDelete?s("a",{attrs:{href:"javascript:;"},on:{click:function(s){return t.themeOpera(e._data.id,4)}}},[t._v("删除")]):t._e()])]):t._e()])]),t._v(" "),e.firstPost?s("div",{staticClass:"postContent"},[s("a",{domProps:{innerHTML:t._s(e.firstPost._data.contentHtml)},on:{click:function(s){return t.jumpThemeDet(e._data.id)}}})]):t._e(),t._v(" "),e.firstPost.imageList&&e.firstPost.imageList.length>0?s("div",{staticClass:"themeImgBox"},[s("div",{staticClass:"themeImgList moreImg"},t._l(e.firstPost.imageList,(function(t,e){return s("van-image",{staticClass:"themeImgChild",attrs:{fit:"cover",width:"113px",height:"113px","lazy-load":"",src:t}})})),1)]):t._e()]),t._v(" "),s("div",{staticClass:"operaBox"},[e.firstPost.likedUsers.length>0||e.rewardedUsers.length>0?s("div",{staticClass:"isrelationGap"}):t._e(),t._v(" "),e.firstPost.likedUsers.length>0?s("div",{staticClass:"likeBox"},[s("span",{staticClass:"icon iconfont icon-praise-after"}),t._v(" "),t._l(e.firstPost.likedUsers,(function(a){return s("a",{on:{click:function(e){return t.jumpPerDet(a._data.id)}}},[t._v(t._s(t.userArr(e.firstPost.likedUsers)))])})),t._v(" "),e.firstPost._data.likeCount>10?s("i",[t._v(" 等"),s("span",[t._v(t._s(e.firstPost._data.likeCount))]),t._v("个人觉得很赞")]):t._e()],2):t._e(),t._v(" "),e.rewardedUsers.length>0?s("div",{staticClass:"reward"},[s("span",{staticClass:"icon iconfont icon-money"}),t._v(" "),t._l(e.rewardedUsers,(function(a){return s("a",{attrs:{href:"javascript:;"},on:{click:function(e){return t.jumpPerDet(a._data.id)}}},[t._v(t._s(t.userArr(e.rewardedUsers)))])}))],2):t._e(),t._v(" "),e.lastThreePosts.length>0&&e.firstPost.likedUsers.length>0||e.lastThreePosts.length>0&&e.rewardedUsers.length>0?s("div",{staticClass:"isrelationLine"}):t._e(),t._v(" "),e.lastThreePosts.length>0?s("div",{staticClass:"replyBox"},[t._l(e.lastThreePosts,(function(e){return s("div",{staticClass:"replyCon"},[e.user?s("a",{attrs:{href:"javascript:;"}},[t._v(t._s(e.user._data.username))]):s("a",{attrs:{href:"javascript:;"}},[t._v("该用户已被删除")]),t._v(" "),e._data.replyUserId?s("span",{staticClass:"font9"},[t._v("回复")]):t._e(),t._v(" "),e._data.replyUserId&&e.replyUser?s("a",{attrs:{href:"javascript:;"}},[t._v(t._s(e.replyUser._data.username))]):e._data.replyUserId&&!e.replyUser?s("a",{attrs:{href:"javascript:;"}},[t._v("该用户已被删除")]):t._e(),t._v(" "),s("span",{domProps:{innerHTML:t._s(e._data.contentHtml)}})])})),t._v(" "),e._data.postCount>4?s("a",{staticClass:"allReply",on:{click:function(s){return t.jumpThemeDet(e._data.id)}}},[t._v("全部"+t._s(e._data.postCount-1)+"条回复"),s("span",{staticClass:"icon iconfont icon-right-arrow"})]):t._e()],2):t._e()]),t._v(" "),t.ischeckShow?s("van-checkbox",{ref:"checkboxes",refInFor:!0,staticClass:"memberCheck",attrs:{name:e._data.id}}):t._e()],1),t._v(" "),s("div",{staticClass:"gap"})])})),t._v(" "),t.ischeckShow?s("div",{staticClass:"manageFootFixed choFixed"},[s("a",{attrs:{href:"javascript:;"},on:{click:t.checkAll}},[t._v("全选")]),t._v(" "),s("a",{attrs:{href:"javascript:;"},on:{click:t.signOutDele}},[t._v("取消全选")]),t._v(" "),s("button",{staticClass:"checkSubmit",on:{click:t.deleteAllClick}},[t._v("删除选中")])]):t._e()],2)],1),t._v(" "),s("van-image-preview",{attrs:{images:t.priview},on:{change:t.onChange},scopedSlots:t._u([{key:"index",fn:function(){return[t._v("第"+t._s(t.index)+"页")]},proxy:!0}]),model:{value:t.imageShow,callback:function(e){t.imageShow=e},expression:"imageShow"}})],1)},i=[];s.d(e,"a",(function(){return a})),s.d(e,"b",(function(){return i}))},"xry+":function(t,e,s){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var a=r(s("QbLZ")),i=r(s("/Zpk"));function r(t){return t&&t.__esModule?t:{default:t}}s("E2jd"),e.default=(0,a.default)({name:"themeDetView"},i.default)}}]);