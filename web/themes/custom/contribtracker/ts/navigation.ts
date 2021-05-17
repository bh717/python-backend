/////////////////////////////
//      Selectors
/////////////////////////////
const navlists = document.querySelectorAll('.nav__menu-item-expanded');
const sideBar = document.querySelector('.nav__sidebar-menu');
const burger = document.querySelector('.burger');

/////////////////////////////
//      Nav Dropdown
/////////////////////////////
navlists?.forEach((list) => {
  list?.querySelector('ul')?.classList.add('nav__dropdown-closed');
});

/////////////////////////////
//   Burger Click Listener
/////////////////////////////
burger?.addEventListener('click', () => {
  if (sideBar?.classList.contains('show')) {
    sideBar.classList.toggle('hide');
  } else if (sideBar?.classList.contains('hide')) {
    sideBar.classList.toggle('show');
  }
});
