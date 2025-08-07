#!/bin/bash

IP="$1"
MAC="$2"
/usr/sbin/ipset -exist add temp_clients "$IP","$MAC" timeout 120 2>&1
exit $?

