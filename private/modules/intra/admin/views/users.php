<?php

global $language, $baseUrl;
?>
<section class="p-4">
  <h2 class="mb-3"><?= htmlspecialchars($language['admin_users']['users'] ?? 'Usuarios') ?></h2>

  <div class="filters" style="display:grid; gap:8px; grid-template-columns: 1fr 160px 160px 120px;">
    <input id="f-q" type="search" placeholder='<?= htmlspecialchars($language['admin_users']['search_ph'] ?? 'Buscar: usuario, nombre, apellidos, NIF, email…') ?>'/>
    <select id="f-role">
      <option value=""><?= htmlspecialchars($language['admin_users']['all_roles'] ?? 'Todos los roles') ?></option>
      <option value="user"  ${u.roles==='user' || u.roles==='usuario' ? 'selected' : ''}><?= htmlspecialchars($language['admin_users']['user'] ?? 'Usuario') ?></option>
      <option value="admin" ${u.roles==='admin' ? 'selected' : ''}><?= htmlspecialchars($language['admin_users']['admin'] ?? 'Admin') ?></option>
    </select>
    <select id="f-sort">
      <option value="usuario|ASC"><?= htmlspecialchars($language['admin_users']['sort_user_asc'] ?? 'Orden: Usuario ↑') ?></option>
      <option value="usuario|DESC"><?= htmlspecialchars($language['admin_users']['sort_user_desc'] ?? 'Orden: Usuario ↓') ?></option>
      <option value="roles|ASC"><?= htmlspecialchars($language['admin_users']['sort_role_asc'] ?? 'Orden: Rol ↑') ?></option>
      <option value="roles|DESC"><?= htmlspecialchars($language['admin_users']['sort_role_desc'] ?? 'Orden: Rol ↓') ?></option>
    </select>
    <select id="f-per">
      <option value="20" selected><?= htmlspecialchars($language['admin_users']['20_per_page'] ?? '20 orrialdeko ') ?></option>
      <option value="50"><?= htmlspecialchars($language['admin_users']['50_per_page'] ?? '50 orrialdeko ') ?></option>
      <option value="100"><?= htmlspecialchars($language['admin_users']['100_per_page'] ?? '100 orrialdeko ') ?></option>
    </select>
  </div>

  <div id="users-wrap" class="mt-3">
    <table id="users-table" class="table" style="width:100%; border-collapse:collapse;">
      <thead>
        <tr>
          <th style="text-align:left;"><?= htmlspecialchars($language['admin_users']['user'] ?? 'Usuario') ?></th>
          <th><?= htmlspecialchars($language['admin_users']['name'] ?? 'Nombre') ?></th>
          <th><?= htmlspecialchars($language['admin_users']['surname'] ?? 'Apellidos') ?></th>
          <th><?= htmlspecialchars($language['admin_users']['nif'] ?? 'NIF') ?></th>
          <th><?= htmlspecialchars($language['admin_users']['email'] ?? 'Email') ?></th>
          <th><?= htmlspecialchars($language['admin_users']['role'] ?? 'Rol') ?></th>
          <th style="width:140px;"><?= htmlspecialchars($language['admin_users']['action'] ?? 'Acción') ?></th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
    <div id="pager" class="mt-2" style="display:flex; gap:8px; align-items:center;">
      <button id="prev">« <?= htmlspecialchars($language['admin_users']['prev'] ?? 'Anterior') ?></button>
      <span id="pageinfo"></span>
      <button id="next"><?= htmlspecialchars($language['admin_users']['next'] ?? 'Siguiente') ?> »</button>
    </div>
  </div>
</section>

<script>
(function(){
  const API_BASE = window.location.pathname + '?url=admin';
  const API_LIST = API_BASE + '&ajax=users_data';
  const API_UPD  = window.location.pathname + '?url=admin';

  let state = { page: 1, per: 20, sort: 'usuario', dir: 'ASC', role: '', q: '' };
  const $q = document.getElementById('f-q');
  const $role = document.getElementById('f-role');
  const $sort = document.getElementById('f-sort');
  const $per = document.getElementById('f-per');
  const $tbody = document.querySelector('#users-table tbody');
  const $pageinfo = document.getElementById('pageinfo');
  const $prev = document.getElementById('prev');
  const $next = document.getElementById('next');

  function debounce(fn, ms){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); } }
  const refetchDebounced = debounce(fetchAndRender, 250);

  $q.addEventListener('input', ()=>{ state.q = $q.value.trim(); state.page = 1; refetchDebounced(); });
  $role.addEventListener('change', ()=>{ state.role = $role.value; state.page = 1; fetchAndRender(); });
  $sort.addEventListener('change', ()=>{
    const [s, d] = $sort.value.split('|'); state.sort = s; state.dir = d; state.page = 1; fetchAndRender();
  });
  $per.addEventListener('change', ()=>{ state.per = parseInt($per.value||20,10); state.page = 1; fetchAndRender(); });

  $prev.addEventListener('click', ()=>{ if (state.page>1) { state.page--; fetchAndRender(); } });
  $next.addEventListener('click', ()=>{ state.page++; fetchAndRender(); });

  async function fetchAndRender(){
    const url = new URL(API_LIST, window.location.origin);
    Object.entries({
      page: state.page, per: state.per, sort: state.sort, dir: state.dir, role: state.role, q: state.q
    }).forEach(([k,v])=> url.searchParams.set(k, v));

    console.log('Llamando a:', url.href);

    const res = await fetch(url, { credentials: 'same-origin' });
    const ctype = (res.headers.get('content-type') || '').toLowerCase();
    const body  = await res.text();

    if (!res.ok) {
      console.error('HTTP', res.status, body.slice(0,500));
      $tbody.innerHTML = '<tr><td colspan="7"><?= htmlspecialchars($language['admin_users']['error_loading'] ?? 'Error cargando usuarios.') ?></td></tr>';
      return;
    }

    if (!ctype.includes('application/json')) {
      console.error('Respuesta NO JSON. content-type:', ctype, 'body:', body.slice(0,500));
      $tbody.innerHTML = '<tr><td colspan="7"><?= htmlspecialchars($language['admin_users']['error_loading'] ?? 'Error cargando usuarios.') ?></td></tr>';
      return;
    }

    let data;
    try {
      data = JSON.parse(body);
    } catch (e) {
      console.error('JSON inválido:', e, body.slice(0,500));
      $tbody.innerHTML = '<tr><td colspan="7"><?= htmlspecialchars($language['admin_users']['error_loading'] ?? 'Error cargando usuarios.') ?></td></tr>';
      return;
    }

    console.log('users data', data);

    const totalPages = Math.max(1, Math.ceil(data.total / state.per));
    if (state.page > totalPages) state.page = totalPages;
    $pageinfo.textContent = `Página ${state.page} de ${totalPages} · ${data.total} usuarios`;

    $prev.disabled = (state.page<=1);
    $next.disabled = (state.page>=totalPages);

    $tbody.innerHTML = '';
    (data.rows||[]).forEach(u=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td style="text-align:left;">${escapeHTML(u.usuario ?? '')}</td>
        <td>${escapeHTML(u.nombre ?? '')}</td>
        <td>${escapeHTML(u.apel ?? '')}</td>
        <td>${escapeHTML(u.nif ?? '')}</td>
        <td>${escapeHTML(u.email ?? '')}</td>
        <td>
          <select class="role-select" data-id="${u.id}">
            <option value="user" ${u.roles==='user'?'selected':''}><?= htmlspecialchars($language['admin_users']['user'] ?? 'Usuario') ?></option>
            <option value="admin" ${u.roles==='admin'?'selected':''}><?= htmlspecialchars($language['admin_users']['admin'] ?? 'Admin') ?></option>
          </select>
        </td>
        <td><button class="btn-update" data-id="${u.id}"><?= htmlspecialchars($language['admin_users']['update'] ?? 'Actualizar') ?></button></td>
      `;
      $tbody.appendChild(tr);
    });

    document.querySelectorAll('.btn-update').forEach(btn=>{
      btn.addEventListener('click', async (e)=>{
        const id = btn.getAttribute('data-id');
        const sel = btn.closest('tr').querySelector('.role-select');
        const role = sel.value;

        btn.disabled = true;

        const form = new URLSearchParams();
        form.set('__action__', 'users_update_role');
        form.set('id', id);
        form.set('role', role);
        const resp = await fetch(API_UPD, {
          method:'POST',
          headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body: form.toString(),
          credentials:'same-origin'
        });
        if (!resp.ok) {
          alert('Error HTTP ' + resp.status);
          btn.disabled = false;
          return;
        }

        const j = await resp.json();
        btn.disabled = false;
        alert(j.ok ? '<?= htmlspecialchars($language['admin_users']['role_updated'] ?? 'Rol actualizado correctamente.') ?>' : ('<?= htmlspecialchars($language['admin_users']['error_role'] ?? 'Error, no se pudo actualizar el rol: ') ?>' + (j.msg)));

      });
    });
  }

  function escapeHTML(s){ return String(s).replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

  fetchAndRender();
})();
</script>
