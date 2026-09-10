@local_evalia @local_evalia_setup
Feature: EVAL-IA setup wizard
  As a site administrator
  I need to configure the EVAL-IA engine connection
  So that teachers can generate rubrics and grade exams with AI

  @javascript
  Scenario: Admin can access the setup wizard
    Given I log in as "admin"
    When I navigate to "/local/evalia/setup.php" in site administration
    Then I should see "EVAL-IA"
    And I should not see "You do not have permission"

  @javascript
  Scenario: Non-admin teacher is denied access to setup wizard
    Given the following "courses" exist:
      | fullname    | shortname  | category |
      | EVALIA Test | EVALIATEST | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Ana       | Docente  | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course     | role           |
      | teacher1 | EVALIATEST | editingteacher |
    When I am on the "/local/evalia/setup.php" page logged in as "teacher1"
    Then I should see "You do not have permission"

  @javascript
  Scenario: Guest is redirected to login from setup wizard
    Given I am not logged in
    When I visit "/local/evalia/setup.php"
    Then I should see "Log in"
