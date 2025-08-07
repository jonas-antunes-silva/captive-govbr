#!/bin/bash

IP="$1"
MAC="$2"
/usr/sbin/ipset -exist add authenticated_clients "$IP","$MAC" timeout 3600 2>&1
exit $?

