产品管理功能说明（supplier/index.php）

概述
----
在 `supplier/index.php` 中已实现客户端产品管理功能，覆盖：

- 搜索（按产品 ID / 名称）
- 筛选（按分类、按状态）
- 新增产品（弹窗表单）
- 编辑产品（弹窗表单）
- 上架 / 下架 切换
- 补货（在弹窗中输入补货数量）
- 客户端持久化：使用 localStorage（键名：`supplier_products_v1`）

如何测试
-------
1. 在浏览器中打开页面（例如：http://localhost/ui/supplier/index.php）。
2. 找到“产品管理”板块。
3. 使用右侧的“新增产品”按钮可打开弹窗创建新产品。
4. 在产品列表点击“编辑”可修改记录；“下架/上架”可切换状态；“补货”可增加库存说明。
5. 搜索框与下拉筛选会在当前 localStorage 数据中进行过滤。

恢复初始数据 / 清空数据
----------------
页面采用 localStorage 做持久化，若要重置为文件中预置的初始静态数据：

- 在浏览器 Console（开发者工具）中运行：

  localStorage.removeItem('supplier_products_v1');

- 刷新页面即可看到回退到初始演示数据。

下一步（可选）
-----------
如果需要后端持久化（PHP 接口 + 文件/数据库），我可以：
1. 添加一个简单的 REST 风格 PHP API（增删改查）并把页面切换为通过 fetch 调用 API；或
2. 集成数据库（如 sqlite / MySQL）保存真实数据。

如需后端支持，请告诉我希望的持久化方式（JSON 文件 / sqlite / MySQL）。
