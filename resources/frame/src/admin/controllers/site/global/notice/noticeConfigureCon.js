/*
* 通知设置配置控制器
* */

import Card from '../../../../view/site/common/card/card';
import CardRow from '../../../../view/site/common/card/cardRow';
import TableContAdd from '../../../../view/site/common/table/tableContAdd';

export default {
    data: function () {
      return {
        noticeTitle: '',      //用户角色通知标题
        noticeContent: '',    //用户通知内容
        query: '',            //获取当前用户的ID
        typeName: '',         //获取当前typename
        systemTitle: '',      //系统通知title
        systemId: '',         //系统通知ID
        systemDes: '',        //系统提示
        systemContent: '',    //系统通知内容
        template_id: '',      //系统通知模版ID
        wxTitle: '',          //微信通知title
        wxfrist: '',          //微信模版
        wxId: '',             //微信ID
        template_wxid: '',    //微信模版ID
        remark: '',           //微信remark
        redirect_type: 0,     //微信通知跳转 0 无跳转 1 h5 2 小程序
        redirect_url: '',     //h5网址
        keywords_data: [],    //keywords
        noticeStatus: 0,      //系统状态
        showSystem: false,    //系统显示
        showWx: false,        //微信显示
        wxNoticeCon:'',       //微信配置ID
        noticeList: [],       // 通知方式
        noticeType: 0,
        appletsList: [],
      }
    },
    components: {
      Card,
      CardRow,
      TableContAdd
    },
    created() {
      this.query = this.$route.query;
      this.typeName = this.$route.query.typeName;

      this.noticeConfigure();
      // this.getNoticeList();
    },
    methods: {
      // 点击添加关键字
      tableContAdd() {
        console.log('添加')
        this.appletsList.push('')
      },
      // 点击删除图标
      delectClick(index) {
        this.appletsList.splice(index,1);
      },
      // 通知方式切换
      noticeListChange(data) {
        if (data.indexOf('0') === -1) {
          this.showSystem = false;
        } else {
          this.showSystem = true;
        }
        if (data.indexOf('1') === -1) {
          this.showWx = false;
        } else {
          this.showWx = true;
        }
        console.log(data.indexOf('0'), 'shshhhsh')
      },
      // 初始化配置列表信息
      noticeConfigure() {
        this.appFetch({
          url: 'noticeDetail',
          method: 'get',
          splice: `?type_name=${this.typeName}`,
          data: {}
        }).then(res => {
          console.log(res, 'ressshhshhs')
          if (res.readdata[0]) {
            this.systemContent = res.readdata[0]._data.content;
            this.systemTitle = res.readdata[0]._data.title;
            this.systemDes = res.readdata[0]._data.vars;
            this.systemId = res.readdata[0]._data.tpl_id;
            this.template_id = res.readdata[0]._data.template_id;
            if (res.readdata[0]._data.status) {
              this.noticeList.push("0");
              if (this.noticeList.indexOf(0) === -1) {
                this.showSystem = true
              } else {
                this.showSystem = false
              }
            }
          }
          if (res.readdata[1]) {
            this.wxTitle = res.readdata[1]._data.title;
            this.wxfrist = res.readdata[1]._data.first_data;
            this.remark = res.readdata[1]._data.remark_data;
            this.wxId = res.readdata[1]._data.tpl_id;
            this.redirect_type = res.readdata[1]._data.redirect_type;
            this.redirect_url = res.readdata[1]._data.redirect_url;
            this.keywords_data = res.readdata[1]._data.keywords_data;
            this.template_wxid = res.readdata[1]._data.template_id;
            this.appletsList = [];
            this.keywords_data.forEach((item, index) => {
              this.appletsList.push(item)
            })
          }

          if (res.readdata[1]._data.status) {
            this.noticeList.push("1");
            if(this.noticeList.indexOf(1) === -1) {
              this.showWx = true;
            } else {
              this.showWx = false;
            }
          }
        })
      },
      // 提交按钮
      Submission() {
        let data = [];

        if (this.showSystem === true){
          data.push({
            'attributes':{
              "id": this.systemId,
              "status": 1,
              "template_id": this.template_id,
              "title": this.systemTitle,
              "content": this.systemContent
            }
          });
        }
        if (this.showWx === true){
          data.push({
            'attributes':{
              "id": this.wxId,
              "status": 1,
              "template_id": this.template_wxid,
              "first_data": this.wxfrist,
              "keywords_data": this.appletsList,
              "remark_data": this.remark,
              "redirect_type": this.redirect_type,
              "redirect_url": this.redirect_url
            }
          });
        }

        this.appFetch({
          url: 'noticeList',
          method: 'patch',
          data: {
            "data": data,
          }
      }).then(res=>{
        if (res.errors) {
            this.$message.error(res.errors[0].code + '\n' + res.errors[0].detail[0]);
          } else {
            this.$message({
              message: '提交成功',
              type: 'success'
          });
          this.noticeConfigure();
        }
      })
      }
    }
}
