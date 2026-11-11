@local @local_evalia @local_evalia_setup
Feature: EVAL-IA setup wizard
  As a site administrator
  I need to configure the EVAL-IA engine connection
  So that teachers can generate rubrics and grade exams with AI

  @javascript
  Scenario: Admin can access the setup wizard
    Given I log in as "admin"
    When I am on the "local_evalia > Setup wizard" page
    Then I should see "Welcome to EVAL-IA"

  @javascript
  Scenario: Guest is redirected to login from setup wizard
    Given I am not logged in
    When I visit "/local/evalia/setup.php"
    Then I should see "Log in"
