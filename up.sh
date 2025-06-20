#!/bin/bash
set -e
cd "$(dirname "$0")"

DOCKER_HOST_PORT=80

if [ -f .env ]; then
  source .env
  DOCKER_HOST_PORT=$LOCAL_PORT
fi

echo "use --rebuild to rebuild the docker env."

if [[ $1 == '--rebuild' ]]; then
  echo $(./down.sh)
  docker build --no-cache -t photoarchiver --label="photoarchiver" .
fi

cd build
docker run --name photoarchiver_web -d --volume $(pwd)/../:/var/www/html -p $DOCKER_HOST_PORT:80 photoarchiver
