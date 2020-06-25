#!/bin/bash

root="$(git rev-parse --show-toplevel)"

# shellcheck disable=SC1090,SC1091
source "$root/.env"

rsync -a "$XZ_APPLICATIONS_JSON_DIR" "$XZ_APPLICATIONS_TRACKED_DIR"
rsync -a --delete "$XZ_APPLICATIONS_TRACKED_DIR" "$XZ_APPLICATIONS_MU_PLUGIN_DIR"
