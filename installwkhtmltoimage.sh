#!/bin/bash

cd /tmp
wget http://wkhtmltopdf.googlecode.com/files/wkhtmltoimage-0.11.0_rc1-static-i386.tar.bz2
tar xvjf ./wkhtmltoimage-0.11.0_rc1-static-i386.tar.bz2
chown apache:apache ./wkhtmltoimage-i386
chmod +x ./wkhtmltoimage-i386
mv ./wkhtmltoimage-i386 ./usr/bin/wkhtmltoimage-i386
cd ~

