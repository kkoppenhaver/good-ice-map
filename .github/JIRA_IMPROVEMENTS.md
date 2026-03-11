# Jira Integration — Improvement Tracker

## TODO

### 1. Claude asks clarifying questions during planning
**Status:** Done — needs testing
Claude now puts clarifying questions at the top of the plan comment under a "Questions" heading. The implement workflow fetches all ticket comments (plan + answers) and passes the full thread to Claude.

### 2. Speed up the planning GitHub Actions run
**Status:** Done
Applied `--model claude-sonnet-4-6` and `--max-turns 15` to the plan workflow. Expected to cut execution from ~62s to ~25-35s and cost from ~$0.47 to ~$0.15-0.25 per run.

### 4. Weekly cost report
**Status:** Not started
The `execution_file` from each run logs `total_cost_usd`. We could build a script or scheduled GitHub Action that:
- Uses `gh run list` to find all plan/implement runs in a time range
- Downloads each run's logs and extracts `total_cost_usd`
- Sums up the total and posts a summary (Slack, Jira comment, or just a workflow artifact)

### 3. Skip planning — go straight to implementation
**Status:** Fixed — needs testing
Implement prompt now handles the no-plan case gracefully. If no plan exists, Claude is told to analyze the ticket itself rather than following a nonexistent plan.

**To test:** Move a Backlog ticket directly to Ready for Dev, assign Claude, verify it implements without a plan comment.

