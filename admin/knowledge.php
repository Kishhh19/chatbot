<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
session_init(); $user = require_admin(true); apply_security_headers();
$userInitial = mb_strtoupper(mb_substr($user['display_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Knowledge Base – Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <svg width="18" height="18" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      </div>
      <span class="sidebar-brand">Admin Panel</span>
    </div>
    <nav class="admin-nav">
      <a href="dashboard.php" class="nav-item">Dashboard</a>
      <a href="knowledge.php" class="nav-item active">Knowledge Base</a>
      <a href="documents.php" class="nav-item">Documents</a>
      <a href="settings.php" class="nav-item">Bot Settings</a>
      <a href="users.php" class="nav-item">Users</a>
      <div class="divider"></div>
      <a href="../index.php" class="nav-item">Back to Chat</a>
    </nav>
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="avatar"><?=e($userInitial)?></div>
        <div class="user-info">
          <div class="user-name"><?=e($user['display_name'])?></div>
          <div class="user-role">Administrator</div>
        </div>
      </div>
    </div>
  </aside>

  <main class="admin-main">
    <div class="page-header" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <div>
        <h1 class="page-title">Knowledge Base</h1>
        <p class="page-subtitle">Manage the AI's knowledge items</p>
      </div>
      <button class="btn btn-primary" onclick="openEditor()">Add Knowledge</button>
    </div>

    <div class="card mb-4" style="padding:16px">
      <div class="flex gap-3" style="flex-wrap:wrap;align-items:center">
        <div class="search-box" style="flex:1;min-width:200px;margin:0">
          <span class="search-icon" style="font-size:13px">&#128269;</span>
          <input class="form-control" id="search-input" placeholder="Search knowledge items..." style="padding-left:36px">
        </div>
        <select class="form-control" id="category-filter" style="width:auto;min-width:160px">
          <option value="">All Categories</option>
        </select>
        <span class="text-sm text-muted"><span id="total-count">–</span> items</span>
      </div>
    </div>

    <div id="knowledge-list"><div class="empty-state"><div class="spinner spinner-lg" style="margin:0 auto 12px"></div></div></div>
    <div id="pagination" class="flex gap-2 justify-between items-center mt-3" style="flex-wrap:wrap"></div>
  </main>
</div>

<!-- Editor Modal -->
<div class="modal-overlay" id="editor-modal">
  <div class="modal" style="max-width:680px">
    <div class="modal-header">
      <span class="modal-title" id="editor-title">Add Knowledge</span>
      <button class="btn-icon" onclick="closeModal('editor-modal')">&#10005;</button>
    </div>
    <div id="editor-alert"></div>
    <div class="form-group">
      <label class="form-label">Title <span style="color:var(--danger)">*</span></label>
      <input class="form-control" id="kn-title" placeholder="e.g. Enrollment Procedure" maxlength="255">
    </div>
    <div class="flex gap-3">
      <div class="form-group" style="flex:1">
        <label class="form-label">Category</label>
        <select class="form-control" id="kn-category"><option value="">None</option></select>
      </div>
      <div class="form-group" style="flex:1">
        <label class="form-label">Tags <span class="text-muted">(comma separated)</span></label>
        <input class="form-control" id="kn-tags" placeholder="enrollment, students, process">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Description <span class="text-muted">(optional)</span></label>
      <input class="form-control" id="kn-description" placeholder="Brief summary of this knowledge item">
    </div>
    <div class="form-group">
      <label class="form-label">Content <span style="color:var(--danger)">*</span></label>
      <textarea class="form-control" id="kn-content" rows="10" placeholder="Enter the full knowledge content here..." style="min-height:200px"></textarea>
      <div class="form-hint" id="char-count">0 characters</div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('editor-modal')">Cancel</button>
      <button class="btn btn-primary" id="save-btn" onclick="saveKnowledge()">
        <span id="save-spinner" class="spinner spinner-sm hidden"></span>
        Save Knowledge
      </button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script>
let currentPage=1,editingId=null;
function escHtml(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function showToast(msg,type='success'){const c=document.getElementById('toast-container'),t=document.createElement('div');t.className=`toast toast-${type}`;t.innerHTML=`<span>${escHtml(msg)}</span>`;c.appendChild(t);setTimeout(()=>t.remove(),3500);}
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}

async function loadCategories(){
  const r=await fetch('../api/knowledge.php?action=categories');
  const d=await r.json();if(!d.success)return;
  const opts=d.categories.map(c=>`<option value="${c.id}">${escHtml(c.name)}</option>`).join('');
  document.getElementById('category-filter').innerHTML='<option value="">All Categories</option>'+opts;
  document.getElementById('kn-category').innerHTML='<option value="">None</option>'+opts;
}

async function loadKnowledge(page=1){
  currentPage=page;
  const search=document.getElementById('search-input').value;
  const cat=document.getElementById('category-filter').value;
  const params=new URLSearchParams({action:'list',page});
  if(search)params.append('search',search);
  if(cat)params.append('category',cat);
  document.getElementById('knowledge-list').innerHTML='<div class="empty-state"><div class="spinner spinner-lg" style="margin:0 auto 12px"></div></div>';
  const r=await fetch('../api/knowledge.php?'+params);
  const d=await r.json();
  if(!d.success){document.getElementById('knowledge-list').innerHTML='<div class="alert alert-error">Failed to load knowledge items.</div>';return;}
  document.getElementById('total-count').textContent=d.total;
  if(!d.items.length){
    document.getElementById('knowledge-list').innerHTML='<div class="empty-state"><div class="empty-state-text">No knowledge items found.</div></div>';
    document.getElementById('pagination').innerHTML='';return;
  }
  document.getElementById('knowledge-list').innerHTML=`
    <div class="table-wrap"><table>
      <thead><tr><th>Title</th><th>Category</th><th>Tags</th><th>Length</th><th>Updated</th><th>Actions</th></tr></thead>
      <tbody>${d.items.map(item=>`
        <tr>
          <td>
            <div style="font-weight:600;font-size:14px">${escHtml(item.title)}</div>
            <div style="font-size:12px;color:var(--text-muted);max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escHtml(item.content_preview)}</div>
          </td>
          <td>${item.category_name?`<span class="badge badge-purple">${escHtml(item.category_name)}</span>`:'<span class="text-muted">–</span>'}</td>
          <td style="max-width:150px">${item.tags?item.tags.split(',').map(t=>`<span class="badge badge-gray">${escHtml(t.trim())}</span>`).join(' '):'–'}</td>
          <td class="text-muted text-sm">${Number(item.content_length).toLocaleString()} chars</td>
          <td class="text-muted text-sm">${new Date(item.updated_at).toLocaleDateString()}</td>
          <td>
            <div class="flex gap-2">
              <button class="btn btn-secondary btn-sm" onclick="editKnowledge(${item.id})">Edit</button>
              <button class="btn btn-danger btn-sm" onclick="deleteKnowledge(${item.id})">Delete</button>
            </div>
          </td>
        </tr>`).join('')}
      </tbody></table></div>`;
  if(d.pages>1){
    let pg='';
    for(let i=1;i<=d.pages;i++)pg+=`<button class="btn ${i===d.page?'btn-primary':'btn-secondary'} btn-sm" onclick="loadKnowledge(${i})">${i}</button>`;
    document.getElementById('pagination').innerHTML=`<span class="text-muted text-sm">Page ${d.page} of ${d.pages}</span><div class="flex gap-2">${pg}</div>`;
  } else document.getElementById('pagination').innerHTML='';
}

function openEditor(item=null){
  editingId=item?item.id:null;
  document.getElementById('editor-title').textContent=item?'Edit Knowledge':'Add Knowledge';
  document.getElementById('kn-title').value=item?.title||'';
  document.getElementById('kn-description').value=item?.description||'';
  document.getElementById('kn-content').value=item?.content||'';
  document.getElementById('kn-tags').value=item?.tags||'';
  document.getElementById('kn-category').value=item?.category_id||'';
  document.getElementById('editor-alert').innerHTML='';
  updateCharCount();
  openModal('editor-modal');
  setTimeout(()=>document.getElementById('kn-title').focus(),100);
}

document.getElementById('kn-content').addEventListener('input',updateCharCount);
function updateCharCount(){document.getElementById('char-count').textContent=document.getElementById('kn-content').value.length.toLocaleString()+' characters';}

async function editKnowledge(id){
  const r=await fetch(`../api/knowledge.php?action=get&id=${id}`);
  const d=await r.json();
  if(d.success)openEditor(d.item);else showToast('Failed to load item.','error');
}

async function saveKnowledge(){
  const title=document.getElementById('kn-title').value.trim();
  const content=document.getElementById('kn-content').value.trim();
  const description=document.getElementById('kn-description').value.trim();
  const tags=document.getElementById('kn-tags').value.trim();
  const category_id=document.getElementById('kn-category').value||null;
  if(!title||!content){document.getElementById('editor-alert').innerHTML='<div class="alert alert-error">Title and content are required.</div>';return;}
  const btn=document.getElementById('save-btn');const sp=document.getElementById('save-spinner');
  btn.disabled=true;sp.classList.remove('hidden');
  try{
    const method=editingId?'PUT':'POST';
    const url=editingId?`../api/knowledge.php?action=update&id=${editingId}`:'../api/knowledge.php?action=create';
    const r=await fetch(url,{method,headers:{'Content-Type':'application/json'},body:JSON.stringify({title,content,description,tags,category_id})});
    const d=await r.json();
    if(d.success){closeModal('editor-modal');showToast(d.message||'Saved!');loadKnowledge(currentPage);}
    else document.getElementById('editor-alert').innerHTML=`<div class="alert alert-error">${escHtml(d.error)}</div>`;
  }catch{document.getElementById('editor-alert').innerHTML='<div class="alert alert-error">Network error. Please try again.</div>';}
  finally{btn.disabled=false;sp.classList.add('hidden');}
}

async function deleteKnowledge(id){
  if(!confirm('Delete this knowledge item? This cannot be undone.'))return;
  const r=await fetch(`../api/knowledge.php?action=delete&id=${id}`,{method:'DELETE'});
  const d=await r.json();
  if(d.success){showToast('Knowledge item deleted.');loadKnowledge(currentPage);}
  else showToast(d.error||'Failed to delete.','error');
}

let debounce;
document.getElementById('search-input').addEventListener('input',()=>{clearTimeout(debounce);debounce=setTimeout(()=>loadKnowledge(1),400);});
document.getElementById('category-filter').addEventListener('change',()=>loadKnowledge(1));
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal('editor-modal');});
loadCategories();
loadKnowledge();
</script>
</body>
</html>
