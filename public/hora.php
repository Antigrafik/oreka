<?php
// hora.php
// Opcional: fija la zona horaria del servidor (ajústala si te conviene)
date_default_timezone_set('Europe/Madrid');

// Timestamp del servidor al cargar la página
$serverNow = time();
// Cadena legible para mostrar al cargar
$serverNowStr = date('Y-m-d H:i:s');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Reloj (temporal)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root { --c:#c00; --bg:#fff; --txt:#222; }
    html,body{margin:0;padding:0;background:var(--bg);color:var(--txt);font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,"Helvetica Neue",Arial,sans-serif}
    .wrap{min-height:100vh;display:grid;place-items:center}
    .clock{
      border:1px solid var(--c); border-radius:14px; padding:24px 28px;
      box-shadow:0 6px 18px rgba(0,0,0,.06);
      text-align:center; max-width:520px; width:calc(100% - 32px);
    }
    h1{margin:.2rem 0 1rem; font-size:1.25rem; color:var(--c)}
    .big{font-variant-numeric:tabular-nums; letter-spacing:.02em; font-size:2.2rem; font-weight:700}
    .small{opacity:.7; margin-top:.5rem}
    .row{display:flex; gap:10px; justify-content:center; align-items:baseline; flex-wrap:wrap}
    .pill{border:1px solid var(--c); border-radius:999px; padding:.2rem .6rem; font-size:.85rem}
    .muted{opacity:.6}
    button{
      margin-top:14px; border:1px solid var(--c); background:#fff; color:#000;
      border-radius:10px; padding:.5rem .9rem; cursor:pointer;
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="clock">
      <h1>Hora actual</h1>
      <div class="big" id="clock"><?= htmlspecialchars($serverNowStr) ?></div>
      <div class="row small" style="margin-top:.6rem">
        <span class="pill">Zona servidor: <strong><?= htmlspecialchars(date_default_timezone_get()) ?></strong></span>
        <span class="pill muted" id="status">sincronizado</span>
      </div>
      <button id="resync">Resincronizar con servidor</button>
      <div class="small muted">Este reloj toma la hora del servidor al cargar y avanza en el navegador.</div>
    </div>
  </div>

  <script>
    (function(){
      // Timestamp inicial del servidor (segundos)
      const serverEpoch = <?= (int)$serverNow ?> * 1000; // ms
      // Epoch de cliente cuando recibimos la página
      const clientEpochAtStart = Date.now();

      const el = document.getElementById('clock');
      const st = document.getElementById('status');
      const btn = document.getElementById('resync');

      function pad(n){ return n < 10 ? '0'+n : ''+n; }
      function fmt(d){
        const y = d.getFullYear();
        const m = pad(d.getMonth()+1);
        const da = pad(d.getDate());
        const h = pad(d.getHours());
        const mi = pad(d.getMinutes());
        const s = pad(d.getSeconds());
        return `${y}-${m}-${da} ${h}:${mi}:${s}`;
      }

      // Calcula “ahora” en base a la hora de servidor + el tiempo transcurrido en el cliente
      function computeNow(){
        const elapsed = Date.now() - clientEpochAtStart; // ms transcurridos en cliente
        return new Date(serverEpoch + elapsed);
      }

      function tick(){
        el.textContent = fmt(computeNow());
      }

      // Ticker 1s
      tick();
      const iv = setInterval(tick, 1000);

      // Botón de resincronizado (lleno pero sin llamadas extra; solo reancla al reloj del cliente)
      btn?.addEventListener('click', () => {
        st.textContent = 'reanudado';
        // “Resincroniza” tomando la hora del cliente como referencia dura
        // (si quieres una llamada real al servidor, sustituye esto por un fetch a hora.php con JSON)
        const now = new Date();
        // Reancla: la hora “servidor” pasa a ser ahora (cliente) para que no derive si el tab se durmió
        // Nota: Esto no consulta al servidor; para fines temporales es suficiente.
        window._tmpAnchor = now.getTime();
        // Ajustamos las bases para que computeNow devuelva la hora del cliente desde aquí
        const drift = window._tmpAnchor - (serverEpoch + (Date.now() - clientEpochAtStart));
        // Aplicamos el drift sumándolo al serverEpoch
        // (esto mantiene el mismo esquema computeNow sin crear más estados)
        window._serverEpochAdj = (window._serverEpochAdj || serverEpoch) + drift;

        // Reemplazamos computeNow usando el epoch ajustado
        const adj = window._serverEpochAdj;
        computeNow = function(){
          const elapsed = Date.now() - clientEpochAtStart;
          return new Date(adj + elapsed);
        }
      });
    })();
  </script>
</body>
</html>
