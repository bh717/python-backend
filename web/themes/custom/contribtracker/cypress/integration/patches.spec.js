describe('Patches', function () {
  it('loads properly', function () {
    cy.visit('/user/2/patches');
    cy.percySnapshot('Patches');
  });
});
