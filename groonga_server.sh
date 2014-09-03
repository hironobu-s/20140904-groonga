#!/bin/bash

# HTTPサーバとしてGroongaを起動する。
# 起動する前に groonga -n db/twstream でDBを作成しておく必要がある
groonga -d --protocol http -p 10041 db/twstream 2>&1 | tee groonga.pid

