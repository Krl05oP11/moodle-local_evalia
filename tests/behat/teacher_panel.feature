@local_evalia @local_evalia_teacher
Feature: EVAL-IA teacher panel
  As a teacher enrolled in a course
  I need to access the EVAL-IA teacher panel
  So that I can create rubrics, generate questions and manage exams

  Background:
    Given the following "courses" exist:
      | fullname    | shortname  | category |
      | EVALIA Test | EVALIATEST | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Ana       | Docente  | teacher1@example.com |
      | student1 | Carlos    | Alumno   | student1@example.com |
      | student2 | Luisa     | Alumna   | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course     | role           |
      | teacher1 | EVALIATEST | editingteacher |
      | student1 | EVALIATEST | student        |
      | student2 | EVALIATEST | student        |
    And the following config values are set as admin:
      | setup_complete | 1 | local_evalia |

  @javascript
  Scenario: Teacher can access the teacher panel
    When I am on the "EVALIATEST" course page logged in as "teacher1"
    And I navigate to "EVAL-IA" in current page administration
    Then I should see "EVAL-IA"

  @javascript
  Scenario: Teacher sees all four tabs in the panel
    When I am on the "EVALIATEST" course page logged in as "teacher1"
    And I navigate to "EVAL-IA" in current page administration
    Then I should see "Rubric"
    And I should see "Question Bank"
    And I should see "Exams"
    And I should see "Portfolios"

  @javascript
  Scenario: Student cannot access teacher panel
    When I am on the "EVALIATEST" course page logged in as "student1"
    And I follow "EVAL-IA"
    Then I should see "You do not have permission"

  @javascript
  Scenario: Guest is redirected to login page
    Given I am not logged in
    When I visit "/local/evalia/teacher.php?courseid=1"
    Then I should see "Log in"
