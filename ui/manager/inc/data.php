<?php
// 演示用共享数据（可被各页面 require）
$partners = [
    ['id' => 'ST1001', 'name' => '高新店', 'contact' => '李经理', 'phone' => '13812345678', 'address' => '高新路1号', 'status' => '合作中'],
    ['id' => 'ST1002', 'name' => '曲江店', 'contact' => '王小姐', 'phone' => '13923456789', 'address' => '曲江商圈2号', 'status' => '合作中'],
    ['id' => 'ST1003', 'name' => '莲湖店', 'contact' => '赵先生', 'phone' => '13734567890', 'address' => '莲湖大道3号', 'status' => '暂停合作'],
    ['id' => 'ST1004', 'name' => '未央店', 'contact' => '邓主管', 'phone' => '13655551234', 'address' => '未央路56号', 'status' => '合作中'],
    ['id' => 'ST1005', 'name' => '南门店', 'contact' => '孙小姐', 'phone' => '13899998888', 'address' => '南门街12号', 'status' => '已终止'],
    ['id' => 'ST1006', 'name' => '浐灞店', 'contact' => '周先生', 'phone' => '13577776666', 'address' => '浐灞生态区5号', 'status' => '合作中'],
    ['id' => 'ST1007', 'name' => '高陵店', 'contact' => '吴经理', 'phone' => '13633334444', 'address' => '高陵大道20号', 'status' => '暂停合作'],
    ['id' => 'ST1008', 'name' => '锦业路店', 'contact' => '郑小姐', 'phone' => '13944445555', 'address' => '锦业路88号', 'status' => '合作中'],
];



// 新增：员工示例数据（用于 employees.php）
$employees = [
    ['id'=>'EMP001','name'=>'张伟','role'=>'仓库管理员','salary'=>4500,'start_date'=>'2021-03-15','status'=>'在职'],
    ['id'=>'EMP002','name'=>'李静','role'=>'采购专员','salary'=>5600,'start_date'=>'2020-07-01','status'=>'在职'],
    ['id'=>'EMP003','name'=>'王强','role'=>'司机','salary'=>4200,'start_date'=>'2019-11-20','status'=>'在职'],
    ['id'=>'EMP004','name'=>'赵敏','role'=>'质检员','salary'=>4800,'start_date'=>'2022-01-05','status'=>'试用'],
    ['id'=>'EMP005','name'=>'周磊','role'=>'门店主管','salary'=>6200,'start_date'=>'2018-05-10','status'=>'在职'],
];

// 新增：顾客示例数据（用于 customers.php）
$customers = [
    ['id'=>'CUST1001','name'=>'刘洋','phone'=>'13800001111','registered'=>'2022-02-14','orders'=>12,'total_spent'=>2580.50],
    ['id'=>'CUST1002','name'=>'陈芳','phone'=>'13900002222','registered'=>'2021-10-01','orders'=>5,'total_spent'=>760.00],
    ['id'=>'CUST1003','name'=>'杨帆','phone'=>'13700003333','registered'=>'2020-06-20','orders'=>23,'total_spent'=>9450.20],
    ['id'=>'CUST1004','name'=>'吴娟','phone'=>'13600004444','registered'=>'2023-01-12','orders'=>3,'total_spent'=>180.00],
    ['id'=>'CUST1005','name'=>'何磊','phone'=>'13500005555','registered'=>'2019-09-30','orders'=>47,'total_spent'=>22500.75],
];

// 便捷计数
$employeesCount = count($employees);
$customersCount = count($customers);
$storesCount = count($partners);
$suppliersCount = 4;
$employeesCount = count($employees);
$customersCount = count($customers);
$revenueSample = [1200,1500,1800,1700,2100,2400,2200,2600,3000,2800,3200,3600];

// 简化产品数据，仅用于goods.php页面展示
$products = [
    [
        'name' => '矿泉水',
        'description' => '清爽健康，适合日常饮用。',
        'image' => '', // 图片占位
        'spec' => '500ml/瓶',
        'unit' => '瓶'
    ],
    [
        'name' => '方便面',
        'description' => '劲道爽滑，红烧牛肉味。',
        'image' => '',
        'spec' => '100g/包',
        'unit' => '包'
    ],
    [
        'name' => '纸巾',
        'description' => '柔软亲肤，吸水性强。',
        'image' => '',
        'spec' => '200抽/包',
        'unit' => '包'
    ],
    [
        'name' => '可乐',
        'description' => '经典口味，畅爽解渴。',
        'image' => '',
        'spec' => '330ml/罐',
        'unit' => '罐'
    ],
    [
        'name' => '洗手液',
        'description' => '温和配方，洁净双手。',
        'image' => '',
        'spec' => '300ml/瓶',
        'unit' => '瓶'
    ]
];

// 简化流水信息，仅用于goods.php页面展示
$transactions = [
    [
        'productName' => '矿泉水',
        'batchId' => 'BATCH001',
        'type' => 'in',
        'qty' => 100,
        'unit' => '瓶',
        'time' => strtotime('2025-12-01 09:00:00') * 1000,
        'note' => '首批进货'
    ],
    [
        'productName' => '方便面',
        'batchId' => 'BATCH002',
        'type' => 'in',
        'qty' => 50,
        'unit' => '包',
        'time' => strtotime('2025-12-02 10:30:00') * 1000,
        'note' => '补货'
    ],
    [
        'productName' => '矿泉水',
        'batchId' => 'BATCH001',
        'type' => 'out',
        'qty' => 20,
        'unit' => '瓶',
        'time' => strtotime('2025-12-03 14:00:00') * 1000,
        'note' => '发往高新店'
    ],
    [
        'productName' => '纸巾',
        'batchId' => 'BATCH003',
        'type' => 'in',
        'qty' => 80,
        'unit' => '包',
        'time' => strtotime('2025-12-04 15:00:00') * 1000,
        'note' => '新货到店'
    ],
    [
        'productName' => '可乐',
        'batchId' => 'BATCH004',
        'type' => 'out',
        'qty' => 30,
        'unit' => '罐',
        'time' => strtotime('2025-12-05 16:00:00') * 1000,
        'note' => '门店促销'
    ]
];
