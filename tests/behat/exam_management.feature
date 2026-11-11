@local @local_evalia @local_evalia_exams
Feature: EVAL-IA exam management
  As a teacher
  I need to create and manage exams for my course
  So that students can be assessed using AI-graded evaluations

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
  Scenario: Teacher sees the Exams tab in the teacher panel
    When I am on the "EVALIATEST" course page logged in as "teacher1"
    And I navigate to "EVAL-IA" in current page administration
    Then I should see "Exams"

  @javascript
  Scenario: Teacher sees the Question Bank tab in the teacher panel
    When I am on the "EVALIATEST" course page logged in as "teacher1"
    And I navigate to "EVAL-IA" in current page administration
    Then I should see "Question bank"

  @javascript
  Scenario: Student sees empty exam list before any exam is assigned
    When I am on the "EVALIATEST" "local_evalia > My exams" page logged in as "student1"
    Then I should see "You don't have any exams assigned yet."

  @javascript
  Scenario: Teacher can access student portfolio page
    When I am on the "EVALIATEST" course page logged in as "teacher1"
    And I navigate to "EVAL-IA" in current page administration
    Then I should see "Portfolios"

  @javascript
  Scenario: Student is offered their exams, not the teacher panel
    When I am on the "EVALIATEST" course page logged in as "student1"
    Then "EVAL-IA" "link" should not exist
    And "My exams" "link" should exist
