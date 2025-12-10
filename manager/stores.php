<?php
require_once __DIR__ . '/inc/data.php';
require_once __DIR__ . '/inc/header.php';
?>
<section class="section">
    <h2 class="section-title">门店列表</h2>
    <div class="filter-bar" style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
        <input id="storeSearch" class="filter-input" placeholder="按门店ID或名称搜索" style="width:260px;">
        <button class="btn btn-primary" id="storeSearchBtn">搜索</button>
        <button class="btn btn-success" id="storeAddBtn" style="margin-left:auto;">新增门店</button>
    </div>
    <div style="max-height:520px;overflow:auto;">
        <table class="data-table" style="min-width:900px;">
            <thead>
                <tr><th>门店ID</th><th>门店名称</th><th>联系人</th><th>联系电话</th><th>地址</th><th>状态</th><th>操作</th></tr>
            </thead>
            <tbody id="storesTbody"></tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/inc/footer.php'; ?>

<script>
(function () {
    // ---------- 新增：取消页面内的 hash 跳转与清理旧 section id ----------
    try {
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            try {
                a.setAttribute('href', 'index.php');
                a.addEventListener('click', function (ev) {
                    if (ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return;
                    ev.preventDefault();
                    window.location.href = this.getAttribute('href');
                });
            } catch (e) {}
        });
        ['dashboard','products','partners','inventory','settlement','orders','profile'].forEach(id=>{
            const el = document.getElementById(id);
            if (el) el.removeAttribute('id');
        });
    } catch (err) {
        console && console.warn && console.warn('Anchor cleanup failed', err);
    }
    // ---------- 清理结束，下面为原有门店逻辑（保持不变） ----------

    const initialPartners = <?= json_encode($partners, JSON_UNESCAPED_UNICODE) ?>;
    let partners = JSON.parse(JSON.stringify(initialPartners));

    function renderStores(list){
        const tb = document.getElementById('storesTbody'); tb.innerHTML='';
        if (!list.length) { tb.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#666;padding:18px;">暂无门店</td></tr>'; return; }
        list.forEach(p=>{
            const tr = document.createElement('tr');
            tr.dataset.id = p.id;
            tr.innerHTML = `<td>${p.id}</td><td>${p.name}</td><td>${p.contact}</td><td>${p.phone}</td><td>${p.address}</td><td><span class="status-tag status-accepted">${p.status}</span></td>
                <td><button class="btn btn-primary btn-view">查看详情</button><button class="btn btn-warning btn-edit" style="margin-left:8px;">编辑</button><button class="btn btn-danger btn-del" style="margin-left:8px;">删除</button></td>`;
            tb.appendChild(tr);
        });
    }

    function filterAndRender(){
        const q = (document.getElementById('storeSearch')||{}).value.trim().toLowerCase();
        const list = partners.filter(p => (p.id+' '+p.name).toLowerCase().includes(q));
        renderStores(list);
    }

    document.addEventListener('click', async (e)=>{
        const btn = e.target.closest('.btn');
        if (!btn) return;
        // 新增门店
        if (btn.id === 'storeAddBtn') {
            const html = `<div style="display:flex;flex-direction:column;gap:8px;">
                <label>门店名称<input id="newName" class="form-control"></label>
                <label>联系人<input id="newContact" class="form-control"></label>
                <label>电话<input id="newPhone" class="form-control"></label>
                <label>地址<input id="newAddr" class="form-control"></label>
                <label>状态<select id="newStatus" class="form-control"><option>合作中</option><option>暂停合作</option><option>已终止</option></select></label>
            </div>`;
            const ok = await showAppModal('新增门店', html, {showCancel:true, okText:'添加'});
            if (!ok) return;
            const body = document.getElementById('appModalBody');
            const name = (body.querySelector('#newName')||{}).value || '';
            if (!name.trim()){ await showAppModal('错误','<p>名称必填</p>',{showCancel:false}); return; }
            const nextId = 'ST' + (1000 + partners.length + 1);
            partners.push({id: nextId, name: name.trim(), contact: (body.querySelector('#newContact')||{}).value || '', phone: (body.querySelector('#newPhone')||{}).value||'', address:(body.querySelector('#newAddr')||{}).value||'', status:(body.querySelector('#newStatus')||{}).value||'合作中'});
            filterAndRender();
            await showAppModal('已添加', `<p>门店 <strong>${name}</strong> 已添加</p>`, {showCancel:false});
            return;
        }

        // 编辑 / 删除 / 查看
        if (btn.classList.contains('btn-edit') || btn.classList.contains('btn-del') || btn.classList.contains('btn-view')) {
            const tr = btn.closest('tr');
            const id = tr && tr.dataset.id;
            const p = partners.find(x=>x.id===id);
            if (!p) return;
            if (btn.classList.contains('btn-view')) {
                await showAppModal('门店详情', `<div><h4>${p.name} (${p.id})</h4><p>联系人：${p.contact}<br>电话：${p.phone}<br>地址：${p.address}<br>状态：${p.status}</p></div>`, {showCancel:false});
                return;
            }
            if (btn.classList.contains('btn-edit')) {
                const html = `<div style="display:flex;flex-direction:column;gap:8px;">
                    <label>门店名称<input id="eName" class="form-control" value="${p.name}"></label>
                    <label>联系人<input id="eContact" class="form-control" value="${p.contact}"></label>
                    <label>电话<input id="ePhone" class="form-control" value="${p.phone}"></label>
                    <label>地址<input id="eAddr" class="form-control" value="${p.address}"></label>
                    <label>状态<select id="eStatus" class="form-control"><option ${p.status==='合作中'?'selected':''}>合作中</option><option ${p.status==='暂停合作'?'selected':''}>暂停合作</option><option ${p.status==='已终止'?'selected':''}>已终止</option></select></label>
                </div>`;
                const ok = await showAppModal('编辑门店', html, {showCancel:true, okText:'保存'});
                if (!ok) return;
                const body = document.getElementById('appModalBody');
                p.name = (body.querySelector('#eName')||{}).value || p.name;
                p.contact = (body.querySelector('#eContact')||{}).value || p.contact;
                p.phone = (body.querySelector('#ePhone')||{}).value || p.phone;
                p.address = (body.querySelector('#eAddr')||{}).value || p.address;
                p.status = (body.querySelector('#eStatus')||{}).value || p.status;
                filterAndRender();
                await showAppModal('已保存','<p>保存成功</p>',{showCancel:false});
                return;
            }
            if (btn.classList.contains('btn-del')) {
                const ok = await showAppModal('确认删除', `<p>确定删除门店 <strong>${p.id}</strong> 吗？</p>`, {showCancel:true, okText:'删除'});
                if (!ok) return;
                partners = partners.filter(x=>x.id!==id);
                filterAndRender();
                await showAppModal('已删除','<p>删除成功</p>',{showCancel:false});
                return;
            }
        }
    });

    document.getElementById('storeSearchBtn').addEventListener('click', filterAndRender);
    document.getElementById('storeSearch').addEventListener('keydown', function(e){ if (e.key==='Enter') filterAndRender(); });

    filterAndRender();
})();
</script>
