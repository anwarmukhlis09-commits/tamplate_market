function togglePassword(){
  const pass = document.getElementById('password');
  if(!pass) return;
  pass.type = pass.type === 'password' ? 'text' : 'password';
}
function focusUsername(){
  const user = document.getElementById('username');
  if(user) user.focus();
}
window.addEventListener('load', focusUsername);
