<template>
  <div class="site-sort-set-box" >
    <div v-show="isShow">
      <!-- 添加内容 -->
      <p class="sort-desc">添加内容，在首页显示：</p>

      <div class="sort-switch-box">
        <div class="sort-switch">
          <el-switch v-model="siteOpenSort" active-color="#336699" inactive-color="#bbbbbb">
          </el-switch>
        </div>
        <p class="sort-switch-desc">说明：<br/>开启配置后，添加内容在首页显示，是取得下列各维度的并集；排除内容不在首页显示，是在并集中将排除内容抽离。<br/>未点击开启时，会暂存该页面的配置内容；点击开启时，智能排序将生效，但未配置任何内容，则是 显示出全部内容；<br/>开启后，用户可选择按最新排序进行；最新排序是首先是划分版块，然后根据主题的已读未读<br/>（未读的排在前面，已读的排在后面），最后根据时间倒序进行排序</p>
      </div>

      <div v-show="siteOpenSort">

        <el-card class="box-card" shadow="never">
          <div slot="header" class="clearfix">
            <span>按分类添加：</span>
          </div>
          <div class="sort-class-item" v-for="(item,index) in categoriesList" :key="item.id">
            <el-checkbox v-model="item.checked" @change="handleCheckChange(index)">{{item.name}}</el-checkbox>
            <el-select v-model="item.checkedList" 
              multiple
              collapse-tags
              placeholder="请选择"
              :disabled="!item.checked"
              @change="handleChange(index)">
              <el-option label="全部" :value="item.id"></el-option>
              <el-option
                v-for="child in item.childrens"
                :key="child.id"
                :label="child.name"
                :value="child.id"
                :disabled="item.checkAll">
              </el-option>
            </el-select>
          </div>
        </el-card>

        <Card header="按用户角色添加："></Card>
        <div v-for="item in groupsList" :key="item.id">
          <Card>
            <CardRow>
              <el-checkbox v-model="item.checked">{{item.name}}</el-checkbox>
            </CardRow>
          </Card>
        </div>

        <Card class="sort-border-bottom">
          <CardRow >
            <div class="sort-item-wrap">
              <span>按用户添加：</span>
              <el-select 
                ref="addUser"
                v-model="sortData.usersList" 
                multiple 
                collapse-tags 
                @visible-change="isShowSelectOptions">
                <el-option
                  v-for="item in sortData.usersList"
                  :key="item.id"
                  :label="item.name"
                  :value="item">
                </el-option>
              </el-select>
            </div>
            <span class="sort-select-btn" @click="toSearch('usersList','用户')">选择用户</span>
          </CardRow>
        </Card>

        <Card class="sort-border-bottom">
          <CardRow>
            <div class="sort-item-wrap">
              <span>按话题添加：</span>
              <el-select 
                ref="addTopic"
                v-model="sortData.topicsList" 
                multiple 
                collapse-tags 
                @visible-change="isShowSelectOptions">
                <el-option
                  v-for="item in sortData.topicsList"
                  :key="item.id"
                  :label="item.name"
                  :value="item">
                </el-option>
              </el-select>
            </div>
            <span class="sort-select-btn" @click="toSearch('topicsList','话题')">选择话题</span>
          </CardRow>
        </Card>

        <Card class="sort-border-bottom">
          <CardRow description="id如有多个请用,隔开，例如：12,54,35帖子id在具体的帖子中的地址后缀展示">
            <div class="sort-item-wrap">
              <span>按主题添加：</span>
              <el-input v-model="threads"></el-input>
            </div>
          </CardRow>
        </Card>

        <!-- 排除内容 -->
        <p class="sort-desc">排除内容，不在首页显示：</p>

        <Card class="sort-border-bottom">
          <CardRow >
            <div class="sort-item-wrap">
              <span>按用户角色排除：</span>
              <el-select 
                ref="excludeUser"
                v-model="sortData.blockUsersList" 
                multiple 
                collapse-tags 
                @visible-change="isShowSelectOptions">
                <el-option
                  v-for="item in sortData.blockUsersList"
                  :key="item.id"
                  :label="item.name"
                  :value="item">
                </el-option>
              </el-select>
            </div>
            <span class="sort-select-btn" @click="toSearch('blockUsersList','用户')">选择用户</span>
          </CardRow>
        </Card>

        <Card class="sort-border-bottom">
          <CardRow>
            <div class="sort-item-wrap">
              <span>按话题排除：</span>
              <el-select 
                ref="excludeTopic"
                v-model="sortData.blockTopicsList" 
                multiple 
                collapse-tags 
                @visible-change="isShowSelectOptions">
                <el-option
                  v-for="item in sortData.blockTopicsList"
                  :key="item.id"
                  :label="item.name"
                  :value="item">
                </el-option>
              </el-select>
            </div>
            <span class="sort-select-btn" @click="toSearch('blockTopicsList','话题')">选择话题</span>
          </CardRow>
        </Card>

        <Card class="sort-border-bottom">
          <CardRow description="id如有多个请用,隔开，例如：12,54,35帖子id在具体的帖子中的地址后缀展示">
            <div class="sort-item-wrap">
              <span>按主题排除：</span>
              <el-input v-model="blockThreads"></el-input>
            </div>
          </CardRow>
        </Card>
      </div>

      <!-- 提交按钮-->
      <el-button type="primary" size="medium" @click="handleSortSubmit">提交</el-button>
    </div>

    <!-- 搜索选择界面 -->
    <div class="select-box" v-show="!isShow">
      <p class="sort-desc">选择{{searchText}}</p>
      <div class="selected-user">
        <el-tag 
          v-for="item in selectedData"
          :key="item.id"
          closable
          type="info"
          @close="handleClose(item)"
        >{{item.name}}</el-tag>
      </div>

      <p class="sort-desc">请输入{{searchText === '用户' ? '用户' : '关键字'}}，搜索{{searchText}}</p>
      <el-autocomplete
        v-model="state"
        :fetch-suggestions="querySearchAsync"
        placeholder="请输入..."
        @select="handleSelect"
      ></el-autocomplete>

      <p class="sort-desc">推荐{{searchText}}</p>
       <div class="selected-user">
        <el-tag 
          class="recommend-tag"
          v-for="item in recommendData"
          :key="item.id"
          type="info"
          @click="selectRecommend(item)"
        >{{item.name}}</el-tag>
      </div>

      <el-button type="primary" size="medium" @click="backSortPage">返回</el-button>
    </div>

  </div>
</template>

<script>
  import "../../../../scss/site/module/globalStyle.scss";
  import siteFunctionSetCon from "../../../../controllers/site/global/siteSet/siteSortSetCon";
  export default {
    name: "site-sort-set-view",
    ...siteFunctionSetCon
  };
</script>