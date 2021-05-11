describe("Contribution Count", function () {
  it("loads properly", function () {
    cy.visit("/contribution-count");
    cy.percySnapshot("ContributionCount");
  });
});
