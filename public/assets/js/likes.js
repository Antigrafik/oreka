document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.rec-like').forEach(btn => {
    btn.addEventListener('click', async () => {
      const icon  = btn.querySelector('i');
      const count = btn.querySelector('.like-count');
      const recId = btn.dataset.recId;
      const liked = !btn.classList.contains('liked');

      // feedback visual
      btn.classList.toggle('liked', liked);
      icon.classList.toggle('fa-solid', liked);
      icon.classList.toggle('fa-regular', !liked);

      try {
        const res = await fetch('/modules/community/sections/recommendations/toggle_like.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `rec_id=${encodeURIComponent(recId)}&action=${liked ? 'like' : 'unlike'}`
        });

        const data = await res.json();
        if (data && data.success) {
          count.textContent = data.likes; // valor real desde la BD
        } else {
          console.error('Server error:', data);
        }
      } catch (e) {
        console.error('Fetch error:', e);
      }
    });
  });
});
