describe('Contribution Details', function () {
  it('loads properly', function () {
    cy.visit('/node/79');
    cy.percySnapshot('ContributionDetails');
  });
});
