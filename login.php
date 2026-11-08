<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff sign in · Sabinox</title>
<meta name="description" content="Sign in to manage the Sabinox café helper.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css">
<style>
.auth-page{min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg-primary);padding:20px;position:relative;overflow:hidden}
.auth-bg{position:absolute;inset:0;background:radial-gradient(ellipse at 30% 20%,rgba(182,109,68,0.16) 0%,transparent 60%),radial-gradient(ellipse at 70% 80%,rgba(104,62,45,0.12) 0%,transparent 60%)}
.auth-card{width:100%;max-width:420px;position:relative;z-index:1}
.auth-logo{display:flex;align-items:center;gap:12px;justify-content:center;margin-bottom:32px}
.auth-logo-icon{width:48px;height:48px;background:linear-gradient(135deg,var(--accent),#683e2d);border-radius:14px;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 24px rgba(182,109,68,0.25)}
.auth-logo-text{font-size:22px;font-weight:700}
.auth-title{font-size:22px;font-weight:700;margin-bottom:6px}
.auth-subtitle{color:var(--text-secondary);font-size:14px;margin-bottom:28px}
.auth-footer{text-align:center;margin-top:20px;font-size:13px;color:var(--text-muted)}
.password-wrap{position:relative}
.password-wrap .form-control{padding-right:42px}
.toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px;display:flex;align-items:center}
.toggle-pw:hover{color:var(--text-primary)}
</style>
</head>
<body>
<div class="auth-page">
  <div class="auth-bg"></div>
  <div class="auth-card">
    <div class="auth-logo">
      <div class="auth-logo-icon">
        <svg width="22" height="22" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.38-1 1.72V7h1a7 7 0 0 1 7 7H3a7 7 0 0 1 7-7h1V5.72A2 2 0 0 1 10 4a2 2 0 0 1 2-2zM5 14v1a7 7 0 0 0 14 0v-1"/><path d="M9 14v4M15 14v4M12 14v6"/></svg>
      </div>
      <span class="auth-logo-text">Sabinox</span>
    </div>
    <div class="card">
      <div id="alert-container"></div>
      <!-- Login Form -->
      <div id="login-panel">
        <h1 class="auth-title">Welcome back</h1>
        <p class="auth-subtitle">Sign in to manage Sabino’s café helper</p>
        <form id="login-form" novalidate>
          <div class="form-group">
            <label class="form-label" for="credential">Email or Username</label>
            <input class="form-control" type="text" id="credential" name="credential" placeholder="you@example.com" autocomplete="username" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <div class="password-wrap">
              <input class="form-control" type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
              <button type="button" class="toggle-pw" onclick="togglePw('password')">Show</button>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-full" id="login-btn">
            <span id="login-spinner" class="spinner spinner-sm hidden"></span>
            <span>Sign In</span>
          </button>
        </form>
        <div class="auth-footer">
          Don't have an account? <a href="#" onclick="switchPanel('register')">Create one</a> · <a href="index.php">Back to Sabinox</a>
        </div>
      </div>
      <!-- Register Form -->
      <div id="register-panel" class="hidden">
        <h1 class="auth-title">Create account</h1>
        <p class="auth-subtitle">Create a Sabinox staff account</p>
        <form id="register-form" novalidate>
          <div class="form-group">
            <label class="form-label" for="reg-username">Username</label>
            <input class="form-control" type="text" id="reg-username" placeholder="johndoe" autocomplete="username" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="reg-display">Display Name</label>
            <input class="form-control" type="text" id="reg-display" placeholder="John Doe">
          </div>
          <div class="form-group">
            <label class="form-label" for="reg-email">Email</label>
            <input class="form-control" type="email" id="reg-email" placeholder="you@example.com" autocomplete="email" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="reg-password">Password <span class="text-muted">(min 8 chars)</span></label>
            <div class="password-wrap">
              <input class="form-control" type="password" id="reg-password" placeholder="••••••••" autocomplete="new-password" required>
              <button type="button" class="toggle-pw" onclick="togglePw('reg-password')">Show</button>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-full" id="register-btn">
            <span id="register-spinner" class="spinner spinner-sm hidden"></span>
            <span>Create Account</span>
          </button>
        </form>
        <div class="auth-footer">
          Already have an account? <a href="#" onclick="switchPanel('login')">Sign in</a>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
function switchPanel(panel){
  document.getElementById('login-panel').classList.toggle('hidden', panel!=='login');
  document.getElementById('register-panel').classList.toggle('hidden', panel!=='register');
  document.getElementById('alert-container').innerHTML='';
}
function togglePw(id){
  const el=document.getElementById(id);
  const isPw=el.type==='password';
  el.type=isPw?'text':'password';
  event.target.textContent=isPw?'Hide':'Show';
}
function showAlert(msg,type='error'){
  document.getElementById('alert-container').innerHTML=
    `<div class="alert alert-${type}">${msg}</div>`;
}
function setLoading(btnId,spinnerId,state){
  const btn=document.getElementById(btnId);
  const sp=document.getElementById(spinnerId);
  btn.disabled=state;
  sp.classList.toggle('hidden',!state);
}

document.getElementById('login-form').addEventListener('submit',async e=>{
  e.preventDefault();
  const cred=document.getElementById('credential').value.trim();
  const pass=document.getElementById('password').value;
  if(!cred||!pass){showAlert('Please fill in all fields.');return;}
  setLoading('login-btn','login-spinner',true);
  try{
    const r=await fetch('api/auth.php?action=login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({credential:cred,password:pass})});
    const d=await r.json();
    if(d.success){window.location.href=d.redirect||'index.php';}
    else showAlert(d.error||'Login failed.');
  }catch{showAlert('Network error. Please try again.');}
  finally{setLoading('login-btn','login-spinner',false);}
});

document.getElementById('register-form').addEventListener('submit',async e=>{
  e.preventDefault();
  const un=document.getElementById('reg-username').value.trim();
  const dn=document.getElementById('reg-display').value.trim();
  const em=document.getElementById('reg-email').value.trim();
  const pw=document.getElementById('reg-password').value;
  if(!un||!em||!pw){showAlert('Please fill in all required fields.');return;}
  if(pw.length<8){showAlert('Password must be at least 8 characters.');return;}
  setLoading('register-btn','register-spinner',true);
  try{
    const r=await fetch('api/auth.php?action=register',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({username:un,display_name:dn,email:em,password:pw})});
    const d=await r.json();
    if(d.success){showAlert(d.message,'success');setTimeout(()=>switchPanel('login'),1500);}
    else showAlert(d.error||'Registration failed.');
  }catch{showAlert('Network error. Please try again.');}
  finally{setLoading('register-btn','register-spinner',false);}
});
</script>
</body>
</html>
