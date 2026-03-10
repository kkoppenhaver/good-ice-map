# Jira → Claude Code Integration Setup

This guide walks through connecting Jira Cloud to the Claude Code GitHub Actions workflows.

## Overview

Assign a Jira ticket to Claude → Claude plans → human approves → Claude implements → PR opened → human reviews.

## Prerequisites

- Jira Cloud project with admin access
- GitHub repo with `CLAUDE_CODE_OAUTH_TOKEN` secret already configured
- A GitHub Personal Access Token (PAT) with `repo` scope
- A Jira user account for Claude (requires a licensed seat)

## 1. Create the Claude Jira User

1. Go to **admin.atlassian.com** → Users → Invite users
2. Create a user for Claude (e.g., `claude@yourteam.com`)
3. Add the Claude user to your Jira project with permissions to:
   - Browse projects
   - Be assigned issues
   - Add comments
   - Transition issues
4. Generate an API token for the Claude user at https://id.atlassian.com/manage-profile/security/api-tokens

## 2. Add GitHub Secrets

In GitHub → Settings → Secrets and variables → Actions, add:

| Secret | Value |
|--------|-------|
| `JIRA_BASE_URL` | Your Jira instance URL, e.g. `https://yourteam.atlassian.net` |
| `JIRA_EMAIL` | The Claude Jira user's email |
| `JIRA_API_TOKEN` | The Claude Jira user's API token |

The `CLAUDE_CODE_OAUTH_TOKEN` should already exist from the existing Claude workflows.

## 3. Jira Board Columns

Your board should have these columns (in order):

1. **Backlog** — Unstarted work
2. **Planning** — Claude is analyzing the ticket and writing a plan
3. **In Progress** — Claude is implementing the changes
4. **Ready for Review** — PR is open, ready for human review
5. **Approved for Deploy** — Human has approved and merged

## 4. Create Jira Automation Rule

Go to **Project Settings → Automation → Create rule**.

You need **one rule** with branching logic:

### Rule: "Ticket assigned to Claude"

**Trigger:** Field value changed → **Assignee**

**Condition:** Assignee equals "Claude" (the Claude user account)

**Branch / If-else:**

#### If status is "Backlog":

**Action:** Send web request
- **URL:** `https://api.github.com/repos/kkoppenhaver/good-ice-map/dispatches`
- **Method:** POST
- **Headers:**
  ```
  Accept: application/vnd.github.v3+json
  Authorization: Bearer <YOUR_GITHUB_PAT>
  ```
- **Body (Custom data):**
  ```json
  {
    "event_type": "claude-plan",
    "client_payload": {
      "issue_key": "{{issue.key}}",
      "summary": "{{issue.summary}}",
      "description": "{{issue.description}}",
      "comment": "",
      "previous_assignee_id": "{{issue.previousAssignee.accountId}}"
    }
  }
  ```

#### Else if status is "Planning":

**Action:** Send web request
- **URL:** `https://api.github.com/repos/kkoppenhaver/good-ice-map/dispatches`
- **Method:** POST
- **Headers:**
  ```
  Accept: application/vnd.github.v3+json
  Authorization: Bearer <YOUR_GITHUB_PAT>
  ```
- **Body (Custom data):**
  ```json
  {
    "event_type": "claude-implement",
    "client_payload": {
      "issue_key": "{{issue.key}}",
      "summary": "{{issue.summary}}",
      "description": "{{issue.description}}",
      "previous_assignee_id": "{{issue.previousAssignee.accountId}}"
    }
  }
  ```

> **Note:** The `{{issue.previousAssignee.accountId}}` smart value captures who had the ticket before Claude, so Claude can reassign it back after finishing.

## 5. The Complete Workflow

```
 Human assigns ticket to Claude (Backlog)
         │
         ▼
 claude-jira-plan.yml
   ├── Claude analyzes codebase
   ├── Posts implementation plan to Jira
   ├── Moves ticket to "Planning"
   └── Reassigns ticket to previous assignee
         │
         ▼
 Human reviews plan, reassigns to Claude (Planning)
         │
         ▼
 claude-jira-implement.yml
   ├── Moves ticket to "In Progress"
   ├── Fetches approved plan from Jira comments
   ├── Claude creates branch, writes code, opens PR
   ├── Posts PR link to Jira
   ├── Moves ticket to "Ready for Review"
   └── Reassigns ticket to previous assignee
         │
         ▼
 Human reviews PR on GitHub
   ├── Claude responds to review comments (claude.yml)
   └── Human approves and merges PR
```

## 6. Test the Integration

1. Create a test Jira ticket in Backlog with a clear description
2. Assign it to Claude
3. Watch for:
   - GitHub Actions → "Claude Jira - Plan" workflow runs
   - Claude posts a plan as a Jira comment
   - Ticket moves to "Planning"
   - Ticket is reassigned back to you
4. Review the plan, then reassign to Claude
5. Watch for:
   - GitHub Actions → "Claude Jira - Implement" workflow runs
   - Ticket moves to "In Progress"
   - Claude opens a PR in GitHub
   - Claude posts the PR link to Jira
   - Ticket moves to "Ready for Review"
   - Ticket is reassigned back to you
6. Review the PR on GitHub, leave comments if needed
7. Approve and merge when ready

## Expanding to Other Projects

To add this to another repo:

1. Copy the 2 workflow files: `.github/workflows/claude-jira-plan.yml` and `claude-jira-implement.yml`
2. Set the GitHub secrets (`JIRA_BASE_URL`, `JIRA_EMAIL`, `JIRA_API_TOKEN`)
3. Create the Jira Automation rule (update the repo URL in the webhook)
4. Ensure the board has the required columns
5. Customize `CLAUDE.md` for the new project
6. Add the Claude user to the new Jira project
