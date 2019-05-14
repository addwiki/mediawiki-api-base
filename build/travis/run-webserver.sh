#! /bin/bash

set -x

# Run a web server for MediaWiki and wait until it is up
nohup php -S 0.0.0.0:8080 -t ./../web > /dev/null 2>&1 &
until curl -s localhost:8080; do true; done > /dev/null 2>&1
