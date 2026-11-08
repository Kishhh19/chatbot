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
<title>Bot Settings – Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/app.css">
<style>
.tab-nav{display:flex;border-bottom:1px solid var(--border);margin-bottom:24px;gap:4px}
.tab-btn{padding:10px 20px;background:transparent;border:none;border-bottom:2px solid transparent;color:var(--text-secondary);font-size:14px;font-weight:500;cursor:pointer;transition:all var(--transition);margin-bottom:-1px}
.tab-btn.active{border-bottom-color:var(--accent);color:var(--text-primary)}
.tab-btn:hover{color:var(--text-primary)}
.mode-option{cursor:pointer;padding:14px 16px;background:var(--bg-secondary);border-radius:var(--radius-md);border:2px solid var(--border);flex:1;min-width:220px;transition:all var(--transition);display:flex;align-items:flex-start;gap:10px}
.mode-option:has(input:checked){border-color:var(--accent);background:var(--accent-dim)}
.mode-option input{margin-top:2px;accent-color:var(--accent)}
.model-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-md);padding:12px}
.model-card .provider{font-size:11px;color:var(--text-muted);margin-bottom:4px}
.model-card .name{font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--accent-hover);word-break:break-all}
.model-card .desc{font-size:12px;color:var(--text-secondary);margin-top:4px}
</style>
</head>
<body>
<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <svg width="18" height="18" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.38-1 1.72V7h1a7 7 0 0 1 7 7H3a7 7 0 0 1 7-7h1V5.72A2 2 0 0 1 10 4a2 2 0 0 1 2-2zM5 14v1a7 7 0 0 0 14 0v-1"/><path d="M9 14v4M15 14v4M12 14v6"/></svg>
      </div>
      <span class="sidebar-brand">Admin Panel</span>
    </div>
    <nav class="admin-nav">
      <a href="dashboard.php" class="nav-item"><span class="nav-icon">&#9632;</span>Dashboard</a>
      <a href="knowledge.php" class="nav-item"><span class="nav-icon">&#9632;</span>Knowledge Base</a>
      <a href="documents.php" class="nav-item"><span class="nav-icon">&#9632;</span>Documents</a>
      <a href="settings.php" class="nav-item active"><span class="nav-icon">&#9632;</span>Bot Settings</a>
      <a href="users.php" class="nav-item"><span class="nav-icon">&#9632;</span>Users</a>
      <div class="divider"></div>
      <a href="../index.php" class="nav-item"><span class="nav-icon">&#9632;</span>Back to Chat</a>
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
      <h1 class="page-title">Bot Settings</h1>
      <p class="page-subtitle">Configure chatbot personality, behavior, and AI connection</p>
    </div>

    <div class="tab-nav">
      <button class="tab-btn active" id="tab-bot" onclick="switchTab('bot')">Chatbot</button>
      <button class="tab-btn" id="tab-ai" onclick="switchTab('ai')">AI API</button>
    </div>

    <!-- Bot Panel -->
    <div id="panel-bot">
      <div id="bot-alert"></div>
      <div class="card mb-4">
        <div class="card-header"><span class="card-title">Identity</span></div>
        <div class="flex gap-3" style="flex-wrap:wrap">
          <div class="form-group" style="flex:1;min-width:200px">
            <label class="form-label">Bot Name</label>
            <input class="form-control" id="bot_name" placeholder="AI Assistant" maxlength="100">
          </div>
          <div class="form-group" style="flex:1;min-width:200px">
            <label class="form-label">Personality</label>
            <select class="form-control" id="bot_personality">
              <option value="professional">Professional</option>
              <option value="friendly">Friendly</option>
              <option value="academic">Academic</option>
              <option value="technical">Technical</option>
              <option value="concise">Concise</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <input class="form-control" id="bot_description" placeholder="Short description shown to users" maxlength="500">
        </div>
        <div class="form-group">
          <label class="form-label">Welcome Message</label>
          <input class="form-control" id="welcome_message" placeholder="Hello! How can I help you?" maxlength="500">
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><span class="card-title">System Instructions</span></div>
        <div class="form-group">
          <textarea class="form-control" id="system_instructions" rows="8" placeholder="Define exactly how the chatbot should behave. Example: You are a helpful assistant for [Organization]. Only answer questions related to [topic]..." style="font-family:monospace;font-size:13px"></textarea>
          <div class="form-hint">This is the core system prompt sent with every AI request. Be specific and clear.</div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><span class="card-title">Knowledge Mode</span></div>
        <div class="flex gap-3" style="flex-wrap:wrap">
          <label class="mode-option" id="mode-kb-only">
            <input type="radio" name="knowledge_mode" value="knowledge_only" id="mode-knowledge-only">
            <div>
              <div style="font-weight:600;font-size:14px">Knowledge Base Only</div>
              <div style="font-size:12px;color:var(--text-muted);margin-top:4px">Answers strictly from your knowledge base. Refuses off-topic questions.</div>
            </div>
          </label>
          <label class="mode-option" id="mode-hybrid">
            <input type="radio" name="knowledge_mode" value="hybrid" id="mode-hybrid">
            <div>
              <div style="font-weight:600;font-size:14px">Knowledge Base + General AI</div>
              <div style="font-size:12px;color:var(--text-muted);margin-top:4px">Prioritizes knowledge base, supplements with general AI knowledge.</div>
            </div>
          </label>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><span class="card-title">Response Settings</span></div>
        <div class="flex gap-3" style="flex-wrap:wrap">
          <div class="form-group" style="flex:1;min-width:160px">
            <label class="form-label">Temperature: <span id="temp-val">0.7</span></label>
            <input type="range" id="temperature" min="0" max="2" step="0.1" value="0.7" style="width:100%;accent-color:var(--accent)">
            <div class="form-hint">0 = precise &nbsp; 1 = balanced &nbsp; 2 = creative</div>
          </div>
          <div class="form-group" style="flex:1;min-width:160px">
            <label class="form-label">Max Response Tokens</label>
            <input class="form-control" type="number" id="max_tokens" min="100" max="8000" value="2000">
          </div>
          <div class="form-group" style="flex:1;min-width:160px">
            <label class="form-label">Max Context Chunks</label>
            <input class="form-control" type="number" id="context_chunks" min="1" max="20" value="5">
            <div class="form-hint">Knowledge snippets injected per query</div>
          </div>
        </div>
        <div class="flex gap-4" style="flex-wrap:wrap;margin-top:8px">
          <label class="flex items-center gap-2" style="cursor:pointer;font-size:14px">
            <input type="checkbox" id="show_sources" style="accent-color:var(--accent)">
            Show knowledge sources to users
          </label>
          <label class="flex items-center gap-2" style="cursor:pointer;font-size:14px">
            <input type="checkbox" id="allow_regenerate" checked style="accent-color:var(--accent)">
            Allow response regeneration
          </label>
        </div>
      </div>

      <button class="btn btn-primary" onclick="saveBotSettings()" id="save-bot-btn">
        <span id="save-bot-spinner" class="spinner spinner-sm hidden"></span>
        Save Bot Settings
      </button>
    </div>

    <!-- AI API Panel -->
    <div id="panel-ai" class="hidden">
      <div id="api-alert"></div>
      <div class="card mb-4">
        <div class="card-header"><span class="card-title">AI Provider Configuration</span></div>
        <div class="form-group">
          <label class="form-label">Provider</label>
          <select class="form-control" id="api_provider" onchange="updateProviderDefaults()">
            <option value="openai">OpenAI</option>
            <option value="anthropic">Anthropic (Claude)</option>
            <option value="openrouter">OpenRouter</option>
            <option value="custom">Custom OpenAI-Compatible</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">API Key</label>
          <input class="form-control" type="password" id="api_key" placeholder="Leave blank to keep existing key" autocomplete="new-password">
          <div class="form-hint" id="key-status">Checking API key status...</div>
        </div>
        <div class="form-group">
          <label class="form-label">Model</label>
          <input class="form-control" id="api_model" placeholder="gpt-4o-mini">
          <div class="form-hint" id="model-hint"></div>
        </div>
        <div class="form-group">
          <label class="form-label">API URL <span class="text-muted">(leave blank to use provider default)</span></label>
          <input class="form-control" id="api_url" placeholder="https://api.openai.com/v1">
        </div>
        <div class="flex gap-3 items-center" style="flex-wrap:wrap;margin-top:4px">
          <button class="btn btn-primary" onclick="saveApiSettings()" id="save-api-btn">
            <span id="save-api-spinner" class="spinner spinner-sm hidden"></span>
            Save API Settings
          </button>
          <button class="btn btn-secondary" onclick="testApi()" id="test-api-btn">Test Connection</button>
          <span id="test-api-result" class="text-sm" style="display:flex;align-items:center"></span>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><span class="card-title">Model Reference</span></div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px">
          <?php
          $models=[
            ['OpenAI','gpt-4o-mini','Fast, affordable, great for most use cases'],
            ['OpenAI','gpt-4o','Most capable GPT-4 model'],
            ['OpenAI','gpt-4-turbo','GPT-4 with 128K context'],
            ['Anthropic','claude-3-5-sonnet-20241022','Claude Sonnet – balanced performance'],
            ['Anthropic','claude-3-haiku-20240307','Claude Haiku – fast and economical'],
            ['OpenRouter','meta-llama/llama-3.1-8b-instruct:free','Llama 3.1 – free tier available'],
          ];
          foreach($models as [$prov,$model,$desc]):?>
          <div class="model-card">
            <div class="provider"><?=e($prov)?></div>
            <div class="name"><?=e($model)?></div>
            <div class="desc"><?=e($desc)?></div>
          </div>
          <?php endforeach;?>
        </div>
      </div>
    </div>
  </main>
</div>

<div id="toast-container"></div>
<script>
function escHtml(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function showToast(msg,type='success'){
  const c=document.getElementById('toast-container'),t=document.createElement('div');
  t.className=`toast toast-${type}`;
  t.innerHTML=`<span>${escHtml(msg)}</span>`;
  c.appendChild(t);setTimeout(()=>t.remove(),3500);
}

function switchTab(tab){
  ['bot','ai'].forEach(t=>{
    document.getElementById('panel-'+t).classList.toggle('hidden',t!==tab);
    const btn=document.getElementById('tab-'+t);
    btn.classList.toggle('active',t===tab);
  });
}

document.getElementById('temperature').addEventListener('input',function(){
  document.getElementById('temp-val').textContent=parseFloat(this.value).toFixed(1);
});

async function loadBotSettings(){
  try{
    const r=await fetch('../api/settings.php?action=get');
    const d=await r.json();if(!d.success)return;
    const v=k=>d.settings[k]?.value||'';
    document.getElementById('bot_name').value=v('bot_name');
    document.getElementById('bot_description').value=v('bot_description');
    document.getElementById('bot_personality').value=v('bot_personality')||'professional';
    document.getElementById('system_instructions').value=v('system_instructions');
    document.getElementById('welcome_message').value=v('welcome_message');
    const mode=v('knowledge_mode')||'hybrid';
    document.querySelector(`input[name="knowledge_mode"][value="${mode}"]`).checked=true;
    const temp=parseFloat(v('temperature')||'0.7');
    document.getElementById('temperature').value=temp;
    document.getElementById('temp-val').textContent=temp.toFixed(1);
    document.getElementById('max_tokens').value=v('max_tokens')||'2000';
    document.getElementById('context_chunks').value=v('context_chunks')||'5';
    document.getElementById('show_sources').checked=v('show_sources')==='true';
    document.getElementById('allow_regenerate').checked=v('allow_regenerate')!=='false';
  }catch(e){console.error('Failed to load settings',e);}
}

async function saveBotSettings(){
  const btn=document.getElementById('save-bot-btn');
  const sp=document.getElementById('save-bot-spinner');
  btn.disabled=true;sp.classList.remove('hidden');
  document.getElementById('bot-alert').innerHTML='';
  const mode=document.querySelector('input[name="knowledge_mode"]:checked')?.value||'hybrid';
  const payload={
    bot_name:document.getElementById('bot_name').value,
    bot_description:document.getElementById('bot_description').value,
    bot_personality:document.getElementById('bot_personality').value,
    system_instructions:document.getElementById('system_instructions').value,
    welcome_message:document.getElementById('welcome_message').value,
    knowledge_mode:mode,
    temperature:document.getElementById('temperature').value,
    max_tokens:document.getElementById('max_tokens').value,
    context_chunks:document.getElementById('context_chunks').value,
    show_sources:document.getElementById('show_sources').checked?'true':'false',
    allow_regenerate:document.getElementById('allow_regenerate').checked?'true':'false',
  };
  try{
    const r=await fetch('../api/settings.php?action=update',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    const d=await r.json();
    if(d.success)showToast('Settings saved successfully.');
    else{document.getElementById('bot-alert').innerHTML=`<div class="alert alert-error">${escHtml(d.error)}</div>`;showToast(d.error,'error');}
  }catch(e){showToast('Network error saving settings.','error');}
  finally{btn.disabled=false;sp.classList.add('hidden');}
}

const providerDefaults={
  openai:{url:'https://api.openai.com/v1',model:'gpt-4o-mini',hint:'Models: gpt-4o-mini, gpt-4o, gpt-4-turbo'},
  anthropic:{url:'https://api.anthropic.com',model:'claude-3-5-sonnet-20241022',hint:'Models: claude-3-5-sonnet-20241022, claude-3-haiku-20240307'},
  openrouter:{url:'https://openrouter.ai/api/v1',model:'meta-llama/llama-3.1-8b-instruct:free',hint:'See openrouter.ai/models for available models'},
  custom:{url:'',model:'',hint:'Enter any OpenAI-compatible API base URL and model name'},
};

function updateProviderDefaults(){
  const p=document.getElementById('api_provider').value;
  const def=providerDefaults[p];
  if(!def)return;
  const currentUrl=document.getElementById('api_url').value;
  const isDefaultUrl=Object.values(providerDefaults).some(d=>d.url===currentUrl)||!currentUrl;
  if(isDefaultUrl)document.getElementById('api_url').value=def.url;
  if(!document.getElementById('api_model').value)document.getElementById('api_model').value=def.model;
  document.getElementById('model-hint').textContent=def.hint;
}

async function loadApiSettings(){
  try{
    const r=await fetch('../api/settings.php?action=api');
    const d=await r.json();if(!d.success)return;
    document.getElementById('api_provider').value=d.settings.provider||'openai';
    document.getElementById('api_model').value=d.settings.model||'gpt-4o-mini';
    document.getElementById('api_url').value=d.settings.api_url||'';
    const statusEl=document.getElementById('key-status');
    if(d.has_key){
      statusEl.style.color='var(--success)';
      statusEl.textContent='API key is configured. Leave blank to keep the existing key.';
    } else {
      statusEl.style.color='var(--warning)';
      statusEl.textContent='No API key configured. Enter your key below.';
    }
    updateProviderDefaults();
  }catch(e){console.error('Failed to load API settings',e);}
}

async function saveApiSettings(){
  const btn=document.getElementById('save-api-btn');
  const sp=document.getElementById('save-api-spinner');
  btn.disabled=true;sp.classList.remove('hidden');
  document.getElementById('api-alert').innerHTML='';
  document.getElementById('test-api-result').textContent='';
  const payload={
    provider:document.getElementById('api_provider').value,
    api_key:document.getElementById('api_key').value,
    model:document.getElementById('api_model').value,
    api_url:document.getElementById('api_url').value
  };
  try{
    const r=await fetch('../api/settings.php?action=update_api',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    const d=await r.json();
    if(d.success){
      showToast(d.message||'API settings saved.');
      document.getElementById('api_key').value='';
      loadApiSettings();
    } else {
      document.getElementById('api-alert').innerHTML=`<div class="alert alert-error">${escHtml(d.error)}</div>`;
    }
  }catch(e){document.getElementById('api-alert').innerHTML='<div class="alert alert-error">Network error. Could not save settings.</div>';}
  finally{btn.disabled=false;sp.classList.add('hidden');}
}

async function testApi(){
  const btn=document.getElementById('test-api-btn');
  const res=document.getElementById('test-api-result');
  btn.disabled=true;
  res.style.color='var(--text-muted)';
  res.textContent='Testing connection...';
  try{
    const r=await fetch('../api/settings.php?action=test_api');
    const d=await r.json();
    if(d.success){
      res.style.color='var(--success)';
      res.textContent='Connection successful!';
    } else {
      res.style.color='var(--danger)';
      res.textContent=d.error||'Connection failed.';
    }
  }catch{
    res.style.color='var(--danger)';
    res.textContent='Network error during test.';
  }
  btn.disabled=false;
}

loadBotSettings();
loadApiSettings();
</script>
</body>
</html>
