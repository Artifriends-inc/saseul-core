#!/usr/bin/env bash

rm -rf ./blockdata/*
rm -rf ./data/db/*
rm -rf ./data/core/*.tar.gz

block_dir=("apichunks" "broadcastchunks" "transactions" "txarchives")

for dir in "${block_dir[@]}"; do
  mkdir -p ./blockdata/"${dir}"
  touch ./blockdata/"${dir}"/.keep
done

PID_FILE=./data/core/saseuld.pid
if [[ -f "$PID_FILE" ]]; then
  rm $PID_FILE
fi
