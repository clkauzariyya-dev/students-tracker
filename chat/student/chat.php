<?php
// student/chat.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    header('Location: ../public/index.php');
    exit;
}

$user = current_user($conn);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Class Chat — <?=h($user['name'])?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    #messages { height: 60vh; overflow:auto; background:#fff; padding:15px; border:1px solid #ddd; border-radius:6px;}
    .msg { margin-bottom:12px; }
    .msg .meta { font-size:12px; color:#666; }
    .mymsg { background: #e9f7ef; padding:10px; border-radius:8px; display:inline-block; }
    .othermsg { background: #f7f7f7; padding:10px; border-radius:8px; display:inline-block; }
    .img-thumb { max-width:220px; max-height:220px; border-radius:6px; display:block; margin-top:8px; }
  </style>
</head>
<body class="bg-light">
<div class="container py-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h5>Classroom Public Chat</h5>
    <div>
      <strong><?=h($user['name'])?></strong> (<?=h($user['mobile'])?>)
      <a href="../logout.php" class="btn btn-sm btn-outline-secondary ms-2">Logout</a>
    </div>
  </div>

  <div id="messages" class="mb-3"></div>

  <form id="sendForm" enctype="multipart/form-data">
    <div class="row g-2 align-items-center">
      <div class="col-md-7">
        <input type="text" name="message" id="messageInput" class="form-control" placeholder="Type message or paste link">
      </div>
      <div class="col-md-3">
        <input type="file" id="imageInput" name="image" class="form-control" accept="image/*">
        <div class="form-text">Images ≤ 5 MB.</div>
      </div>
      <div class="col-md-2 d-grid">
        <button type="submit" class="btn btn-primary">Send</button>
      </div>
    </div>
  </form>

  <hr>
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <button id="showArchiveBtn" class="btn btn-sm btn-outline-info">View Archive</button>
    </div>
    <small class="text-muted">Only current month's messages shown. Older months are archived and can be viewed from archive.</small>
  </div>

  <div id="archiveModal" class="mt-3" style="display:none;">
    <div class="card">
      <div class="card-body">
        <h6>Archived months</h6>
        <div id="archiveMonths" class="mb-2"></div>
        <div id="archiveMessages"></div>
        <button id="closeArchive" class="btn btn-sm btn-secondary mt-2">Close</button>
      </div>
    </div>
  </div>

</div>

<script>
let lastId = 0;
const messagesDiv = document.getElementById('messages');

function renderMessage(msg) {
    const me = <?=json_encode($user['id'])?>;
    const wrapper = document.createElement('div');
    wrapper.className = 'msg';
    const meta = document.createElement('div');
    meta.className = 'meta';
    meta.innerText = msg.name + ' · ' + msg.created_at;
    const content = document.createElement('div');
    content.className = (msg.user_id == me) ? 'mymsg' : 'othermsg';
    if (msg.message) {
        // Link auto-detect: if starts with http
        const m = document.createElement('div');
        const text = msg.message;
        if (/^https?:\/\//i.test(text)) {
            const a = document.createElement('a');
            a.href = text;
            a.target = '_blank';
            a.rel = 'noopener';
            a.innerText = text;
            m.appendChild(a);
        } else {
            m.innerText = text;
        }
        content.appendChild(m);
    }
    if (msg.image_path) {
        const img = document.createElement('img');
        img.src = msg.image_path;
        img.className = 'img-thumb';
        content.appendChild(img);
    }
    wrapper.appendChild(meta);
    wrapper.appendChild(content);
    return wrapper;
}

function fetchMessages() {
    fetch('../api/fetch_messages.php?last_id=' + lastId)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            data.messages.forEach(m => {
                const el = renderMessage(m);
                messagesDiv.appendChild(el);
                lastId = Math.max(lastId, parseInt(m.id));
            });
            // keep scroll at bottom
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        })
        .catch(e => console.error(e));
}

// initial load
fetchMessages();
// polling every 2s
setInterval(fetchMessages, 2000);

// handle send
document.getElementById('sendForm').addEventListener('submit', function(e){
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    // If image present, upload via upload_image first
    const fileInput = document.getElementById('imageInput');
    if (fileInput.files && fileInput.files.length > 0) {
        const fd = new FormData();
        fd.append('image', fileInput.files[0]);
        fetch('../api/upload_image.php', { method: 'POST', body: fd})
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    formData.append('image_path', res.path);
                    sendMessage(formData);
                    fileInput.value = '';
                } else {
                    alert('Image upload failed: ' + res.error);
                }
            })
            .catch(e => alert('Upload error'));
    } else {
        sendMessage(formData);
    }
});

function sendMessage(formData) {
    fetch('../api/send_message.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (!res.success) alert('Send failed: ' + (res.error || 'unknown'));
            else {
                document.getElementById('messageInput').value = '';
                // Immediately append message (optimistic)
                // fetchMessages will pick it up too
                fetchMessages();
            }
        });
}

// Archive view
document.getElementById('showArchiveBtn').addEventListener('click', function(){
    const modal = document.getElementById('archiveModal');
    modal.style.display = 'block';
    fetch('../api/fetch_archive_months.php')
        .then(r => r.json())
        .then(res => {
            const cont = document.getElementById('archiveMonths');
            cont.innerHTML = '';
            if (res.success && res.months.length) {
                res.months.forEach(m => {
                    const btn = document.createElement('button');
                    btn.className = 'btn btn-sm btn-outline-primary me-2 mb-1';
                    btn.innerText = m.label;
                    btn.onclick = () => {
                        fetch('../api/fetch_archive.php?year='+m.year+'&month='+m.month)
                          .then(r => r.json())
                          .then(res2 => {
                              const box = document.getElementById('archiveMessages');
                              box.innerHTML = '';
                              if (res2.success) {
                                  res2.messages.forEach(msg => {
                                      box.appendChild(renderMessage(msg));
                                  });
                              } else {
                                  box.innerText = 'No messages';
                              }
                          });
                    };
                    cont.appendChild(btn);
                });
            } else {
                cont.innerText = 'No archived months.';
            }
        });
});
document.getElementById('closeArchive').addEventListener('click', function(){
    document.getElementById('archiveModal').style.display = 'none';
});
</script>
</body>
</html>
