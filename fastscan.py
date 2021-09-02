#! /usr/bin/env python
# -*- coding: utf-8 -*-

from concurrent.futures import ThreadPoolExecutor
import time
import requests
import re
import csv



result = []

def scan(ip):
    ip = ip.rstrip("\n")
    url = "https://xxxxx/.fastscan.php?u=http://" + ip
    print(repr(url))
    res = requests.get(url,verify=False)
    status = re.findall('@@@.*?@@@',res.text)[0]
    result.append([ip,status,res.text])

    print(ip,status,res.text,"...")

def main():
    ips = open('ips.txt','r').readlines()
    with ThreadPoolExecutor(15) as executor:
        for ip in ips:
            executor.submit(scan,ip)

if __name__ == '__main__':
    main()
    print(result)
    f = open('result.csv', 'w')
    writer = csv.writer(f)
    for q in result:
        q[2] = q[2].encode('utf-8')
        writer.writerow(q)
    f.close()