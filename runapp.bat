cd .\docker\
del .env
echo MACHINE_HOST=%computername% > .env
echo MACHINE_USER=%username%  >> .env
cd ..
docker-compose -f docker/docker-compose.yml  up -d --build
