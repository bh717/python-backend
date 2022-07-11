describe('Non Code Contributions Page', function () {
  it('loads properly', function () {
    cy.visit('/non-code-contributions');
    cy.percySnapshot('NonCodeContributionsPage');
  });
});
