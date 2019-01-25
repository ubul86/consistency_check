#!/bin/bash

OUTPUT="$(which php)"
[[ -z "${OUTPUT}" ]] && { echo "PHP not found" ; exit 1; }
${OUTPUT} index.php ${OUTPUT}
