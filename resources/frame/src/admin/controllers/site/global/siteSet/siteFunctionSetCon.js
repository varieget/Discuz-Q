import Card from "../../../../view/site/common/card/card";
import CardRow from "../../../../view/site/common/card/cardRow";

export default {
  data(){
    return {
      purchase:false, // 购买权限
      // 发布功能
      publishing:{
        text:false,
        post:false,
        picture:false,
        video:false,
        voice:false,
        goods:false,
        question:false
      }
    }
  },
  methods:{
    // 加载功能权限
    loadFunctionStatus() {
      this.appFetch({
        url: "forum",
        method: "get",
        data: {}
      })
        .then(data => {
          if (data.errors) {
            this.$message.error(data.errors[0].code);
          } else {
            // 购买权限
            this.purchase = data.readdata._data.set_site.site_pay_group_close === '1';

            // 发布功能
            this.publishing.text = data.readdata._data.set_site.site_create_thread0===1;
            this.publishing.post = data.readdata._data.set_site.site_create_thread1===1;
            this.publishing.video = data.readdata._data.set_site.site_create_thread2===1;
            this.publishing.picture = data.readdata._data.set_site.site_create_thread3===1;
            this.publishing.voice = data.readdata._data.set_site.site_create_thread4===1;
            this.publishing.question = data.readdata._data.set_site.site_create_thread5===1;
            this.publishing.goods = data.readdata._data.set_site.site_create_thread6===1;
          }
        })
        .catch(error => {});
    },
    // 提交功能状态更改
    handlePublishingSubmit(){
      this.appFetch({
        url: "settings",
        method: "post",
        data: {
          data: [
            {
              attributes: {
                key: "site_pay_group_close",
                value: this.purchase,
                tag: "default"
              }
            },
            {
              attributes: {
                key: "site_create_thread0",
                value: this.publishing.text ?1:0,
                tag: "default"
              }
            },
            {
              attributes: {
                key: "site_create_thread1",
                value: this.publishing.post?1:0,
                tag: "default"
              }
            },
            {
              attributes: {
                key: "site_create_thread2",
                value: this.publishing.video?1:0,
                tag: "default"
              }
            },
            {
              attributes: {
                key: "site_create_thread3",
                value: this.publishing.picture?1:0,
                tag: "default"
              }
            },
            {
              attributes: {
                key: "site_create_thread4",
                value: this.publishing.voice?1:0,
                tag: "default"
              }
            },
            {
              attributes: {
                key: "site_create_thread5",
                value: this.publishing.question?1:0,
                tag: "default"
              }
            },
            {
              attributes: {
                key: "site_create_thread6",
                value: this.publishing.goods?1:0,
                tag: "default"
              }
            }
          ]
        }
      })
        .then(data => {
          if (data.errors) {
            if (data.errors[0].detail) {
              this.$message.error(
                data.errors[0].code + "\n" + data.errors[0].detail[0]
              );
            } else {
              this.$message.error(data.errors[0].code);
            }
          } else {
            this.$message({
              message: "提交成功",
              type: "success"
            });
          }
        })
        .catch(error => {});
    },
  },
  created(){
    this.loadFunctionStatus()
  },
  components:{
    Card,
    CardRow
  }
}