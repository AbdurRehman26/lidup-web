#!/bin/sh

set -eu

repository_root=$(git rev-parse --show-toplevel)
git -C "$repository_root" config core.hooksPath .githooks

printf '%s\n' 'Git hooks installed. CodeRabbit review is now required before every commit.'
