#!/bin/sh
find data -type f -exec jsonlint --validate schemas/hotel-schema.json -q '{}' +
