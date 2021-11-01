#### 1. 数据导入命令参数说明：

```
topic    关键词
number   导入数量
cookie   平台登录态 
auto     是否自动导入，1是，0否
type     自动导入类型，1以年为循环自动导入，2以月为循环，3以周为循环，4以日为循环
interval 循环间隔，1、2、3···
month    月
week     周
day      天
hour     时
minute   分
url      爬取网站的地址(目前仅Discuz! X网站内容爬取在使用)
port     端口
userAgent   http请求头之一，伪造浏览器请求
articleUrl  微信公众号文章链接
```

##### 1.1 cookie平台登录态获取

登录平台->随意浏览页面->按键F12->点击“network”->点击“Fetch/XHR”->任意点击左侧某一请求->查看“Headers”->获取cookie

![](https://discuz.chat/assets/import_data_cookie_example.png)

##### 1.2 userAgent 获取

登录平台->随意浏览页面->按键F12->点击“network”->点击“Fetch/XHR”->任意点击左侧某一请求->查看“Headers”->获取User-Agent

![](https://discuz.chat/assets/import_data_ua_example.png)

##### 1.3 articleUrl公众号文章链接获取

点击某一篇公众号文章->进入文章详情->点击右上角“···”->再“复制链接”

![](https://discuz.chat/assets/import_data_wx_example.png)

#### 2. 数据导入命令示例：

##### 2.1 微博

即时导入：topic，number为必传参数。
设置自动导入：topic，number，auto，type，interval为必传参数，其他参数根据自己设计的时间分别取用。
		`**以天为自动导入周期**`：topic，number，auto，type，interval，hour为必传。如需具体到分钟，再传minute。
		`**以周为自动导入周期**`：topic，number，auto，type，interval，week，hour为必传。如需具体到分钟，再传minute。
		`**以月为自动导入周期**`：topic，number，auto，type，interval，day，hour为必传。如需具体到分钟，再传minute。
		`**以年为自动导入周期**`：topic，number，auto，type，interval，month，day，hour为必传。如需具体到分钟，再传minute。

```php
(1). 即时导入：php disco importData:insertWeiBoData --topic=xxx --number=5
(2). 设置自动导入：
  php disco importData:insertWeiBoData --topic=xxx --number=5 --auto=1 --type=4 --interval=1 --hour=10 --minute=15 # 每一天10:15自动导入
   
  php disco importData:insertWeiBoData --topic=xxx --number=5 --auto=1 --type=4 --interval=2 --hour=10 --minute=15 # 每2天10:15自动导入
  
  php disco importData:insertWeiBoData --topic=xxx --number=5 --auto=1 --type=3 --interval=1 --week=1 --hour=10 # 每周一10:00自动导入
  
  php disco importData:insertWeiBoData --topic=xxx --number=5 --auto=1 --type=3 --interval=2 --week=3 --hour=10 # 每2周的周三10:00自动导入
  
  php disco importData:insertWeiBoData --topic=xxx --number=5 --auto=1 --type=2 --interval=1 --day=3 --hour=10 # 每月3号10:00自动导入
  
  php disco importData:insertWeiBoData --topic=xxx --number=5 --auto=1 --type=2 --interval=2 --day=3 --hour=10 # 每2月的3号10:00自动导入
  
  php disco importData:insertWeiBoData --topic=xxx --number=5 --auto=1 --type=1 --interval=1 --month=11 --day=11 --hour=10 # 每年11月11号10:00自动导入
  
  php disco importData:insertWeiBoData --topic=xxx --number=5 --auto=1 --type=1 --interval=2 --month=12 --day=12 --hour=10 # 每2年的12月12号10:00自动导入
```

其他平台设置自动导入的格式·基本同“微博”一致，以下不再赘述。

##### 2.2 贴吧

贴吧的防爬机制，导致请求多次后跳转贴吧图片验证，无法继续抓取内容，数据导入将会中断。
即时导入：topic，number，cookie为必传参数。
设置自动导入：topic，number，cookie，auto，type，interval为必传参数，其他参数根据自己设计的时间分别取用。

```php
(1). 即时导入：php disco importData:insertTieBaData --topic=xxx --number=5 --cookie='登录态'
(2). 设置自动导入：参考2.1微博“设置自动导入”命令。另，设置自动导入需考虑cookie的有效性，如cookie失效，将无法执行自动导入。
```

##### 2.3 豆瓣

如命令行不带cookie登录态参数，请求多次后将会跳转登录，无法继续抓取内容。
即时导入：topic，number，cookie为必传参数。
设置自动导入：topic，number，cookie，auto，type，interval为必传参数，其他参数根据自己设计的时间分别取用。

```php
(1). 即时导入：php disco importData:insertDouBanData --topic=xxx --number=5 --cookie='登录态'
(2). 设置自动导入：参考2.1微博“设置自动导入”命令。另，设置自动导入需考虑cookie的有效性，如cookie失效，将无法执行自动导入。
```

##### 2.4 知识星球

即时导入：topic，number，cookie，userAgent为必传参数。
设置自动导入：topic，number，cookie，userAgent，auto，type，interval为必传参数，其他参数根据自己设计的时间分别取用。

```php
(1). 即时导入：php disco importData:insertLearnStarData --topic=xxx --number=5 --cookie='登录态' --userAgent='模拟浏览器user agent，举例谷歌浏览器(Mac)请求：Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36'
(2). 设置自动导入：参考2.1微博“设置自动导入”命令。另，设置自动导入需考虑cookie的有效性，如cookie失效，将无法执行自动导入。
```

##### 2.5 Discuz! X

Discuz! X内容抓取基于官方版本v3.4-20210926(UTF-8)，不适用于二开站点和安装了多样化插件的站点。
即时导入：topic，number，url，cookie为必传参数。
设置自动导入：topic，number，url，cookie，auto，type，interval为必传参数，其他参数根据自己设计的时间分别取用。

```php
(1). 即时导入：
php disco importData:insertDiscuzData --topic=xxx --number=30 --url="网站地址" --cookie="登录态" # 80端口站点内容爬取

php disco importData:insertDiscuzData --topic=xxx --number=30 --url="网站地址" --port=30001 --cookie="登录态" # 特殊端口(如30001)站点内容爬取

(2). 设置自动导入：参考2.1微博“设置自动导入”命令。另，设置自动导入需考虑cookie的有效性，如cookie失效，将无法执行自动导入。
```

##### 2.6 微信公众号文章

公众号文章只能以文章链接逐篇导入，不支持自动导入命令，只支持即时导入命令。多篇文章链接请以`英文逗号`隔开。

```php
(1). 即时导入：
php disco importData:insertOfficialAccountArticleData --articleUrl='https://mp.weixin.qq.com/s/M49aEEEcpdzbjB-PEUhyhw'  # 导入单篇文章

php disco importData:insertOfficialAccountArticleData --articleUrl='https://mp.weixin.qq.com/s/eqlaxq6eod2Lpe-p6JFnlQ,https://mp.weixin.qq.com/s/zWqNzA2qzTz78VURZTeFDQ,https://mp.weixin.qq.com/s/Gu6jwVM78-dhrytuclyxLg'  # 导入多篇文章，以英文逗号隔开 

(2). 设置自动导入：不支持
```

