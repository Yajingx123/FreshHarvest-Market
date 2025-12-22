<?php
// 个人中心界面
?>
<!DOCTYPE html>
<html lang="zh-CN">
<?php include 'header.php'; ?>
<body>
    <main class="main">
        <section class="section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 class="section-title">个人中心</h2>
                <button type="button" class="btn btn-danger" onclick="location.href='logout.php'">退出登录</button>
            </div>
            <form>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">员工姓名</label>
                            <input type="text" class="form-control" value="陈晓峰" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">员工编号</label>
                            <input type="text" class="form-control" value="YG20240508" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">所属门店</label>
                            <input type="text" class="form-control" value="高新店" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">岗位</label>
                            <input type="text" class="form-control" value="门店运营专员" readonly>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label class="form-label">联系电话</label>
                            <input type="text" class="form-control" value="135****7890">
                        </div>
                        <div class="form-group">
                            <label class="form-label">入职日期</label>
                            <input type="text" class="form-control" value="2024-05-08" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">账号状态</label>
                            <input type="text" class="form-control" value="正常" readonly style="color: #43a047;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">修改密码</label>
                            <input type="password" class="form-control" placeholder="请输入新密码">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">备注</label>
                    <textarea class="form-control" rows="3" placeholder="填写个人备注信息">负责门店日常订单处理、商品管理及顾客服务工作</textarea>
                </div>
                <button type="button" class="btn btn-primary">保存修改</button>
                <button type="button" class="btn btn-warning" style="margin-left: 10px;">重置</button>
            </form>
        </section>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>