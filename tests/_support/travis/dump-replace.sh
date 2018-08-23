#!/usr/bin/env bash

sed -i 's#https://commerce.dev#http://tribe.localhost#g' tests/_data/restv1-dump.sql
sed -i 's#commerce.dev#tribe.localhost#g' tests/_data/restv1-dump.sql
