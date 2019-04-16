describe('Pages Load Test', function() {
  it('Open Staging Site', function() {
    cy.viewport('macbook-11')

    cy.visit('https://raddws.com/sites/stm/');

    cy.get('#navigation > ul .menu-item').find('a').each(function($el, index, $list){
      const url = $el.attr('href');
      if(url.includes('https://raddws.com/sites/stm/')){
        cy.visit(url)
        // cy.get('#footer').should('be.visible')
      }
    });
  })
})
