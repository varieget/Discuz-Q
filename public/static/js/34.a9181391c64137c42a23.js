(window.webpackJsonp=window.webpackJsonp||[]).push([[34,80],{"+1ub":function(t,e,i){"use strict";Object.defineProperty(e,"__esModule",{value:!0});e.autoTextarea=function(t,e,i,n){e=e||0;var o=!!document.getBoxObjectFor||"mozInnerScreenX"in window,s=!!window.opera&&!!window.opera.toString().indexOf("Opera"),a=function(e,i){t.addEventListener?t.addEventListener(e,i,!1):t.attachEvent("on"+e,i)},c=t.currentStyle?function(e){var i=t.currentStyle[e];if("height"===e&&1!==i.search(/px/i)){var n=t.getBoundingClientRect();return n.bottom-n.top-parseFloat(c("paddingTop"))-parseFloat(c("paddingBottom"))+"px"}return i}:function(e){return getComputedStyle(t,null)[e]},r=parseFloat(c("height"));t.style.resize="none";var l=function(){var a,l,d=0,u=t.style;t._length!==t.value.length&&(t._length=t.value.length,o||s||(d=parseInt(c("paddingTop"))+parseInt(c("paddingBottom"))),a=document.body.scrollTop||document.documentElement.scrollTop,t.style.height=r+"px",t.scrollHeight>r&&(i&&t.scrollHeight>i?(l=i-d,u.overflowY="auto"):(l=t.scrollHeight-d,u.overflowY="hidden"),u.height=l+e+"px",a+=parseInt(u.height)-t.currHeight,document.body.scrollTop=a,document.documentElement.scrollTop=a,t.currHeight=parseInt(u.height),n(parseInt(u.height))))};a("propertychange",l),a("input",l),a("focus",l),l()},e.debounce=function(t,e){var i=void 0;return function(){for(var n=this,o=arguments.length,s=Array(o),a=0;a<o;a++)s[a]=arguments[a];i&&clearTimeout(i),i=setTimeout((function(){t.apply(n,s)}),e||500)}}},"1m/t":function(t,e,i){"use strict";i.r(e);var n=i("9jAL"),o=i.n(n);for(var s in n)"default"!==s&&function(t){i.d(e,t,(function(){return n[t]}))}(s);e.default=o.a},"1nxv":function(t,e,i){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var n=i("+1ub"),o=(s(i("edxw")),s(i("UgcE")),s(i("6NK7")));function s(t){return t&&t.__esModule?t:{default:t}}var a=parseFloat(document.documentElement.style.fontSize);e.default={data:function(){return{headerTitle:"发布主题",selectSort:"选择分类",showPopup:!1,categories:[],categoriesId:[],cateId:"",content:"",showFacePanel:!1,keyboard:!1,keywordsMax:1e3,list:[],footMove:!1,faceData:[],fileList:[],fileListOne:[],uploadShow:!1,enclosureList:[],avatar:"",themeId:"",postsId:"",files:{name:"",type:""},headerImage:null,picValue:null,upImgUrl:"",enclosureShow:!1,isWeixin:!1,isPhone:!1,themeCon:!1,attriAttachment:!1,canUploadImages:"",canUploadAttachments:"",supportImgExt:"",supportFileExt:"",supportFileArr:"",limitMaxLength:!0,limitMaxEncLength:!0,fileListOneLen:"",enclosureListLen:"",isiOS:!1,encuploadShow:!1,testingRes:!1}},mounted:function(){var t=this;this.$nextTick((function(){var e=t.$refs.textarea;e.focus();var i=300;e&&(0,n.autoTextarea)(e,5,0,(function(t){if((t+=20)!==i){i=t}}))})),1!=this.isWeixin&&1!=this.isPhone&&(console.log(this.isWeixin),this.limitWidth())},created:function(){this.isWeixin=o.default.isWeixin().isWeixin,this.isPhone=o.default.isWeixin().isPhone;var t=navigator.userAgent;if(this.isAndroid=t.indexOf("Android")>-1||t.indexOf("Adr")>-1,this.isiOS=!!t.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/),console.log(this.isiOS),this.isiOS&&(this.encuploadShow=!0,console.log(this.encuploadShow)),this.$route.params.themeId){var e=this.$route.params.themeId,i=this.$route.params.postsId,n=this.$route.params.themeContent;this.themeId=e,this.postsId=i,this.content=n}this.loadCategories(),this.detailsLoad(),this.getInfo()},watch:{"fileListOne.length":function(t,e){this.fileListOneLen=t,this.fileListOneLen>=12?this.limitMaxLength=!1:this.limitMaxLength=!0,console.log(this.fileListOneLen+"dddd")},"enclosureList.length":function(t,e){this.enclosureListLen=t,this.enclosureListLen>=3?this.limitMaxEncLength=!1:this.limitMaxEncLength=!0,console.log(this.enclosureListLen+"sssss")}},methods:{getInfo:function(){var t=this;this.appFetch({url:"forum",method:"get",data:{include:["users"]}}).then((function(e){if(e.errors)throw t.$toast.fail(e.errors[0].code),new Error(e.error);console.log(e),console.log("888887");for(var i=e.readdata._data.supportImgExt.split(","),n="",o=0;o<i.length;o++)n="."+i[o]+",",t.supportImgExt+=n;var s=e.readdata._data.supportFileExt.split(","),a="";for(o=0;o<s.length;o++)a="."+s[o]+",",t.supportFileExt+=a;t.canUploadImages=e.readdata._data.canUploadImages,t.canUploadAttachments=e.readdata._data.canUploadAttachments}))},detailsLoad:function(){var t=this;if(this.postsId&&this.content){var e="threads/"+this.themeId;this.appFetch({url:e,method:"get",data:{include:["firstPost","firstPost.images","firstPost.attachments","category"]}}).then((function(e){if(e.errors)throw t.$toast.fail(e.errors[0].code),new Error(e.error);console.log(e),console.log("1234"),console.log(t.cateId);var i=e.readdata.category._data.id;t.selectSort=e.readdata.category._data.description,console.log(t.selectSort),t.cateId!=i&&(t.cateId=i)}))}},publish:function(){var t=this;if(this.postsId&&this.content){console.log("回复");var e="posts/"+this.postsId;this.appFetch({url:e,method:"patch",data:{data:{type:"posts",attributes:{content:this.content}}}}).then((function(e){e.errors?e.errors[0].detail?t.$toast.fail(e.errors[0].code+"\n"+e.errors[0].detail[0]):t.$toast.fail(e.errors[0].code):t.$router.push({path:"details/"+t.themeId})}))}else{this.attriAttachment=this.fileListOne.concat(this.enclosureList);for(var i=0;i<this.attriAttachment.length;i++)this.attriAttachment[i]={type:"attachments",id:this.attriAttachment[i].id};this.appFetch({url:"threads",method:"post",data:{data:{type:"threads",attributes:{content:this.content},relationships:{category:{data:{type:"categories",id:this.cateId}},attachments:{data:this.attriAttachment}}}}}).then((function(e){if(e.errors)throw t.$toast.fail(e.errors[0].code),new Error(e.error);var i=e.readdata._data.id;t.$router.push({path:"details/"+i})}))}},limitWidth:function(){document.getElementById("post-topic-footer").style.width="640px";var t=window.innerWidth;document.getElementById("post-topic-footer").style.marginLeft=(t-640)/2+"px"},deleteEnclosure:function(t,e){this.fileListOne.length<1&&(this.uploadShow=!1),this.appFetch({url:"attachment",method:"delete",splice:"/"+t.id})},deleteEnc:function(t,e){var i=this;this.fileListOne.length<1&&(this.uploadShow=!1),this.appFetch({url:"attachment",method:"delete",splice:"/"+t.id}).then((function(e){var n=i.enclosureList.filter((function(e){return e.id!==t.id}));i.enclosureList=n,console.log(i.enclosureList),console.log("2567")}))},beforeHandleFile:function(){this.canUploadImages?this.limitMaxLength||this.$toast.fail("已达上传图片上限"):this.$toast.fail("没有上传图片的权限")},beforeHandleEnclosure:function(){this.canUploadAttachments?this.limitMaxEncLength||this.$toast.fail("已达上传附件上限"):this.$toast.fail("没有上传附件的权限")},handleFile:function(t){this.isAndroid&&this.isWeixin?(alert("安卓微信内"),this.testingType(t.file,this.supportImgExt),console.log(this.testingRes+"445"),this.testingRes&&this.compressFile(t.file,!1)):(alert("其他设备"),this.compressFile(t.file,!1))},handleFileUp:function(t){this.isAndroid&&this.isWeixin?(alert("安卓微信内"),this.testingType(t.target.files[0],this.supportImgExt),this.testingRes&&this.compressFile(t.target.files[0],!0)):(alert("其他设备"),this.compressFile(t.target.files[0],!0))},handleEnclosure:function(t){if(this.isAndroid&&this.isWeixin){if(alert("安卓微信内"),this.testingType(t.target.files[0],this.supportFileExt),this.testingRes){var e=t.target.files[0],i=new FormData;i.append("file",e),i.append("isGallery",0),this.uploaderEnclosure(i,!1,!1,!0)}}else alert("其他设备"),this.uploaderEnclosure(formdata,!1,!1,!0)},testingType:function(t,e){var i=t.name.substring(t.name.lastIndexOf(".")).toLowerCase();"-1"==e.indexOf(i+",")?(this.$toast.fail("文件格式不正确!"),this.testingRes=!1):this.testingRes=!0},uploaderEnclosure:function(t,e,i,n){var o=this;console.log(t,e,n),this.appFetch({url:"attachment",method:"post",data:t}).then((function(t){if(t.errors)throw o.$toast.fail(t.errors[0].code),new Error(t.error);i&&(console.log(o.fileList),o.fileList.push({url:t.readdata._data.url,id:t.readdata._data.id}),o.fileListOne[o.fileListOne.length-1].id=t.data.attributes.id),e&&(o.fileListOne.push({url:t.readdata._data.url,id:t.readdata._data.id}),o.fileListOne.length>0&&(o.uploadShow=!0)),n&&(o.enclosureShow=!0,o.enclosureList.push({type:t.readdata._data.extension,name:t.readdata._data.fileName,id:t.readdata._data.id})),o.loading=!1}))},compressFile:function(t,e){var i=arguments.length>2&&void 0!==arguments[2]?arguments[2]:15e4,n=(arguments[3],t.size||.8*t.length),o=(Math.max(i/n,.8),this);lrz(t,{quality:.8}).then((function(i){var n=new FormData;n.append("file",i.file,t.name),n.append("isGallery",1),o.uploaderEnclosure(n,e,!e),o.loading=!1})).catch((function(t){})).always((function(){}))},clearKeywords:function(){this.keywords="",this.list=[];var t=this.$refs.textarea,e=40/a;t.style.height=e+"rem",e=60/a,t.focus()},searchChange:(0,n.debounce)((function(){if(this.keywords&&this.keywords.trim())this.keywords;else this.list=[]})),handleFaceChoose:function(t){var e=this.content,i=this.$refs.textarea,n=i.selectionStart,o=i.selectionEnd,s=e.substring(0,n)+t+e.substring(o,e.length);this.content=s,i.setSelectionRange&&setTimeout((function(){var e=n+t.length;i.setSelectionRange(e,e)}),0)},addExpression:function(){var t=this;this.keyboard=!this.keyboard,this.appFetch({url:"emojis",method:"get",data:{include:""}}).then((function(e){t.faceData=e.readdata})),this.showFacePanel=!this.showFacePanel,this.footMove=!this.footMove},backClick:function(){this.$router.go(-1)},dClick:function(){this.showPopup=!0},onConfirm:function(t,e){console.log(t);var i=t.id;this.cateId=i,console.log(this.cateId);t.text;this.showPopup=!1,this.selectSort=t.text},loadCategories:function(){var t=this;this.appFetch({url:"categories",method:"get",data:{include:""}}).then((function(e){if(e.errors)throw t.$toast.fail(e.errors[0].code),new Error(e.error);console.log(e,"res1111");var i;i=e.readdata,console.log(e.readdata);for(var n=0,o=i.length;n<o;n++)t.categories.push({text:i[n]._data.name,id:i[n]._data.id}),t.categoriesId.push(i[n]._data.id)}))},onCancel:function(){this.showPopup=!1}}}},"6GI9":function(t,e,i){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default={data:function(){return{active:0,faceIndex:0}},props:{faceData:{type:Array}},computed:{faces:function(){for(var t=this.faceData,e=(this.faceIndex,t),i=0,n=[];28*i<e.length;)n.push(e.slice(28*i,28*(i+1))),i+=1;return n},scrollWidth:function(){return this.faces.length*document.body.clientWidth},scrollPosition:function(){return this.active*document.body.clientWidth}},mounted:function(){var t=this,e=this.$refs.faceContent,i=0,n=0;e.ontouchstart=function(t){i=t.targetTouches[0].pageX},e.ontouchend=function(e){(n=e.changedTouches[0].pageX)-i>50?0!==t.active&&t.active--:n-i<-50&&t.active!==t.faces.length-1&&t.active++}},created:function(){},methods:{getUrlCode:function(){var t=this;this.code=this.$utils.getUrlKey("code"),alert(code),this.appFetch({url:"weixin",method:"get",data:{code:this.code}}).then((function(t){alert(65756765)}),(function(e){100004==e.errors[0].status&&t.$router.go(-1)}))},loginWxClick:function(){this.$router.push({path:"/wx-login-bd"})},loginPhoneClick:function(){this.$router.push({path:"/login-phone"})},onFaceClick:function(t){this.$emit("onFaceChoose",t)}}}},"9jAL":function(t,e,i){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var n=a(i("QbLZ"));i("E2jd");var o=a(i("1nxv")),s=(i("+1ub"),a(i("SDcr")));function a(t){return t&&t.__esModule?t:{default:t}}e.default=(0,n.default)({name:"post-topic",components:{Expression:s.default}},o.default)},FI70:function(t,e,i){"use strict";i.r(e);var n=i("L7me"),o=i("1m/t");for(var s in o)"default"!==s&&function(t){i.d(e,t,(function(){return o[t]}))}(s);var a=i("KHd+"),c=Object(a.a)(o.default,n.a,n.b,!1,null,null,null);e.default=c.exports},L7me:function(t,e,i){"use strict";var n=function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("div",{staticClass:"post-topic-box"},[i("header",{staticClass:"post-topic-header"},[i("span",{staticClass:"icon iconfont icon-back post-topic-header-icon",on:{click:t.backClick}}),t._v(" "),i("h2",{staticClass:"postHeadTit"},[t._v(t._s(t.headerTitle))]),t._v(" "),i("van-button",{attrs:{type:"primary",size:"mini"},on:{click:t.publish}},[t._v("发布")])],1),t._v(" "),i("div",{staticClass:"post-topic-form"},[i("textarea",{directives:[{name:"model",rawName:"v-model",value:t.content,expression:"content"}],ref:"textarea",staticClass:"reply-box",attrs:{id:"post-topic-form-text",name:"post-topic",placeholder:"请输入内容",maxlength:t.keywordsMax},domProps:{value:t.content},on:{change:t.searchChange,focus:function(e){t.showFacePanel=!1,t.footMove=!1,t.keyboard=!1},input:function(e){e.target.composing||(t.content=e.target.value)}}}),t._v(" "),t.uploadShow&&t.isAndroid&&t.isWeixin?i("div",{staticClass:"uploadBox"},[i("van-uploader",{attrs:{"max-count":12,"after-read":t.handleFile,multiple:""},on:{delete:function(e){return t.deleteEnclosure(e,"img")}},model:{value:t.fileListOne,callback:function(e){t.fileListOne=e},expression:"fileListOne"}})],1):t._e(),t._v(" "),!t.uploadShow||t.isAndroid||t.isWeixin?t._e():i("div",{staticClass:"uploadBox"},[i("van-uploader",{attrs:{"max-count":12,accept:t.supportImgExt,"after-read":t.handleFile,multiple:""},on:{delete:function(e){return t.deleteEnclosure(e,"img")}},model:{value:t.fileListOne,callback:function(e){t.fileListOne=e},expression:"fileListOne"}})],1),t._v(" "),t.enclosureShow?i("div",{staticClass:"enclosure"},t._l(t.enclosureList,(function(e,n){return i("div",{key:n,staticClass:"enclosureChi"},["rar"===e.type?i("span",{staticClass:"icon iconfont icon-rar"}):t._e(),t._v(" "),"zip"===e.type?i("span",{staticClass:"icon iconfont icon-rar"}):"docx"===e.type?i("span",{staticClass:"icon iconfont icon-word"}):"doc"===e.type?i("span",{staticClass:"icon iconfont icon-word"}):"pdf"===e.type?i("span",{staticClass:"icon iconfont icon-pdf"}):"jpg"===e.type?i("span",{staticClass:"icon iconfont icon-jpg"}):"mp"===e.type?i("span",{staticClass:"icon iconfont icon-mp3"}):"mp1"===e.type?i("span",{staticClass:"icon iconfont icon-mp4"}):"png"===e.type?i("span",{staticClass:"icon iconfont icon-PNG"}):"ppt"===e.type?i("span",{staticClass:"icon iconfont icon-ppt"}):"swf"===e.type?i("span",{staticClass:"icon iconfont icon-swf"}):"TIFF"===e.type?i("span",{staticClass:"icon iconfont icon-TIFF"}):"txt"===e.type?i("span",{staticClass:"icon iconfont icon-txt"}):"xls"===e.type?i("span",{staticClass:"icon iconfont icon-xls"}):i("span",{staticClass:"icon iconfont icon-doubt"}),t._v(" "),i("span",{staticClass:"encName"},[t._v(t._s(e.name))]),t._v(" "),i("van-icon",{staticClass:"encDelete",attrs:{name:"clear"},on:{click:function(i){return t.deleteEnc(e,"enclosure")}}})],1)})),0):t._e()]),t._v(" "),i("div",[t._v("调试安卓"+t._s(t.isAndroid)+",调试微信"+t._s(t.isWeixin))]),t._v(" "),i("footer",{staticClass:"post-topic-footer",class:{footMove:t.footMove},attrs:{id:"post-topic-footer"}},[i("div",{staticClass:"post-topic-footer-left",class:{width20:t.encuploadShow}},[i("span",{staticClass:"icon iconfont icon-label post-topic-header-icon",class:{"icon-keyboard":t.keyboard},on:{click:t.addExpression}}),t._v(" "),t.canUploadImages&&t.limitMaxLength?i("span",{staticClass:"icon iconfont icon-picture post-topic-header-icon uploadIcon"},[t.isAndroid&&t.isWeixin?i("input",{staticClass:"hiddenInput",attrs:{type:"file"},on:{change:t.handleFileUp}}):i("input",{staticClass:"hiddenInput",attrs:{type:"file",accept:t.supportImgExt,mutiple:"mutiple",capture:"camera"},on:{change:t.handleFileUp}})]):i("span",{staticClass:"icon iconfont icon-picture post-topic-header-icon uploadIcon",on:{click:t.beforeHandleFile}}),t._v(" "),t.canUploadAttachments&&t.limitMaxEncLength?i("span",{staticClass:"icon iconfont icon-enclosure post-topic-header-icon uploadIcon",class:{hide:t.encuploadShow}},[t.isAndroid&&t.isWeixin?i("input",{staticClass:"hiddenInput",attrs:{type:"file"},on:{change:t.handleEnclosure}}):i("input",{staticClass:"hiddenInput",attrs:{type:"file",accept:t.supportFileExt,capture:"camera"},on:{change:t.handleEnclosure}})]):i("span",{staticClass:"icon iconfont icon-enclosure post-topic-header-icon uploadIcon",class:{hide:t.encuploadShow},on:{click:t.beforeHandleEnclosure}})]),t._v(" "),i("div",{staticClass:"post-topic-footer-right",on:{click:t.dClick}},[i("span",{staticClass:"post-topic-footer-right-sort"},[t._v(t._s(t.selectSort))]),t._v(" "),i("span",{staticClass:"icon iconfont icon-down-menu post-topic-header-icon",staticStyle:{color:"#888888"}})])]),t._v(" "),t.showFacePanel?i("Expression",{staticClass:"expressionBox",attrs:{faceData:t.faceData},on:{onFaceChoose:t.handleFaceChoose}}):t._e(),t._v(" "),i("div",{staticClass:"popup"},[i("van-popup",{style:{height:"50%"},attrs:{position:"bottom",round:""},model:{value:t.showPopup,callback:function(e){t.showPopup=e},expression:"showPopup"}},[i("van-picker",{attrs:{columns:t.categories,"show-toolbar":"",title:"选择分类"},on:{cancel:t.onCancel,confirm:t.onConfirm}})],1)],1)],1)},o=[];i.d(e,"a",(function(){return n})),i.d(e,"b",(function(){return o}))},SDcr:function(t,e,i){"use strict";i.r(e);var n=i("j2JY"),o=i("uwTP");for(var s in o)"default"!==s&&function(t){i.d(e,t,(function(){return o[t]}))}(s);var a=i("KHd+"),c=Object(a.a)(o.default,n.a,n.b,!1,null,null,null);e.default=c.exports},j2JY:function(t,e,i){"use strict";var n=function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("div",{staticClass:"face-container"},[i("div",{staticClass:"scroll-wrapper"},[i("div",{ref:"faceContent",staticClass:"face-content",style:{width:t.scrollWidth+"px",marginLeft:-t.scrollPosition+"px"},on:{touchmove:function(t){t.preventDefault()}}},t._l(t.faces,(function(e,n){return i("div",{key:n,staticClass:"face-page"},t._l(e,(function(e,n){return i("a",{key:n},[i("img",{staticClass:"emoji",attrs:{src:e._data.url},on:{click:function(i){return t.onFaceClick(" "+e._data.code+" ")}}})])})),0)})),0),t._v(" "),i("div",{staticClass:"page-dot"},t._l(t.faces.length,(function(e){return i("div",{key:e,staticClass:"dot-item",class:e===t.active+1?"active":"",on:{click:function(i){t.active=e-1}}})})),0)])])},o=[];i.d(e,"a",(function(){return n})),i.d(e,"b",(function(){return o}))},uwTP:function(t,e,i){"use strict";i.r(e);var n=i("yaIx"),o=i.n(n);for(var s in n)"default"!==s&&function(t){i.d(e,t,(function(){return n[t]}))}(s);e.default=o.a},yaIx:function(t,e,i){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var n=s(i("QbLZ")),o=s(i("6GI9"));function s(t){return t&&t.__esModule?t:{default:t}}i("E2jd"),e.default=(0,n.default)({name:"expressionView"},o.default)}}]);