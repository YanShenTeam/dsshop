#

## 项目初始化

```bash
npm install 
# HBuilder X导入小程序目录
# /utils/config.js 修改服务器地址
# 修改`BaseURL`为API访问地址
# 修改 `secret` 为 `api/config/dswjcms.php` 中的 `PROJECT_KEY`
# HBuilder X可以通过浏览器、微信小程序运行项目，也可以直接发布项目，但需要配置账号，具体请参考HBuilder X
# 本地调试小程序开发工具需要开放服务端口，在`设置-安全`
# 本地调试小程序开发工具需要关闭不校验合法 url，在项目的`manifest.json`中｀urlCheck｀设置为 `false`
```

## 项目 commit 规范

commit 信息应该遵循 Angular 规范，形如

```bash
feat: 新增命令行参数 -y 跳过询问
```

其中，`feat` 可以被替换为以下这些类型选项：

```bash
feat: 新增feature
fix: 修复bug
docs: 仅仅修改了文档，比如README, CHANGELOG, CONTRIBUTE等等
style: 仅仅修改了空格、格式缩进、逗号等等，不改变代码逻辑
refactor: 代码重构，没有加新功能或者修复bug
perf: 优化相关，比如提升性能、体验
test: 测试用例，包括单元测试、集成测试等
build: 构建系统或者包依赖更新
ci: CI 配置，脚本文件等更新
chore: 改变构建流程、或者增加依赖库、工具等
revert: 回滚到上一个版本
```

使用 `npm run commit` 命令替代 `git commit`。

```bash
# 问答式 commit
npm run commit
```

## 工具

**request网络请求**

https://ext.dcloud.net.cn/plugin?id=468

**canvas生成海报**
https://github.com/xlfsummer/mp-painter




### canvasData
|  属性 | 类型   | 是否必须  |  说明 |
| ------------ |------------ | ------------ | ------------ |
| block  |  number | 否 | 水平分割，如2、4、6 设置后  |
|  align |  string | 否 |  对方方式 左对齐：left;居中对齐:center;右对齐: right |
|   x |  number | 否 |  绘制文本的左上角 x 坐标位置，当设置align后，x为偏移量 |
|   y |  number | 否 |  绘制文本的左上角 y 坐标位置 |
|  width |  number | 否 |  宽度 默认为元素自身宽度 |
|  height |  number | 否 |  宽度 默认为20 |
|  line |  number | 否 |  显示行数 默认为1 |
|  share |  number | 否 |  份额，共12份 |
|  strokeStyle |  number | 否 |  描边颜色 |
|  type |  string | 否 |  类型 图片:image; 文本:text; 带边框的文本:box;table:表格(设为table后，以下属性除son外都失效) |
|  padding |  number | 否 |  内边距 |
|  fillStyle |  number | 否 |  设置填充色，仅针对text |
|  fontSize |  number | 否 |  字体大小，仅针对text |
|  textAlign |  string | 否 |  文字的对齐 左对齐：left;居中对齐:center;右对齐: right |
|  bold |  boolean | 否 |  是否加粗，默认为 false |
|  textBaseline |  string | 否 |  设置文字的竖直对齐 顶部:top;底部:bottom;居中:middle;无:normal ，仅针对text|
|  text |  string | 否 |  内容 |
|  son |  object | 否 |  子项，设有排列方式时，son为必须 |


### block.son
|  属性 | 类型   | 是否必须  |  说明 |
| ------------ |------------ | ------------ | ------------ |
|  width |  number | 否 |  宽度 默认为元素自身宽度 |
|  height |  number | 否 |  宽度 默认为20 |
|  line |  number | 否 |  显示行数 默认为1 |
|  align |  string | 否 |  对方方式 左对齐：left;居中对齐:center;右对齐: right |
|  type |  string | 是 |  类型 th:表头 td:表格 |
|  fillStyle |  number | 否 |  设置填充色，仅针对text |
|  fontSize |  number | 否 |  字体大小，仅针对text |
|  bold |  boolean | 否 |  是否加粗，默认为 false |
|  padding |  number | 否 |  内边距 |
|  textAlign |  string | 否 |  文字的对齐 左对齐：left;居中对齐:center;右对齐: right |
|  text |  string | 是 |  内容 |

### canvasData.son
|  属性 | 类型   | 是否必须  |  说明 |
| ------------ |------------ | ------------ | ------------ |
|  align |  string | 否 |  对方方式 左对齐：left;居中对齐:center;右对齐: right |
|   x |  number | 否 |  绘制文本的左上角 x 坐标位置，当设置align后，x为偏移量 |
|   y |  number | 否 |  绘制文本的左上角 y 坐标位置 |
|  width |  number | 否 |  宽度 默认为元素自身宽度 |
|  height |  number | 否 |  宽度 默认为20 |
|  line |  number | 否 |  显示行数 默认为1 |
|  align |  string | 否 |  对方方式 左对齐：left;居中对齐:center;右对齐: right |
|  type |  string | 是 |  类型 图片:image; 文本:text; 带边框的文本:box; table:表格 |
|  fillStyle |  number | 否 |  设置填充色，仅针对text |
|  fontSize |  number | 否 |  字体大小，仅针对text |
|  textAlign |  string | 否 |  文字的对齐 左对齐：left;居中对齐:center;右对齐: right |
|  bold |  boolean | 否 |  是否加粗，默认为 false |
|  padding |  number | 否 |  内边距 |
|  textBaseline |  string | 否 |  设置文字的竖直对齐 顶部:top;底部:bottom;居中:middle;无:normal ，仅针对text|
|  text |  string | 是 |  内容 |
|  son |  object | 否 |  子项，type为table时，son为必须 |


### canvasData.son.table.son
|  属性 | 类型   | 是否必须  |  说明 |
| ------------ |------------ | ------------ | ------------ |
|  width |  number | 否 |  宽度 默认为元素自身宽度 |
|  height |  number | 否 |  宽度 默认为20 |
|  line |  number | 否 |  显示行数 默认为1 |
|  align |  string | 否 |  对方方式 左对齐：left;居中对齐:center;右对齐: right |
|  type |  string | 是 |  类型 th:表头 td:表格 |
|  fillStyle |  number | 否 |  设置填充色，仅针对text |
|  fontSize |  number | 否 |  字体大小，仅针对text |
|  bold |  boolean | 否 |  是否加粗，默认为 false |
|  textAlign |  string | 否 |  文字的对齐 左对齐：left;居中对齐:center;右对齐: right |
|  text |  string | 是 |  内容 |
|  padding |  number | 否 |  内边距 |

##### 子类都是基于父类累加的，比如x、y
##### 子类如果有配置，子类优先级将大于父类，如padding