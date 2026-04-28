# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This repository is a static marketing/documentation site for **Auto Sync Pro**.

Core runtime assets are at repo root:
- `index.html`: landing/pricing page
- `guide.html`: interactive multi-panel usage guide (tabs + hash deep-linking + embedded PDFs)
- `demo.html`: demo showcase page
- `styles.css`: shared visual system and responsive styling
- `script.js`: shared behavior for reveal animations, smooth-scroll, and countdown timer

The only non-static automation script is:
- `sync_sites.py`: one-way mirror/sync script that copies site assets to multiple sibling site directories and pushes them to separate Git remotes.

## Development Commands

There is no app build pipeline (no npm/webpack/vite). Development is direct HTML/CSS/JS editing.

Run site locally (any static server):

```bash
python -m http.server 8000
```

Then open `http://localhost:8000`.

### Test/Validation commands available in this repo

Hook validation scripts (Node):

```bash
node .claude/hooks/tests/test-scout-block.js
node .claude/hooks/tests/test-modularization-hook.js
```

Scan Claude command metadata:

```bash
python .claude/scripts/scan_commands.py
```

Scan Claude skill metadata:

```bash
python .claude/scripts/scan_skills.py
```

Resolve env vars using project/user Claude hierarchy:

```bash
python .claude/scripts/resolve_env.py GEMINI_API_KEY --skill ai-multimodal --verbose
```

### No canonical commands currently present

- No `package.json` at repo root
- No project-level lint command
- No project-level unit/integration test framework for site code

## Architecture Notes

### 1) Static site composition

- The pages are serverless static documents; behavior is DOM-driven and page-local.
- `styles.css` defines global tokens (`:root` CSS variables), glassmorphism UI primitives, layout blocks, animations, and responsive breakpoints reused across pages.
- `script.js` adds cross-page progressive enhancement:
  - IntersectionObserver-based reveal animations (`.reveal -> .active`)
  - Smooth scrolling for same-page anchor links
  - Per-browser countdown persistence via `localStorage.saleEndTime`

### 2) `guide.html` is the most stateful page

`guide.html` contains substantial inline JS/CSS and acts like a mini-doc app:
- Card-based tab navigation (`switchTab`) mapped via `tabMap`
- URL hash deep-link support on load and history navigation (`popstate`)
- Expand/collapse sections for prep blocks and PDF viewers
- Clipboard helper for bulk key copy

When changing guide behavior, treat inline JS in `guide.html` as authoritative for tab state and deep-link semantics.

### 3) Distribution/mirroring workflow (`sync_sites.py`)

`sync_sites.py` mirrors content from one source directory to multiple target directories (`Tan`, `Dat`, `Dai`):
- Copies text assets and replaces contact/Zalo strings per target
- Copies binary assets (`*.pdf`, screenshot) and `images/`
- Initializes git repo if missing
- Commits and force-pushes target `main` branch

This script is opinionated for one-way sync and destructive overwrite in mirror dirs (e.g., deleting/recopying folders, force push).

## Claude-specific local tooling in this repo

### Hook enforcement

`.claude/settings.json` enables a `PreToolUse` hook for `Bash|Glob|Grep|Read|Edit|Write`:
- `node "$CLAUDE_PROJECT_DIR"/.claude/hooks/scout-block.js`

`scout-block.js` dispatches to platform-specific scripts and blocks access to heavy directories (`node_modules`, `.git`, `dist`, `build`, `__pycache__`) while allowing common build commands.

### Status line

Custom status line is configured:
- command: `node .claude/statusline.js`

It renders cwd, git branch, model/version, session timing, cost, and line-change stats based on Claude runtime input and local transcript metadata.
