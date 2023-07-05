#!/bin/bash

sigint_handler()
{
  kill $PID
  exit
}

trap sigint_handler SIGINT

while true; do
  composer dump -o --apcu
  twcss -i public/assets/style.css -o public/assets/web.css --minify
  $@ &
  PID=$!
  inotifywait -e modify -e move -e create -e delete -e attrib -r "$(pwd)"
  kill $PID
done
