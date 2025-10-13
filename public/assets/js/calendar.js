document.addEventListener('DOMContentLoaded', () => {
  const calendarEl = document.querySelector('.forum-calendar');
  if (!calendarEl) return;

  const container = document.getElementById('calendar-container');
  const monthTitle = document.getElementById('calendar-month-title');
  const eventDays = JSON.parse(calendarEl.dataset.eventDays || '[]');
  if (!container || eventDays.length === 0) return;

  const today = new Date();
  const year = today.getFullYear();
  const month = today.getMonth();

  const monthNames = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO',
                      'JULIO', 'AGOSTO', 'SEPTIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];

  monthTitle.textContent = monthNames[month];

  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const firstDay = new Date(year, month, 1).getDay(); // 0 (Sun) - 6 (Sat)

  const calendarTable = document.createElement('table');
  calendarTable.className = 'mini-calendar';

  const headerRow = document.createElement('tr');
  ['L', 'M', 'X', 'J', 'V', 'S', 'D'].forEach(day => {
    const th = document.createElement('th');
    th.textContent = day;
    headerRow.appendChild(th);
  });
  calendarTable.appendChild(headerRow);

  let row = document.createElement('tr');
  for (let i = 0; i < (firstDay === 0 ? 6 : firstDay - 1); i++) {
    row.appendChild(document.createElement('td'));
  }

  for (let d = 1; d <= daysInMonth; d++) {
    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    const td = document.createElement('td');
    td.textContent = d;

    if (eventDays.includes(dateStr)) {
      td.classList.add('has-event');
    }

    row.appendChild(td);

    if (row.children.length % 7 === 0) {
      calendarTable.appendChild(row);
      row = document.createElement('tr');
    }
  }

  if (row.children.length > 0) {
    while (row.children.length < 7) {
      row.appendChild(document.createElement('td'));
    }
    calendarTable.appendChild(row);
  }

  container.appendChild(calendarTable);
});
