describe("Home page", function () {
  it("loads properly", function () {
    cy.visit("/");
    cy.percySnapshot("Homepage");
  });
});
