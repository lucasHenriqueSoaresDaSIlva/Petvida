document.addEventListener('DOMContentLoaded', function () {
  const menu = document.querySelector('.mobile-menu');
  const sidebar = document.querySelector('.sidebar');

  if (menu && sidebar) {
    menu.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
  }

  const tabs = document.querySelectorAll('.tab');
  const panels = document.querySelectorAll('.tab-panel');

  tabs.forEach((tab, index) => {
    tab.addEventListener('click', function () {
      tabs.forEach((item) => item.classList.remove('active'));
      panels.forEach((panel) => panel.classList.remove('active'));
      tab.classList.add('active');
      panels[index]?.classList.add('active');
    });
  });

  const confirmButtons = document.querySelectorAll('button[data-confirm]');
  confirmButtons.forEach((button) => {
    button.addEventListener('click', function () {
      const message = button.getAttribute('data-confirm') || 'Tem certeza?';
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });
});
