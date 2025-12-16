一、数据库文件说明
仓库中包含数据库导出文件：/db/mydb_schema.sql

该文件包含：
1.数据库 mydb
2.所有表（tables）
3.所有视图（views）
4.所有初始化数据（库存、商品、供应商、订单等）

注意：不需要手动建表，不需要单独执行 SQL，统一通过该文件导入。

二、数据库导入步骤
Step 1：创建数据库（如果不存在）
在 MySQL Workbench 中执行：
CREATE DATABASE IF NOT EXISTS mydb
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

Step 2：导入数据库文件
打开 MySQL Workbench
进入：Administration → Data Import / Restore
选择：Import from Self-Contained File
选择文件：db/mydb_schema.sql
Default Target Schema：填写mydb
保持默认选项，点击Start Import

导入完成后，你应该能在左侧看到：
mydb
 ├── Tables
 ├── Views
 ├── Stored Procedures（如有）

三、前端数据库连接配置
项目统一使用以下配置文件：UI/config/db_connect.php
默认内容如下（无需修改）：
$DB_HOST = "127.0.0.1";
$DB_PORT = "3306";
$DB_NAME = "mydb";
$DB_USER = "root";
$DB_PASS = "NewRootPwd123!";
如果你的本地 MySQL 用户名或密码不同，只需要修改用户名 / 密码，不要修改数据库名 mydb。


四、协作约定
数据库结构 / 初始化数据的修改→ 必须重新导出 mydb_schema.sql 并提交
❌ 不要提交本地 MySQL 数据目录
❌ 不要提交个人测试用数据库名（如 mydb_test）

五、维护说明
如需更新数据库：
1.在本地修改数据库
2.使用 MySQL Workbench 重新导出
3.覆盖 db/mydb_schema.sql
4.提交 Git

