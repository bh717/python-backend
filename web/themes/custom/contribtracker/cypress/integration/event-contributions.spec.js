describe('Event Contributions Page', function () {
  it('loads properly', function () {
    cy.visit('/event-contributions');
    cy.percySnapshot('EventContributionsPage');
  });
});
