describe('Contribution Author', function () {
  it('loads properly', function () {
    cy.login('admin');
    cy.visit('/user/2');
    cy.percySnapshot('ContributionAuthor');
  });
});
