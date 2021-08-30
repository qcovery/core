#!/bin/bash

WHITE='\033[39m';
GREEN='\033[32m';
RED='\033[31m';

function reOrder() {
    BEFORE=$1;
    AFTER=$2;
    LIST=$3;
    ITEM='';
    for I in $(seq 0 $(( ${#LIST[*]} - 1 ))); do
        if [ ${LIST[$I]} = $BEFORE ]; then
            if [ -n "$ITEM" ]; then
                LIST[$I]=$ITEM;
            fi;
            return 0;
        elif [ ${LIST[$I]} = $AFTER ]; then
            ITEM=${LIST[$I]};
            LIST[$I]=$BEFORE;
        elif [ -n "$ITEM" ]; then
            TMP=${LIST[$I]};
            LIST[$I]=$ITEM;
            ITEM=$TMP;
        fi;
    done;
}

# Zentrale Größen einlesen

ACTUALPATH=$(pwd);

TMPPATH=$(echo $ACTUALPATH | sed -s "s/^\(.\+\)\/\([^/]\+\)$/\\1/");
echo -n "Basispfad der Anwendung (oberhalb des Anwendungsverzeichnisses) ["$TMPPATH"]: ";
read BASEPATH;
[ -z "$BASEPATH" ] && BASEPATH=$TMPPATH;

while [ -z "$APP" ]; do
    echo -n "Pfad der Anwendung (relativ zum Basispfad): ";
    read APP;
done;

TMPPATH=$(echo $ACTUALPATH | sed -s "s/^\(.\+\)\/\([^/]\+\)$/\\2/");
echo -n "Pfad der Module (relativ zum Basispfad) ["$TMPPATH"]: ";
read MOD;
[ -z "$MOD" -o "$MOD" = "$APP" ] && MOD=$TMPPATH;

echo -n "git-Branch der Module [modules-hh]: ";
read MODBRANCH;
[ -z "$MODBRANCH" ] && MODBRANCH="modules-hh";

ABSAPPDIR=${BASEPATH}/${APP};
ABSMODDIR=${BASEPATH}/${MOD};

[ -d "$ABSAPPDIR" ] || echo "$RED Das Verzeichnis ${ABSAPPDIR} existiert nicht; breche ab ...$WHITE";
[ -d "$ABSMODDIR" ] || echo "$RED Das Verzeichnis ${ABSMODDIR} existiert nicht; breche ab ...$WHITE";
[ -d "$ABSAPPDIR" -a -d "$ABSMODDIR" ] || exit 1;

echo -e "$GREEN application path:$WHITE ${ABSAPPDIR}";
echo -e "$GREEN module path:$WHITE ${ABSMODDIR}";

MODDIR=../../${MOD}/module;
THEMEDIR=../../${MOD}/themes;

# Quelltexte aus den Repositories holen und initialisieren

cd $ABSAPPDIR;
echo "VuFind wird aktualisiert ...";
git pull;

echo "Die Module werden aktualisiert ...";
cd $ABSMODDIR;
git pull;
git checkout $MODBRANCH;
git checkout module;
git checkout themes;

# Abhängigkeiten der Module auflösen

echo "Die Modulabhängigkeiten werden aufgelöst ...";
BEFORES=();
AFTERS=();
for FILE in $(grep -rl 'use \*' module); do
    ACTMOD=$(echo $FILE | cut -d '/' -f2);
    for LINE in "$(grep 'use \*' $FILE)"; do
        DEPS=$(echo "$LINE" | cut -d ';' -f2);
        for MOD in $DEPS; do
            if [ -d module/$MOD ]; then
                MODLISTED=0;
                for J in $(seq 0 $(( ${#BEFORES[*]} - 1 ))); do
                    if [ ${BEFORES[$J]} = "$MOD" ] && [ ${AFTERS[$J]} = "$ACTMOD" ]; then
                        MODLISTED=1;
                    fi;
                done;
                if [ $MODLISTED -eq 0 ]; then
                    BEFORES+=($MOD);
                    AFTERS+=($ACTMOD);
                fi;
            fi;
        done;
    done;
    php linkModules.php $FILE;
done;

# Module in die richtige Reihenfolge bringen

LIST=($(ls module));
N=${#BEFORES[*]};
for J in $(seq 0 $(( $N - 1 ))); do
    for K in $(seq 0 $(( $N - $J - 1 ))); do
        reOrder ${BEFORES[$K]} ${AFTERS[$K]} $LIST;
    done;
done; 

# theme.config.php schreiben
cd ${ABSMODDIR}/themes/${MAINTHEME};
echo "theme.config.php schreiben ...";
touch theme.config.tmp;
while IFS= read -r LINE; do
    if [ "$LINE" = "MIXINS" ]; then
        for MOD in $(ls ${ABSMODDIR}/module); do
            printf '%s\n' "        '"$(echo $MOD | tr '[:upper:]' '[:lower:]')"'," >> theme.config.tmp;
        done;
    else
        printf '%s\n' "$LINE" >> theme.config.tmp;
    fi;
done < theme.config.php;
mv theme.config.tmp theme.config.php;

# Symlinks setzen - nur wo sie fehlen

echo "Module werden verlinkt und eingerichtet ...";
cd ${ABSAPPDIR}/themes;
for THEME in $(ls $THEMEDIR); do
    [ -h $THEME ] || ln -s ${THEMEDIR}/$THEME $THEME;
done;

cd ${ABSAPPDIR}/module;
for MOD in $(ls $MODDIR); do
    [ -h $MOD ] || ln -s ${MODDIR}/$MOD $MOD;
done;

# Module einrichten

cd $ABSAPPDIR;
for MOD in $(ls module); do
    if [ -d "module/${MOD}/sql" -a -f "module/${MOD}/sql/mysql.sql" ]; then
# aber nur bei einem neuen Modul - oder Tabelle nur, wenn noch nicht vorhanden
        mysql -u $DBUSER -p$DBPASS $DB < module/${MOD}/sql/mysql.sql;
    fi;
    if [ -d "module/${MOD}/composer" -a -f "module/${MOD}/composer/composer.list" ]; then
        while read LINE; do
            composer require $LINE;
        done < module/${MOD}/composer/composer.list;
    fi;
done;

echo "Die Aktualisierung ist durchgeführt";

exit $?;
