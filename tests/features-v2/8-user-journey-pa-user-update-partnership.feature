Feature: PA User - Update a partnership

    @v2-ci-pending @directpartnership
    Scenario: PA User - Update a partnership

        #LOGIN

        Given I am logged in as "par_authority@example.com"

        # GO TO A PARTNERSHIP PAGE

        And I go to detail page for partnership with authority "Lower East Side Borough Council"

        # EDIT ABOUT THE ORGANISATION

        When I edit about the partnership

        # EDIT MAIN AUTHORITY CONTACT

        And I edit the main authority contact

        # COMPLETE CHANGES

        And I click on the button "#edit-done"
        Then the element "h1.heading-xlarge" contains the text "Your partnerships"
