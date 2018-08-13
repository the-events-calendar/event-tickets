#!/usr/bin/env bash

sed -i .bak 's#https://commerce.dev#http://tribe.localhost#' tests/_data/restv1-dump.sql
sed -i .bak 's#commerce.dev#tribe.localhost#' tests/_data/restv1-dump.sql
