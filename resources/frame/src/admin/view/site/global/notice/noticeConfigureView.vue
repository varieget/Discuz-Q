<template>
  <div>
    <Card :header="query.typeName"></Card>
    <Card header="通知方式：" class="card-radio-con">
      <CardRow description="若没勾选，则下面不显示对应的方式。若不能支持，则置灰不能勾选 。 ">
      <el-checkbox-group v-model="noticeList" @change="noticeListChange">
        <el-checkbox label=0>系统通知</el-checkbox>
        <!-- <el-checkbox label="noticeType">小程序通知</el-checkbox> -->
        <el-checkbox label=1>微信模板通知</el-checkbox>
        <!-- <el-checkbox label="noticeType">短信通知</el-checkbox> -->
      </el-checkbox-group>
      </CardRow>
    </Card>
    <!-- 系统通知 -->
    <div class="system-notice" v-show="showSystem">
      <p class="system-title">系统通知</p>
    <Card header="用户角色通知标题：">
      <CardRow description="系统发送的欢迎信息的标题，不支持HTML，不超过75字节">
        <el-input type="text" maxlength="75" v-model="systemList.title" ></el-input>
      </CardRow>
    </Card>

    <Card header="通知内容：">
      <CardRow row :description="systemDes">
        <el-input type="textarea" :autosize="{ minRows: 5, maxRows: 5}" v-model="systemList.content" clearable></el-input>
      </CardRow>
    </Card>
  </div>

  <!-- 微信模板信息 -->
    <div class="system-notice" v-show="showWx">
      <p class="system-title">微信模板信息</p>
    <Card header="模板ID：">
      <CardRow description="请填写模板消息的ID">
        <el-input type="text" maxlength="75" v-model="wxList.template_id" ></el-input>
      </CardRow>
    </Card>

    <Card header="">
    <div class="applets-box">
      <div class="applets-box-content">
      <CardRow row :description="wxDes">
      <div class="applets">
        <span class="applets-titles">first：</span>
        <el-input type="input" v-model="wxList.first_data" style="width:352px" class="applets-input"></el-input>
      </div>
      <div v-for="(item,index) in appletsList" :key="index" class="applets">
        <span class="applets-title">{{`keyword${index}`}}:</span>
        <el-input type="input" v-model="appletsList[index]" style="width:330px" class="applets-input"></el-input>
        <span class="iconfont iconicon_delect iconhuishouzhan" @click="delectClick(index)" v-show="appletsList.length>2"></span>
      </div>
      <div class="applets">
      <span class="applets-titles"></span>
      <TableContAdd
        @tableContAddClick="tableContAdd"
        cont="添加关键字"
      ></TableContAdd>
      </div>
      <div class="applets">
        <span class="applets-titles">remark：</span>
        <el-input type="input" v-model="wxList.remark_data" style="width:352px" class="applets-input"></el-input>
      </div>
      <div class="applets">
        <span class="applets-titles">跳转：</span>
        <el-radio v-model="wxList.redirect_type" :label="0">无跳转</el-radio>
        <el-radio v-model="wxList.redirect_type" :label="2">跳转至小程序</el-radio>
        <el-radio v-model="wxList.redirect_type" :label="1">跳转至H5</el-radio>
      </div>
      <div class="applets">
        <span class="applets-titles">H5网址：</span>
        <el-input type="input" v-model="wxList.redirect_url" style="width:352px" class="applets-input"></el-input>
      </div>
      </CardRow>
      </div>
    </div>
    </Card>
  </div>

    <Card class="footer-btn">
      <el-button type="primary" size="medium" @click="Submission">提交</el-button>
    </Card>
  </div>
</template>

<script>
import "../../../../scss/site/module/globalStyle.scss";
import noticeConfigureCon from "../../../../controllers/site/global/notice/noticeConfigureCon";

export default {
  name: "notice-configure-view",
  ...noticeConfigureCon
};
</script>
