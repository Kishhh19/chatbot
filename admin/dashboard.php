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
<title>Dashboard – Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <svg width="18" height="18" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
      </div>
      <span class="sidebar-brand">Admin Panel</span>
    </div>
    <nav class="admin-nav">
      <a href="dashboard.php" class="nav-item active">Dashboard</a>
      <a href="knowledge.php" class="nav-item">Knowledge Base</a>
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
    <div class="page-header">
      <h1 class="page-title">Dashboard</h1>
      <p class="page-subtitle">System overview and recent activity</p>
    </div>

    <div class="stats-grid">
      <div class="stat-card"><div class="stat-label">Total Users</div><div class="stat-value" id="stat-users">–</div></div>
      <div class="stat-card"><div class="stat-label">Conversations</div><div class="stat-value" id="stat-convs">–</div></div>
      <div class="stat-card"><div class="stat-label">Messages</div><div class="stat-value" id="stat-msgs">–</div></div>
      <div class="stat-card"><div class="stat-label">Knowledge Items</div><div class="stat-value" id="stat-know">–</div></div>
      <div class="stat-card"><div class="stat-label">Documents</div><div class="stat-value" id="stat-docs">–</div></div>
      <div class="stat-card"><div class="stat-label">Indexed Chunks</div><div class="stat-value" id="stat-chunks">–</div></div>
    </div>

    <div class="card mb-4">
      <div class="card-header">
        <span class="card-title">AI Configuration</span>
        <a href="settings.php" class="btn btn-secondary btn-sm">Configure</a>
      </div>
      <div class="flex gap-4" style="flex-wrap:wrap;align-items:center">
        <div><span class="text-muted text-sm">Provider: </span><span id="ai-provider" class="badge badge-purple">–</span></div>
        <div><span class="text-muted text-sm">Model: </span><span id="ai-model" class="badge badge-blue">–</span></div>
        <div><span class="text-muted text-sm">Documents Indexed: </span><span id="ai-indexed" class="badge badge-green">–</span></div>
        <button class="btn btn-secondary btn-sm" onclick="testConnection()" id="test-btn">Test Connection</button>
        <span id="test-result" class="text-sm"></span>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header"><span class="card-title">Quick Actions</span></div>
      <div class="flex gap-3" style="flex-wrap:wrap">
        <a href="knowledge.php" class="btn btn-primary btn-sm">Add Knowledge</a>
        <a href="documents.php" class="btn btn-secondary btn-sm">Upload Document</a>
        <a href="settings.php" class="btn btn-secondary btn-sm">Bot Settings</a>
        <a href="users.php" class="btn btn-secondary btn-sm">Manage Users</a>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">Recent Activity</span></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Role</th><th>Message Preview</th><th>Conversation</th><th>Time</th></tr></thead>
          <tbody id="recent-table"><tr><td colspan="4" style="text-align:center;color:var(--text-muted)">Loading...</td></tr></tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<div id="toast-container"></div>
<script>
function escHtml(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
async function loadDashboard(){
  try{
    const r=await fetch('../api/settings.php?action=dashboard');
    const d=await r.json();
    if(!d.success)return;
    const s=d.stats;
    document.getElementById('stat-users').textContent=s.users;
    document.getElementById('stat-convs').textContent=s.conversations;
    document.getElementById('stat-msgs').textContent=s.messages;
    document.getElementById('stat-know').textContent=s.knowledge;
    document.getElementById('stat-docs').textContent=s.documents;
    document.getElementById('stat-chunks').textContent=s.chunks;
    document.getElementById('ai-provider').textContent=s.ai_provider;
    document.getElementById('ai-model').textContent=s.ai_model;
    document.getElementById('ai-indexed').textContent=s.indexed_docs+'/'+s.documents+' docs';
    const tbody=document.getElementById('recent-table');
    if(d.recent_messages&&d.recent_messages.length){
      tbody.innerHTML=d.recent_messages.map(m=>`<tr>
        <td><span class="badge ${m.role==='user'?'badge-blue':'badge-purple'}">${escHtml(m.role)}</span></td>
        <td class="truncate" style="max-width:300px">${escHtml(m.content_preview)}</td>
        <td class="truncate" style="max-width:180px">${escHtml(m.conversation_title)}</td>
        <td class="text-muted text-sm">${new Date(m.created_at).toLocaleString()}</td>
      </tr>`).join('');
    } else {
      tbody.innerHTML='<tr><td colspan="4" style="text-align:center;color:var(--text-muted)">No recent activity</td></tr>';
    }
  }catch(e){console.error(e);}
}
async function testConnection(){
  const btn=document.getElementById('test-btn');
  const res=document.getElementById('test-result');
  btn.disabled=true;res.textContent='Testing...';res.style.color='var(--text-muted)';
  try{
    const r=await fetch('../api/settings.php?action=test_api');
    const d=await r.json();
    if(d.success){res.style.color='var(--success)';res.textContent='Connection successful!';}
    else{res.style.color='var(--danger)';res.textContent=d.error||'Connection failed.';}
  }catch{res.style.color='var(--danger)';res.textContent='Network error.';}
  btn.disabled=false;
}
loadDashboard();
</script>
</body>
</html>
