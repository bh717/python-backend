describe('Taxonomy Page', function () {
  it('loads properly', function () {
    cy.visit('/taxonomy/term/2');
    cy.percySnapshot('TaxonomyPage');
  });
});
