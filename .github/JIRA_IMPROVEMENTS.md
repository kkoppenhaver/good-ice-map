# Jira Integration — Improvement Tracker

## TODO

### 1. Immediate acknowledgement on ticket assignment
**Status:** In progress
When Claude is assigned a ticket, the Jira automation should immediately transition the ticket:
- Backlog → Planning (before firing `claude-plan` webhook)
- Ready for Dev → In Progress (before firing `claude-implement` webhook)

This gives instant visual feedback that Claude picked up the work. The GitHub workflow already handles the "done" transitions (Planning → Ready for Dev, In Progress → Ready for Review).

**Action needed:** Add "Transition issue" steps in the Jira automation rule, before each "Send web request" action.

### 2. Empty plan output from Claude Code action
**Status:** In progress
First test run (SCRUM-1) posted a comment but the plan body was empty — just the header `**Claude's Implementation Plan**` and footer text, no actual plan content.

Possible causes to investigate:
- The `claude-code-action` output key may not be `response` — check the action's docs for the correct output name
- Check the GitHub Actions run logs for the "Run Claude Code - Generate Plan" step to see if Claude produced output
- SCRUM-1 had no description (all info was in the title)

Changes made:
- Added fallback text for empty descriptions in the plan prompt so Claude knows to work from the summary

**Action needed:** Check the Actions run log, verify the correct output key from `claude-code-action`, and retest.

## DONE

_(None yet)_
