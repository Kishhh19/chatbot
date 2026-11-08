<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
session_init(); $user = require_admin(true); apply_security_headers();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_active') {
        $uid = sanitize_int($_POST['user_id'] ?? 0, 1);
        $active = (int)(bool)$_POST['is_active'];
        if ($uid == $user['id']) { json_error('Cannot deactivate yourself.'); }
        db_query("UPDATE users SET is_active=? WHERE id=?", [$active, $uid]);
        json_response(['success' => true]);
    }
    if ($action === 'change_role') {
        $uid = sanitize_int($_POST['user_id'] ?? 0, 1);
        $role = in_array($_POST['role'], ['admin','user']) ? $_POST['role'] : 'user';
        if ($uid == $user['id']) { json_error('Cannot change your own role.'); }
        db_query("UPDATE users SET role=? WHERE id=?", [$role, $uid]);
        json_response(['success' => true]);
    }
    if ($action === 'reset_password') {
        $uid = sanitize_int($_POST['user_id'] ?? 0, 1);
        $newPw = $_POST['new_password'] ?? '';
        if (strlen($newPw) < 8) { json_error('Password must be at least 8 characters.'); }
        $hash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        db_query("UPDATE users SET password=? WHERE id=?", [$hash, $uid]);
        log_audit($user['id'], 'reset_password', 'users', $uid);
        json_response(['success' => true]);
    }
    json_error('Invalid action');
}

$users = db_fetch_all("SELECT id,username,email,display_name,role,is_active,last_login,created_at FROM users ORDER BY created_at DESC");
$userInitial = mb_strtoupper(mb_substr($user['display_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Users – Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <svg width="18" height="18" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <span class="sidebar-brand">Admin Panel</span>
    </div>
    <nav class="admin-nav">
      <a href="dashboard.php" class="nav-item">Dashboard</a>
      <a href="knowledge.php" class="nav-item">Knowledge Base</a>
      <a href="documents.php" class="nav-item">Documents</a>
      <a href="settings.php" class="nav-item">Bot Settings</a>
      <a href="users.php" class="nav-item active">Users</a>
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
        <h1 class="page-title">Users</h1>
        <p class="page-subtitle"><?= count($users) ?> registered users</p>
      </div>
      <button class="btn btn-primary" onclick="openRegister()">Add User</button>
    </div>
    <div id="page-alert"></div>

    <div class="card">
      <div class="table-wrap">
        <table>
          <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Joined</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach($users as $u): ?>
          <tr>
            <td>
              <div class="flex items-center gap-2">
                <div class="avatar" style="width:30px;height:30px;font-size:12px"><?=e(mb_strtoupper(mb_substr($u['display_name'],0,1)))?></div>
                <div><div style="font-weight:600;font-size:13px"><?=e($u['display_name'])?></div><div style="font-size:11px;color:var(--text-muted)">@<?=e($u['username'])?></div></div>
              </div>
            </td>
            <td class="text-sm"><?=e($u['email'])?></td>
            <td>
              <select class="form-control" style="width:auto;font-size:12px;padding:4px 8px" onchange="changeRole(<?=$u['id']?>,this.value)" <?=$u['id']==$user['id']?'disabled':''?>>
                <option value="user" <?=$u['role']==='user'?'selected':''?>>User</option>
                <option value="admin" <?=$u['role']==='admin'?'selected':''?>>Admin</option>
              </select>
            </td>
            <td>
              <span class="badge <?=$u['is_active']?'badge-green':'badge-red'?>"><?=$u['is_active']?'Active':'Inactive'?></span>
            </td>
            <td class="text-muted text-sm"><?=$u['last_login']?date('M j, Y',strtotime($u['last_login'])):'Never'?></td>
            <td class="text-muted text-sm"><?=date('M j, Y',strtotime($u['created_at']))?></td>
            <td>
              <div class="flex gap-2">
                <?php if($u['id'] != $user['id']): ?>
                <button class="btn btn-secondary btn-sm" onclick="toggleActive(<?=$u['id']?>,<?=$u['is_active']?0:1?>)">
                  <?=$u['is_active']?'Deactivate':'Activate'?>
                </button>
                <button class="btn btn-ghost btn-sm" onclick="openResetPw(<?=$u['id']?>,'<?=e($u['username'])?>',this)">Reset Password</button>
                <?php else: ?>
                <span class="text-muted text-sm">Current user</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- Reset password modal -->
<div class="modal-overlay" id="reset-pw-modal">
  <div class="modal" style="max-width:380px">
    <div class="modal-header"><span class="modal-title">Reset Password</span><button class="btn-icon" onclick="closeModal('reset-pw-modal')">✕</button></div>
    <p class="text-secondary mb-3" id="reset-pw-for" style="font-size:14px"></p>
    <div class="form-group">
      <label class="form-label">New Password</label>
      <input class="form-control" type="password" id="new-password" placeholder="Min 8 characters" minlength="8">
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('reset-pw-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="doResetPw()">Reset Password</button>
    </div>
  </div>
</div>

<!-- Register modal -->
<div class="modal-overlay" id="register-modal">
  <div class="modal" style="max-width:420px">
    <div class="modal-header"><span class="modal-title">Add User</span><button class="btn-icon" onclick="closeModal('register-modal')">✕</button></div>
    <div id="reg-alert"></div>
    <div class="form-group"><label class="form-label">Username</label><input class="form-control" id="new-username" placeholder="johndoe"></div>
    <div class="form-group"><label class="form-label">Display Name</label><input class="form-control" id="new-display" placeholder="John Doe"></div>
    <div class="form-group"><label class="form-label">Email</label><input class="form-control" type="email" id="new-email" placeholder="john@example.com"></div>
    <div class="form-group"><label class="form-label">Password</label><input class="form-control" type="password" id="new-pw" placeholder="Min 8 characters"></div>
    <div class="form-group"><label class="form-label">Role</label><select class="form-control" id="new-role"><option value="user">User</option><option value="admin">Admin</option></select></div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('register-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="doRegister()">Create Account</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>
<script>
let resetPwUserId=null;
function escHtml(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function showToast(msg,type='success'){
  const c=document.getElementById('toast-container'),t=document.createElement('div');
  t.className=`toast toast-${type}`;
  t.innerHTML=`<span>${escHtml(msg)}</span>`;
  c.appendChild(t);setTimeout(()=>t.remove(),3500);
}
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}

async function post(data){
  const fd=new FormData();Object.entries(data).forEach(([k,v])=>fd.append(k,v));
  const r=await fetch('users.php',{method:'POST',body:fd});return r.json();
}

async function toggleActive(uid,state){
  const d=await post({action:'toggle_active',user_id:uid,is_active:state});
  if(d.success){showToast(state?'User activated':'User deactivated');setTimeout(()=>location.reload(),800);}
  else showToast(d.error||'Failed','error');
}

async function changeRole(uid,role){
  const d=await post({action:'change_role',user_id:uid,role});
  if(d.success)showToast('Role updated');
  else showToast(d.error||'Failed','error');
}

function openResetPw(uid,username){
  resetPwUserId=uid;
  document.getElementById('reset-pw-for').textContent='Resetting password for: @'+username;
  document.getElementById('new-password').value='';
  openModal('reset-pw-modal');
  setTimeout(()=>document.getElementById('new-password').focus(),100);
}

async function doResetPw(){
  const pw=document.getElementById('new-password').value;
  if(pw.length<8){showToast('Password must be at least 8 characters.','error');return;}
  const d=await post({action:'reset_password',user_id:resetPwUserId,new_password:pw});
  if(d.success){closeModal('reset-pw-modal');showToast('Password reset!');}
  else showToast(d.error||'Failed','error');
}

function openRegister(){
  document.getElementById('reg-alert').innerHTML='';
  ['new-username','new-display','new-email','new-pw'].forEach(id=>document.getElementById(id).value='');
  openModal('register-modal');
}

async function doRegister(){
  const un=document.getElementById('new-username').value.trim();
  const dn=document.getElementById('new-display').value.trim();
  const em=document.getElementById('new-email').value.trim();
  const pw=document.getElementById('new-pw').value;
  const role=document.getElementById('new-role').value;
  if(!un||!em||!pw){document.getElementById('reg-alert').innerHTML='<div class="alert alert-error">All fields except display name are required.</div>';return;}
  try{
    const r=await fetch('../api/auth.php?action=register',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({username:un,display_name:dn,email:em,password:pw})});
    const d=await r.json();
    if(d.success){
      closeModal('register-modal');showToast('User created!');setTimeout(()=>location.reload(),800);
    } else {
      document.getElementById('reg-alert').innerHTML=`<div class="alert alert-error">${escHtml(d.error)}</div>`;
    }
  }catch{document.getElementById('reg-alert').innerHTML='<div class="alert alert-error">Network error</div>';}
}

document.addEventListener('keydown',e=>{if(e.key==='Escape')document.querySelectorAll('.modal-overlay.open').forEach(m=>m.classList.remove('open'));});
</script>
</body>
</html>
