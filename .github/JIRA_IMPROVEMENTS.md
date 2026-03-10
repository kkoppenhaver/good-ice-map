# Jira Integration — Improvement Tracker

## TODO

### 1. Immediate acknowledgement on ticket assignment
**Status:** Done
Jira automation now transitions tickets immediately when Claude is assigned:
- Backlog → Planning (before firing `claude-plan` webhook)
- Ready for Dev → In Progress (before firing `claude-implement` webhook)

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

### 4. Speed up the planning GitHub Actions run
**Status:** Not started
The planning workflow feels slow. Possible optimizations to investigate:
- Bun install step takes ~700ms but the action downloads it fresh each time — check if caching helps
- The checkout uses `fetch-depth: 1` which is good, but could a sparse checkout reduce time further?
- Claude Code SDK setup/initialization overhead — is there a way to warm this up or use a pre-built image?
- Could we use a smaller/faster model for planning since it's read-only analysis (no code writing)?
- Look into whether `anthropics/claude-code-action` supports any performance-related options

### 5. Skip planning — go straight to implementation
**Status:** Not tested
Should be able to manually move a ticket to "Ready for Dev" and assign Claude to skip planning entirely. The automation should fire `claude-implement` since it only checks status + assignee. The implement workflow fetches the plan from Jira comments — if none exists, it falls back to "No plan found" and works from the summary/description.

**To test:** Move a Backlog ticket directly to Ready for Dev, assign Claude, verify it implements without a plan comment.
**To consider:** Should the implement prompt handle the no-plan case more gracefully? Currently it just passes "No plan found" as the plan text.

## DONE

_(None yet)_
