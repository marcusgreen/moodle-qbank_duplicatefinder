# moodle-qbank_duplicatefinder
![PHP Support](https://img.shields.io/badge/php-8.1--8.3-blue)
[![Moodle Plugin CI](https://github.com/marcusgreen/moodle-qbank_duplicatefinder/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/marcusgreen/moodle-qbank_duplicatefinder/actions/workflows/moodle-ci.yml)
[![Moodle Support](https://img.shields.io/badge/moodle/Moodle-5.0+-orange)](https://github.com/marcusgreen/moodle-qbank_duplicatefinder/actions)

This is a Moodle Question Bank plugin that detects potential duplicate questions within a category or across all categories in a context.

For custom development and consultancy contact Moodle Partner Catalyst EU (https://www.catalyst-eu.net/).

# Why was it created?

Large question banks often accumulate near-identical questions over time — written by different authors, imported from different sources, or copied and lightly edited. Finding these by hand is tedious and error-prone.

This plugin adds a **Find duplicates** report to the question bank that automatically compares question texts and groups those that are sufficiently similar, so you can review and clean up duplicates in one place.

# How it works

Questions are compared using PHP's built-in `similar_text()` function, which measures the longest-common-substring similarity between pairs of normalised question texts. Before comparison, HTML tags are stripped, entities decoded, text lowercased, and whitespace collapsed, so superficial formatting differences do not inflate the difference score.

Questions are clustered using a union-find algorithm. The configurable similarity threshold (default 70 %) controls how aggressive the matching is:

- **Lower threshold** — flags more potential duplicates, including loosely related questions.
- **Higher threshold** — only flags near-identical questions.

For performance, comparisons are capped at 500 questions per search.

# Installation

Clone into the `question/bank/duplicatefinder` directory from the root of your Moodle installation:

```bash
git clone https://github.com/marcusgreen/moodle-qbank_duplicatefinder.git question/bank/duplicatefinder
```

Then visit **Site administration → Notifications** to complete the installation.

The latest source can be found at

https://github.com/marcusgreen/moodle-qbank_duplicatefinder

# Usage

1. Open the **Question bank** for a course or category.
2. Click **Find duplicates** in the question bank navigation.
3. Choose the **scope** (current category or all categories in context) and set a **similarity threshold**.
4. Click **Find duplicates** to run the report.
5. Review the grouped results. Each group shows the baseline question and the similarity percentage of every other member relative to it.
6. Use the **Edit** and **View in bank** action links to review and remove unwanted duplicates.

# Settings

| Setting | Default | Description |
|---|---|---|
| Similarity threshold (%) | 70 | Questions at or above this similarity will be flagged as potential duplicates. |

The default threshold can be changed at **Site administration → Plugins → Question bank → Find duplicate questions**.
