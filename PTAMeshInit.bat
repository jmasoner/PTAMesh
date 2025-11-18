@echo off
setlocal enabledelayedexpansion

:: PTAMesh Git bootstrap (Gripper-ready)
:: Usage: run once in the repo root to initialize Git, branches, hooks, and templates.

set REPO_NAME=PTAMesh
set REMOTE_URL=
set DEFAULT_BRANCH=main

echo Initializing %REPO_NAME% repository...
if not exist .git (
  git init
)

:: Create standard branches
git checkout -q -B %DEFAULT_BRANCH%
git branch -q -M %DEFAULT_BRANCH%
git checkout -q -B dev

:: Git attributes and ignores
echo # Auto normalize line endings > .gitattributes
echo * text=auto >> .gitattributes

echo # Node and WordPress ignores > .gitignore
echo node_modules/>> .gitignore
echo vendor/>> .gitignore
echo .DS_Store>> .gitignore
echo *.log>> .gitignore
echo wordpress/assets/build/>> .gitignore

:: Commit templates
mkdir .github 2>nul
mkdir .github\ISSUE_TEMPLATE 2>nul

(
echo ---
echo name: Feature request
echo about: Suggest an idea
echo ---
echo **Summary**
echo
echo **Acceptance Criteria**
echo - [ ] ...
echo
echo **Notes**
) > .github\ISSUE_TEMPLATE\feature.md

(
echo ---
echo name: Bug report
echo about: Create a report
echo ---
echo **Steps to reproduce**
echo 1. ...
echo
echo **Expected**
echo
echo **Actual**
echo
echo **Logs/Context**
) > .github\ISSUE_TEMPLATE\bug.md

:: Optional remote add
if not "%REMOTE_URL%"=="" (
  git remote add origin %REMOTE_URL%
)

echo Done. Switch to 'dev' for active work.
endlocal
