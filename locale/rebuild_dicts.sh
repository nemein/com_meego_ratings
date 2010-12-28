#!/bin/sh

CURRDIR=`dirname $0`
echo $CURRDIR

for dir in `find . -type d -name "LC_MESSAGES" | grep -v ".svn"`; do 
 echo "Rebuild $dir"
 rm -rf $dir/*~
 msgfmt --statistics -f -c -v -o $dir/com_meego_ratings.mo $dir/com_meego_ratings.po
done
