#!/usr/bin/env bash

sleep 15
mongo mydb_test --eval 'db.createUser({user:"travis",pwd:"test",roles:["readWrite"]});'
