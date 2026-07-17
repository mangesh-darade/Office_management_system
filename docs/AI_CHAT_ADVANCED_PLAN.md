# AI Chat — Advanced Feature Plan

**Goal:** Turn Office AI into a reliable multilingual office assistant (tools-first, scoped, testable).

| # | Feature | Status | Test |
|---|---------|--------|------|
| P0 | This plan doc | Done | — |
| P1 | More tools (late today, my pending leave, team on leave, my points) + hard hierarchy scope on org tools | Done | CLI |
| P2 | Follow-up context + memory slots (`last_tool`, `last_rows`, date range) | Done | CLI |
| P3 | Suggestion chips + conversation history sidebar | Done | Manual + CLI |
| P4 | SQL confirm gate for non-tool queries + deep links in answers | Done | CLI |
| P5 | Rate limit + intent audit log + deep eval suite | Done | CLI |

## Out of scope this pass (later)
- Write actions (apply leave) with confirm modal
- True token streaming / voice-in ASR
- Chart rendering / MuRIL models
- External Indic translation APIs

## Execute order
P1 → P2 → P3 → P4 → P5 (each with CLI tests before next).

## Run tests
```bash
php index.php ai_chat_test_cli run
php index.php ai_chat_test_cli eval
```
