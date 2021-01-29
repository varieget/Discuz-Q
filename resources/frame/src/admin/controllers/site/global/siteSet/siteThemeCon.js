export default {
  data() {
    return {
      selectedTheme: 1, // 选中主题 1蓝色 2红色
      newTheme: 1, // 当前绑定主题值
      currentUrl: '', // 预览图地址
      isPreview: false,
      dialogVisible: false
    }
  },
  mounted() {
    this.getThemeSelect();
  },
  methods: {
    showPreview(e) {
      this.currentUrl = e.target.getAttribute('src');
      this.isPreview = true;
    },
    closePreview() {
      this.isPreview = false;
      this.currentUrl = '';
    },

    // 请求
    getThemeSelect() {
      this.appFetch({
        url: 'forum',
        methods: 'get'
      }).then(res => {
        if (res.errors) {
          this.$message.error(res.errors[0].code);
        } else {
          this.selectedTheme = res.data.attributes.set_site.site_skin;
          this.newTheme = res.data.attributes.set_site.site_skin;
        }
      }).catch(error => {
      });
    },
    submitThemeSelect() {
      let str = '';
      if (this.selectedTheme === 1 && this.newTheme === 2) {
        str = '您确定要切换红色三栏版本吗？';
      } else if (this.selectedTheme === 2 && this.newTheme === 1) {
        str = '蓝色两栏版本功能升级中，现在切换回蓝色两栏版本时，将会暂时丢失红色三栏版本下的红包、悬赏贴哟，您确定要切换吗？';
      } else {
        str = '相同的提交可能不生效哦。'
      }
      const res = this.$confirm(str, '确认信息', {
        confirmButtonText: '确认',
        cancelButtonText: '取消',
        type: 'success'
      })
        .then(() => {
          this.postThemeSelect();
        })
        .catch(action => {
          this.$message({
            type: 'info',
            message: '取消成功'
          })
        });
    },
    postThemeSelect() {
      this.appFetch({
        url: 'switchskin',
        method: 'post',
        data: {
          data: {
            attributes: {
              skin: this.newTheme
            }
          }
        }
      })
      .then(res => {
        this.handleResult(res);
      })
    },
    handleResult(res) {
      if (res.errors && res.errors[0].status === '500') {
        return this.$message.warning(res.rawData[0].code);
      }
      if (res.data && res.data.attributes.code === 200){
        this.$message.success(res.data.attributes.message);
        this.selectedTheme = res.data.attributes.site_skin;
        this.newTheme = res.data.attributes.site_skin;
      } else {
        this.$confirm(res, '提示信息', {
          dangerouslyUseHTMLString: true,
          confirmButtonText: '重试',
          cancelButtonText: '取消',
          type: 'info'
        })
          .then(() => {
            this.postThemeSelect();
          })
          .catch(action => {
            this.$message({
              type: 'info',
              message: '取消成功'
            })
          });
      }
    }
  }
}