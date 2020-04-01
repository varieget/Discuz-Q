
import Card from '../../../../view/site/common/card/card';
import CardRow from '../../../../view/site/common/card/cardRow';

export default {
  data: function () {
    return {
      loginStatus: 'default',   //default h5 applets pc
      appId: '',
      appSecret: '',
      type: '',
      prefix: '',
      typeCopywriting: {
        wx_offiaccount: {
          title: '公众号接口配置',
          appIdDescription: '填写申请公众号后，你获得的APPID ',
          appSecretDescription: '填写申请公众号后，你获得的App secret',
          serverUrl:'服务器地址URL',
          appToken:'随便填写或随机生成',
          encodingAESKey:'随便填写或随机生成',
          url: 'https://mp.weixin.qq.com/',
        },
        wx_miniprogram: {
          title: '小程序微信授权登录设置',
          appIdDescription: '填写申请小程序后，你获得的APPID ',
          appSecretDescription: '填写申请小程序后，你获得的App secret',
          url: 'https://mp.weixin.qq.com/',
        },
        wx_oplatform: {
          title: 'PC端微信扫码登录',
          appIdDescription: '填写申请PC端微信扫码后，你获得的APPID ',
          appSecretDescription: '填写申请PC端微信扫码后，你获得的App secret',
          url: 'https://open.weixin.qq.com/',
        }
      },
      serverUrl:'',             //服务器URL
      appToken:'',              //令牌
      encodingAESKey:'',        //消息加解密密匙
    }
  },
  created() {
    var type = this.$route.query.type;
    this.type = type;
    this.loadStatus();
  },
  methods: {
    loadStatus() {
      this.appFetch({
        url: 'forum',
        method: 'get',
        data: {}
      }).then(data => {
        if (data.errors) {
          this.$message.error(data.errors[0].code);
        } else {
          // 获取对应值渲染
          this.getPrefix(this.type, data);
        }
      }).catch(error => {
      })
    },
    submitConfiguration() {
      let data = [];

      data = [
        {
          "attributes": {
            "key": this.prefix + "app_id",
            "value": this.appId,
            "tag": this.type
          }
        },
        {
          "attributes": {
            "key": this.prefix + "app_secret",
            "value": this.appSecret,
            "tag": this.type
          }
        }
      ];

      if (this.type === 'wx_offiaccount'){
        data.push(
          {
            "attributes": {
              "key": "oplatform_url",
              "value": this.serverUrl,
              "tag": 'wx_oplatform'
            }
          },
          {
            "attributes": {
              "key": "oplatform_app_token",
              "value": this.appToken,
              "tag": 'wx_oplatform'
            }
          },
          {
            "attributes": {
              "key": "oplatform_app_aes_key",
              "value": this.encodingAESKey,
              "tag": 'wx_oplatform'
            }
          }
        )
      }

      this.appFetch({
        url: 'settings',
        method: 'post',
        data: {
          "data": data
        }
      }).then(data => {
        if (data.errors) {
          this.$message.error(data.errors[0].code);
        } else {
          // this.$router.push({
          //   path: '/admin/worth-mentioning-set'
          // });
          this.$message({
            message: '提交成功',
            type: 'success'
          });
        }
      })
    },
    getPrefix(type, data) {    // 传参
      switch (type) {
        case 'wx_offiaccount':
          this.prefix = 'offiaccount_';
          this.appId = data.readdata._data.passport.offiaccount_app_id;
          this.appSecret = data.readdata._data.passport.offiaccount_app_secret;
          this.serverUrl = data.readdata._data.passport.oplatform_url;
          this.appToken = data.readdata._data.passport.oplatform_app_token;
          this.encodingAESKey = data.readdata._data.passport.oplatform_app_aes_key;
          break;
        case 'wx_miniprogram':
          this.prefix = 'miniprogram_';
          this.appId = data.readdata._data.passport.miniprogram_app_id;
          this.appSecret = data.readdata._data.passport.miniprogram_app_secret;
          break;
        case 'wx_oplatform':
          this.prefix = 'oplatform_';
          this.appId = data.readdata._data.passport.oplatform_app_id;
          this.appSecret = data.readdata._data.passport.oplatform_app_secret;
          break;
      }
    },
    randomClick(type){
      if (type === 'token'){
        this.appToken = Math.random(Date.parse(new Date())).toString(35).substr(2);
      } else if (type === 'aes'){
        let aeskey = '';

        for (let i = 0; i<5 ; i++){
          aeskey += Math.random(Date.parse(new Date())).toString(35).substr(2);
        }

        this.encodingAESKey = aeskey.slice(0, 43)
      }
    },
  },
  components: {
    Card,
    CardRow
  }
}
