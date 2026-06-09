(function(){
  const btn = document.getElementById('themeToggle');
  const saved = localStorage.getItem('hotspot-theme');
  if(saved === 'dark') document.documentElement.classList.add('dark');
  if(btn){
    btn.textContent = document.documentElement.classList.contains('dark') ? '☀' : '☾';
    btn.addEventListener('click', function(){
      document.documentElement.classList.toggle('dark');
      const dark = document.documentElement.classList.contains('dark');
      localStorage.setItem('hotspot-theme', dark ? 'dark' : 'light');
      btn.textContent = dark ? '☀' : '☾';
    });
  }
})();
