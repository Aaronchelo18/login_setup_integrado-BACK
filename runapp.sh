#!/bin/sh
rm -rf /docker/.env
touch docker/.env

echo "MACHINE_HOST=$(cat /etc/hostname)"  > docker/.env
echo "MACHINE_USER=$USER"  >> docker/.env

docker-compose -f docker/docker-compose.yml  up -d --build