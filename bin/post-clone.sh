#!/bin/bash

if [ ! -d "storage" ]; then
  mkdir -p storage
fi

if [ ! -f ".env" ]; then
  cp  vendor/craftcms/craft/.env.example.dev ./.env.example
fi

if ! grep -q "CRAFT_RUN_QUEUE_AUTOMATICALLY=false" .env.example; then
  echo "" >> .env
  echo "CRAFT_RUN_QUEUE_AUTOMATICALLY=false" >> .env.example
  echo "" >> .env
fi

if ! grep -q "CRAFT_TEMPLATES_PATH=./tests/templates" .env.example; then
  echo "" >> .env
  echo "CRAFT_TEMPLATES_PATH=./tests/templates" >> .env.example
  echo "" >> .env
fi

if [ ! -d "config" ]; then
  cp -r stubs/config ./
fi

if [ ! -d "web" ]; then
  cp -r vendor/craftcms/craft/web ./
fi

if [ ! -f "craft" ]; then
  cp  vendor/craftcms/craft/craft ./
  chmod +x ./craft
fi

if [ ! -f "bootstrap.php" ]; then
  cp  vendor/craftcms/craft/bootstrap.php ./
fi

php craft setup/keys
