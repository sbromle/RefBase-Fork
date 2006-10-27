#!/bin/bash
#
# EndNote - Importer to RefBase MySQL table
#
# Andreas Czerniak <ac@oceanlibrary.org>
#
# initial: 05-11-2005
#
# modified:
# 2005-12-11 ; ac ; clean up static codes
# 2005-12-15 ; rk ; remove "v.9", import into CVS
# 2006-01-03 ; ac ; replace LOAD DATA INTO statement with mysqlimport - Thx. Matthias Steffens <refbase@extracts.de> 
#

if [ $# -lt 1 ]; then
  echo "Which endnote file ?"
  echo -e "\nusage: $0 endnote.file [database [mysql-options] ]\n"
  exit 127
fi

ENFILE=$1

MYSQLDB=$2     || MYSQLDB="literature"      # default: literature
MYSQLOPTION=$3 || MYSQLOPTION="-p"		  # default: with password

if [ ! -d imported ] ; then
  mkdir imported
fi

./endnote2mysql.php $1

if [ ! -f import.txt ] ; then
  echo "endnote2mysql convert failed !"
  exit 0
fi

mv import.txt refs.txt
mysqlimport --local $MYSQLOPTION $MYSQLDB "refs.txt" > sqloutput.txt

cat sqloutput.txt

rm refs.txt
rm sqloutput.txt

cat $ENFILE | tail

echo "\n\nrows imported: "
cat $ENFILE | wc -l

mv $ENFILE imported/.

