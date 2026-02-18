# PublicTestLink
A Moodle local plugin that allows for non-users to take quizzes without enrollment or onboarding.

## Statement of the Problem
Moodle is an open-source extensible Learning Management System (LMS). Moodle offers a service for quiz taking, bearing a question engine and a quiz flow. This allows users to take quizzes within Moodle smoothly, as well as being able to utilize features such as automatic grading, various question types, and attempt reviewing.

However, the quiz flow requires quiz takers to have official user accounts. Due to company policy, administrators are the only ones allowed to have users to increase security of the LMS. This now presents a problem for quiz takers: How would they take a quiz without a user?

This plugin aims to allow quiz takers to take quizzes without an official user through the concept of a “non-user”, users only identified through an email, first name, and last name.

## Objectives
This plugin aims to allow quiz takes to take quizzes made within Moodle without the need for official users. Specifically, this plugin aims to do the following:

- Add an option that can open a quiz to anyone without an official user account.
- Generate a tokenized secure link to be shared to quiz takers.
- Reimplement the quiz flow for non-users, including the following features:
- Quiz opening and closing times
- Attempt time limit
- Attempt count limit
- Allow teachers to view and manage the grades and attempts of non-users within the course context

# Installation
Please visit the [Quickstart: Installation](https://github.com/azelynn-aretex/moodle-publictestlink/wiki/%5B%5D-Quickstart#installation) page in the wiki.
