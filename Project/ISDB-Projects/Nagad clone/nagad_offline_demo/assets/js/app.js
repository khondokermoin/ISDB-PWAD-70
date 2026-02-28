(function(){
  // language toggle demo
  const langButtons = document.querySelectorAll('[data-lang-btn]');
  langButtons.forEach(btn=>{
    btn.addEventListener('click', ()=>{
      langButtons.forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
    });
  });
})();