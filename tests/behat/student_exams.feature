@local_evalia @local_evalia_student
Feature: EVAL-IA student exam list
  As a student enrolled in a course
  I need to see my assigned exams
  So that I can take them within the allowed time window

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

  @javascript
  Scenario: Student sees empty state when no exams are assigned
    When I am on the "EVALIATEST" course page logged in as "student1"
    And I follow "EVAL-IA"
    Then I should see "No ten"

  @javascript
  Scenario: Student can access the student exam page
    When I am on the "EVALIATEST" course page logged in as "student1"
    And I visit "/local/evalia/student.php?courseid=1"
    Then I should not see "You do not have permission"

  @javascript
  Scenario: Unenrolled user cannot access student exam page
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | outsider | Pedro     | Externo  | outsider@example.com |
    When I am on the "EVALIATEST" course page logged in as "outsider"
    Then I should see "You can not enrol yourself"
