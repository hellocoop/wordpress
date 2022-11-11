#!/bin/zsh

# take down docker containers
docker-compose down

# remove volumes
docker volume prune -f

# clean plugin subfolder
rm -r plugin/
