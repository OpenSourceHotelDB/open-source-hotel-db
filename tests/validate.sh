#!/bin/sh
find schemas -type f -exec jsonlint -q '{}' +
