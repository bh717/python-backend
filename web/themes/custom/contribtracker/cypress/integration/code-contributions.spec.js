describe('All Contributions Page', function () {
  it('loads properly', function () {
    cy.visit('/code-contributions');
    cy.percySnapshot('CodeContributionsPage');
  });
});
