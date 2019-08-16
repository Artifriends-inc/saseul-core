#!/usr/bin/env bash

rm -rf ./blockdata/*
rm -rf ./data/db/*

block_dir=("apichunks" "broadcastchunks" "generations" "transactions" "txarchives")

for dir in "${block_dir[@]}"; do
  mkdir -p ./blockdata/"${dir}"
  touch ./blockdata/"${dir}"/.keep
done
