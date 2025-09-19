</div> <!-- end main -->
<div class="popup" id="popup">
  <div class="box" role="dialog" aria-modal="true" aria-labelledby="popupTitle">
    <div class="close" id="popupClose">&times;</div>
    <div id="popup-data"></div>
  </div>
</div>

<script>
function escapeHtml(str){
  if(str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#039;');
}
function resolveImagePath(path){
  if(!path) return 'https://via.placeholder.com/140';
  if(/^https?:\/\//i.test(path) || path.startsWith('/')) return path;
  return '../' + path.replace(/^\/+/, '');
}
function openRecordPopup(data){
  window.currentRecord = data;
  const container = document.getElementById('popup-data');
  const imgSrc = resolveImagePath(data.profile_image || '');
  const html = `<h3 id="popupTitle">${escapeHtml(data.full_name)}</h3>
    <div class="info-row">
      <div style="flex:0 0 140px;text-align:center;margin-right:12px;">
        <img src="${escapeHtml(imgSrc)}" style="width:120px;height:120px;object-fit:cover;border-radius:8px;display:block;margin:0 auto 8px;">
      </div>
      <div class="info">
        <div><strong>Name:</strong> ${escapeHtml(data.full_name)}</div>
        <div><strong>Phone:</strong> ${escapeHtml(data.phone)}</div>
        <div><strong>Class:</strong> ${escapeHtml(data.class)}</div>
        <div><strong>School:</strong> ${escapeHtml(data.school)}</div>
        <div><strong>Place:</strong> ${escapeHtml(data.place)}</div>
        <div><strong>Age:</strong> ${escapeHtml(data.age)}</div>
        <div style="margin-top:8px;"><strong>Knowledge:</strong><div style="margin-top:6px;color:var(--muted)">${escapeHtml(data.knowledge)}</div></div>
        <div style="margin-top:8px;"><strong>DOB:</strong> ${escapeHtml(data.dob)}</div>
      </div>
    </div>
    <div style="text-align:right;margin-top:12px;">
      <button class="print-btn" onclick="printRecord()">🖨️ Print</button>
      <a href="edit.php?id=${encodeURIComponent(data.id)}"><button class="btn edit">✏ Edit</button></a>
    </div>`;
  container.innerHTML = html;
  document.getElementById('popup').style.display = 'flex';
}
function printRecord(){
  const data = window.currentRecord;
  if(!data) return alert('No record selected.');
  const html = `<html><head><title>${escapeHtml(data.full_name)}</title></head>
  <body>${document.getElementById('popup-data').innerHTML}</body></html>`;
  const w = window.open();
  w.document.write(html);
  w.document.close();
  w.print();
}
document.getElementById('popupClose').onclick = ()=>document.getElementById('popup').style.display='none';
window.onclick = e => { if(e.target.id=='popup') document.getElementById('popup').style.display='none'; }
</script>
</body>
</html>
