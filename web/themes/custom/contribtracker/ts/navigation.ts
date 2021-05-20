/////////////////////////////
//      Selectors
/////////////////////////////
const sideBar = document.querySelector('.nav__sidebar-menu');
const burger = document.querySelector('.nav__burger');
const branding = document.querySelector('.branding');

/////////////////////////////
//   Burger Click Listener
/////////////////////////////
burger?.addEventListener('click', () => {
  branding?.querySelector('a')?.classList.add('nav__sidebar-menu_link');
  document
    ?.querySelector('.nav__item-account')
    ?.classList.add('nav__sidebar_ul-account');
  sideBar
    ?.querySelector('.nav__auth-link')
    ?.classList.add('nav__sidebar_auth-link');
  sideBar?.querySelector('.nav__item')?.classList.add('nav__sidebar_item');
  sideBar?.classList.toggle('is-visible');
});
