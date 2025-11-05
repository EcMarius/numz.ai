# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## CRITICAL RULES

**DO NOT CREATE DOCUMENTATION OR MARKDOWN FILES WITHOUT EXPLICIT REQUEST**
- NEVER create .md files, README files, or any documentation files unless specifically asked by the user
- Do not create summary files, implementation reports, or progress documents
- Focus on writing code and making changes - not documenting them
- This wastes tokens and quota unnecessarily
- Only create documentation when the user explicitly requests it
- Do not ever check database content or terminal commands (unless they are to create migrations, composer packages, models, etc), because our main site is on the production server, it makes no sense to do this on local.
- I use PHP 8.4
- We don't use git
- files are uploaded automatically uploaded into the server when you edit them on local
- if you have the mcp server installed, keep in mind that our server path is /www/wwwroot/evenleads.com/
- When editing files, do not edit them directly in SSH server (if mcp ssh is there), just edit them on local because they are automatically uploaded into the server after editing
- When doing modifications, please use ./cc.sh (using the ssh-mcp server, if available) in the website path to clear all the cached, use "./cc.sh without-queue" only when we did not made modifications on the queue work, because it makes no sense to restart the queue worker if we did not modified it.
- When updating db on the server, use artisan tinker on ssh-mcp
- IF you modify the evenleads-extension, after you do all the modifications, ALWAYS build it
