# Jira Integration — Improvement Tracker

## TODO

### 1. Claude asks clarifying questions during planning
**Status:** Not started
During the planning phase, Claude should identify any ambiguities or open questions and post them as a comment on the Jira ticket. The human answers in the ticket comments. When the implement workflow runs, it should fetch both the plan and the Q&A thread so Claude has full context.

Implementation approach:
- Update the plan prompt to instruct Claude to list questions separately from the plan
- Post questions as a distinct Jira comment (e.g. with a `**Questions**` header)
- Update `claude-jira-implement.yml`'s "Fetch plan from Jira" step to also grab Q&A comments and pass them to the implement prompt
- The implement prompt should reference the answers when coding

### 2. Speed up the planning GitHub Actions run
**Status:** Not started
The planning workflow feels slow. Possible optimizations to investigate:
- Bun install step takes ~700ms but the action downloads it fresh each time — check if caching helps
- The checkout uses `fetch-depth: 1` which is good, but could a sparse checkout reduce time further?
- Claude Code SDK setup/initialization overhead — is there a way to warm this up or use a pre-built image?
- Could we use a smaller/faster model for planning since it's read-only analysis (no code writing)?
- Look into whether `anthropics/claude-code-action` supports any performance-related options

### 3. Skip planning — go straight to implementation
**Status:** Fixed — needs testing
Implement prompt now handles the no-plan case gracefully. If no plan exists, Claude is told to analyze the ticket itself rather than following a nonexistent plan.

**To test:** Move a Backlog ticket directly to Ready for Dev, assign Claude, verify it implements without a plan comment.

