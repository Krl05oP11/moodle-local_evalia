@local_evalia @local_evalia_privacy
Feature: EVAL-IA privacy compliance
  As a site administrator
  I need to be able to export and delete user data stored by EVAL-IA
  So that the plugin complies with GDPR and Moodle privacy requirements

  Background:
    Given the following "courses" exist:
      | fullname    | shortname  | category |
      | EVALIA Test | EVALIATEST | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Ana       | Docente  | teacher1@example.com |
      | student1 | Carlos    | Alumno   | student1@example.com |
    And the following "course enrolments" exist:
      | user     | course     | role           |
      | teacher1 | EVALIATEST | editingteacher |
      | student1 | EVALIATEST | student        |
    And the following config values are set as admin:
      | setup_complete | 1 | local_evalia |

  Scenario: Privacy provider is registered for local_evalia
    Given I log in as "admin"
    When I navigate to "Users > Privacy and policies > Data registry" in site administration
    Then "local_evalia" "text" should exist in the page

  Scenario: Admin can request data export for a student
    Given I log in as "admin"
    When I navigate to "Users > Privacy and policies > Data requests" in site administration
    And I follow "New request"
    And I set the field "user" to "student1"
    And I select "Export data" from the "requesttype" singleselect
    And I press "Send request"
    Then I should see "student1"
    And I should see "Export"
