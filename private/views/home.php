<div id="module-learn"><?= $learnSection ?></div>
<div id="module-forum"><?= $forumSection ?></div>
<div id="module-community"><?= $communitySection ?></div>

<div id="module-legal" class="hidden"><?= $legalSection ?></div>

<div id="module-myspace" class="hidden"><?= $mySpaceSection ?></div>

<?php if (!empty($adminSection)): ?>
  <div id="module-admin" class="hidden"><?= $adminSection ?></div>
<?php endif; ?>

<style>.hidden{display:none!important}</style>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const $learnModule = document.getElementById('module-learn');
    const $forumModule = document.getElementById('module-forum');
    const $communityModule = document.getElementById('module-community');
    const $legalModule = document.getElementById('module-legal');
    const $myspaceModule = document.getElementById('module-myspace');
    const $adminModule = document.getElementById('module-admin');

    function show(el){ if(el) el.classList.remove('hidden'); }
    function hide(el){ if(el) el.classList.add('hidden'); }

    function toggleByHash(hash) {
      const isLegal   = (hash === '#legal' || (hash && hash.startsWith('#legal/')));
      const isAdmin   = (hash === '#admin' || (hash && hash.startsWith('#admin/')));
      const isMySpace = (hash === '#my-space' || (hash && hash.startsWith('#my-space/')));

      if (isAdmin) {
        show($adminModule);
        hide($learnModule); hide($forumModule); hide($communityModule); hide($myspaceModule);
      } else if (isMySpace) {
        show($myspaceModule);
        hide($learnModule); hide($forumModule); hide($communityModule); hide($adminModule);
      } else if (isLegal) {
        show($legalModule);
        hide($learnModule); hide($forumModule); hide($communityModule); hide($myspaceModule); hide($adminModule);
      } else {
        show($learnModule); show($forumModule); show($communityModule);
        hide($adminModule); hide($myspaceModule);
      }
    }

    toggleByHash(location.hash || '#learn');
    window.addEventListener('hashchange', () => toggleByHash(location.hash));
  });
</script>
