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
**Status:** Fixed — needs retest
**Root cause:** `claude-code-action` does not have an `outputs.response` field. The available outputs are `execution_file`, `branch_name`, `session_id`, `structured_output`, and `github_token`. The plan workflow was referencing `steps.claude-plan.outputs.response` which doesn't exist.

**Fix applied:**
- Added an "Extract plan from execution output" step that reads the result from the `execution_file` JSON
- Fixed the same issue in `claude-jira-implement.yml` for extracting the PR URL
- Added fallback text for empty ticket descriptions
- Also improved the curl/jq pattern to pipe JSON body via `@-` instead of inline shell expansion (avoids quoting issues with markdown)

**Action needed:** Commit, push, and retest with a ticket.

### 3. Claude asks clarifying questions during planning
**Status:** Not started
During the planning phase, Claude should identify any ambiguities or open questions and post them as a comment on the Jira ticket. The human answers in the ticket comments. When the implement workflow runs, it should fetch both the plan and the Q&A thread so Claude has full context.

Implementation approach:
- Update the plan prompt to instruct Claude to list questions separately from the plan
- Post questions as a distinct Jira comment (e.g. with a `**Questions**` header)
- Update `claude-jira-implement.yml`'s "Fetch plan from Jira" step to also grab Q&A comments and pass them to the implement prompt
- The implement prompt should reference the answers when coding

## DONE

_(None yet)_
