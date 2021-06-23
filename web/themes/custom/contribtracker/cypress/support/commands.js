import '@percy/cypress';

// Login
Cypress.Commands.add('login', (type) => {
  let perms = {};
  switch (type) {
    case 'admin':
      perms = {
        name: Cypress.env('ADMIN_USERNAME'),
        pass: Cypress.env('ADMIN_PASSWORD'),
      };
      break;
  }
  return cy.request({
    method: 'POST',
    url: '/user/login',
    form: true,
    body: {
      ...perms,
      form_id: 'user_login_form',
    },
  });
});
