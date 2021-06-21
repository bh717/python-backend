describe('Patches on Issues', function () {
  it('loads properly', function () {
    cy.visit('/user/2/patches-on-issues');
    cy.percySnapshot('PatchesOnIssues');
  });
});
