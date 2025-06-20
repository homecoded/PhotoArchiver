#!/bin/bash
set -e
cd "$(dirname "$0")"

echo "Stop :"
docker stop photoarchiver_web
echo "Remove :"
docker rm photoarchiver_web
