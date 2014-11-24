#!/bin/bash

echo "select guid from releases where ID = $1" | mysql --user=root -p nzedb -h userverho
