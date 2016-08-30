#!/bin/sh
find data -type f -exec jsonlint -q '{}' +
