<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
session_init(); $user = require_admin(true); apply_security_headers();
$userInitial = mb_strtoupper(mb_substr($user['display_name'], 0, 1));
$maxMB = MAX_FILE_SIZE / 1024 / 1024;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Documents – Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/app.css">
<style>
.drop-zone{border:2px dashed var(--border);border-radius:var(--radius-lg);padding:40px;text-align:center;cursor:pointer;transition:all var(--transition);background:var(--bg-secondary)}
.drop-zone:hover,.drop-zone.drag-over{border-color:var(--accent);background:var(--accent-dim)}
.file-info{display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg-tertiary);border-radius:var(--radius-md);margin-top:12px}
.progress-bar{height:6px;background:var(--bg-tertiary);border-radius:3px;overflow:hidden;margin-top:8px}
.progress-fill{height:100%;background:linear-gradient(90deg,var(--accent),#8b5cf6);border-radius:3px;transition:width 0.3s ease;width:0}
</style>
</head>
<body>
<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <svg width="18" height="18" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <span class="sidebar-brand">Admin Panel</span>
    </div>
    <nav class="admin-nav">
      <a href="dashboard.php" class="nav-item">Dashboard</a>
      <a href="knowledge.php" class="nav-item">Knowledge Base</a>
      <a href="documents.php" class="nav-item active">Documents</a>
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
    <div class="page-header">
      <h1 class="page-title">Documents</h1>
      <p class="page-subtitle">Upload and manage knowledge documents</p>
    </div>

    <div class="card mb-4">
      <div class="card-header"><span class="card-title">Upload Document</span></div>
      <div id="upload-alert"></div>

      <div class="drop-zone" id="drop-zone" onclick="document.getElementById('file-input').click()">
        <div style="font-weight:600;margin-bottom:6px;font-size:15px">Drop file here or click to browse</div>
        <div style="color:var(--text-muted);font-size:13px">Supported formats: TXT, CSV, PDF, DOCX &nbsp;&#183;&nbsp; Max <?= $maxMB ?>MB</div>
        <input type="file" id="file-input" accept=".txt,.csv,.pdf,.docx" hidden>
      </div>

      <div id="file-preview" class="hidden">
        <div class="file-info">
          <div style="flex:1">
            <div id="file-name" style="font-weight:600;font-size:14px"></div>
            <div id="file-size" style="font-size:12px;color:var(--text-muted)"></div>
          </div>
          <button class="btn-icon" onclick="clearFile()">&#10005;</button>
        </div>
        <div class="progress-bar" id="progress-bar" style="display:none">
          <div class="progress-fill" id="progress-fill"></div>
        </div>
      </div>

      <div class="flex gap-3 mt-3" style="flex-wrap:wrap">
        <div class="form-group" style="flex:1;min-width:200px;margin:0">
          <label class="form-label">Document Title</label>
          <input class="form-control" id="doc-title" placeholder="Auto-filled from filename">
        </div>
        <div class="form-group" style="flex:1;min-width:160px;margin:0">
          <label class="form-label">Category</label>
          <select class="form-control" id="doc-category"><option value="">None</option></select>
        </div>
      </div>
      <div class="form-group mt-3">
        <label class="form-label">Description <span class="text-muted">(optional)</span></label>
        <input class="form-control" id="doc-description" placeholder="Brief description of this document">
      </div>
      <button class="btn btn-primary mt-3" id="upload-btn" onclick="uploadDocument()" disabled>
        <span id="upload-spinner" class="spinner spinner-sm hidden"></span>Upload and Index
      </button>
    </div>

    <div class="card">
      <div class="card-header">
        <span class="card-title">Uploaded Documents</span>
        <button class="btn btn-secondary btn-sm" onclick="loadDocuments()">Refresh</button>
      </div>
      <div id="docs-list"><div class="empty-state"><div class="spinner spinner-lg" style="margin:0 auto 12px"></div></div></div>
    </div>
  </main>
</div>

<div id="toast-container"></div>
<script>
let selectedFile=null;
function escHtml(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function showToast(msg,type='success'){const c=document.getElementById('toast-container'),t=document.createElement('div');t.className=`toast toast-${type}`;t.innerHTML=`<span>${escHtml(msg)}</span>`;c.appendChild(t);setTimeout(()=>t.remove(),4000);}

const dz=document.getElementById('drop-zone');
dz.addEventListener('dragover',e=>{e.preventDefault();dz.classList.add('drag-over');});
dz.addEventListener('dragleave',()=>dz.classList.remove('drag-over'));
dz.addEventListener('drop',e=>{e.preventDefault();dz.classList.remove('drag-over');if(e.dataTransfer.files[0])handleFile(e.dataTransfer.files[0]);});
document.getElementById('file-input').addEventListener('change',e=>{if(e.target.files[0])handleFile(e.target.files[0]);});

function handleFile(file){
  selectedFile=file;
  document.getElementById('file-name').textContent=file.name;
  document.getElementById('file-size').textContent=(file.size/1024/1024).toFixed(2)+' MB';
  document.getElementById('file-preview').classList.remove('hidden');
  document.getElementById('upload-btn').disabled=false;
  if(!document.getElementById('doc-title').value){
    document.getElementById('doc-title').value=file.name.replace(/\.[^.]+$/,'').replace(/[-_]/g,' ');
  }
}
function clearFile(){
  selectedFile=null;
  document.getElementById('file-input').value='';
  document.getElementById('file-preview').classList.add('hidden');
  document.getElementById('upload-btn').disabled=true;
  document.getElementById('doc-title').value='';
}

async function uploadDocument(){
  if(!selectedFile)return;
  const btn=document.getElementById('upload-btn');const sp=document.getElementById('upload-spinner');
  const pb=document.getElementById('progress-bar');const pf=document.getElementById('progress-fill');
  btn.disabled=true;sp.classList.remove('hidden');pb.style.display='block';
  document.getElementById('upload-alert').innerHTML='';
  let prog=0;const timer=setInterval(()=>{prog=Math.min(prog+Math.random()*15,85);pf.style.width=prog+'%';},300);
  const form=new FormData();
  form.append('document',selectedFile);
  form.append('title',document.getElementById('doc-title').value||selectedFile.name);
  form.append('description',document.getElementById('doc-description').value);
  form.append('category_id',document.getElementById('doc-category').value);
  try{
    const r=await fetch('../api/documents.php?action=upload',{method:'POST',body:form});
    const d=await r.json();
    clearInterval(timer);pf.style.width='100%';
    setTimeout(()=>{pb.style.display='none';pf.style.width='0';},500);
    if(d.success){
      let alertType='success';let alertMsg=d.message;
      if(d.warning){alertMsg+=`<br><small style="opacity:0.8">${escHtml(d.warning)}</small>`;alertType='warning';}
      document.getElementById('upload-alert').innerHTML=`<div class="alert alert-${alertType}">${alertMsg}</div>`;
      clearFile();loadDocuments();
    }else{
      document.getElementById('upload-alert').innerHTML=`<div class="alert alert-error">${escHtml(d.error)}</div>`;
    }
  }catch{
    clearInterval(timer);
    document.getElementById('upload-alert').innerHTML='<div class="alert alert-error">Network error during upload.</div>';
  }
  finally{btn.disabled=!selectedFile;sp.classList.add('hidden');}
}

async function loadDocuments(){
  document.getElementById('docs-list').innerHTML='<div class="empty-state"><div class="spinner spinner-lg" style="margin:0 auto 12px"></div></div>';
  const r=await fetch('../api/documents.php?action=list');const d=await r.json();
  if(!d.success){document.getElementById('docs-list').innerHTML='<div class="alert alert-error">Failed to load documents.</div>';return;}
  if(!d.documents.length){
    document.getElementById('docs-list').innerHTML='<div class="empty-state"><div class="empty-state-text">No documents uploaded yet.</div></div>';
    return;
  }
  const statusBadge=s=>({indexed:'badge-green',processing:'badge-amber',failed:'badge-red',pending:'badge-gray'}[s]||'badge-gray');
  document.getElementById('docs-list').innerHTML=`
    <div class="table-wrap"><table>
      <thead><tr><th>Document</th><th>Type</th><th>Category</th><th>Status</th><th>Chunks</th><th>Size</th><th>Uploaded</th><th>Actions</th></tr></thead>
      <tbody>${d.documents.map(doc=>`
        <tr>
          <td>
            <div style="font-weight:600;font-size:14px">${escHtml(doc.title)}</div>
            <div style="font-size:11px;color:var(--text-muted)">${escHtml(doc.original_name)}</div>
          </td>
          <td><span class="badge badge-gray">${doc.file_type.toUpperCase()}</span></td>
          <td>${doc.category_name?`<span class="badge badge-purple">${escHtml(doc.category_name)}</span>`:'–'}</td>
          <td>
            <span class="badge ${statusBadge(doc.status)}">${doc.status}</span>
            ${doc.error_message?`<div style="font-size:11px;color:var(--danger);margin-top:2px">${escHtml(doc.error_message.substring(0,60))}</div>`:''}
          </td>
          <td>${doc.chunk_count||0}</td>
          <td class="text-muted text-sm">${(doc.file_size/1024).toFixed(1)} KB</td>
          <td class="text-muted text-sm">${new Date(doc.created_at).toLocaleDateString()}</td>
          <td>
            <div class="flex gap-2">
              <button class="btn btn-secondary btn-sm" onclick="reindex(${doc.id})" title="Re-index">Re-index</button>
              <button class="btn btn-danger btn-sm" onclick="deleteDoc(${doc.id})">Delete</button>
            </div>
          </td>
        </tr>`).join('')}
      </tbody></table></div>`;
}

async function reindex(id){
  if(!confirm('Re-index this document? Existing chunks will be replaced.'))return;
  showToast('Re-indexing document...','info');
  const r=await fetch(`../api/documents.php?action=reindex&id=${id}`);
  const d=await r.json();
  if(d.success){showToast(d.message);loadDocuments();}
  else showToast(d.error||'Re-index failed.','error');
}

async function deleteDoc(id){
  if(!confirm('Delete this document? This cannot be undone.'))return;
  const r=await fetch(`../api/documents.php?action=delete&id=${id}`,{method:'DELETE'});
  const d=await r.json();
  if(d.success){showToast('Document deleted.');loadDocuments();}
  else showToast(d.error||'Failed to delete.','error');
}

async function loadCategories(){
  const r=await fetch('../api/knowledge.php?action=categories');
  const d=await r.json();
  if(d.success)document.getElementById('doc-category').innerHTML='<option value="">None</option>'+d.categories.map(c=>`<option value="${c.id}">${escHtml(c.name)}</option>`).join('');
}
loadCategories();
loadDocuments();
</script>
</body>
</html>
