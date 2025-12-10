<?php
// 员工信息界面
?>
<!DOCTYPE html>
<html lang="zh-CN">
<?php include 'header.php'; ?>
<style>
.employee-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 36px;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.employee-table th, .employee-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #f0f0f0;
    text-align: left;
    font-size: 15px;
}
.employee-table th { background: #f8f9fa; color: #1976d2; letter-spacing: 1px; }
.employee-table tr:last-child td { border-bottom: none; }
.section-title {
    font-size: 22px;
    color: #1976d2;
    margin-bottom: 18px;
    letter-spacing: 1px;
    font-weight: 700;
}
</style>
<body>
    <main class="main">
        <section class="section">
            <h2 class="section-title">门店员工信息</h2>
            <div style="overflow-x:auto;">
                <table class="employee-table">
                    <thead>
                        <tr>
                            <th>员工编号</th>
                            <th>姓名</th>
                            <th>职位</th>
                            <th>联系方式</th>
                            <th>邮箱</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTableBody"></tbody>
                </table>
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
    <script>
    // 示例员工数据
    const employees = [
        { id: 'E202401', name: '王小明', role: '店长', phone: '13811112222', email: 'wangxm@store.com' },
        { id: 'E202402', name: '李晓红', role: '收银员', phone: '13922223333', email: 'lixh@store.com' },
        { id: 'E202403', name: '张伟', role: '理货员', phone: '13733334444', email: 'zhangw@store.com' },
        { id: 'E202404', name: '赵丽', role: '配送员', phone: '13644445555', email: 'zhaol@store.com' }
    ];
    function renderEmployeeTable() {
        const tbody = document.getElementById('employeeTableBody');
        tbody.innerHTML = '';
        employees.forEach(emp => {
            tbody.innerHTML += `<tr>
                <td>${emp.id}</td>
                <td>${emp.name}</td>
                <td>${emp.role}</td>
                <td>${emp.phone}</td>
                <td>${emp.email}</td>
            </tr>`;
        });
    }
    renderEmployeeTable();
    </script>
</body>
</html>
