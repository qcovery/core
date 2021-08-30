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

[ -d "$ABSAPPDIR" ] || mkdir $ABSAPPDIR || echo "$RED Das Verzeichnis ${ABSAPPDIR} existiert nicht; breche ab ...$WHITE";
[ -d "$ABSMODDIR" ] || mkdir $ABSMODDIR || echo "$RED Das Verzeichnis ${ABSMODDIR} existiert nicht; breche ab ...$WHITE";
[ -d "$ABSAPPDIR" -a -d "$ABSMODDIR" ] || exit 1;

# [ -f "${ABSAPPDIR}/logs/vufind.log" ] || echo -e "$RED Bitte stellen Sie sicher, dass die Datei ${ABSAPPDIR}/logs/vufind.log existiert und vom Webserver beschrieben werden kann; breche ab ...$WHITE";
# [ -f "${ABSAPPDIR}/logs/vufind.log" ] || exit 1;

echo -e "$GREEN application path:$WHITE ${ABSAPPDIR}";
echo -e "$GREEN module path:$WHITE ${ABSMODDIR}";

MODDIR=../../${MOD}/module;
THEMEDIR=../../${MOD}/themes;

# Weitere Daten einlesen

echo -n "URL des Katalogs: ";
read URL;
echo -n "Emailadresse für Anfragen: ";
read EMAIL;
echo -n "URL des solr-Index: ";
read INDEX;
echo -n "Core des solr-Index: ";
read CORE;

echo -n "Theme belugax verwenden? [n]: ";
read BELUGAX;
if [ "$BELUGAX" = "y" -o "$BELUGAX" = "j" ]; then
    MAINTHEME=belugax;
else
    MAINTHEME=bootstrap3plus;
    rm -rf ${ABSMODDIR}/themes/belugax;
fi;

DBTEST=10;
while [ "$DBTEST" -ne 0 ]; do
    [ "$DBTEST" -eq 1 ] && echo "Bitte überprüfen Sie die Datenbankangaben";
    echo -n "Datenbank: ";
    read DB;
    echo -n "Datenbank Nutzer: ";
    read DBUSER;
    echo -n "Datenbank Password: ";
    read DBPASS;
    TEST=$(echo "EXIT" | mysql -u $DBUSER -p$DBPASS $DB);
    DBTEST=$?;
done;

# Quelltexte aus den Repositories holen und initialisieren

cd $ABSAPPDIR;
echo "VuFind wird installiert bzw. aktualisiert ...";
if ! [ -d ".git" ]; then
    git clone https://github.com/vufind-org/vufind .;
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');";
    php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;";
    php composer-setup.php;
    php -r "unlink('composer-setup.php');";
    php composer.phar install;
else
    git pull;
fi;
[ -d "logs" ] || mkdir logs;
touch logs/vufind.log;
chmod 666 logs/vufind.log;
chmod 777 local/cache;

echo "Die Module werden installier bzw. aktualisiert ...";
cd $ABSMODDIR;
if ! [ -d ".git" ]; then
    git clone https://github.com/beluga-core/core .;
    git checkout $MODBRANCH;
else
    git pull;
    git checkout $MODBRANCH;
    git checkout module;
    git checkout themes;
    git checkout local/config/vufind;
fi;

# Module auswählen

echo "Module werden ausgewählt ..."
for MOD in $(ls module); do
    echo -n "Modul ${MOD} installieren? (j/n): ";
    read YN;
    if [ "$YN" = "n" ]; then
        echo -e "    Modul$RED ${MOD} abgewählt$WHITE";
        rm -rf module/$MOD;
        THEME=$(echo $MOD | tr '[:upper:]' '[:lower:]');
        rm -rf themes/$THEME;
    else
        echo -e "    Modul$GREEN ${MOD} ausgewählt$WHITE";
    fi;
done;

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

# Konfigurationsdateien erstellen und kopieren

echo "Modulkonfiguration in die httpd-vufind.conf schreiben ...";
cd ${ABSMODDIR}/local/config;
echo -n > httpd-vufind.conf;
while IFS= read -r LINE; do
    NEWLINE='';
    if [[ "$LINE" =~ "<ServerName>" ]]; then
        SERVERNAME=$( echo $URL | sed -s 's|https?://||' );
        NEWLINE=$( echo "$LINE" | sed -s "s|<ServerName>|${SERVERNAME}|" );
    elif [[ "$LINE" =~ "<AdminEmail>" ]]; then
        NEWLINE=$( echo "$LINE" | sed -s "s|<AdminEmail>|${EMAIL}|" );
    elif [[ "$LINE" =~ "<ApplicationPath>" ]]; then
        NEWLINE=$( echo "$LINE" | sed -s "s|<ApplicationPath>|${ABSAPPDIR}|" );
    elif [[ "$LINE" =~ "<ServerUrl>" ]]; then
        NEWLINE=$( echo "$LINE" | sed -s "s|<ServerUrl>|${URL}|" );
    elif [[ "$LINE" =~ "<LocalModules>" ]]; then
        MODLIST=$( echo "${LIST[@]}" | sed -s "s/\s/,/g" );
        NEWLINE=$( echo "$LINE" | sed -s "s|<LocalModules>|${MODLIST}|" );
    fi;
    if [ -n "$NEWLINE" ]; then
        printf '%s\n' "$NEWLINE" >> httpd-vufind.conf;
    else
        printf '%s\n' "$LINE" >> httpd-vufind.conf;
    fi;
done < httpd-vufind.template;
echo "Die apache-Konfiguration ist in die ${ABSMODDIR}/config/vufind/httpd-vufind.conf geschrieben";

echo "Hauptkonfiguration wird geschrieben ...";
CONFIG=${ABSMODDIR}/local/config/vufind/config.ini;
echo "[Site]" > $CONFIG;
echo "url = "$URL >> $CONFIG;
echo "email = "$EMAIL >> $CONFIG;
echo "theme = "$MAINTHEME >> $CONFIG;
echo >> $CONFIG;
echo "[Index]" >> $CONFIG;
echo "url = "$INDEX >> $CONFIG;
echo "default_core = "$CORE >> $CONFIG;
echo >> $CONFIG;
echo "[Database]" >> $CONFIG;
echo "database = mysql://"${DBUSER}":"${DBPASS}"@localhost/"${DB} >> $CONFIG;
echo >> $CONFIG;
echo "[Logging]" >> $CONFIG;
echo "file = ${ABSAPPDIR}/logs/vufind.log:alert-5,error-5,notice-5,debug-5" >> $CONFIG;

echo "lokale Konfigurationsdateien kopieren";
cd ${ABSAPPDIR}/local/config/vufind;
cp ${ABSMODDIR}/local/config/vufind/* .;
cp ${ABSMODDIR}/local/config/httpd-vufind.conf ${ABSAPPDIR}/local/config/;

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

# Symlinks setzen

echo "Module werden verlinkt und eingerichtet ...";
cd ${ABSAPPDIR}/themes;
for THEME in $(ls $THEMEDIR); do
    ln -s ${THEMEDIR}/$THEME $THEME;
done;

cd ${ABSAPPDIR}/module;
for MOD in $(ls $MODDIR); do
    ln -s ${MODDIR}/$MOD $MOD;
done;

# Module einrichten

cd $ABSAPPDIR;
for MOD in $(ls module); do
    if [ -d "module/${MOD}/sql" -a -f "module/${MOD}/sql/mysql.sql" ]; then
        mysql -u $DBUSER -p$DBPASS $DB < module/${MOD}/sql/mysql.sql;
    fi;
    if [ -d "module/${MOD}/composer" -a -f "module/${MOD}/composer/composer.list" ]; then
        while read LINE; do
            composer require $LINE;
        done < module/${MOD}/composer/composer.list;
    fi;
done;

echo "Die Installation ist durchgeführt";

exit $?;
