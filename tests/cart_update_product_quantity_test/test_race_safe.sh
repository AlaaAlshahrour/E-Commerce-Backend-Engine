#!/bin/bash
ab -n 1 -c 1 \
-H "Authorization: Bearer 2|mUwZlUtX0g5DyMldWzfal9Zb3hQXQIycql16yYo8b9144314" \
-p body1.json \
-T application/json \
http://127.0.0.1:8000/api/cart/update/21 &



ab -n 1 -c 1 \
-H "Authorization: Bearer 2|mUwZlUtX0g5DyMldWzfal9Zb3hQXQIycql16yYo8b9144314" \
-p body2.json \
-T application/json \
http://127.0.0.1:8000/api/cart/update/21 &

wait
