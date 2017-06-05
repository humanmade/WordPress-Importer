# Contributing

So you want to contribute to the Importer? Fantastic! There are a few rules you'll need to follow for all contributions.

(There are always exceptions to these rules. :) )

## Process

1. Ideally, start with an issue to check the need for a PR. It's possible that a feature may be rejected at an early stage, and it's better to find out before you write the code.
2. Write the code. Small, atomic commits are preferred. Explain the motivation behind the change when needed.
3. File a PR. If it isn't ready for merge yet, note that in the description. If your PR closes an existing issue, add "fixes #xxx" to the message, so that the issue will be closed when the PR is merged.
4. If needed, iterate on the code until it is ready. This includes adding unit tests. When you're ready, comment that the PR is complete.
5. A committer will review your code and offer you feedback.
6. Update with the feedback as necessary.
7. PR will be merged.

Notes:

* All code needs to go through peer review. Committers may not merge their own PR.
* PRs should **never be squashed or rebased**. This includes when merging. Keeping the history is important for tracking motivation behind changes later.

## Best Practices

All code in the Importer must be compatible with PHP 5.2. Treat this code as if it were part of WordPress core, and apply the [same best practices](https://make.wordpress.org/core/handbook/best-practices/).

### Commit Messages

Commit messages should follow the [general git best practices](http://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html):

```
Capitalized, short (50 chars or less) summary

More detailed explanatory text, if necessary.  Wrap it to about 72
characters or so.  In some contexts, the first line is treated as the
subject of an email and the rest of the text as the body.  The blank
line separating the summary from the body is critical (unless you omit
the body entirely); tools like rebase can get confused if you run the
two together.

Write your commit message in the imperative: "Fix bug" and not "Fixed bug"
or "Fixes bug."  This convention matches up with commit messages generated
by commands like git merge and git revert.

Further paragraphs come after blank lines.

- Bullet points are okay, too

- Typically a hyphen or asterisk is used for the bullet, followed by a
  single space, with blank lines in between, but conventions vary here

- Use a hanging indent
```

There is no need to reference issues inside commits, as all interaction with issues is handled via pull requests.


## Coding Style

The coding style should match [the WordPress coding standards](https://make.wordpress.org/core/handbook/coding-standards/php/).


## Unit Tests

PRs should include unit tests for any changes. These are written in PHPUnit, and should be added to the file corresponding to the class they test (that is, tests for `class-wxr-importer.php` would be in `tests/test-wxr-importer.php`).

Where possible, features should be unit tested. The eventual aim is to have >90% coverage.

<!--
We aim for >90% coverage at all times. The master branch may drop below 90% if features are merged independently of their tests, but there is a hard limit of 85%. Release versions must have >90% coverage.

For complex features by third-parties, PRs may be merged that drop coverage below the 90% threshold, with the intent of increasing tests back up in a subsequent PR.
-->


## Licensing

By contributing code to this repository, you agree to license your code for use under the [GPL License](https://github.com/humanmade/WordPress-Importer/blob/master/LICENSE).
