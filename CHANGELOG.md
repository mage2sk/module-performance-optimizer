# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.12] - 2026-10-30

### Changed
- Released under the MIT License: this module is free and open-source software.

## [1.0.11] - 2026-10-03

### Fixed
- The x-cloak style is output in the page head instead of at the end of the body, so cloaked Alpine.js elements are hidden before they are first painted.
- Stores > Configuration > Performance Optimizer no longer shows the Defer Third-Party Scripts and Excluded Domains settings, which had no effect; saved values are ignored.
