describe('Identify a Donor', () => {
    it('displays two todo items by default', () => {
        cy.visit('/')
        cy.contains('Is the caller prepared to perform the ID check?')
        cy.get('.govuk-button').click()
    })
})
